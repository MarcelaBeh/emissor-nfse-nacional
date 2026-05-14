<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Enum;

enum TipoChaveDocumentoFiscal: string
{
    case NFS_E = '1';
    case NF_E = '2';
    case CT_E = '3';
    case OUTRO = '9';

    public static function fromValue(string $value): self
    {
        return match ($value) {
            '1' => self::NFS_E,
            '2' => self::NF_E,
            '3' => self::CT_E,
            '9' => self::OUTRO,
            default => throw new \InvalidArgumentException("Invalid TipoChaveDocumentoFiscal: {$value}"),
        };
    }
}
