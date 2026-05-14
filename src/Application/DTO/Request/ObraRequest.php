<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Request;

final readonly class ObraRequest
{
    public function __construct(
        public ?string $inscImobFisc = null,
        public ?string $cObra = null,
        public ?string $cCIB = null,
        public ?IbsCbsEnderecoObraRequest $endereco = null,
    ) {
    }
}
