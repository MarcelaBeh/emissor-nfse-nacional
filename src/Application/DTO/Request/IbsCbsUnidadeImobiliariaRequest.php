<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Request;

final readonly class IbsCbsUnidadeImobiliariaRequest
{
    /** @param IbsCbsAjusteBCLocImoveisRequest[] $ajustes */
    public function __construct(
        public ?string $inscImobFisc = null,
        public ?string $cCIB = null,
        public ?IbsCbsEnderecoImovelRequest $endereco = null,
        public array $ajustes = [],
    ) {
    }
}
