<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Request;

final readonly class IbsCbsAjusteBCLocImoveisRequest
{
    public function __construct(
        public string $tpAjusteBCLocImoveis,
        public string $vAjusteBCLocImoveis,
        public ?string $xTpAjusteBCLocImoveis = null,
    ) {
    }
}
