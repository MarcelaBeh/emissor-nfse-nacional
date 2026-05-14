<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cnpj;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cpf;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Nif;

class IbsCbsDest
{
    public function __construct(
        private ?Cnpj $cnpj = null,
        private ?Cpf $cpf = null,
        private ?Nif $nif = null,
        private ?string $codigoNaoNif = null,
        private string $xNome = '',
        private ?Endereco $endereco = null,
        private ?string $fone = null,
        private ?string $email = null,
    ) {
    }

    public function getCnpj(): ?Cnpj
    {
        return $this->cnpj;
    }

    public function getCpf(): ?Cpf
    {
        return $this->cpf;
    }

    public function getNif(): ?Nif
    {
        return $this->nif;
    }

    public function getCodigoNaoNif(): ?string
    {
        return $this->codigoNaoNif;
    }

    public function getXNome(): string
    {
        return $this->xNome;
    }

    public function getEndereco(): ?Endereco
    {
        return $this->endereco;
    }

    public function getFone(): ?string
    {
        return $this->fone;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }
}
