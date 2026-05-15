<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\ValueObject;

use MarcelaBeh\EmissorNfseNacional\Domain\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoCreditoPresumido;
use PHPUnit\Framework\TestCase;

final class CodigoCreditoPresumidoTest extends TestCase
{
    public function test_valid_codigo(): void
    {
        $codigo = new CodigoCreditoPresumido('12');
        $this->assertSame('12', $codigo->getCodigo());
    }

    public function test_codigo_with_leading_zero(): void
    {
        $codigo = new CodigoCreditoPresumido('01');
        $this->assertSame('01', $codigo->getCodigo());
    }

    public function test_too_short_throws_exception(): void
    {
        $this->expectException(ValidationException::class);
        new CodigoCreditoPresumido('1');
    }

    public function test_too_long_throws_exception(): void
    {
        $this->expectException(ValidationException::class);
        new CodigoCreditoPresumido('123');
    }

    public function test_with_letters_throws_exception(): void
    {
        $this->expectException(ValidationException::class);
        new CodigoCreditoPresumido('1a');
    }

    public function test_to_string(): void
    {
        $codigo = new CodigoCreditoPresumido('12');
        $this->assertSame('12', (string) $codigo);
    }
}
