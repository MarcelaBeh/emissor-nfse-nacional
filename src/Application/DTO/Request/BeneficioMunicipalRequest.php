<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Request;

final readonly class BeneficioMunicipalRequest
{
    public function __construct(
        public ?string $numeroBeneficio = null,
    ) {
    }
}
