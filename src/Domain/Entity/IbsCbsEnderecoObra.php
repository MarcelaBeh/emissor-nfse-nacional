<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Entity;

class IbsCbsEnderecoObra
{
    public function __construct(
        private ?string $cep = null,
        private ?IbsCbsEnderecoExterior $endExt = null,
        private string $xLgr = '',
        private string $nro = '',
        private ?string $xCpl = null,
        private string $xBairro = '',
    ) {
    }

    public function getCep(): ?string
    {
        return $this->cep;
    }

    public function getEndExt(): ?IbsCbsEnderecoExterior
    {
        return $this->endExt;
    }

    public function getXLgr(): string
    {
        return $this->xLgr;
    }

    public function getNro(): string
    {
        return $this->nro;
    }

    public function getXCpl(): ?string
    {
        return $this->xCpl;
    }

    public function getXBairro(): string
    {
        return $this->xBairro;
    }
}
