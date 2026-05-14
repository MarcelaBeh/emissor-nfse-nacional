<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\ValueObject;

use MarcelaBeh\EmissorNfseNacional\Domain\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoMunicipio;
use PHPUnit\Framework\TestCase;

final class CodigoMunicipioTest extends TestCase
{
    public function test_valid_codigo(): void
    {
        $codigo = new CodigoMunicipio('3550308');
        $this->assertSame('3550308', $codigo->getCodigo());
    }

    public function test_short_codigo_throws_exception(): void
    {
        $this->expectException(ValidationException::class);
        new CodigoMunicipio('355030');
    }

    public function test_long_codigo_throws_exception(): void
    {
        $this->expectException(ValidationException::class);
        new CodigoMunicipio('35503088');
    }

    public function test_with_dots(): void
    {
        $codigo = new CodigoMunicipio('35.5030-8');
        $this->assertSame('3550308', $codigo->getCodigo());
    }

    public function test_to_string(): void
    {
        $codigo = new CodigoMunicipio('3550308');
        $this->assertSame('3550308', (string) $codigo);
    }
}
