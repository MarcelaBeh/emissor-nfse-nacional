<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Enum;

/**
 * Tipo de parcela não integrante da base de cálculo do IBS/CBS nas operações de
 * locação, cessão onerosa ou arrendamento de bens imóveis (TSAjusteBCLocImoveis),
 * em `imovel/gUnidImob/gAjusteBCLocImoveis/tpAjusteBCLocImoveis` (NT 009, item 2.6).
 */
enum TipoAjusteBCLocImoveis: string
{
    case TRIBUTOS_INCLUSOS = '01';
    case EMOLUMENTO_INCLUSO = '02';
    case CONDOMINIO_INCLUSO = '03';
    case REDUTOR_SOCIAL = '04';
    case OUTRAS_PARCELAS = '99';

    public function descricao(): string
    {
        return match ($this) {
            self::TRIBUTOS_INCLUSOS => 'Tributos inclusos no aluguel ou equivalente',
            self::EMOLUMENTO_INCLUSO => 'Emolumento incluso no aluguel ou equivalente',
            self::CONDOMINIO_INCLUSO => 'Condomínio incluso no aluguel ou equivalente',
            self::REDUTOR_SOCIAL => 'Redutor Social',
            self::OUTRAS_PARCELAS => 'Outras parcelas inclusas no aluguel ou equivalente',
        };
    }

    /** A descrição `xTpAjusteBCLocImoveis` é obrigatória apenas para "99" (RN E0949). */
    public function exigeDescricao(): bool
    {
        return $this === self::OUTRAS_PARCELAS;
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
