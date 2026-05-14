<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Entity;

class BeneficioMunicipal
{
    public function __construct(
        private ?string $numeroBeneficio = null,
    ) {
    }

    public function getNumeroBeneficio(): ?string
    {
        return $this->numeroBeneficio;
    }
}
