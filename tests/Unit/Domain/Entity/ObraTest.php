<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsEnderecoObra;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Obra;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoCIB;
use PHPUnit\Framework\TestCase;

final class ObraTest extends TestCase
{
    public function test_create_with_cobra(): void
    {
        $obra = new Obra(
            inscImobFisc: '12345',
            cObra: 'CNO123456789',
        );

        $this->assertSame('12345', $obra->getInscImobFisc());
        $this->assertSame('CNO123456789', $obra->getCObra());
        $this->assertNull($obra->getCCIB());
        $this->assertNull($obra->getEndereco());
        $this->assertTrue($obra->isPorCodigoObra());
    }

    public function test_create_with_cib(): void
    {
        $obra = new Obra(
            cCIB: new CodigoCIB('12345678'),
        );

        $this->assertSame('12345678', $obra->getCCIB()->getCodigo());
        $this->assertNull($obra->getCObra());
        $this->assertTrue($obra->isPorCIB());
    }

    public function test_create_with_endereco(): void
    {
        $end = new IbsCbsEnderecoObra(
            cep: '01001001',
            endExt: null,
            xLgr: 'Rua da Obra',
            nro: '500',
            xBairro: 'Industrial',
        );
        $obra = new Obra(endereco: $end);

        $this->assertSame('Rua da Obra', $obra->getEndereco()->getXLgr());
        $this->assertSame('500', $obra->getEndereco()->getNro());
        $this->assertNull($obra->getCObra());
        $this->assertNull($obra->getCCIB());
        $this->assertTrue($obra->isPorEndereco());
    }

    public function test_create_with_all_null_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Obra deve informar exatamente um dos campos: cObra (CNO/CEI), cCIB ou endereco');
        new Obra();
    }

    public function test_create_with_multiple_fields_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Obra(
            cObra: 'CNO123',
            cCIB: new CodigoCIB('12345678'),
        );
    }
}
