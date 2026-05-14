<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Evento;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEvento;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\ChaveAcesso;
use PHPUnit\Framework\TestCase;

final class EventoTest extends TestCase
{
    private const CHAVE_50 = '12345678901234567890123456789012345678901234567890';

    public function test_create_cancelamento_minimo(): void
    {
        $chave = new ChaveAcesso(self::CHAVE_50);
        $evento = new Evento(
            tipo: TipoEvento::CANCELAMENTO,
            chaveNfse: $chave,
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            cnpjAutor: '11444777000161',
            codigoMotivo: '1',
            descricaoMotivo: 'Erro na emissão da NFSe',
        );

        $this->assertSame(TipoEvento::CANCELAMENTO, $evento->getTipo());
        $this->assertSame(self::CHAVE_50, $evento->getChaveNfse());
        $this->assertSame($chave, $evento->getChaveAcesso());
        $this->assertSame('1.0.0', $evento->getVersaoAplicacao());
        $this->assertSame('11444777000161', $evento->getCnpjAutor());
        $this->assertNull($evento->getCpfAutor());
        $this->assertSame('1', $evento->getCodigoMotivo());
        $this->assertSame('Erro na emissão da NFSe', $evento->getDescricaoMotivo());
    }

    public function test_create_substituicao_completa(): void
    {
        $chave = new ChaveAcesso(self::CHAVE_50);
        $evento = new Evento(
            tipo: TipoEvento::SUBSTITUICAO,
            chaveNfse: $chave,
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            cnpjAutor: '11444777000161',
            codigoMotivo: '02',
            descricaoMotivo: 'Desenquadramento do Simples Nacional',
            chSubstituta: '22345678901234567890123456789012345678901234567890',
        );

        $this->assertSame(TipoEvento::SUBSTITUICAO, $evento->getTipo());
        $this->assertSame('22345678901234567890123456789012345678901234567890', $evento->getChSubstituta());
    }

    public function test_create_with_cpf_autor(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::CONFIRMACAO_PRESTADOR,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            cpfAutor: '52998224725',
        );

        $this->assertNull($evento->getCnpjAutor());
        $this->assertSame('52998224725', $evento->getCpfAutor());
    }

    public function test_create_with_n_seq_evento_amb_ger(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::CANCELAMENTO,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            cnpjAutor: '11444777000161',
            nSeqEvento: '001',
            ambGer: 2,
            nDFSe: '123',
        );

        $this->assertSame('001', $evento->getNSeqEvento());
        $this->assertSame(2, $evento->getAmbGer());
        $this->assertSame('123', $evento->getNDFSe());
    }

    public function test_create_all_16_tipo_evento(): void
    {
        $casos = [
            TipoEvento::CANCELAMENTO,
            TipoEvento::SUBSTITUICAO,
            TipoEvento::SOLICITACAO_ANALISE_FISCAL,
            TipoEvento::CANCELAMENTO_DEFERIDO,
            TipoEvento::CANCELAMENTO_INDEFERIDO,
            TipoEvento::CONFIRMACAO_PRESTADOR,
            TipoEvento::CONFIRMACAO_TOMADOR,
            TipoEvento::CONFIRMACAO_INTERMEDIARIO,
            TipoEvento::CONFIRMACAO_TACITA,
            TipoEvento::REJEICAO_PRESTADOR,
            TipoEvento::REJEICAO_TOMADOR,
            TipoEvento::REJEICAO_INTERMEDIARIO,
            TipoEvento::ANULACAO_REJEICAO,
            TipoEvento::CANCELAMENTO_OFICIO,
            TipoEvento::BLOQUEIO_OFICIO,
            TipoEvento::DESBLOQUEIO_OFICIO,
        ];

        foreach ($casos as $tipo) {
            $params = [
                'tipo' => $tipo,
                'chaveNfse' => new ChaveAcesso(self::CHAVE_50),
                'dataEvento' => new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
                'versaoAplicacao' => '1.0.0',
                'cnpjAutor' => '11444777000161',
            ];

            if ($tipo === TipoEvento::SUBSTITUICAO) {
                $params['chSubstituta'] = '22345678901234567890123456789012345678901234567890';
            }

            $evento = new Evento(...$params);

            $this->assertSame($tipo, $evento->getTipo());
            $this->assertSame(self::CHAVE_50, $evento->getChaveNfse());
        }
    }

    public function test_empty_versao_aplicacao_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Versão da aplicação é obrigatória');

        new Evento(
            tipo: TipoEvento::CANCELAMENTO,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '',
        );
    }

    public function test_cnpj_e_cpf_juntos_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Informar apenas CNPJ Autor ou CPF Autor, não ambos');

        new Evento(
            tipo: TipoEvento::CANCELAMENTO,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            cnpjAutor: '11444777000161',
            cpfAutor: '52998224725',
        );
    }

    public function test_substituicao_sem_ch_substituta_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Chave da NFS-e substituta (chSubstituta) é obrigatória');

        new Evento(
            tipo: TipoEvento::SUBSTITUICAO,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            cnpjAutor: '11444777000161',
        );
    }

    public function test_substituicao_ch_substituta_invalida_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('chSubstituta deve ter 50 dígitos numéricos');

        new Evento(
            tipo: TipoEvento::SUBSTITUICAO,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            cnpjAutor: '11444777000161',
            chSubstituta: '123',
        );
    }

    public function test_x_desc_cancelamento(): void
    {
        $this->assertSame('Cancelamento de NFS-e', TipoEvento::CANCELAMENTO->xDesc());
    }

    public function test_x_desc_substituicao(): void
    {
        $this->assertSame('Cancelamento de NFS-e por Substituição', TipoEvento::SUBSTITUICAO->xDesc());
    }

    public function test_event_type_tag(): void
    {
        $this->assertSame('e101101', TipoEvento::CANCELAMENTO->eventTypeTag());
        $this->assertSame('e105102', TipoEvento::SUBSTITUICAO->eventTypeTag());
        $this->assertSame('e101103', TipoEvento::SOLICITACAO_ANALISE_FISCAL->eventTypeTag());
    }

    public function test_needs_ch_substituta(): void
    {
        $this->assertTrue(TipoEvento::SUBSTITUICAO->needsChSubstituta());
        $this->assertFalse(TipoEvento::CANCELAMENTO->needsChSubstituta());
    }

    public function test_cancelamento_oficio_fields(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::CANCELAMENTO_OFICIO,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            cpfAgTrib: '52998224725',
            nProcAdm: '12345',
            xProcAdm: 'Processo administrativo de cancelamento por ofício',
        );

        $this->assertSame('52998224725', $evento->getCpfAgTrib());
        $this->assertSame('12345', $evento->getNProcAdm());
        $this->assertSame('Processo administrativo de cancelamento por ofício', $evento->getXProcAdm());
    }
}
