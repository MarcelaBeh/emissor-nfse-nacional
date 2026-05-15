<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsEnderecoExterior;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsEnderecoObra;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsImovel;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoCIB;
use PHPUnit\Framework\TestCase;

final class IbsCbsImovelTest extends TestCase
{
    public function test_create_with_cib_only(): void
    {
        $imovel = new IbsCbsImovel(
            inscImobFisc: '12345',
            cCIB: new CodigoCIB('12345678'),
        );

        $this->assertSame('12345', $imovel->getInscImobFisc());
        $this->assertSame('12345678', $imovel->getCCIB()?->getCodigo());
        $this->assertNull($imovel->getEndereco());
        $this->assertTrue($imovel->isPorCIB());
    }

    public function test_create_with_endereco(): void
    {
        $endereco = new IbsCbsEnderecoObra(
            cep: '01001001',
            endExt: null,
            xLgr: 'Rua do Imóvel',
            nro: '100',
            xBairro: 'Centro',
            xCpl: 'Apto 42',
        );
        $imovel = new IbsCbsImovel(
            endereco: $endereco,
        );

        $this->assertNotNull($imovel->getEndereco());
        $this->assertSame('01001001', $imovel->getEndereco()->getCep());
        $this->assertSame('Rua do Imóvel', $imovel->getEndereco()->getXLgr());
        $this->assertSame('100', $imovel->getEndereco()->getNro());
        $this->assertSame('Apto 42', $imovel->getEndereco()->getXCpl());
        $this->assertSame('Centro', $imovel->getEndereco()->getXBairro());
        $this->assertNull($imovel->getCCIB());
        $this->assertTrue($imovel->isPorEndereco());
    }

    public function test_create_with_endereco_exterior(): void
    {
        $endExt = new IbsCbsEnderecoExterior(
            cEndPost: '90210',
            xCidade: 'Beverly Hills',
            xEstProvReg: 'CA',
        );
        $endereco = new IbsCbsEnderecoObra(
            cep: null,
            endExt: $endExt,
            xLgr: 'Sunset Blvd',
            nro: '200',
            xBairro: 'Beverly Hills',
        );
        $imovel = new IbsCbsImovel(endereco: $endereco);

        $this->assertNotNull($imovel->getEndereco()->getEndExt());
        $this->assertSame('90210', $imovel->getEndereco()->getEndExt()->getCEndPost());
        $this->assertSame('Beverly Hills', $imovel->getEndereco()->getEndExt()->getXCidade());
        $this->assertSame('CA', $imovel->getEndereco()->getEndExt()->getXEstProvReg());
    }

    public function test_create_with_all_null_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Imóvel deve informar exatamente um dos campos: cCIB ou endereco');
        new IbsCbsImovel();
    }

    public function test_create_with_multiple_fields_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new IbsCbsImovel(
            cCIB: new CodigoCIB('12345678'),
            endereco: new IbsCbsEnderecoObra(
                cep: '01001001',
                endExt: null,
                xLgr: 'Rua',
                nro: '1',
                xBairro: 'Centro',
            ),
        );
    }
}
