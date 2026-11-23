<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoMunicipio;

/**
 * Grupo `IBSCBS/imovel` (TCRTCInfoImovel): operações com bens imóveis, exceto obras.
 *
 * Estrutura (NT 009, item 2.6): município do imóvel, dados de locação (`gLocacao`, apenas 99.03)
 * e até 99 unidades imobiliárias (`gUnidImob`).
 */
class IbsCbsImovel
{
    public const MAX_UNIDADES = 99;

    /** @param IbsCbsUnidadeImobiliaria[] $unidades */
    public function __construct(
        private CodigoMunicipio $cMun,
        private ?IbsCbsLocacaoImovel $locacao = null,
        private array $unidades = [],
    ) {
        if (count($unidades) > self::MAX_UNIDADES) {
            throw new \InvalidArgumentException(
                'imovel aceita no máximo ' . self::MAX_UNIDADES . ' unidades imobiliárias (gUnidImob)'
            );
        }
    }

    public function getCMun(): CodigoMunicipio
    {
        return $this->cMun;
    }

    public function getLocacao(): ?IbsCbsLocacaoImovel
    {
        return $this->locacao;
    }

    /** @return IbsCbsUnidadeImobiliaria[] */
    public function getUnidades(): array
    {
        return $this->unidades;
    }
}
