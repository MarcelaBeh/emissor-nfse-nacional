<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Integration;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Evento;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEvento;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\ChaveAcesso;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Builder\EventoXmlBuilder;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Validator\XsdValidator;
use PHPUnit\Framework\TestCase;

final class EventoXsdValidationTest extends TestCase
{
    private const CHAVE_50 = '12345678901234567890123456789012345678901234567890';

    private EventoXmlBuilder $builder;
    private XsdValidator $xsdValidator;

    protected function setUp(): void
    {
        $this->builder = new EventoXmlBuilder();
        $this->xsdValidator = new XsdValidator();
    }

    public function test_cancelamento_validates_against_xsd(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::CANCELAMENTO,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            tipoAmbiente: '2',
            cnpjAutor: '11444777000161',
            codigoMotivo: '1',
            descricaoMotivo: 'Erro na emissão da NFSe',
        );

        $xml = $this->builder->build($evento);
        $this->xsdValidator->validate($xml, 'pedRegEvento');

        $this->expectNotToPerformAssertions();
    }

    public function test_substituicao_validates_against_xsd(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::SUBSTITUICAO,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            tipoAmbiente: '2',
            cnpjAutor: '11444777000161',
            codigoMotivo: '02',
            descricaoMotivo: 'Desenquadramento do Simples Nacional',
            chSubstituta: '22345678901234567890123456789012345678901234567890',
        );

        $xml = $this->builder->build($evento);
        $this->xsdValidator->validate($xml, 'pedRegEvento');

        $this->expectNotToPerformAssertions();
    }

    public function test_solicitacao_analise_fiscal_validates_against_xsd(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::SOLICITACAO_ANALISE_FISCAL,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            tipoAmbiente: '2',
            cnpjAutor: '11444777000161',
            codigoMotivo: '9',
            descricaoMotivo: 'Solicitação de análise para cancelamento',
        );

        $xml = $this->builder->build($evento);
        $this->xsdValidator->validate($xml, 'pedRegEvento');

        $this->expectNotToPerformAssertions();
    }

    public function test_confirmacao_prestador_validates_against_xsd(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::CONFIRMACAO_PRESTADOR,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            tipoAmbiente: '2',
            cnpjAutor: '11444777000161',
        );

        $xml = $this->builder->build($evento);
        $this->xsdValidator->validate($xml, 'pedRegEvento');

        $this->expectNotToPerformAssertions();
    }

    public function test_confirmacao_tomador_validates_against_xsd(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::CONFIRMACAO_TOMADOR,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            tipoAmbiente: '2',
            cpfAutor: '52998224725',
        );

        $xml = $this->builder->build($evento);
        $this->xsdValidator->validate($xml, 'pedRegEvento');

        $this->expectNotToPerformAssertions();
    }

    public function test_confirmacao_intermediario_validates_against_xsd(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::CONFIRMACAO_INTERMEDIARIO,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            tipoAmbiente: '2',
            cnpjAutor: '11444777000161',
        );

        $xml = $this->builder->build($evento);
        $this->xsdValidator->validate($xml, 'pedRegEvento');

        $this->expectNotToPerformAssertions();
    }

    public function test_confirmacao_tacita_validates_against_xsd(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::CONFIRMACAO_TACITA,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            tipoAmbiente: '2',
            cnpjAutor: '11444777000161',
        );

        $xml = $this->builder->build($evento);
        $this->xsdValidator->validate($xml, 'pedRegEvento');

        $this->expectNotToPerformAssertions();
    }

    public function test_rejeicao_prestador_validates_against_xsd(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::REJEICAO_PRESTADOR,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            tipoAmbiente: '2',
            cnpjAutor: '11444777000161',
            codigoMotivo: '1',
        );

        $xml = $this->builder->build($evento);
        $this->xsdValidator->validate($xml, 'pedRegEvento');

        $this->expectNotToPerformAssertions();
    }

    public function test_cancelamento_oficio_validates_against_xsd(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::CANCELAMENTO_OFICIO,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            tipoAmbiente: '2',
            cnpjAutor: '11444777000161',
            cpfAgTrib: '52998224725',
            nProcAdm: '12345',
            xProcAdm: 'Processo administrativo de cancelamento por ofício',
        );

        $xml = $this->builder->build($evento);
        $this->xsdValidator->validate($xml, 'pedRegEvento');

        $this->expectNotToPerformAssertions();
    }

    public function test_bloqueio_oficio_validates_against_xsd(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::BLOQUEIO_OFICIO,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            tipoAmbiente: '2',
            cnpjAutor: '11444777000161',
            cpfAgTrib: '52998224725',
            codEventoBloqueio: 'e101101',
            descricaoMotivo: 'Bloqueio determinado por autoridade fiscal municipal',
        );

        $xml = $this->builder->build($evento);
        $this->xsdValidator->validate($xml, 'pedRegEvento');

        $this->expectNotToPerformAssertions();
    }

    public function test_desbloqueio_oficio_validates_against_xsd(): void
    {
        $evento = new Evento(
            tipo: TipoEvento::DESBLOQUEIO_OFICIO,
            chaveNfse: new ChaveAcesso(self::CHAVE_50),
            dataEvento: new \DateTimeImmutable('2026-06-15T10:00:00-03:00'),
            versaoAplicacao: '1.0.0',
            tipoAmbiente: '2',
            cnpjAutor: '11444777000161',
            cpfAgTrib: '52998224725',
            idBloqOfic: '12345678901234567890123456789012345678901234567890123456789',
        );

        $xml = $this->builder->build($evento);
        $this->xsdValidator->validate($xml, 'pedRegEvento');

        $this->expectNotToPerformAssertions();
    }
}
