<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Request;

final readonly class IbsCbsFornecedorRequest
{
    public function __construct(
        public ?string $cnpj = null,
        public ?string $cpf = null,
        public ?string $nif = null,
        public ?string $codigoNaoNif = null,
        public string $xNome = '',
    ) {
    }
}
