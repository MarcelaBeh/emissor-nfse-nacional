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
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\SanitizedLogger;
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
            tipoAmbiente: 2,
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

    public function test_executar_erro_sefin_extrai_codigo_e_descricao(): void
    {
        // A SEFIN retorna os erros de evento em erro[] com Codigo/Descricao —
        // não no campo 'mensagem'. A resposta deve trazer a mensagem real.
        $request = $this->createValidEventoRequest();
        $xml = '<?xml version="1.0"?><pedRegEvento></pedRegEvento>';
        $xmlAssinado = '<?xml version="1.0" encoding="UTF-8"?><pedRegEvento></pedRegEvento>';

        $this->validator->expects($this->once())->method('validate');
        $this->xmlBuilder->expects($this->once())->method('build')->willReturn($xml);
        $this->xsdValidator->expects($this->once())->method('validate');
        $this->xmlSigner->expects($this->once())->method('sign')->willReturn($xmlAssinado);
        $this->requestBuilder->expects($this->once())->method('buildEventoPayload')->willReturn(['xml' => $xmlAssinado]);
        $this->apiEndpoints->expects($this->once())->method('cancelarNfse')->willReturn('https://api/x');
        $this->apiConnector->expects($this->once())->method('post')->willReturn([
            'success' => false,
            'data' => [
                'versaoAplicativo' => 'SefinNacional_1.6.0',
                'erro' => [
                    ['Codigo' => 'E1860', 'Descricao' => 'Evento impede o cancelamento.'],
                ],
            ],
        ]);

        $response = $this->service->executar($request);

        $this->assertFalse($response->success);
        $this->assertSame('E1860 - Evento impede o cancelamento.', $response->mensagem);
        $this->assertCount(1, $response->erros);
        $this->assertSame(['codigo' => 'E1860', 'descricao' => 'Evento impede o cancelamento.'], $response->erros[0]);
    }

    public function test_executar_erro_sefin_vazio_usa_fallback(): void
    {
        // Caso real observado: a SEFIN retorna erro:[] vazio, sem detalhe.
        // Deve cair no fallback claro, e o payload cru segue em dados.
        $request = $this->createValidEventoRequest();
        $xml = '<?xml version="1.0"?><pedRegEvento></pedRegEvento>';
        $xmlAssinado = '<?xml version="1.0" encoding="UTF-8"?><pedRegEvento></pedRegEvento>';

        $this->validator->expects($this->once())->method('validate');
        $this->xmlBuilder->expects($this->once())->method('build')->willReturn($xml);
        $this->xsdValidator->expects($this->once())->method('validate');
        $this->xmlSigner->expects($this->once())->method('sign')->willReturn($xmlAssinado);
        $this->requestBuilder->expects($this->once())->method('buildEventoPayload')->willReturn(['xml' => $xmlAssinado]);
        $this->apiEndpoints->expects($this->once())->method('cancelarNfse')->willReturn('https://api/x');
        $dados = ['versaoAplicativo' => 'SefinNacional_1.6.0', 'erro' => []];
        $this->apiConnector->expects($this->once())->method('post')->willReturn([
            'success' => false,
            'data' => $dados,
        ]);

        $response = $this->service->executar($request);

        $this->assertFalse($response->success);
        $this->assertSame('Erro ao cancelar NFSe', $response->mensagem);
        $this->assertSame($dados, $response->dados);
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

    public function test_logger_injetado_sanitiza_dados_sensiveis_no_log(): void
    {
        // Prova fim-a-fim do logger seguro: um SanitizedLogger injetado no serviço
        // (como faz a ServiceFactory) deve mascarar CNPJ/chave antes de escrever.
        // Sem o wiring de logger, este recurso era inalcançável (código morto).
        $capturado = '';
        $logger = new SanitizedLogger(function (string $linha) use (&$capturado): void {
            $capturado .= $linha;
        });

        $service = new CancelarNfseService(
            $this->apiConnector,
            $this->xmlBuilder,
            $this->xmlSigner,
            $this->xsdValidator,
            $this->validator,
            $this->requestBuilder,
            $this->apiEndpoints,
            $logger,
        );

        // O validador falha com uma mensagem que contém CNPJ (14 dígitos) cru.
        $this->validator->expects($this->once())->method('validate')
            ->willThrowException(new DomainException('CNPJ 12345678000195 inválido'));

        try {
            $service->executar($this->createValidEventoRequest());
        } catch (ValidationException) {
            // esperado — interessa o que foi logado
        }

        $this->assertStringContainsString('Validação cancelamento falhou', $capturado);
        $this->assertStringNotContainsString('12345678000195', $capturado); // CNPJ cru não vaza
        $this->assertStringContainsString('**************', $capturado);     // mascarado (14 *)
    }

    public function test_aceita_logger_psr3_generico(): void
    {
        // Interoperabilidade PSR-3: qualquer Psr\Log\LoggerInterface (Monolog,
        // Symfony, etc.) pode ser injetado — não só o SanitizedLogger da lib.
        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, new SanitizedLogger(fn () => null));

        $psrLogger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $psrLogger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Validação cancelamento falhou'), $this->arrayHasKey('msg'));

        $service = new CancelarNfseService(
            $this->apiConnector,
            $this->xmlBuilder,
            $this->xmlSigner,
            $this->xsdValidator,
            $this->validator,
            $this->requestBuilder,
            $this->apiEndpoints,
            $psrLogger,
        );

        $this->validator->expects($this->once())->method('validate')
            ->willThrowException(new DomainException('erro'));

        try {
            $service->executar($this->createValidEventoRequest());
        } catch (ValidationException) {
            // esperado
        }
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
