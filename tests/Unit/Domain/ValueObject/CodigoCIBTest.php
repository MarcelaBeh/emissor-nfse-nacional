<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\ValueObject;

use MarcelaBeh\EmissorNfseNacional\Domain\Exception\DomainException;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoCIB;
use PHPUnit\Framework\TestCase;

final class CodigoCIBTest extends TestCase
{
    public function test_create_valid_codigo_cib(): void
    {
        $cib = new CodigoCIB('12345678');
        $this->assertSame('12345678', $cib->getCodigo());
    }

    public function test_create_with_less_than_8_chars_throws(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('CIB deve ter exatamente 8 caracteres');
        new CodigoCIB('1234567');
    }

    public function test_create_with_more_than_8_chars_throws(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('CIB deve ter exatamente 8 caracteres');
        new CodigoCIB('123456789');
    }

    public function test_create_with_whitespace_trimmed(): void
    {
        $cib = new CodigoCIB(' 12345678 ');
        $this->assertSame('12345678', $cib->getCodigo());
    }
}
