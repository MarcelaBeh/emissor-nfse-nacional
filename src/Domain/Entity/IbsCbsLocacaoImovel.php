<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Entity;

/**
 * Grupo `imovel/gLocacao` (TCRTCLocacaoImovel): dados exclusivos das operações de locação, cessão
 * onerosa ou arrendamento de bens imóveis (subitem 99.03). Base de `vServ`, `vDescIncond` e
 * `vDescCond`: vServ = pCopropriedade x vTotOper (NT 009, item 2.6).
 */
class IbsCbsLocacaoImovel
{
    public function __construct(
        private string $pCopropriedade,
        private string $vTotOper,
        private ?string $vDescIncondTot = null,
        private ?string $vDescCondTot = null,
        private ?\DateTimeInterface $dVencOrig = null,
    ) {
    }

    public function getPCopropriedade(): string
    {
        return $this->pCopropriedade;
    }

    public function getVTotOper(): string
    {
        return $this->vTotOper;
    }

    public function getVDescIncondTot(): ?string
    {
        return $this->vDescIncondTot;
    }

    public function getVDescCondTot(): ?string
    {
        return $this->vDescCondTot;
    }

    public function getDVencOrig(): ?\DateTimeInterface
    {
        return $this->dVencOrig;
    }
}
