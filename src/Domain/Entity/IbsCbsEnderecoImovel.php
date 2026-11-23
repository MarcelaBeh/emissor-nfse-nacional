<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Entity;

/**
 * Endereço do imóvel (TCEnderImovel): sempre nacional. CEP, logradouro e número são obrigatórios;
 * complemento e bairro são opcionais. Diferente de IbsCbsEnderecoObra, não admite endereço no exterior.
 */
class IbsCbsEnderecoImovel
{
    public function __construct(
        private string $cep,
        private string $xLgr,
        private string $nro,
        private ?string $xCpl = null,
        private ?string $xBairro = null,
    ) {
    }

    public function getCep(): string
    {
        return $this->cep;
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

    public function getXBairro(): ?string
    {
        return $this->xBairro;
    }
}
