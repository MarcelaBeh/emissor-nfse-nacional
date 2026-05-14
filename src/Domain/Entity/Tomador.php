<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Domain\Entity;

use emissorNfseNacional\NfseNacional\Domain\ValueObject\Cnpj;
use emissorNfseNacional\NfseNacional\Domain\ValueObject\Cpf;
use emissorNfseNacional\NfseNacional\Domain\ValueObject\Email;
use emissorNfseNacional\NfseNacional\Domain\ValueObject\Telefone;

class Tomador
{
    public function __construct(
        private Cnpj|Cpf|null $documento,
        private string $razaoSocial,
        private ?string $nomeFantasia,
        private ?Telefone $telefone,
        private ?Email $email,
        private Endereco $endereco,
        private ?string $nif = null,
        private ?string $inscricaoMunicipal = null,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->razaoSocial)) {
            throw new \InvalidArgumentException('Razão social do tomador é obrigatória');
        }

        if (strlen($this->razaoSocial) > 150) {
            throw new \InvalidArgumentException('Razão social do tomador deve ter no máximo 150 caracteres');
        }
    }

    public function getDocumento(): Cnpj|Cpf|null
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

    public function getNomeFantasia(): ?string
    {
        return $this->nomeFantasia;
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

    public function getInscricaoMunicipal(): ?string
    {
        return $this->inscricaoMunicipal;
    }
}
