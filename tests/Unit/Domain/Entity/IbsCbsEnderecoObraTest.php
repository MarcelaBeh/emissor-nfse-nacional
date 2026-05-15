<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsEnderecoExterior;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsEnderecoObra;
use PHPUnit\Framework\TestCase;

final class IbsCbsEnderecoObraTest extends TestCase
{
    public function test_create_with_cep(): void
    {
        $end = new IbsCbsEnderecoObra(
            cep: '01001001',
            endExt: null,
            xLgr: 'Rua Exemplo',
            nro: '123',
            xBairro: 'Centro',
            xCpl: 'Bloco A',
        );

        $this->assertSame('01001001', $end->getCep());
        $this->assertSame('Rua Exemplo', $end->getXLgr());
        $this->assertSame('123', $end->getNro());
        $this->assertSame('Bloco A', $end->getXCpl());
        $this->assertSame('Centro', $end->getXBairro());
        $this->assertNull($end->getEndExt());
        $this->assertTrue($end->isNacional());
    }

    public function test_create_with_endereco_exterior(): void
    {
        $endExt = new IbsCbsEnderecoExterior(
            cEndPost: '90210',
            xCidade: 'Beverly Hills',
            xEstProvReg: 'CA',
        );
        $end = new IbsCbsEnderecoObra(
            cep: null,
            endExt: $endExt,
            xLgr: 'Sunset Blvd',
            nro: '200',
            xBairro: 'Beverly Hills',
        );

        $this->assertNull($end->getCep());
        $this->assertNotNull($end->getEndExt());
        $this->assertSame('90210', $end->getEndExt()->getCEndPost());
        $this->assertTrue($end->isExterior());
    }

    public function test_create_without_cep_or_endext_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Endereço de obra deve informar CEP ou endereço no exterior');
        new IbsCbsEnderecoObra(
            cep: null,
            endExt: null,
            xLgr: 'Rua',
            nro: '1',
            xBairro: 'Bairro',
        );
    }

    public function test_create_with_both_cep_and_endext_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Endereço de obra não pode informar CEP e endereço no exterior simultaneamente');
        new IbsCbsEnderecoObra(
            cep: '01001001',
            endExt: new IbsCbsEnderecoExterior('123', 'City', 'State'),
            xLgr: 'Rua',
            nro: '1',
            xBairro: 'Bairro',
        );
    }
}
