<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\ValueObject;

use MarcelaBeh\EmissorNfseNacional\Domain\Exception\InvalidCpfException;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cpf;
use PHPUnit\Framework\TestCase;

final class CpfTest extends TestCase
{
    public function test_valid_cpf(): void
    {
        $cpf = new Cpf('52998224725');
        $this->assertSame('52998224725', $cpf->getNumero());
    }

    public function test_valid_cpf_with_mask(): void
    {
        $cpf = new Cpf('529.982.247-25');
        $this->assertSame('52998224725', $cpf->getNumero());
    }

    public function test_invalid_cpf_throws_exception(): void
    {
        $this->expectException(InvalidCpfException::class);
        new Cpf('11111111111');
    }

    public function test_short_cpf_throws_exception(): void
    {
        $this->expectException(InvalidCpfException::class);
        new Cpf('1234567890');
    }

    public function test_repeated_digits_throws_exception(): void
    {
        $this->expectException(InvalidCpfException::class);
        new Cpf('00000000000');
    }

    public function test_formatado(): void
    {
        $cpf = new Cpf('52998224725');
        $this->assertSame('529.982.247-25', $cpf->formatado());
    }

    public function test_equals(): void
    {
        $a = new Cpf('52998224725');
        $b = new Cpf('52998224725');
        $c = new Cpf('11144477735');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function test_to_string(): void
    {
        $cpf = new Cpf('52998224725');
        $this->assertSame('52998224725', (string) $cpf);
    }
}
