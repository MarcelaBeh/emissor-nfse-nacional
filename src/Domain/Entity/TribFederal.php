<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Entity;

class TribFederal
{
    public function __construct(
        private ?string $pisCofinsCst = null,
        private ?string $pisCofinsTipo = null,
        private ?float $pisCofinsAliquotaPis = null,
        private ?float $pisCofinsAliquotaCofins = null,
        private ?string $valorRetidoCP = null,
        private ?string $valorRetidoIRRF = null,
        private ?string $valorRetidoCSLL = null,
    ) {
    }

    public function getPisCofinsCst(): ?string
    {
        return $this->pisCofinsCst;
    }

    public function getPisCofinsTipo(): ?string
    {
        return $this->pisCofinsTipo;
    }

    public function getPisCofinsAliquotaPis(): ?float
    {
        return $this->pisCofinsAliquotaPis;
    }

    public function getPisCofinsAliquotaCofins(): ?float
    {
        return $this->pisCofinsAliquotaCofins;
    }

    public function getValorRetidoCP(): ?string
    {
        return $this->valorRetidoCP;
    }

    public function getValorRetidoIRRF(): ?string
    {
        return $this->valorRetidoIRRF;
    }

    public function getValorRetidoCSLL(): ?string
    {
        return $this->valorRetidoCSLL;
    }
}
