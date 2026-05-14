<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Entity;

class AtvEvento
{
    public function __construct(
        private string $descricao,
        private \DateTimeImmutable $dataInicio,
        private \DateTimeImmutable $dataFim,
        private ?string $identificacaoEvento = null,
        private ?Endereco $endereco = null,
    ) {
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }

    public function getDataInicio(): \DateTimeImmutable
    {
        return $this->dataInicio;
    }

    public function getDataFim(): \DateTimeImmutable
    {
        return $this->dataFim;
    }

    public function getIdentificacaoEvento(): ?string
    {
        return $this->identificacaoEvento;
    }

    public function getEndereco(): ?Endereco
    {
        return $this->endereco;
    }
}
