<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Request;

final readonly class ConsultaRequest
{
    public function __construct(
        public string $chave,
        public ?string $tipoEvento = null,
        public ?int $nSequencial = null,
    ) {
    }
}
