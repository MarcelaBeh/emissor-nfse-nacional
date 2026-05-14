<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Request;

final readonly class TomadorRequest
{
    public function __construct(
        public ?string $documento,
        public bool $isCnpj,
        public string $razaoSocial,
        public ?string $nomeFantasia,
        public ?string $telefone,
        public ?string $email,
        public string $logradouro,
        public string $numero,
        public ?string $complemento,
        public string $bairro,
        public string $codigoMunicipio,
        public string $uf,
        public string $cep,
        public ?string $nif = null,
        public ?string $inscricaoMunicipal = null,
    ) {
    }
}
