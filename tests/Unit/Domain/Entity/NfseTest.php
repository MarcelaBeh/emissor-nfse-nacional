<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Nfse;
use PHPUnit\Framework\TestCase;

final class NfseTest extends TestCase
{
    private const CHAVE_50 = '12345678901234567890123456789012345678901234567890';

    public function test_create_minimal(): void
    {
        $nfse = new Nfse(
            chaveAcesso: self::CHAVE_50,
            numero: '123',
            codigoVerificacao: 'ABC123',
            serie: '1',
            dataEmissao: '2026-06-15T10:00:00-03:00',
            prestadorCnpj: '11444777000161',
            prestadorNome: 'Prestador Ltda',
            tomadorNome: 'Tomador Ltda',
            valorServicos: '1000.00',
            valorIss: '50.00',
        );

        $this->assertSame(self::CHAVE_50, $nfse->getChaveAcesso());
        $this->assertSame('123', $nfse->getNumero());
        $this->assertSame('ABC123', $nfse->getCodigoVerificacao());
        $this->assertSame('1', $nfse->getSerie());
        $this->assertSame('2026-06-15T10:00:00-03:00', $nfse->getDataEmissao());
        $this->assertSame('11444777000161', $nfse->getPrestadorCnpj());
        $this->assertSame('Prestador Ltda', $nfse->getPrestadorNome());
        $this->assertSame('Tomador Ltda', $nfse->getTomadorNome());
        $this->assertSame('1000.00', $nfse->getValorServicos());
        $this->assertSame('50.00', $nfse->getValorIss());
        $this->assertNull($nfse->getXml());
    }

    public function test_create_with_xml(): void
    {
        $xml = '<?xml version="1.0"?><Nfse xmlns="http://www.sped.fazenda.gov.br/nfse"></Nfse>';
        $nfse = new Nfse(
            chaveAcesso: self::CHAVE_50,
            numero: '123',
            codigoVerificacao: 'ABC123',
            serie: '1',
            dataEmissao: '2026-06-15T10:00:00-03:00',
            prestadorCnpj: '11444777000161',
            prestadorNome: 'Prestador Ltda',
            tomadorNome: 'Tomador Ltda',
            valorServicos: '1000.00',
            valorIss: '50.00',
            xml: $xml,
        );

        $this->assertSame($xml, $nfse->getXml());
    }

    public function test_create_empty_xml(): void
    {
        $nfse = new Nfse(
            chaveAcesso: self::CHAVE_50,
            numero: '123',
            codigoVerificacao: 'ABC123',
            serie: '1',
            dataEmissao: '2026-06-15T10:00:00-03:00',
            prestadorCnpj: '11444777000161',
            prestadorNome: 'Prestador Ltda',
            tomadorNome: 'Tomador Ltda',
            valorServicos: '1000.00',
            valorIss: '50.00',
            xml: '',
        );

        $this->assertSame('', $nfse->getXml());
    }

    public function test_get_all_fields(): void
    {
        $nfse = new Nfse(
            chaveAcesso: self::CHAVE_50,
            numero: '456',
            codigoVerificacao: 'XYZ789',
            serie: '2',
            dataEmissao: '2026-07-20T15:30:00-03:00',
            prestadorCnpj: '22333444000155',
            prestadorNome: 'Outro Prestador',
            tomadorNome: 'Outro Tomador',
            valorServicos: '5000.00',
            valorIss: '250.00',
            xml: '<Nfse/>',
        );

        $this->assertSame(self::CHAVE_50, $nfse->getChaveAcesso());
        $this->assertSame('456', $nfse->getNumero());
        $this->assertSame('XYZ789', $nfse->getCodigoVerificacao());
        $this->assertSame('2', $nfse->getSerie());
        $this->assertSame('2026-07-20T15:30:00-03:00', $nfse->getDataEmissao());
        $this->assertSame('22333444000155', $nfse->getPrestadorCnpj());
        $this->assertSame('Outro Prestador', $nfse->getPrestadorNome());
        $this->assertSame('Outro Tomador', $nfse->getTomadorNome());
        $this->assertSame('5000.00', $nfse->getValorServicos());
        $this->assertSame('250.00', $nfse->getValorIss());
        $this->assertSame('<Nfse/>', $nfse->getXml());
    }

    public function test_implements_interface(): void
    {
        $nfse = new Nfse(
            chaveAcesso: self::CHAVE_50,
            numero: '123',
            codigoVerificacao: 'ABC123',
            serie: '1',
            dataEmissao: '2026-06-15T10:00:00-03:00',
            prestadorCnpj: '11444777000161',
            prestadorNome: 'Prestador Ltda',
            tomadorNome: 'Tomador Ltda',
            valorServicos: '1000.00',
            valorIss: '50.00',
        );

        $this->assertInstanceOf(\MarcelaBeh\EmissorNfseNacional\Domain\Contract\NfseInterface::class, $nfse);
    }

    public function test_chave_acesso_50_digits(): void
    {
        $nfse = new Nfse(
            chaveAcesso: self::CHAVE_50,
            numero: '123',
            codigoVerificacao: 'ABC123',
            serie: '1',
            dataEmissao: '2026-06-15T10:00:00-03:00',
            prestadorCnpj: '11444777000161',
            prestadorNome: 'Prestador Ltda',
            tomadorNome: 'Tomador Ltda',
            valorServicos: '1000.00',
            valorIss: '50.00',
        );

        $this->assertSame(50, strlen($nfse->getChaveAcesso()));
    }

    public function test_numero_string(): void
    {
        $nfse = new Nfse(
            chaveAcesso: self::CHAVE_50,
            numero: '999999',
            codigoVerificacao: 'ABC123',
            serie: '1',
            dataEmissao: '2026-06-15T10:00:00-03:00',
            prestadorCnpj: '11444777000161',
            prestadorNome: 'Prestador Ltda',
            tomadorNome: 'Tomador Ltda',
            valorServicos: '1000.00',
            valorIss: '50.00',
        );

        $this->assertSame('999999', $nfse->getNumero());
    }

    public function test_codigo_verificacao_alpha_numeric(): void
    {
        $nfse = new Nfse(
            chaveAcesso: self::CHAVE_50,
            numero: '123',
            codigoVerificacao: 'A1B2C3',
            serie: '1',
            dataEmissao: '2026-06-15T10:00:00-03:00',
            prestadorCnpj: '11444777000161',
            prestadorNome: 'Prestador Ltda',
            tomadorNome: 'Tomador Ltda',
            valorServicos: '1000.00',
            valorIss: '50.00',
        );

        $this->assertSame('A1B2C3', $nfse->getCodigoVerificacao());
    }

    public function test_valor_servicos_decimal(): void
    {
        $nfse = new Nfse(
            chaveAcesso: self::CHAVE_50,
            numero: '123',
            codigoVerificacao: 'ABC123',
            serie: '1',
            dataEmissao: '2026-06-15T10:00:00-03:00',
            prestadorCnpj: '11444777000161',
            prestadorNome: 'Prestador Ltda',
            tomadorNome: 'Tomador Ltda',
            valorServicos: '999999999.99',
            valorIss: '49999999.99',
        );

        $this->assertSame('999999999.99', $nfse->getValorServicos());
        $this->assertSame('49999999.99', $nfse->getValorIss());
    }

    public function test_prestador_cnpj_14_digits(): void
    {
        $nfse = new Nfse(
            chaveAcesso: self::CHAVE_50,
            numero: '123',
            codigoVerificacao: 'ABC123',
            serie: '1',
            dataEmissao: '2026-06-15T10:00:00-03:00',
            prestadorCnpj: '11444777000161',
            prestadorNome: 'Prestador Ltda',
            tomadorNome: 'Tomador Ltda',
            valorServicos: '1000.00',
            valorIss: '50.00',
        );

        $this->assertSame(14, strlen($nfse->getPrestadorCnpj()));
    }
}
