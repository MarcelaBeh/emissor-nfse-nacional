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
use MarcelaBeh\EmissorNfseNacional\Domain\Exception\DomainException;
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

        $this->service = new EmitirDpsService(
            $this->apiConnector,
            $this->xmlBuilder,
            $this->xmlSigner,
            $this->xsdValidator,
            $this->validator,
            $this->requestBuilder,
            $this->nfseXmlParser,
            $this->ibscbsResponseValidator,
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
            regimeTributario: 1,
        );

        $tomador = new TomadorRequest(
            documento: '12345678000195',
            isCnpj: true,
            razaoSocial: 'Tomador Teste',
            nomeFantasia: 'Tomador',
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
            valorDeducoes: 0.0,
            descontoIncondicionado: 0.0,
            descontoCondicionado: 0.0,
            aliquotaIss: 0.05,
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
        $responseXml = '<?xml version="1.0"?><CompNFSe><NFSe><infNFSe><chNFSe>' . self::CHAVE_50 . '</chNFSe><nNFSe>1</nNFSe><cVerif>1234</cVerif><serie>1</serie><dhEmi>2026-05-15T10:00:00</dhEmi><CNPJ>12345678000195</CNPJ><xNome>Empresa Teste</xNome><vServ>1000.00</vServ><vISS>50.00</vISS></infNFSe></NFSe></CompNFSe>';
        $parsedData = ['chaveAcesso' => self::CHAVE_50, 'numero' => '1'];

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
        $this->assertNotNull($response->chaveAcesso);
        $this->assertSame(50, strlen($response->chaveAcesso));
        $this->assertSame($responseXml, $response->xml);
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
