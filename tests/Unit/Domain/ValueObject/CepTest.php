<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\ValueObject;

use MarcelaBeh\EmissorNfseNacional\Domain\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cep;
use PHPUnit\Framework\TestCase;

final class CepTest extends TestCase
{
    public function test_valid_cep(): void
    {
        $cep = new Cep('01001000');
        $this->assertSame('01001000', $cep->getCep());
    }

    public function test_valid_cep_with_mask(): void
    {
        $cep = new Cep('01001-000');
        $this->assertSame('01001000', $cep->getCep());
    }

    public function test_invalid_cep_length_throws_exception(): void
    {
        $this->expectException(ValidationException::class);
        new Cep('1234567');
    }

    public function test_empty_cep_throws_exception(): void
    {
        $this->expectException(ValidationException::class);
        new Cep('');
    }

    public function test_formatado(): void
    {
        $cep = new Cep('01001000');
        $this->assertSame('01.001-000', $cep->formatado());
    }

    public function test_to_string(): void
    {
        $cep = new Cep('01001000');
        $this->assertSame('01001000', (string) $cep);
    }
}
