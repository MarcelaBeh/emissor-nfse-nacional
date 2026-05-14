<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Enum;

enum IndicadorFinal: string
{
    case NAO = '0';
    case SIM = '1';

    public function descricao(): string
    {
        return match ($this) {
            self::NAO => 'Não',
            self::SIM => 'Sim (uso ou consumo pessoal)',
        };
    }
}
