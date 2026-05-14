<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Request;

final readonly class IbsCbsDiferimentoRequest
{
    public function __construct(
        public float $pDifUF,
        public float $pDifMun,
        public float $pDifCBS,
    ) {
    }
}
