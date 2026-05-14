<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Entity;

class ExigSusp
{
    public function __construct(
        private ?int $tipoSuspensao = null,
        private ?string $numeroProcesso = null,
    ) {
    }

    public function getTipoSuspensao(): ?int
    {
        return $this->tipoSuspensao;
    }

    public function getNumeroProcesso(): ?string
    {
        return $this->numeroProcesso;
    }
}
