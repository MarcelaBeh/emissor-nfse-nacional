<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Response;

final readonly class EventoResponse
{
    /**
     * @param array<string, mixed>|null $dados
     */
    public function __construct(
        public bool $success,
        public ?string $mensagem = null,
        public ?array $dados = null,
    ) {
    }
}
