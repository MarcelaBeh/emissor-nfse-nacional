<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Request;

final readonly class InfoComplRequest
{
    public function __construct(
        public ?string $idDocTecnico = null,
        public ?string $docReferencia = null,
        public ?string $numeroPedido = null,
        public ?array $itensPedido = null,
        public ?string $infoComplementar = null,
    ) {
    }
}
