<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\ValueObject;

use MarcelaBeh\EmissorNfseNacional\Domain\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\InscricaoMunicipal;
use PHPUnit\Framework\TestCase;

final class InscricaoMunicipalTest extends TestCase
{
    public function test_valid_inscricao(): void
    {
        $inscricao = new InscricaoMunicipal('123456789');
        $this->assertSame('123456789', $inscricao->getInscricao());
    }

    public function test_inscricao_with_mask(): void
    {
        $inscricao = new InscricaoMunicipal('123.456.789');
        $this->assertSame('123456789', $inscricao->getInscricao());
    }

    public function test_empty_inscricao_throws_exception(): void
    {
        $this->expectException(ValidationException::class);
        new InscricaoMunicipal('');
    }

    public function test_only_letters_throws_exception(): void
    {
        $this->expectException(ValidationException::class);
        new InscricaoMunicipal('abc');
    }

    public function test_to_string(): void
    {
        $inscricao = new InscricaoMunicipal('123456789');
        $this->assertSame('123456789', (string) $inscricao);
    }
}
