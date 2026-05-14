<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Request;

final readonly class IbsCbsTribRegularRequest
{
    public function __construct(
        public string $cstReg,
        public string $cClassTribReg,
    ) {
    }
}
