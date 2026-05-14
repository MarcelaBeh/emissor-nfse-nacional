<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Domain\Entity;

use emissorNfseNacional\NfseNacional\Domain\Enum\TipoEvento;
use emissorNfseNacional\NfseNacional\Domain\ValueObject\ChaveAcesso;
use emissorNfseNacional\NfseNacional\Domain\Contract\EventoInterface;

class Evento implements EventoInterface
{
    public function __construct(
        private TipoEvento $tipo,
        private ChaveAcesso $chaveNfse,
        private \DateTimeImmutable $dataEvento,
        private string $versaoAplicacao,
        private ?string $cnpjAutor = null,
        private ?string $cpfAutor = null,
        private ?string $codigoMotivo = null,
        private ?string $descricaoMotivo = null,
        private ?\DateTimeImmutable $dataSubstituicao = null,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->versaoAplicacao)) {
            throw new \InvalidArgumentException('Versão da aplicação é obrigatória');
        }
    }

    public function getTipo(): TipoEvento
    {
        return $this->tipo;
    }

    public function getChaveNfse(): string
    {
        return $this->chaveNfse->getChave();
    }

    public function getChaveAcesso(): ChaveAcesso
    {
        return $this->chaveNfse;
    }

    public function getDataEvento(): \DateTimeImmutable
    {
        return $this->dataEvento;
    }

    public function getVersaoAplicacao(): string
    {
        return $this->versaoAplicacao;
    }

    public function getCnpjAutor(): ?string
    {
        return $this->cnpjAutor;
    }

    public function getCpfAutor(): ?string
    {
        return $this->cpfAutor;
    }

    public function getCodigoMotivo(): ?string
    {
        return $this->codigoMotivo;
    }

    public function getDescricaoMotivo(): ?string
    {
        return $this->descricaoMotivo;
    }

    public function getDataSubstituicao(): ?\DateTimeImmutable
    {
        return $this->dataSubstituicao;
    }
}
