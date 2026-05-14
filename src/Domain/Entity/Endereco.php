<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Domain\Entity;

use emissorNfseNacional\NfseNacional\Domain\ValueObject\Cep;
use emissorNfseNacional\NfseNacional\Domain\ValueObject\CodigoMunicipio;

class Endereco
{
    public function __construct(
        private string $logradouro,
        private string $numero,
        private ?string $complemento,
        private string $bairro,
        private CodigoMunicipio $codigoMunicipio,
        private string $uf,
        private Cep $cep,
        private ?string $codigoPais = null,
        private ?string $nomeCidadeExterior = null,
        private ?string $estadoProvinciaExterior = null,
        private ?string $codigoPostalExterior = null,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->logradouro)) {
            throw new \InvalidArgumentException('Logradouro é obrigatório');
        }

        if (empty($this->bairro)) {
            throw new \InvalidArgumentException('Bairro é obrigatório');
        }
    }

    public function getLogradouro(): string
    {
        return $this->logradouro;
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function getComplemento(): ?string
    {
        return $this->complemento;
    }

    public function getBairro(): string
    {
        return $this->bairro;
    }

    public function getCodigoMunicipio(): CodigoMunicipio
    {
        return $this->codigoMunicipio;
    }

    public function getUf(): string
    {
        return $this->uf;
    }

    public function getCep(): Cep
    {
        return $this->cep;
    }

    public function getCodigoPais(): ?string
    {
        return $this->codigoPais;
    }

    public function getNomeCidadeExterior(): ?string
    {
        return $this->nomeCidadeExterior;
    }

    public function getEstadoProvinciaExterior(): ?string
    {
        return $this->estadoProvinciaExterior;
    }

    public function getCodigoPostalExterior(): ?string
    {
        return $this->codigoPostalExterior;
    }

    public function isExterior(): bool
    {
        return $this->codigoPais !== null;
    }
}
