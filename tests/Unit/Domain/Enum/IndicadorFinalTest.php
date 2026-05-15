<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Enum;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\IndicadorFinal;
use PHPUnit\Framework\TestCase;

final class IndicadorFinalTest extends TestCase
{
    public function test_nao(): void
    {
        $this->assertSame('0', IndicadorFinal::NAO->value);
        $this->assertSame('Não', IndicadorFinal::NAO->descricao());
    }

    public function test_sim(): void
    {
        $this->assertSame('1', IndicadorFinal::SIM->value);
        $this->assertSame('Sim (uso ou consumo pessoal)', IndicadorFinal::SIM->descricao());
    }
}
