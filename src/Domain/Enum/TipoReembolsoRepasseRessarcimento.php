<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Enum;

enum TipoReembolsoRepasseRessarcimento: string
{
    case REPASSE_IMOVEIS_CORRETORES = '01';
    case REPASSE_FORNECEDOR_TURISMO = '02';
    case REEMBOLSO_PUBLICIDADE_PROD_EXTERNA = '03';
    case REEMBOLSO_PUBLICIDADE_MIDIA = '04';
    case OUTROS = '99';

    public static function fromValue(string $value): self
    {
        return match ($value) {
            '01' => self::REPASSE_IMOVEIS_CORRETORES,
            '02' => self::REPASSE_FORNECEDOR_TURISMO,
            '03' => self::REEMBOLSO_PUBLICIDADE_PROD_EXTERNA,
            '04' => self::REEMBOLSO_PUBLICIDADE_MIDIA,
            '99' => self::OUTROS,
            default => throw new \InvalidArgumentException("Invalid TipoReembolsoRepasseRessarcimento: {$value}"),
        };
    }
}
