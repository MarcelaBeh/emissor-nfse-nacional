<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\ValueObject;

use MarcelaBeh\EmissorNfseNacional\Domain\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Telefone;
use PHPUnit\Framework\TestCase;

final class TelefoneTest extends TestCase
{
    public function test_valid_10_digit_phone(): void
    {
        $telefone = new Telefone('1198765432');
        $this->assertSame('1198765432', $telefone->getNumero());
    }

    public function test_valid_11_digit_phone(): void
    {
        $telefone = new Telefone('11987654321');
        $this->assertSame('11987654321', $telefone->getNumero());
    }

    public function test_phone_with_mask(): void
    {
        $telefone = new Telefone('(11) 98765-4321');
        $this->assertSame('11987654321', $telefone->getNumero());
    }

    public function test_phone_too_short_throws_exception(): void
    {
        $this->expectException(ValidationException::class);
        new Telefone('12345'); // 5 dígitos — abaixo do mínimo XSD (6)
    }

    public function test_phone_too_long_throws_exception(): void
    {
        $this->expectException(ValidationException::class);
        new Telefone('123456789012345678901'); // 21 dígitos — acima do máximo XSD (20)
    }

    public function test_valid_international_phone(): void
    {
        $telefone = new Telefone('123456'); // 6 dígitos — mínimo válido pelo XSD
        $this->assertSame('123456', $telefone->getNumero());
    }

    public function test_formatado_11_digits(): void
    {
        $telefone = new Telefone('11987654321');
        $this->assertSame('(11) 98765-4321', $telefone->formatado());
    }

    public function test_formatado_10_digits(): void
    {
        $telefone = new Telefone('1198765432');
        $this->assertSame('(11) 9876-5432', $telefone->formatado());
    }

    public function test_to_string(): void
    {
        $telefone = new Telefone('11987654321');
        $this->assertSame('11987654321', (string) $telefone);
    }
}
