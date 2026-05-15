<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Application\Service;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\EventoRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Response\EventoResponse;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ServiceException;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Application\Service\CancelarNfseService;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\EventoValidator;
use MarcelaBeh\EmissorNfseNacional\Domain\Exception\DomainException;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Config\ApiEndpoints;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\ApiConnector;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\Exception\HttpException;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\RequestBuilder;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\XmlSigner;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Builder\EventoXmlBuilder;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Validator\XsdValidator;
use PHPUnit\Framework\TestCase;

final class CancelarNfseServiceTest extends TestCase
{
    private const CHAVE_50 = '12345678901234567890123456789012345678901234567890';

    private ApiConnector $apiConnector;
    private EventoXmlBuilder $xmlBuilder;
    private XmlSigner $xmlSigner;
    private XsdValidator $xsdValidator;
    private EventoValidator $validator;
    private RequestBuilder $requestBuilder;
    private ApiEndpoints $apiEndpoints;
    private CancelarNfseService $service;

    protected function setUp(): void
    {
        $this->apiConnector = $this->createMock(ApiConnector::class);
        $this->xmlBuilder = $this->createMock(EventoXmlBuilder::class);
        $this->xmlSigner = $this->createMock(XmlSigner::class);
        $this->xsdValidator = $this->createMock(XsdValidator::class);
        $this->validator = $this->createMock(EventoValidator::class);
        $this->requestBuilder = $this->createMock(RequestBuilder::class);
        $this->apiEndpoints = $this->createMock(ApiEndpoints::class);

        $this->service = new CancelarNfseService(
            $this->apiConnector,
            $this->xmlBuilder,
            $this->xmlSigner,
            $this->xsdValidator,
            $this->validator,
            $this->requestBuilder,
            $this->apiEndpoints,
        );
    }

    private function createValidEventoRequest(): EventoRequest
    {
        return new EventoRequest(
            tipoEvento: '101101',
            chaveNfse: self::CHAVE_50,
            dataEvento: '2026-05-15',
            versaoAplicacao: '1.0.0',
            tipoAmbiente: '2',
            cnpjAutor: '12345678000195',
            codigoMotivo: '1',
            descricaoMotivo: 'Cancelamento de teste',
            nSeqEvento: '1',
        );
    }

    public function test_executar_success(): void
    {
        $request = $this->createValidEventoRequest();
        $xml = '<?xml version="1.0"?><pedRegEvento></pedRegEvento>';
        $xmlAssinado = '<?xml version="1.0" encoding="UTF-8"?><pedRegEvento></pedRegEvento>';
        $payload = ['xml' => $xmlAssinado];
        $endpoint = 'https://api.example.com/nfse/' . self::CHAVE_50 . '/cancelar';

        $this->validator->expects($this->once())->method('validate')->with($this->isInstanceOf(EventoRequest::class));
        $this->xmlBuilder->expects($this->once())->method('build')->willReturn($xml);
        $this->xsdValidator->expects($this->once())->method('validate')->with($xml, 'pedRegEvento');
        $this->xmlSigner->expects($this->once())->method('sign')->willReturn($xmlAssinado);
        $this->requestBuilder->expects($this->once())->method('buildEventoPayload')->willReturn($payload);
        $this->apiEndpoints->expects($this->once())->method('cancelarNfse')->with(self::CHAVE_50)->willReturn($endpoint);
        $this->apiConnector->expects($this->once())->method('post')->willReturn(['success' => true, 'data' => ['status' => 'cancelado']]);

        $response = $this->service->executar($request);

        $this->assertInstanceOf(EventoResponse::class, $response);
        $this->assertTrue($response->success);
        $this->assertSame('Cancelamento realizado com sucesso', $response->mensagem);
        $this->assertSame(['status' => 'cancelado'], $response->dados);
    }

    public function test_executar_error_response(): void
    {
        $request = $this->createValidEventoRequest();
        $xml = '<?xml version="1.0"?><pedRegEvento></pedRegEvento>';
        $xmlAssinado = '<?xml version="1.0" encoding="UTF-8"?><pedRegEvento></pedRegEvento>';
        $payload = ['xml' => $xmlAssinado];
        $endpoint = 'https://api.example.com/nfse/' . self::CHAVE_50 . '/cancelar';

        $this->validator->expects($this->once())->method('validate');
        $this->xmlBuilder->expects($this->once())->method('build')->willReturn($xml);
        $this->xsdValidator->expects($this->once())->method('validate');
        $this->xmlSigner->expects($this->once())->method('sign')->willReturn($xmlAssinado);
        $this->requestBuilder->expects($this->once())->method('buildEventoPayload')->willReturn($payload);
        $this->apiEndpoints->expects($this->once())->method('cancelarNfse')->willReturn($endpoint);
        $this->apiConnector->expects($this->once())->method('post')->willReturn([
            'success' => false,
            'data' => ['mensagem' => 'NFSe ja cancelada'],
        ]);

        $response = $this->service->executar($request);

        $this->assertInstanceOf(EventoResponse::class, $response);
        $this->assertFalse($response->success);
        $this->assertSame('NFSe ja cancelada', $response->mensagem);
    }

    public function test_executar_validation_error(): void
    {
        $request = $this->createValidEventoRequest();

        $this->validator->expects($this->once())->method('validate')
            ->willThrowException(new DomainException('Chave invalida'));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Dados inválidos: Chave invalida');

        $this->service->executar($request);
    }

    public function test_executar_http_error(): void
    {
        $request = $this->createValidEventoRequest();
        $xml = '<?xml version="1.0"?><pedRegEvento></pedRegEvento>';
        $xmlAssinado = '<?xml version="1.0" encoding="UTF-8"?><pedRegEvento></pedRegEvento>';
        $payload = ['xml' => $xmlAssinado];
        $endpoint = 'https://api.example.com/nfse/' . self::CHAVE_50 . '/cancelar';

        $this->validator->expects($this->once())->method('validate');
        $this->xmlBuilder->expects($this->once())->method('build')->willReturn($xml);
        $this->xsdValidator->expects($this->once())->method('validate');
        $this->xmlSigner->expects($this->once())->method('sign')->willReturn($xmlAssinado);
        $this->requestBuilder->expects($this->once())->method('buildEventoPayload')->willReturn($payload);
        $this->apiEndpoints->expects($this->once())->method('cancelarNfse')->willReturn($endpoint);
        $this->apiConnector->expects($this->once())->method('post')
            ->willThrowException(new HttpException('Connection timeout', 504));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Falha ao cancelar NFSe: Connection timeout');

        $this->service->executar($request);
    }
}
