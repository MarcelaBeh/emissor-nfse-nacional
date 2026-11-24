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

    public function test_do_id_nfse_extrai_a_chave_do_id(): void
    {
        $this->assertSame(self::VALID_CHAVE, ChaveAcesso::doIdNfse('NFS' . self::VALID_CHAVE));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function idsInvalidos(): array
    {
        return [
            'sem prefixo' => [self::VALID_CHAVE],
            'prefixo errado' => ['DPS' . self::VALID_CHAVE],
            'curto' => ['NFS' . substr(self::VALID_CHAVE, 1)],
            'longo' => ['NFS' . self::VALID_CHAVE . '1'],
            'com letra' => ['NFS' . 'A' . substr(self::VALID_CHAVE, 1)],
            'nulo' => [null],
            'nao e texto' => [123],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('idsInvalidos')]
    public function test_do_id_nfse_devolve_null_para_id_invalido(mixed $id): void
    {
        $this->assertNull(ChaveAcesso::doIdNfse($id));
    }
}
