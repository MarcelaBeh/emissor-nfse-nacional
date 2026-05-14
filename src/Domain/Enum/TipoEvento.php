<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Domain\Enum;

enum TipoEvento: string
{
    case CANCELAMENTO = '101101';
    case SUBSTITUICAO = '105102';

    public function descricao(): string
    {
        return match ($this) {
            self::CANCELAMENTO => 'Cancelamento',
            self::SUBSTITUICAO => 'Substituição',
        };
    }
}
