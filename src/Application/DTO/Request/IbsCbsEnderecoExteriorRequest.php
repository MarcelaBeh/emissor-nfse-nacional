<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Request;

final readonly class IbsCbsEnderecoExteriorRequest
{
    public function __construct(
        public string $cEndPost,
        public string $xCidade,
        public string $xEstProvReg,
    ) {
    }
}
