<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Domain\Enum;

enum TipoAmbiente: int
{
    case PRODUCAO = 1;
    case HOMOLOGACAO = 2;

    public function descricao(): string
    {
        return match ($this) {
            self::PRODUCAO => 'Produção',
            self::HOMOLOGACAO => 'Homologação',
        };
    }

    public function isProducao(): bool
    {
        return $this === self::PRODUCAO;
    }
}
