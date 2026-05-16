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
            self::REGIME_NORMAL => 'Não Optante',
            self::MEI => 'Optante - Microempreendedor Individual (MEI)',
            self::SIMPLES_NACIONAL => 'Optante - Microempresa ou Empresa de Pequeno Porte (ME/EPP)',
        };
    }
}
