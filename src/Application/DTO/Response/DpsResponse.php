<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Application\DTO\Response;

final readonly class DpsResponse
{
    public function __construct(
        public bool $success,
        public ?string $chaveAcesso = null,
        public ?array $dados = null,
        public ?string $mensagem = null,
    ) {}
}
