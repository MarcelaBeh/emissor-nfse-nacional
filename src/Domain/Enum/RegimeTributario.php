<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Enum;

enum RegimeTributario: int
{
    case REGIME_NORMAL = 1;
    case MEI = 2;
    case SIMPLES_NACIONAL = 3;

    public function descricao(): string
    {
        return match ($this) {
            self::REGIME_NORMAL => 'Regime Normal',
            self::MEI => 'Microempreendedor Individual',
            self::SIMPLES_NACIONAL => 'Simples Nacional',
        };
    }
}
