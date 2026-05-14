<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Response;

final readonly class NfseResponse
{
    public function __construct(
        public bool $success,
        public ?string $chaveAcesso = null,
        public ?string $numero = null,
        public ?string $codigoVerificacao = null,
        public ?string $mensagem = null,
        public ?array $dados = null,
        public ?string $xml = null,
    ) {
    }
}
