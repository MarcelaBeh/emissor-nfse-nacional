<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Application\DTO\Response;

final readonly class EventoResponse
{
    public function __construct(
        public bool $success,
        public ?string $mensagem = null,
        public ?array $dados = null,
    ) {}
}
