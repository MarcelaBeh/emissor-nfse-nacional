<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Domain\Entity;

use emissorNfseNacional\NfseNacional\Domain\Contract\NfseInterface;

class Nfse implements NfseInterface
{
    public function __construct(
        private string $chaveAcesso,
        private string $numero,
        private string $codigoVerificacao,
        private string $serie,
        private string $dataEmissao,
        private string $prestadorCnpj,
        private string $prestadorNome,
        private string $tomadorNome,
        private string $valorServicos,
        private string $valorIss,
        private ?string $xml = null,
    ) {}

    public function getChaveAcesso(): string
    {
        return $this->chaveAcesso;
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function getCodigoVerificacao(): string
    {
        return $this->codigoVerificacao;
    }

    public function getSerie(): string
    {
        return $this->serie;
    }

    public function getDataEmissao(): string
    {
        return $this->dataEmissao;
    }

    public function getPrestadorCnpj(): string
    {
        return $this->prestadorCnpj;
    }

    public function getPrestadorNome(): string
    {
        return $this->prestadorNome;
    }

    public function getTomadorNome(): string
    {
        return $this->tomadorNome;
    }

    public function getValorServicos(): string
    {
        return $this->valorServicos;
    }

    public function getValorIss(): string
    {
        return $this->valorIss;
    }

    public function getXml(): ?string
    {
        return $this->xml;
    }
}
