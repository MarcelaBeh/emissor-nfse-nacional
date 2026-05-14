<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Domain\Enum;

enum RegimeTributario: int
{
    case SIMPLES_NACIONAL = 1;
    case REGIME_NORMAL = 2;
    case MEI = 3;

    public function descricao(): string
    {
        return match ($this) {
            self::SIMPLES_NACIONAL => 'Simples Nacional',
            self::REGIME_NORMAL => 'Regime Normal',
            self::MEI => 'Microempreendedor Individual',
        };
    }
}
