<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Domain\Entity;

use emissorNfseNacional\NfseNacional\Domain\ValueObject\Cnpj;
use emissorNfseNacional\NfseNacional\Domain\ValueObject\Cpf;
use emissorNfseNacional\NfseNacional\Domain\ValueObject\Email;
use emissorNfseNacional\NfseNacional\Domain\ValueObject\Telefone;

class Intermediario
{
    public function __construct(
        private Cnpj|Cpf $documento,
        private string $razaoSocial,
        private ?string $inscricaoMunicipal,
        private ?Telefone $telefone,
        private ?Email $email,
        private Endereco $endereco,
        private ?string $nif = null,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->razaoSocial)) {
            throw new \InvalidArgumentException('Razão social do intermediário é obrigatória');
        }
    }

    public function getDocumento(): Cnpj|Cpf
    {
        return $this->documento;
    }

    public function isCnpj(): bool
    {
        return $this->documento instanceof Cnpj;
    }

    public function getCnpj(): ?Cnpj
    {
        return $this->isCnpj() ? $this->documento : null;
    }

    public function getCpf(): ?Cpf
    {
        return !$this->isCnpj() ? $this->documento : null;
    }

    public function getRazaoSocial(): string
    {
        return $this->razaoSocial;
    }

    public function getInscricaoMunicipal(): ?string
    {
        return $this->inscricaoMunicipal;
    }

    public function getTelefone(): ?Telefone
    {
        return $this->telefone;
    }

    public function getEmail(): ?Email
    {
        return $this->email;
    }

    public function getEndereco(): Endereco
    {
        return $this->endereco;
    }

    public function getNif(): ?string
    {
        return $this->nif;
    }
}
