<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Enum;

enum FinalidadeNfse: string
{
    case REGULAR = '0';

    public function descricao(): string
    {
        return match ($this) {
            self::REGULAR => 'NFS-e regular',
        };
    }
}
