<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Request;

final readonly class IbsCbsEnderecoImovelRequest
{
    public function __construct(
        public string $cep,
        public string $xLgr,
        public string $nro,
        public ?string $xCpl = null,
        public ?string $xBairro = null,
    ) {
    }
}
