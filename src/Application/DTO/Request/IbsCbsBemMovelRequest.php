<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Request;

final readonly class IbsCbsBemMovelRequest
{
    public function __construct(
        public string $cNCMBemMovel,
        public string $xNCMBemMovel,
        public string $qtdNCMBemMovel,
    ) {
    }
}
