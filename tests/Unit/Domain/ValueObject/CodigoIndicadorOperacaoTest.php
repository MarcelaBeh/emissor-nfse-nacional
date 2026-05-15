<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\ValueObject;

use MarcelaBeh\EmissorNfseNacional\Domain\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoIndicadorOperacao;
use PHPUnit\Framework\TestCase;

final class CodigoIndicadorOperacaoTest extends TestCase
{
    public function test_valid_codigo(): void
    {
        $codigo = new CodigoIndicadorOperacao('123456');
        $this->assertSame('123456', $codigo->getCodigo());
    }

    public function test_codigo_with_leading_zeros(): void
    {
        $codigo = new CodigoIndicadorOperacao('000001');
        $this->assertSame('000001', $codigo->getCodigo());
    }

    public function test_too_short_throws_exception(): void
    {
        $this->expectException(ValidationException::class);
        new CodigoIndicadorOperacao('12345');
    }

    public function test_too_long_throws_exception(): void
    {
        $this->expectException(ValidationException::class);
        new CodigoIndicadorOperacao('1234567');
    }

    public function test_with_letters_throws_exception(): void
    {
        $this->expectException(ValidationException::class);
        new CodigoIndicadorOperacao('12345a');
    }

    public function test_to_string(): void
    {
        $codigo = new CodigoIndicadorOperacao('123456');
        $this->assertSame('123456', (string) $codigo);
    }
}
