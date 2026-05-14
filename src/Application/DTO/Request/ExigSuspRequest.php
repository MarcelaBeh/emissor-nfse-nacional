<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Request;

final readonly class ExigSuspRequest
{
    public function __construct(
        public ?int $tipoSuspensao = null,
        public ?string $numeroProcesso = null,
    ) {
    }
}
