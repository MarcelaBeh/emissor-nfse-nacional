<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\ValueObject;

use MarcelaBeh\EmissorNfseNacional\Domain\Exception\InvalidCnpjException;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cnpj;
use PHPUnit\Framework\TestCase;

final class CnpjTest extends TestCase
{
    public function test_valid_cnpj(): void
    {
        $cnpj = new Cnpj('11444777000161');
        $this->assertSame('11444777000161', $cnpj->getNumero());
    }

    public function test_valid_cnpj_with_mask(): void
    {
        $cnpj = new Cnpj('11.444.777/0001-61');
        $this->assertSame('11444777000161', $cnpj->getNumero());
    }

    public function test_invalid_cnpj_throws_exception(): void
    {
        $this->expectException(InvalidCnpjException::class);
        new Cnpj('11444777000100');
    }

    public function test_short_cnpj_throws_exception(): void
    {
        $this->expectException(InvalidCnpjException::class);
        new Cnpj('1234567890123');
    }

    public function test_repeated_digits_throws_exception(): void
    {
        $this->expectException(InvalidCnpjException::class);
        new Cnpj('00000000000000');
    }

    public function test_formatado(): void
    {
        $cnpj = new Cnpj('11444777000161');
        $this->assertSame('11.444.777/0001-61', $cnpj->formatado());
    }

    public function test_equals(): void
    {
        $a = new Cnpj('11444777000161');
        $b = new Cnpj('11444777000161');
        $c = new Cnpj('33444555000181');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function test_to_string(): void
    {
        $cnpj = new Cnpj('11444777000161');
        $this->assertSame('11444777000161', (string) $cnpj);
    }
}
