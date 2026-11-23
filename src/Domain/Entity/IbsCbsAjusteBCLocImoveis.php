<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoAjusteBCLocImoveis;

/**
 * Parcela não integrante da base de cálculo do IBS/CBS numa unidade imobiliária
 * (`gUnidImob/gAjusteBCLocImoveis`, TCRTCValoresBCImoveis).
 */
class IbsCbsAjusteBCLocImoveis
{
    public function __construct(
        private TipoAjusteBCLocImoveis $tpAjusteBCLocImoveis,
        private string $vAjusteBCLocImoveis,
        private ?string $xTpAjusteBCLocImoveis = null,
    ) {
    }

    public function getTpAjusteBCLocImoveis(): TipoAjusteBCLocImoveis
    {
        return $this->tpAjusteBCLocImoveis;
    }

    public function getVAjusteBCLocImoveis(): string
    {
        return $this->vAjusteBCLocImoveis;
    }

    public function getXTpAjusteBCLocImoveis(): ?string
    {
        return $this->xTpAjusteBCLocImoveis;
    }
}
