<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\ValueObject;

use MarcelaBeh\EmissorNfseNacional\Domain\Exception\DomainException;

final readonly class CodigoCIB
{
    private string $codigo;

    public function __construct(string $codigo)
    {
        $codigo = trim($codigo);

        if (strlen($codigo) !== 8) {
            throw new DomainException('CIB deve ter exatamente 8 caracteres');
        }

        $this->codigo = $codigo;
    }

    public function getCodigo(): string
    {
        return $this->codigo;
    }
}
