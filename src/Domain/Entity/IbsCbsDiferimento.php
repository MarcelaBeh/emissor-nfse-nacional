<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Entity;

class IbsCbsDiferimento
{
    public function __construct(
        private float $pDifUF,
        private float $pDifMun,
        private float $pDifCBS,
    ) {
    }

    public function getPDifUF(): float
    {
        return $this->pDifUF;
    }

    public function getPDifMun(): float
    {
        return $this->pDifMun;
    }

    public function getPDifCBS(): float
    {
        return $this->pDifCBS;
    }
}
