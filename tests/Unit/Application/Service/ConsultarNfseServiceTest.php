<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Application\Service;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\ConsultaRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Response\NfseResponse;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ServiceException;
use MarcelaBeh\EmissorNfseNacional\Application\Service\ConsultarNfseService;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\ConsultaValidator;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Config\ApiEndpoints;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\ApiConnector;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\Exception\HttpException;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Parser\NfseXmlParser;
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

    public function test_consultar_dps_por_chave_success(): void
    {
        $chave = self::CHAVE_50;
        $dpsData = ['numero' => '123', 'serie' => '1'];

        $this->validator->expects($this->once())->method('validate');
        $this->apiEndpoints->expects($this->once())->method('consultarDps');
        $this->apiConnector->expects($this->once())->method('get')->willReturn(['success' => true, 'data' => $dpsData]);

        $result = $this->service->consultarDpsPorChave($chave);

        $this->assertSame($dpsData, $result);
    }

    public function test_consultar_dps_por_chave_not_found(): void
    {
        $chave = self::CHAVE_50;

        $this->validator->expects($this->once())->method('validate');
        $this->apiEndpoints->expects($this->once())->method('consultarDps');
        $this->apiConnector->expects($this->once())->method('get')->willReturn(['success' => false, 'data' => []]);

        $result = $this->service->consultarDpsPorChave($chave);

        $this->assertSame(['erro' => 'DPS não encontrada'], $result);
    }

    public function test_consultar_dps_por_chave_string_data(): void
    {
        $chave = self::CHAVE_50;
        $jsonString = '{"numero": "123"}';

        $this->validator->expects($this->once())->method('validate');
        $this->apiEndpoints->expects($this->once())->method('consultarDps');
        $this->apiConnector->expects($this->once())->method('get')->willReturn(['success' => true, 'data' => $jsonString]);

        $result = $this->service->consultarDpsPorChave($chave);

        $this->assertSame(['numero' => '123'], $result);
    }

    public function test_consultar_eventos_success(): void
    {
        $chave = self::CHAVE_50;
        $eventos = [['tipo' => '101101', 'data' => '2026-06-15']];

        $this->validator->expects($this->once())->method('validate');
        $this->apiEndpoints->expects($this->once())->method('consultarEventos')->with($chave, null, null);
        $this->apiConnector->expects($this->once())->method('get')->willReturn(['success' => true, 'data' => $eventos]);

        $result = $this->service->consultarEventos($chave);

        $this->assertSame($eventos, $result);
    }

    public function test_consultar_eventos_with_tipo_and_sequencial(): void
    {
        $chave = self::CHAVE_50;
        $tipoEvento = '101101';
        $sequencial = 1;

        $this->validator->expects($this->once())->method('validate');
        $this->apiEndpoints->expects($this->once())->method('consultarEventos')->with($chave, $tipoEvento, $sequencial);
        $this->apiConnector->expects($this->once())->method('get')->willReturn(['success' => true, 'data' => []]);

        $result = $this->service->consultarEventos($chave, $tipoEvento, $sequencial);

        $this->assertSame([], $result);
    }

    public function test_consultar_eventos_not_found(): void
    {
        $chave = self::CHAVE_50;

        $this->validator->expects($this->once())->method('validate');
        $this->apiEndpoints->expects($this->once())->method('consultarEventos');
        $this->apiConnector->expects($this->once())->method('get')->willReturn(['success' => false, 'data' => []]);

        $result = $this->service->consultarEventos($chave);

        $this->assertSame(['erro' => 'Eventos não encontrados'], $result);
    }

    public function test_consultar_danfse_success_string(): void
    {
        $chave = self::CHAVE_50;
        $pdfContent = 'PDF_BINARY_CONTENT';

        $this->validator->expects($this->once())->method('validate');
        $this->apiEndpoints->expects($this->once())->method('consultarDanfse');
        $this->apiConnector->expects($this->once())->method('get')->willReturn(['success' => true, 'data' => $pdfContent]);

        $result = $this->service->consultarDanfse($chave);

        $this->assertSame($pdfContent, $result);
    }

    public function test_consultar_danfse_fallback_to_nfse(): void
    {
        $chave = self::CHAVE_50;

        $this->validator->expects($this->once())->method('validate');
        $this->apiEndpoints->expects($this->once())->method('consultarDanfse')->willReturn('https://api.example.com/danfse/' . $chave);
        $this->apiEndpoints->expects($this->once())->method('consultarDanfseNfseCertificado')->willReturn('https://api.example.com/danfse-cert');
        $this->apiEndpoints->expects($this->once())->method('consultarDanfseNfseDownload')->with($chave)->willReturn('https://api.example.com/download/' . $chave);
        $this->apiConnector->expects($this->exactly(3))->method('get')->willReturnOnConsecutiveCalls(
            ['success' => false, 'data' => []],
            ['success' => true, 'data' => ['sucesso' => true]],
            ['success' => true, 'data' => 'DANFSE_CONTENT']
        );

        $result = $this->service->consultarDanfse($chave);

        $this->assertSame('DANFSE_CONTENT', $result);
    }
}
