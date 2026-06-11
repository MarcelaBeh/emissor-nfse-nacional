<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\ValueObject;

use MarcelaBeh\EmissorNfseNacional\Domain\Exception\InvalidChaveAcessoException;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\ChaveAcesso;
use PHPUnit\Framework\TestCase;

final class ChaveAcessoTest extends TestCase
{
    private const VALID_CHAVE = '35230511444777000161550010000012341000012345678901';

    public function test_valid_chave_acesso(): void
    {
        $chave = new ChaveAcesso(self::VALID_CHAVE);
        $this->assertSame(self::VALID_CHAVE, $chave->getChave());
    }

    public function test_invalid_length_throws_exception(): void
    {
        $this->expectException(InvalidChaveAcessoException::class);
        new ChaveAcesso('1234567890123456789012345678901234567890123456789');
    }

    public function test_short_chave_throws_exception(): void
    {
        $this->expectException(InvalidChaveAcessoException::class);
        new ChaveAcesso('1234567890');
    }

    public function test_formatada(): void
    {
        $chave = new ChaveAcesso('12345678901234567890123456789012345678901234567890');
        $this->assertSame('1234 5678 9012 3456 7890 1234 5678 9012 3456 7890 1234 5678 90', $chave->formatada());
    }

    public function test_to_string(): void
    {
        $chave = new ChaveAcesso(self::VALID_CHAVE);
        $this->assertSame(self::VALID_CHAVE, (string) $chave);
    }
}
