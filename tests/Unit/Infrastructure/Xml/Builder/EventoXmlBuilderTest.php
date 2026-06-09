<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Infrastructure\Xml\Builder;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Evento;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEvento;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\ChaveAcesso;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Builder\EventoXmlBuilder;
use PHPUnit\Framework\TestCase;

final class EventoXmlBuilderTest extends TestCase
{
    private const CHAVE_50 = '12345678901234567890123456789012345678901234567890';

    private EventoXmlBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new EventoXmlBuilder();
    }

    public function test_build_cancelamento(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::CANCELAMENTO,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            tipoAmbiente: 2,
            cnpjAutor: '11444777000161',
            codigoMotivo: '1',
            descricaoMotivo: 'Erro na emissão da NFSe',
        );

        $xml = $this->builder->build($evento);

        $this->assertStringContainsString('<pedRegEvento', $xml);
        $this->assertStringContainsString('<infPedReg', $xml);
        $this->assertStringContainsString('<e101101>', $xml);
        $this->assertStringContainsString('<xDesc>Cancelamento de NFS-e</xDesc>', $xml);
        $this->assertStringContainsString('<cMotivo>1</cMotivo>', $xml);
        $this->assertStringContainsString('<xMotivo>Erro na emissão da NFSe</xMotivo>', $xml);
        $this->assertStringContainsString('<CNPJAutor>11444777000161</CNPJAutor>', $xml);
        $this->assertStringContainsString('<tpAmb>2</tpAmb>', $xml);
        $this->assertStringContainsString('<chNFSe>' . self::CHAVE_50 . '</chNFSe>', $xml);
    }

    public function test_id_pedregevento_segue_pattern_tsidpedregevt(): void
    {
        // Regressão: o Id é PRE + chave(50) + tipoEvento(6) = 59 chars, sem nPedRegEvento
        // (campo removido pelo governo em 27/12/2025). Deve casar o pattern XSD PRE[0-9]{56}.
        $evento = new Evento(
            tipo: TipoEvento::CANCELAMENTO,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            tipoAmbiente: 2,
            cnpjAutor: '11444777000161',
            codigoMotivo: '1',
            descricaoMotivo: 'Erro na emissão da NFSe',
        );

        $xml = $this->builder->build($evento);

        self::assertSame(1, preg_match('/Id="(PRE[0-9]{56})"/', $xml, $m), 'Id deve casar PRE[0-9]{56}');
        self::assertSame(59, strlen($m[1]));
        self::assertSame('PRE' . self::CHAVE_50 . '101101', $m[1]);
    }

    public function test_build_cancelamento_with_cpf_autor(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::CANCELAMENTO,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            tipoAmbiente: 2,
            cpfAutor: '52998224725',
            codigoMotivo: '2',
            descricaoMotivo: 'Serviço não prestado conforme contrato',
        );

        $xml = $this->builder->build($evento);

        $this->assertStringContainsString('<CPFAutor>52998224725</CPFAutor>', $xml);
        $this->assertStringNotContainsString('<CNPJAutor', $xml);
    }

    public function test_build_substituicao(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::SUBSTITUICAO,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            tipoAmbiente: 2,
            cnpjAutor: '11444777000161',
            codigoMotivo: '02',
            descricaoMotivo: 'Desenquadramento do Simples Nacional',
            chSubstituta: '22345678901234567890123456789012345678901234567890',
        );

        $xml = $this->builder->build($evento);

        $this->assertStringContainsString('<e105102>', $xml);
        $this->assertStringContainsString('<xDesc>Cancelamento de NFS-e por Substituição</xDesc>', $xml);
        $this->assertStringContainsString('<cMotivo>02</cMotivo>', $xml);
        $this->assertStringContainsString('<chSubstituta>22345678901234567890123456789012345678901234567890</chSubstituta>', $xml);
    }

    public function test_build_solicitacao_analise_fiscal(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::SOLICITACAO_ANALISE_FISCAL,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            tipoAmbiente: 2,
            cnpjAutor: '11444777000161',
            codigoMotivo: '9',
            descricaoMotivo: 'Outros motivos para análise fiscal',
        );

        $xml = $this->builder->build($evento);

        $this->assertStringContainsString('<e101103>', $xml);
        $this->assertStringContainsString('<xDesc>Solicitação de Análise Fiscal para Cancelamento de NFS-e</xDesc>', $xml);
    }

    public function test_build_confirmacao_prestador(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::CONFIRMACAO_PRESTADOR,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            tipoAmbiente: 2,
            cnpjAutor: '11444777000161',
        );

        $xml = $this->builder->build($evento);

        $this->assertStringContainsString('<e202201>', $xml);
        $this->assertStringContainsString('<xDesc>Manifestação de NFS-e - Confirmação do Prestador</xDesc>', $xml);
        $this->assertStringNotContainsString('<cMotivo', $xml);
    }

    public function test_build_confirmacao_tacita(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::CONFIRMACAO_TACITA,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            tipoAmbiente: 2,
            cnpjAutor: '11444777000161',
        );

        $xml = $this->builder->build($evento);

        $this->assertStringContainsString('<e205204>', $xml);
        $this->assertStringContainsString('<xDesc>Manifestação de NFS-e - Confirmação Tácita</xDesc>', $xml);
    }

    public function test_build_rejeicao_prestador(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::REJEICAO_PRESTADOR,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            tipoAmbiente: 2,
            cnpjAutor: '11444777000161',
            codigoMotivo: '1',
        );

        $xml = $this->builder->build($evento);

        $this->assertStringContainsString('<e202205>', $xml);
        $this->assertStringContainsString('<xDesc>Manifestação de NFS-e - Rejeição do Prestador</xDesc>', $xml);
        $this->assertStringContainsString('<cMotivo>1</cMotivo>', $xml);
    }

    public function test_build_anulacao_rejeicao(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::ANULACAO_REJEICAO,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            tipoAmbiente: 2,
            cpfAgTrib: '52998224725',
            idEvManifRej: '12345678901234567890123456789012345678901234567890123456789',
            descricaoMotivo: 'Anulação da rejeição por erro na análise',
        );

        $xml = $this->builder->build($evento);

        $this->assertStringContainsString('<e205208>', $xml);
        $this->assertStringContainsString('<CPFAgTrib>52998224725</CPFAgTrib>', $xml);
        $this->assertStringContainsString('<idEvManifRej>', $xml);
    }

    public function test_build_cancelamento_oficio(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::CANCELAMENTO_OFICIO,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            tipoAmbiente: 2,
            cnpjAutor: '11444777000161',
            cpfAgTrib: '52998224725',
            nProcAdm: '12345',
            xProcAdm: 'Processo administrativo de cancelamento',
        );

        $xml = $this->builder->build($evento);

        $this->assertStringContainsString('<e305101>', $xml);
        $this->assertStringContainsString('<CPFAgTrib>52998224725</CPFAgTrib>', $xml);
        $this->assertStringContainsString('<nProcAdm>12345</nProcAdm>', $xml);
        $this->assertStringContainsString('<xProcAdm>', $xml);
    }

    public function test_build_bloqueio_oficio(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::BLOQUEIO_OFICIO,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            tipoAmbiente: 2,
            cpfAgTrib: '52998224725',
            codEventoBloqueio: 'e101101',
            descricaoMotivo: 'Bloqueio determinado por autoridade fiscal',
        );

        $xml = $this->builder->build($evento);

        $this->assertStringContainsString('<e305102>', $xml);
        $this->assertStringContainsString('<codEvento>e101101</codEvento>', $xml);
    }

    public function test_build_desbloqueio_oficio(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::DESBLOQUEIO_OFICIO,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            tipoAmbiente: 2,
            cpfAgTrib: '52998224725',
            idBloqOfic: '12345678901234567890123456789012345678901234567890123456789',
        );

        $xml = $this->builder->build($evento);

        $this->assertStringContainsString('<e305103>', $xml);
        $this->assertStringContainsString('<idBloqOfic>', $xml);
    }

    public function test_build_inf_ped_reg_id_format(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::CANCELAMENTO,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            tipoAmbiente: 2,
            cnpjAutor: '11444777000161',
            nSeqEvento: '001',
        );

        $xml = $this->builder->build($evento);

        $expectedId = 'PRE' . self::CHAVE_50 . '101101';
        $this->assertStringContainsString('Id="' . $expectedId . '"', $xml);
        $this->assertSame(59, strlen($expectedId));
    }

    public function test_build_all_16_event_types(): void
    {
        $casos = [
            [TipoEvento::CANCELAMENTO, 'e101101'],
            [TipoEvento::SUBSTITUICAO, 'e105102'],
            [TipoEvento::SOLICITACAO_ANALISE_FISCAL, 'e101103'],
            [TipoEvento::CANCELAMENTO_DEFERIDO, 'e105104'],
            [TipoEvento::CANCELAMENTO_INDEFERIDO, 'e105105'],
            [TipoEvento::CONFIRMACAO_PRESTADOR, 'e202201'],
            [TipoEvento::CONFIRMACAO_TOMADOR, 'e203202'],
            [TipoEvento::CONFIRMACAO_INTERMEDIARIO, 'e204203'],
            [TipoEvento::CONFIRMACAO_TACITA, 'e205204'],
            [TipoEvento::REJEICAO_PRESTADOR, 'e202205'],
            [TipoEvento::REJEICAO_TOMADOR, 'e203206'],
            [TipoEvento::REJEICAO_INTERMEDIARIO, 'e204207'],
            [TipoEvento::ANULACAO_REJEICAO, 'e205208'],
            [TipoEvento::CANCELAMENTO_OFICIO, 'e305101'],
            [TipoEvento::BLOQUEIO_OFICIO, 'e305102'],
            [TipoEvento::DESBLOQUEIO_OFICIO, 'e305103'],
        ];

        foreach ($casos as [$tipo, $tag]) {
            $params = [
                'tipo' => $tipo,
                'chaveNfse' => new ChaveAcesso(self::CHAVE_50),
                'dataEvento' => new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
                'versaoAplicacao' => '1.0.0',
                'tipoAmbiente' => 2,
                'cnpjAutor' => '11444777000161',
            ];

            if ($tipo === TipoEvento::SUBSTITUICAO) {
                $params['chSubstituta'] = '22345678901234567890123456789012345678901234567890';
            }

            $evento = new Evento(...$params);
            $xml = $this->builder->build($evento);

            $this->assertStringContainsString("<{$tag}>", $xml, "Faltou a tag {$tag} para o tipo {$tipo->value}");
            $this->assertStringContainsString('<xDesc>', $xml);
        }
    }

    public function test_build_entity_evento_root_structure(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::CANCELAMENTO,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            tipoAmbiente: 2,
            cnpjAutor: '11444777000161',
        );

        $xml = $this->builder->build($evento);

        $this->assertStringContainsString('<pedRegEvento', $xml);
        $this->assertStringContainsString('versao="1.01"', $xml);
        $this->assertStringContainsString('xmlns="http://www.sped.fazenda.gov.br/nfse"', $xml);
    }
}
