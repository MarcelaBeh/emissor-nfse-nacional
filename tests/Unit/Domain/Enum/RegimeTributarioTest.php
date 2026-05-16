<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Enum;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\RegimeTributario;
use PHPUnit\Framework\TestCase;

final class RegimeTributarioTest extends TestCase
{
    public function test_regime_normal(): void
    {
        $this->assertSame(1, RegimeTributario::REGIME_NORMAL->value);
        $this->assertSame('Regime Normal', RegimeTributario::REGIME_NORMAL->descricao());
    }

    public function test_mei(): void
    {
        $this->assertSame(2, RegimeTributario::MEI->value);
        $this->assertSame('Microempreendedor Individual', RegimeTributario::MEI->descricao());
    }

    public function test_simples_nacional(): void
    {
        $this->assertSame(3, RegimeTributario::SIMPLES_NACIONAL->value);
        $this->assertSame('Simples Nacional', RegimeTributario::SIMPLES_NACIONAL->descricao());
    }

    public function test_from(): void
    {
        $this->assertSame(RegimeTributario::REGIME_NORMAL, RegimeTributario::from(1));
        $this->assertSame(RegimeTributario::MEI, RegimeTributario::from(2));
        $this->assertSame(RegimeTributario::SIMPLES_NACIONAL, RegimeTributario::from(3));
    }
}
