<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\ValueObject;

use MarcelaBeh\EmissorNfseNacional\Domain\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoSituacaoTributaria;
use PHPUnit\Framework\TestCase;

final class CodigoSituacaoTributariaTest extends TestCase
{
    public function test_valid_codigo(): void
    {
        $codigo = new CodigoSituacaoTributaria('123');
        $this->assertSame('123', $codigo->getCodigo());
    }

    public function test_codigo_with_leading_zeros(): void
    {
        $codigo = new CodigoSituacaoTributaria('001');
        $this->assertSame('001', $codigo->getCodigo());
    }

    public function test_too_short_throws_exception(): void
    {
        $this->expectException(ValidationException::class);
        new CodigoSituacaoTributaria('12');
    }

    public function test_too_long_throws_exception(): void
    {
        $this->expectException(ValidationException::class);
        new CodigoSituacaoTributaria('1234');
    }

    public function test_with_letters_throws_exception(): void
    {
        $this->expectException(ValidationException::class);
        new CodigoSituacaoTributaria('12a');
    }

    public function test_to_string(): void
    {
        $codigo = new CodigoSituacaoTributaria('123');
        $this->assertSame('123', (string) $codigo);
    }
}
