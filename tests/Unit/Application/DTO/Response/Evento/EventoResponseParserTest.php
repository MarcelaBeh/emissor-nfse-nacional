<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Application\DTO\Response\Evento;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Response\Evento\CancelamentoResponse;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Response\Evento\EventoResponseParser;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Response\Evento\GenericEventoResponse;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Response\Evento\ManifestacaoResponse;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Response\Evento\SubstituicaoResponse;
use PHPUnit\Framework\TestCase;

final class EventoResponseParserTest extends TestCase
{
    private EventoResponseParser $parser;

    protected function setUp(): void
    {
        $this->parser = new EventoResponseParser();
    }

    public function test_parse_cancelamento(): void
    {
        $dados = [
            'tipoEvento' => '101101',
            'chaveNfse' => '12345678901234567890123456789012345678901234567890',
            'dataRegistro' => '2026-06-15T10:00:00-03:00',
            'numeroEvento' => '001',
            'sucesso' => true,
            'codigoMotivo' => '1',
            'descricaoMotivo' => 'Erro na emissão',
        ];

        $response = $this->parser->parse($dados);

        $this->assertInstanceOf(CancelamentoResponse::class, $response);
        $this->assertSame('101101', $response->getTipoEvento());
        $this->assertSame('12345678901234567890123456789012345678901234567890', $response->getChaveNfse());
        $this->assertSame('2026-06-15T10:00:00-03:00', $response->getDataRegistro());
        $this->assertSame('001', $response->getNumeroEvento());
        $this->assertTrue($response->getSucesso());
        $this->assertSame('1', $response->getCodigoMotivo());
        $this->assertSame('Erro na emissão', $response->getDescricaoMotivo());
    }

    public function test_parse_substituicao(): void
    {
        $dados = [
            'tipoEvento' => '105102',
            'chaveNfse' => '12345678901234567890123456789012345678901234567890',
            'chaveSubstituta' => '22345678901234567890123456789012345678901234567890',
            'codigoMotivo' => '02',
            'sucesso' => true,
        ];

        $response = $this->parser->parse($dados);

        $this->assertInstanceOf(SubstituicaoResponse::class, $response);
        $this->assertSame('105102', $response->getTipoEvento());
        $this->assertSame('22345678901234567890123456789012345678901234567890', $response->getChaveSubstituta());
        $this->assertSame('02', $response->getCodigoMotivo());
    }

    public function test_parse_manifestacao_prestador(): void
    {
        $dados = [
            'tipoEvento' => '202201',
            'chaveNfse' => '12345678901234567890123456789012345678901234567890',
            'autor' => 'Prestador',
            'sucesso' => true,
        ];

        $response = $this->parser->parse($dados);

        $this->assertInstanceOf(ManifestacaoResponse::class, $response);
        $this->assertSame('202201', $response->getTipoEvento());
        $this->assertSame('Prestador', $response->getAutor());
    }

    public function test_parse_manifestacao_tomador(): void
    {
        $dados = [
            'tipoEvento' => '203202',
            'chaveNfse' => '12345678901234567890123456789012345678901234567890',
            'autor' => 'Tomador',
            'sucesso' => true,
        ];

        $response = $this->parser->parse($dados);

        $this->assertInstanceOf(ManifestacaoResponse::class, $response);
        $this->assertSame('203202', $response->getTipoEvento());
    }

    public function test_parse_rejeicao(): void
    {
        $dados = [
            'tipoEvento' => '202205',
            'chaveNfse' => '12345678901234567890123456789012345678901234567890',
            'codigoMotivo' => '1',
            'sucesso' => true,
        ];

        $response = $this->parser->parse($dados);

        $this->assertInstanceOf(ManifestacaoResponse::class, $response);
        $this->assertSame('202205', $response->getTipoEvento());
        $this->assertSame('1', $response->getCodigoMotivo());
    }

    public function test_parse_generico(): void
    {
        $dados = [
            'tipoEvento' => '305101',
            'chaveNfse' => '12345678901234567890123456789012345678901234567890',
            'cpfAgTrib' => '52998224725',
            'nProcAdm' => '12345',
            'sucesso' => true,
        ];

        $response = $this->parser->parse($dados);

        $this->assertInstanceOf(GenericEventoResponse::class, $response);
        $this->assertSame('305101', $response->getTipoEvento());
        $this->assertNotNull($response->getDadosAdicionais());
    }

    public function test_parse_lista_eventos(): void
    {
        $response = [
            'eventos' => [
                [
                    'tipoEvento' => '101101',
                    'chaveNfse' => '12345678901234567890123456789012345678901234567890',
                    'sucesso' => true,
                ],
                [
                    'tipoEvento' => '105102',
                    'chaveNfse' => '12345678901234567890123456789012345678901234567890',
                    'sucesso' => true,
                ],
            ],
        ];

        $parsed = $this->parser->parseLista($response);

        $this->assertCount(2, $parsed);
        $this->assertInstanceOf(CancelamentoResponse::class, $parsed[0]);
        $this->assertInstanceOf(SubstituicaoResponse::class, $parsed[1]);
    }

    public function test_parse_with_snake_case_keys(): void
    {
        $dados = [
            'tipo_evento' => '101101',
            'chave_nfse' => '12345678901234567890123456789012345678901234567890',
            'data_registro' => '2026-06-15T10:00:00-03:00',
            'numero_evento' => '001',
            'codigo_motivo' => '2',
            'descricao_motivo' => 'Serviço não prestado',
        ];

        $response = $this->parser->parse($dados);

        $this->assertInstanceOf(CancelamentoResponse::class, $response);
        $this->assertSame('2026-06-15T10:00:00-03:00', $response->getDataRegistro());
        $this->assertSame('2', $response->getCodigoMotivo());
    }

    public function test_parse_failure_response(): void
    {
        $dados = [
            'tipoEvento' => '101101',
            'chaveNfse' => '12345678901234567890123456789012345678901234567890',
            'sucesso' => false,
            'mensagem' => 'Evento já registrado',
        ];

        $response = $this->parser->parse($dados);

        $this->assertFalse($response->getSucesso());
        $this->assertSame('Evento já registrado', $response->getMensagem());
    }
}
