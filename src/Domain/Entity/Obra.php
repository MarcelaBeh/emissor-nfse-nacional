<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoCIB;

class Obra
{
    public function __construct(
        private ?string $inscImobFisc = null,
        private ?string $cObra = null,
        private ?CodigoCIB $cCIB = null,
        private ?IbsCbsEnderecoObra $endereco = null,
    ) {
    }

    public function getInscImobFisc(): ?string
    {
        return $this->inscImobFisc;
    }

    public function getCObra(): ?string
    {
        return $this->cObra;
    }

    public function getCCIB(): ?CodigoCIB
    {
        return $this->cCIB;
    }

    public function getEndereco(): ?IbsCbsEnderecoObra
    {
        return $this->endereco;
    }
}
