<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Application\Service;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\ConsultaRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Response\NfseResponse;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ServiceException;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Application\Service\ConsultarNfseService;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\ConsultaValidator;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEvento;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\EventoProcessado;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Config\ApiEndpoints;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\ApiConnector;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\Exception\HttpException;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Parser\NfseXmlParser;
use MarcelaBeh\EmissorNfseNacional\Tests\Support\EventoProcessadoXml;
use PHPUnit\Framework\TestCase;

final class ConsultarNfseServiceTest extends TestCase
{
    private const CHAVE_50 = '12345678901234567890123456789012345678901234567890';

    private ApiConnector $apiConnector;
    private ApiEndpoints $apiEndpoints;
    private ConsultaValidator $validator;
    private NfseXmlParser $xmlParser;
    private ConsultarNfseService $service;

    protected function setUp(): void
    {
        $this->apiConnector = $this->createMock(ApiConnector::class);
        $this->apiEndpoints = $this->createMock(ApiEndpoints::class);
        $this->validator = $this->createMock(ConsultaValidator::class);
        $this->xmlParser = $this->createMock(NfseXmlParser::class);
        $this->service = new ConsultarNfseService(
            $this->apiConnector,
            $this->apiEndpoints,
            $this->validator,
            $this->xmlParser,
        );
    }

    public function test_consultar_por_chave_success_with_xml(): void
    {
        $chave = self::CHAVE_50;
        $xml = '<?xml version="1.0"?><Nfse xmlns="http://www.sped.fazenda.gov.br/nfse"><numero>123</numero></Nfse>';
        $parsed = ['numero' => '123', 'chaveAcesso' => $chave];

        $this->validator->expects($this->once())->method('validate')->with($this->isInstanceOf(ConsultaRequest::class));
        $this->apiEndpoints->expects($this->once())->method('consultarNfse')->with($chave)->willReturn('https://api.example.com/nfse/' . $chave);
        $this->apiConnector->expects($this->once())->method('get')->willReturn(['success' => true, 'data' => $xml]);
        $this->xmlParser->expects($this->once())->method('parse')->with($xml)->willReturn([$parsed]);

        $response = $this->service->consultarPorChave($chave);

        $this->assertInstanceOf(NfseResponse::class, $response);
        $this->assertTrue($response->success);
        $this->assertSame($parsed, $response->dados);
        $this->assertSame($xml, $response->xml);
    }

    public function test_consultar_por_chave_success_with_encoding(): void
    {
        $chave = self::CHAVE_50;
        $xml = '<?xml version="1.0" encoding="ISO-8859-1"?><Nfse></Nfse>';

        $this->validator->expects($this->once())->method('validate');
        $this->apiEndpoints->expects($this->once())->method('consultarNfse');
        $this->apiConnector->expects($this->once())->method('get')->willReturn(['success' => true, 'data' => $xml]);
        $this->xmlParser->expects($this->once())->method('parse')->willReturn([]);

        $response = $this->service->consultarPorChave($chave, true);

        $this->assertInstanceOf(NfseResponse::class, $response);
        $this->assertTrue($response->success);
    }

    public function test_consultar_por_chave_not_found(): void
    {
        $chave = self::CHAVE_50;

        $this->validator->expects($this->once())->method('validate');
        $this->apiEndpoints->expects($this->once())->method('consultarNfse');
        $this->apiConnector->expects($this->once())->method('get')->willReturn(['success' => false, 'data' => []]);

        $response = $this->service->consultarPorChave($chave);

        $this->assertInstanceOf(NfseResponse::class, $response);
        $this->assertFalse($response->success);
        $this->assertSame('NFSe não encontrada', $response->mensagem);
    }

    public function test_consultar_por_chave_http_exception(): void
    {
        $chave = self::CHAVE_50;

        $this->validator->expects($this->once())->method('validate');
        $this->apiEndpoints->expects($this->once())->method('consultarNfse');
        $this->apiConnector->expects($this->once())->method('get')->willThrowException(new HttpException('Network error', 500));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Falha ao consultar NFSe');

        $this->service->consultarPorChave($chave);
    }

    public function test_consultar_por_chave_preenche_chave_e_numero(): void
    {
        $this->validator->expects($this->once())->method('validate');
        $this->apiEndpoints->method('consultarNfse')->willReturn('u');
        $this->apiConnector->method('get')->willReturn(['success' => true, 'data' => '<NFSe/>']);
        $this->xmlParser->method('parse')->willReturn([['id' => 'NFS' . self::CHAVE_50, 'numero' => '264']]);

        $response = $this->service->consultarPorChave(self::CHAVE_50);

        $this->assertTrue($response->success);
        $this->assertSame(self::CHAVE_50, $response->chaveAcesso);
        $this->assertSame('264', $response->numero);
    }

    public function test_consultar_por_chave_com_id_malformado_nao_inventa_chave(): void
    {
        $this->validator->method('validate');
        $this->apiEndpoints->method('consultarNfse')->willReturn('u');
        $this->apiConnector->method('get')->willReturn(['success' => true, 'data' => '<NFSe/>']);
        $this->xmlParser->method('parse')->willReturn([['id' => 'LIXO', 'numero' => '']]);

        $response = $this->service->consultarPorChave(self::CHAVE_50);

        $this->assertTrue($response->success);
        $this->assertNull($response->chaveAcesso);
        $this->assertNull($response->numero);
    }

    public function test_consultar_por_chave_nao_encontrada_mostra_o_erro_da_sefin(): void
    {
        // Na consulta a SEFIN devolve `erro` como objeto unico.
        $erro = ['codigo' => 'E2401', 'descricao' => 'Chave de acesso não encontrada.'];
        $this->validator->method('validate');
        $this->apiEndpoints->method('consultarNfse')->willReturn('u');
        $this->apiConnector->method('get')->willReturn(['success' => false, 'status' => 404, 'data' => ['erro' => $erro]]);

        $response = $this->service->consultarPorChave(self::CHAVE_50);

        $this->assertFalse($response->success);
        $this->assertSame('E2401 - Chave de acesso não encontrada.', $response->mensagem);
        $this->assertSame([['codigo' => 'E2401', 'descricao' => 'Chave de acesso não encontrada.']], $response->erros);
        $this->assertSame(['erro' => $erro], $response->dados);
    }

    public function test_consultar_por_chave_falha_que_nao_e_404_nao_diz_que_nao_foi_encontrada(): void
    {
        $this->validator->method('validate');
        $this->apiEndpoints->method('consultarNfse')->willReturn('u');
        $this->apiConnector->method('get')->willReturn(['success' => false, 'status' => 403, 'data' => ['message' => '']]);

        $response = $this->service->consultarPorChave(self::CHAVE_50);

        $this->assertFalse($response->success);
        $this->assertSame('Falha ao consultar NFSe (HTTP 403)', $response->mensagem);
        $this->assertSame([], $response->erros);
    }

    public function test_consultar_por_chave_falha_com_erros_em_lista_mostra_a_mensagem_da_sefin(): void
    {
        $this->validator->method('validate');
        $this->apiEndpoints->method('consultarNfse')->willReturn('u');
        $this->apiConnector->method('get')->willReturn([
            'success' => false,
            'status' => 500,
            'data' => ['erros' => [['Codigo' => 'E9000', 'Descricao' => 'Falha interna']]],
        ]);

        $response = $this->service->consultarPorChave(self::CHAVE_50);

        $this->assertSame('E9000 - Falha interna', $response->mensagem);
    }

    private const ID_DPS = 'DPS' . '3550308' . '2' . '11444777000161' . '00001' . '000000000000123';

    public function test_consultar_chave_por_dps_devolve_a_chave_da_nfse(): void
    {
        $this->apiEndpoints->expects($this->once())->method('consultarDps')->with(self::ID_DPS)->willReturn('u');
        $this->apiConnector->method('get')->willReturn(['success' => true, 'status' => 200, 'data' => ['chaveAcesso' => self::CHAVE_50]]);

        $this->assertSame(self::CHAVE_50, $this->service->consultarChavePorDps(self::ID_DPS));
    }

    public function test_consultar_chave_por_dps_sem_nfse_gerada_devolve_null(): void
    {
        $this->apiEndpoints->method('consultarDps')->willReturn('u');
        $this->apiConnector->method('get')->willReturn([
            'success' => false,
            'status' => 404,
            'data' => ['erro' => ['codigo' => 'E2404', 'descricao' => 'Não foi gerada uma NFS-e com o identificador de DPS informado']],
        ]);

        $this->assertNull($this->service->consultarChavePorDps(self::ID_DPS));
    }

    public function test_consultar_chave_por_dps_outra_falha_mostra_o_erro_da_sefin(): void
    {
        $this->apiEndpoints->method('consultarDps')->willReturn('u');
        $this->apiConnector->method('get')->willReturn([
            'success' => false,
            'status' => 400,
            'data' => ['erro' => ['codigo' => 'E2400', 'descricao' => 'Identificador mal formado.']],
        ]);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Falha ao consultar a DPS: E2400 - Identificador mal formado.');

        $this->service->consultarChavePorDps(self::ID_DPS);
    }

    public function test_consultar_chave_por_dps_resposta_sem_chave_lanca_excecao(): void
    {
        $this->apiEndpoints->method('consultarDps')->willReturn('u');
        $this->apiConnector->method('get')->willReturn(['success' => true, 'status' => 200, 'data' => ['chaveAcesso' => 'curta']]);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('sem a chave de acesso');

        $this->service->consultarChavePorDps(self::ID_DPS);
    }

    public function test_consultar_chave_por_dps_recusa_id_fora_do_formato(): void
    {
        $this->apiConnector->expects($this->never())->method('get');

        $this->expectException(ValidationException::class);

        // a chave da NFS-e (50 posições) não é o Id da DPS
        $this->service->consultarChavePorDps(self::CHAVE_50);
    }

    public function test_consultar_chave_por_dps_falha_http_vira_service_exception(): void
    {
        $this->apiEndpoints->method('consultarDps')->willReturn('u');
        $this->apiConnector->method('get')->willThrowException(new HttpException('Connection timeout', 504));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Falha ao consultar a DPS: Connection timeout');

        $this->service->consultarChavePorDps(self::ID_DPS);
    }

    public function test_consultar_eventos_devolve_os_itens_como_a_sefin_envia(): void
    {
        $itens = [
            ['chaveAcesso' => self::CHAVE_50, 'tipoEvento' => 101101, 'numeroPedidoRegistroEvento' => 1, 'arquivoXml' => 'codificado'],
        ];
        $this->validator->expects($this->once())->method('validate');
        $this->apiEndpoints->expects($this->once())->method('consultarEventos')->with(self::CHAVE_50, '101101', 1)->willReturn('u');
        $this->apiConnector->method('get')->willReturn(['success' => true, 'status' => 200, 'data' => ['tipoAmbiente' => 2, 'eventos' => $itens]]);

        $this->assertSame($itens, $this->service->consultarEventos(self::CHAVE_50, '101101', 1));
    }

    public function test_consultar_eventos_404_devolve_lista_vazia(): void
    {
        $this->validator->method('validate');
        $this->apiEndpoints->method('consultarEventos')->willReturn('u');
        $this->apiConnector->method('get')->willReturn(['success' => false, 'status' => 404, 'data' => []]);

        $this->assertSame([], $this->service->consultarEventos(self::CHAVE_50, '101101', 1));
    }

    public function test_consultar_eventos_outra_falha_nao_e_mascarada_como_nao_encontrado(): void
    {
        $this->validator->method('validate');
        $this->apiEndpoints->method('consultarEventos')->willReturn('u');
        $this->apiConnector->method('get')->willReturn(['success' => false, 'status' => 405, 'data' => ['message' => "The requested resource does not support http method 'GET'."]]);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('does not support http method');

        $this->service->consultarEventos(self::CHAVE_50, '101101', 1);
    }

    public function test_consultar_eventos_recusa_tipo_invalido(): void
    {
        $this->validator->method('validate');
        $this->apiConnector->expects($this->never())->method('get');

        $this->expectException(ValidationException::class);

        $this->service->consultarEventos(self::CHAVE_50, '1', 1);
    }

    /**
     * @param array<string, mixed> $resposta
     */
    private function simularConsultaDeEvento(array $resposta): void
    {
        $this->validator->method('validate');
        $this->apiEndpoints->method('consultarEventos')->willReturn('https://api.example.com/nfse/x/eventos/101101/1');
        $this->apiConnector->expects($this->once())->method('get')->willReturn($resposta);
    }

    public function test_consultar_eventos_processados_le_o_xml_em_base64_duplo(): void
    {
        $arquivo = EventoProcessadoXml::gzipBase64Duplo(EventoProcessadoXml::build());
        $this->validator->expects($this->once())->method('validate');
        $this->apiEndpoints->expects($this->once())->method('consultarEventos')->with(self::CHAVE_50, '101101', 1)->willReturn('u');
        $this->apiConnector->method('get')->willReturn([
            'success' => true,
            'status' => 200,
            'data' => ['eventos' => [['chaveAcesso' => self::CHAVE_50, 'tipoEvento' => 101101, 'arquivoXml' => $arquivo]]],
        ]);

        $eventos = $this->service->consultarEventosProcessados(self::CHAVE_50, '101101', 1);

        $this->assertCount(1, $eventos);
        $this->assertInstanceOf(EventoProcessado::class, $eventos[0]);
        $this->assertSame(TipoEvento::CANCELAMENTO, $eventos[0]->pedido->tipoEvento);
        $this->assertSame('1', $eventos[0]->nSeqEvento);
    }

    public function test_consultar_eventos_processados_404_devolve_lista_vazia(): void
    {
        $this->simularConsultaDeEvento(['success' => false, 'status' => 404, 'data' => []]);

        $this->assertSame([], $this->service->consultarEventosProcessados(self::CHAVE_50, '101101', 1));
    }

    public function test_consultar_eventos_processados_mostra_o_erro_da_sefin(): void
    {
        // Nas consultas a SEFIN devolve `erro` como objeto unico, nao como lista.
        $this->simularConsultaDeEvento([
            'success' => false,
            'status' => 400,
            'data' => ['erro' => ['codigo' => 'E2400', 'descricao' => 'Identificador mal formado.']],
        ]);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Falha ao consultar evento: E2400 - Identificador mal formado.');

        $this->service->consultarEventosProcessados(self::CHAVE_50, '101101', 1);
    }

    public function test_consultar_eventos_processados_erro_sem_mensagem_mostra_o_status(): void
    {
        $this->simularConsultaDeEvento(['success' => false, 'status' => 503, 'data' => ['message' => '']]);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('HTTP 503');

        $this->service->consultarEventosProcessados(self::CHAVE_50, '101101', 1);
    }

    public function test_consultar_eventos_processados_falha_http_vira_service_exception(): void
    {
        $this->validator->method('validate');
        $this->apiEndpoints->method('consultarEventos')->willReturn('u');
        $this->apiConnector->method('get')->willThrowException(new HttpException('Connection timeout', 504));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Falha ao consultar evento: Connection timeout');

        $this->service->consultarEventosProcessados(self::CHAVE_50, '101101', 1);
    }

    public function test_consultar_eventos_processados_recusa_tipo_invalido(): void
    {
        $this->validator->method('validate');
        $this->apiConnector->expects($this->never())->method('get');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Tipo de evento inválido');

        $this->service->consultarEventosProcessados(self::CHAVE_50, '999999', 1);
    }

    public function test_consultar_eventos_processados_recusa_sequencial_fora_de_1_a_999(): void
    {
        $this->validator->method('validate');
        $this->apiConnector->expects($this->never())->method('get');

        foreach ([0, 1000, -1] as $sequencial) {
            try {
                $this->service->consultarEventosProcessados(self::CHAVE_50, '101101', $sequencial);
                $this->fail("O sequencial {$sequencial} deveria ser recusado");
            } catch (ValidationException $e) {
                $this->assertStringContainsString('entre 1 e 999', $e->getMessage());
            }
        }
    }

    public function test_consultar_eventos_processados_item_sem_arquivo_xml_lanca_excecao(): void
    {
        $this->simularConsultaDeEvento(['success' => true, 'status' => 200, 'data' => ['eventos' => [['chaveAcesso' => self::CHAVE_50]]]]);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('sem o campo arquivoXml');

        $this->service->consultarEventosProcessados(self::CHAVE_50, '101101', 1);
    }

    public function test_consultar_eventos_processados_arquivo_ilegivel_lanca_excecao(): void
    {
        $this->simularConsultaDeEvento([
            'success' => true,
            'status' => 200,
            'data' => ['eventos' => [['arquivoXml' => EventoProcessadoXml::gzipBase64Duplo('<evento versao="1.01"/>')]]],
        ]);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Não foi possível ler o evento');

        $this->service->consultarEventosProcessados(self::CHAVE_50, '101101', 1);
    }
}
