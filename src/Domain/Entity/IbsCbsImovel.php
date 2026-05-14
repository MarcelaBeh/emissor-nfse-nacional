<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoCIB;

class IbsCbsImovel
{
    public function __construct(
        private ?string $inscImobFisc = null,
        private ?CodigoCIB $cCIB = null,
        private ?IbsCbsEnderecoObra $endereco = null,
    ) {
    }

    public function getInscImobFisc(): ?string
    {
        return $this->inscImobFisc;
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
