<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Enum;

enum TipoEmissao: int
{
    case PRESTADOR = 1;
    case TOMADOR = 2;
    case INTERMEDIARIO = 3;

    public function descricao(): string
    {
        return match ($this) {
            self::PRESTADOR => 'Emitente: Prestador',
            self::TOMADOR => 'Emitente: Tomador',
            self::INTERMEDIARIO => 'Emitente: Intermediário',
        };
    }
}
