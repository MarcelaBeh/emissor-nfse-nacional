<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Application\Service;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\DpsRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\PrestadorRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\ServicoRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\TomadorRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Response\NfseResponse;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ServiceException;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Application\Service\EmitirDpsService;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\DpsValidator;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\IbscbsResponseValidator;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\RegimeTributario;
use MarcelaBeh\EmissorNfseNacional\Domain\Exception\DomainException;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Config\ApiEndpoints;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\ApiConnector;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\Exception\HttpException;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\RequestBuilder;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\XmlSigner;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Builder\DpsXmlBuilder;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Parser\NfseXmlParser;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Validator\XsdValidator;
use PHPUnit\Framework\TestCase;

final class EmitirDpsServiceTest extends TestCase
{
    private const CHAVE_50 = '12345678901234567890123456789012345678901234567890';

    private ApiConnector $apiConnector;
    private DpsXmlBuilder $xmlBuilder;
    private XmlSigner $xmlSigner;
    private XsdValidator $xsdValidator;
    private DpsValidator $validator;
    private RequestBuilder $requestBuilder;
    private NfseXmlParser $nfseXmlParser;
    private IbscbsResponseValidator $ibscbsResponseValidator;
    private ApiEndpoints $apiEndpoints;
    private EmitirDpsService $service;

    protected function setUp(): void
    {
        $this->apiConnector = $this->createMock(ApiConnector::class);
        $this->xmlBuilder = $this->createMock(DpsXmlBuilder::class);
        $this->xmlSigner = $this->createMock(XmlSigner::class);
        $this->xsdValidator = $this->createMock(XsdValidator::class);
        $this->validator = $this->createMock(DpsValidator::class);
        $this->requestBuilder = $this->createMock(RequestBuilder::class);
        $this->nfseXmlParser = $this->createMock(NfseXmlParser::class);
        $this->ibscbsResponseValidator = new IbscbsResponseValidator();
        $this->apiEndpoints = $this->createMock(ApiEndpoints::class);

        $this->service = new EmitirDpsService(
            $this->apiConnector,
            $this->xmlBuilder,
            $this->xmlSigner,
            $this->xsdValidator,
            $this->validator,
            $this->requestBuilder,
            $this->nfseXmlParser,
            $this->ibscbsResponseValidator,
            $this->apiEndpoints,
        );
    }

    private function createValidDpsRequest(): DpsRequest
    {
        $prestador = new PrestadorRequest(
            documento: '12345678000195',
            isCnpj: true,
            inscricaoMunicipal: '123456',
            razaoSocial: 'Empresa Teste LTDA',
            telefone: '11999999999',
            email: 'contato@empresa.com',
            logradouro: 'Rua Teste',
            numero: '123',
            complemento: 'Sala 1',
            bairro: 'Centro',
            codigoMunicipio: '3550308',
            uf: 'SP',
            cep: '01001000',
            regimeTributario: RegimeTributario::SIMPLES_NACIONAL->value,
        );

        $tomador = new TomadorRequest(
            documento: '12345678000195',
            isCnpj: true,
            razaoSocial: 'Tomador Teste',
            telefone: '11888888888',
            email: 'tomador@teste.com',
            logradouro: 'Av Tomador',
            numero: '456',
            complemento: null,
            bairro: 'Bairro',
            codigoMunicipio: '3550308',
            uf: 'SP',
            cep: '01001000',
        );

        $servico = new ServicoRequest(
            discriminacao: 'Servico de teste',
            codigoTributacao: '12345',
            codigoMunicipioPrestacao: '3550308',
            valorServicos: 1000.00,
            tribISSQN: '1',
            tpRetISSQN: '1',
        );

        return new DpsRequest(
            tipoAmbiente: 2,
            dataEmissao: '2026-05-15',
            versaoAplicacao: '1.0.0',
            serie: 1,
            numero: 1,
            dataCompetencia: '2026-05-01',
            tipoEmissao: 1,
            codigoMunicipioEmissor: '3550308',
            prestador: $prestador,
            tomador: $tomador,
            servico: $servico,
        );
    }

    public function test_executar_success(): void
    {
        $request = $this->createValidDpsRequest();
        $xml = '<?xml version="1.0"?><DPS><infDPS Id="' . self::CHAVE_50 . '"></infDPS></DPS>';
        $xmlAssinado = '<?xml version="1.0" encoding="UTF-8"?><DPS><infDPS Id="' . self::CHAVE_50 . '"></infDPS></DPS>';
        $payload = ['xml' => $xmlAssinado];
        $responseXml = '<?xml version="1.0"?><NFSe xmlns="http://www.sped.fazenda.gov.br/nfse" versao="1.01"><infNFSe Id="NFS' . self::CHAVE_50 . '"><nNFSe>196</nNFSe></infNFSe></NFSe>';
        // O parser devolve a chave REAL no 'id' (NFS + 50 dígitos) e o nNFSe em 'numero'.
        $parsedData = ['id' => 'NFS' . self::CHAVE_50, 'numero' => '196'];

        $this->validator->expects($this->once())->method('validate')->with($this->isInstanceOf(DpsRequest::class));
        $this->xmlBuilder->expects($this->once())->method('build')->willReturn($xml);
        $this->xsdValidator->expects($this->once())->method('validate')->with($xml, 'DPS');
        $this->xmlSigner->expects($this->once())->method('sign')->willReturn($xmlAssinado);
        $this->requestBuilder->expects($this->once())->method('buildDpsPayload')->willReturn($payload);
        $this->apiConnector->expects($this->once())->method('post')->willReturn(['success' => true, 'data' => $responseXml]);
        $this->nfseXmlParser->expects($this->once())->method('parse')->willReturn([$parsedData]);

        $response = $this->service->executar($request);

        $this->assertInstanceOf(NfseResponse::class, $response);
        $this->assertTrue($response->success);
        // Chave REAL da NFS-e (sem o prefixo NFS), não a calculada localmente do DPS.
        $this->assertSame(self::CHAVE_50, $response->chaveAcesso);
        $this->assertSame('196', $response->numero);
        $this->assertSame($responseXml, $response->xml);
    }

    /**
     * Regressão fim-a-fim (LIB-A): usa o NfseXmlParser REAL — não o mock — com o
     * XML de emissão real da SEFIN, cuja raiz é <NFSe>. O parser antigo só lia um
     * envelope <CompNFSe> inexistente nesta API e retornava [], fazendo a resposta
     * cair no fallback (chave do DPS, numero null). Este teste exercita
     * parser→serviço de ponta a ponta: chaveAcesso e numero vêm da NFS-e real.
     */
    public function test_executar_emissao_real_popula_chave_e_numero_com_parser_real(): void
    {
        $chave50 = '13026032218586475000177000000000019926060970939220';
        $responseXml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <NFSe xmlns="http://www.sped.fazenda.gov.br/nfse" versao="1.01">
                <infNFSe Id="NFS{$chave50}">
                    <nNFSe>199</nNFSe>
                    <cStat>100</cStat>
                    <emit><CNPJ>18586475000177</CNPJ></emit>
                    <valores><vLiq>0.01</vLiq></valores>
                </infNFSe>
            </NFSe>
            XML;

        $service = new EmitirDpsService(
            $this->apiConnector,
            $this->xmlBuilder,
            $this->xmlSigner,
            $this->xsdValidator,
            $this->validator,
            $this->requestBuilder,
            new NfseXmlParser(),
            $this->ibscbsResponseValidator,
            $this->apiEndpoints,
        );

        $request = $this->createValidDpsRequest();
        $xmlAssinado = '<?xml version="1.0" encoding="UTF-8"?><DPS></DPS>';

        $this->validator->expects($this->once())->method('validate');
        $this->xmlBuilder->expects($this->once())->method('build')->willReturn('<DPS></DPS>');
        $this->xsdValidator->expects($this->once())->method('validate');
        $this->xmlSigner->expects($this->once())->method('sign')->willReturn($xmlAssinado);
        $this->requestBuilder->expects($this->once())->method('buildDpsPayload')->willReturn(['xml' => $xmlAssinado]);
        $this->apiConnector->expects($this->once())->method('post')->willReturn(['success' => true, 'data' => $responseXml]);

        $response = $service->executar($request);

        $this->assertTrue($response->success);
        $this->assertSame($chave50, $response->chaveAcesso);
        $this->assertSame('199', $response->numero);
    }

    public function test_executar_id_malformado_cai_no_fallback_da_chave_local(): void
    {
        // Se o Id da NFS-e não bater TSIdNFSe (NFS + 50 dígitos), não vira chave
        // inválida silenciosa: cai na chave gerada localmente a partir do DPS.
        $request = $this->createValidDpsRequest();
        $xml = '<?xml version="1.0"?><DPS><infDPS Id="' . self::CHAVE_50 . '"></infDPS></DPS>';
        $xmlAssinado = '<?xml version="1.0" encoding="UTF-8"?><DPS></DPS>';
        $responseXml = '<?xml version="1.0"?><NFSe xmlns="http://www.sped.fazenda.gov.br/nfse" versao="1.01"><infNFSe Id="LIXO123"></infNFSe></NFSe>';

        $this->validator->expects($this->once())->method('validate');
        $this->xmlBuilder->expects($this->once())->method('build')->willReturn($xml);
        $this->xsdValidator->expects($this->once())->method('validate');
        $this->xmlSigner->expects($this->once())->method('sign')->willReturn($xmlAssinado);
        $this->requestBuilder->expects($this->once())->method('buildDpsPayload')->willReturn(['xml' => $xmlAssinado]);
        $this->apiConnector->expects($this->once())->method('post')->willReturn(['success' => true, 'data' => $responseXml]);
        $this->nfseXmlParser->expects($this->once())->method('parse')->willReturn([['id' => 'LIXO123', 'numero' => null]]);

        $response = $this->service->executar($request);

        $this->assertTrue($response->success);
        $this->assertSame(50, strlen((string) $response->chaveAcesso)); // chave local (fallback), não 'LIXO'
        $this->assertNull($response->numero);
    }

    public function test_executar_erro_sefin_extrai_codigo_e_descricao(): void
    {
        // RISCO-05: payload REAL da SEFIN (SefinNacional_1.6.0) — os erros vêm em
        // erros[] com Codigo/Descricao (maiúsculas), não no campo 'mensagem'.
        // A resposta deve trazer a mensagem real, não o genérico "Erro ao emitir DPS".
        $request = $this->createValidDpsRequest();
        $xml = '<?xml version="1.0"?><DPS></DPS>';
        $xmlAssinado = '<?xml version="1.0" encoding="UTF-8"?><DPS></DPS>';

        $dadosSefin = [
            'tipoAmbiente' => 2,
            'versaoAplicativo' => 'SefinNacional_1.6.0',
            'dataHoraProcessamento' => '2026-06-10T15:59:08.4981439-03:00',
            'idDPS' => 'DPS130260325797320000014800002000000000000025',
            'erros' => [
                [
                    'Codigo' => 'E0617',
                    'Descricao' => 'Não é permitido informar alíquota quando o prestador de serviço não é optante do simples nacional (opSimpNac = 1) na data de competência informada na DPS.',
                ],
            ],
        ];

        $this->validator->expects($this->once())->method('validate');
        $this->xmlBuilder->expects($this->once())->method('build')->willReturn($xml);
        $this->xsdValidator->expects($this->once())->method('validate');
        $this->xmlSigner->expects($this->once())->method('sign')->willReturn($xmlAssinado);
        $this->requestBuilder->expects($this->once())->method('buildDpsPayload')->willReturn(['xml' => $xmlAssinado]);
        $this->apiConnector->expects($this->once())->method('post')->willReturn([
            'success' => false,
            'data' => $dadosSefin,
        ]);

        $response = $this->service->executar($request);

        $this->assertFalse($response->success);
        $this->assertStringStartsWith('E0617 - Não é permitido informar alíquota', $response->mensagem);
        $this->assertSame($dadosSefin, $response->dados); // payload cru preservado integralmente
        $this->assertCount(1, $response->erros);
        $this->assertSame('E0617', $response->erros[0]['codigo']);
    }

    public function test_executar_erro_sefin_expoe_lista_completa_de_erros(): void
    {
        // A SEFIN pode retornar mais de um erro de uma vez. A 'mensagem' traz o
        // primeiro (resumo), mas 'erros' deve trazer TODOS, estruturados.
        $request = $this->createValidDpsRequest();
        $xml = '<?xml version="1.0"?><DPS></DPS>';
        $xmlAssinado = '<?xml version="1.0" encoding="UTF-8"?><DPS></DPS>';

        $dadosSefin = [
            'versaoAplicativo' => 'SefinNacional_1.6.0',
            'erros' => [
                ['Codigo' => 'E0617', 'Descricao' => 'Alíquota indevida (não optante).'],
                ['Codigo' => 'E0625', 'Descricao' => 'Alíquota indevida (Simples Nacional).'],
            ],
        ];

        $this->validator->expects($this->once())->method('validate');
        $this->xmlBuilder->expects($this->once())->method('build')->willReturn($xml);
        $this->xsdValidator->expects($this->once())->method('validate');
        $this->xmlSigner->expects($this->once())->method('sign')->willReturn($xmlAssinado);
        $this->requestBuilder->expects($this->once())->method('buildDpsPayload')->willReturn(['xml' => $xmlAssinado]);
        $this->apiConnector->expects($this->once())->method('post')->willReturn([
            'success' => false,
            'data' => $dadosSefin,
        ]);

        $response = $this->service->executar($request);

        $this->assertFalse($response->success);
        $this->assertSame('E0617 - Alíquota indevida (não optante).', $response->mensagem);
        $this->assertCount(2, $response->erros);
        $this->assertSame(['codigo' => 'E0617', 'descricao' => 'Alíquota indevida (não optante).'], $response->erros[0]);
        $this->assertSame(['codigo' => 'E0625', 'descricao' => 'Alíquota indevida (Simples Nacional).'], $response->erros[1]);
    }

    public function test_executar_erro_sefin_sem_erros_usa_fallback(): void
    {
        $request = $this->createValidDpsRequest();
        $xml = '<?xml version="1.0"?><DPS></DPS>';
        $xmlAssinado = '<?xml version="1.0" encoding="UTF-8"?><DPS></DPS>';

        $this->validator->expects($this->once())->method('validate');
        $this->xmlBuilder->expects($this->once())->method('build')->willReturn($xml);
        $this->xsdValidator->expects($this->once())->method('validate');
        $this->xmlSigner->expects($this->once())->method('sign')->willReturn($xmlAssinado);
        $this->requestBuilder->expects($this->once())->method('buildDpsPayload')->willReturn(['xml' => $xmlAssinado]);
        $this->apiConnector->expects($this->once())->method('post')->willReturn([
            'success' => false,
            'data' => ['outroCampo' => 'x'],
        ]);

        $response = $this->service->executar($request);

        $this->assertFalse($response->success);
        $this->assertSame('Erro ao emitir DPS', $response->mensagem);
    }

    public function test_executar_validation_error(): void
    {
        $request = $this->createValidDpsRequest();

        $this->validator->expects($this->once())->method('validate')
            ->willThrowException(new DomainException('Dados invalidos'));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Dados inválidos: Dados invalidos');

        $this->service->executar($request);
    }

    public function test_executar_http_error(): void
    {
        $request = $this->createValidDpsRequest();
        $xml = '<?xml version="1.0"?><DPS></DPS>';
        $xmlAssinado = '<?xml version="1.0" encoding="UTF-8"?><DPS></DPS>';
        $payload = ['xml' => $xmlAssinado];

        $this->validator->expects($this->once())->method('validate');
        $this->xmlBuilder->expects($this->once())->method('build')->willReturn($xml);
        $this->xsdValidator->expects($this->once())->method('validate');
        $this->xmlSigner->expects($this->once())->method('sign')->willReturn($xmlAssinado);
        $this->requestBuilder->expects($this->once())->method('buildDpsPayload')->willReturn($payload);
        $this->apiConnector->expects($this->once())->method('post')
            ->willThrowException(new HttpException('Network error', 500));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Falha ao comunicar com API: Network error');

        $this->service->executar($request);
    }
}
