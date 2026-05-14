<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Domain\ValueObject;

use emissorNfseNacional\NfseNacional\Domain\Exception\ValidationException;

final readonly class Telefone
{
    private string $numero;

    public function __construct(string $numero)
    {
        $this->numero = $this->validate($numero);
    }

    private function validate(string $numero): string
    {
        $numero = preg_replace('/[^0-9]/', '', $numero);

        if (strlen($numero) < 10 || strlen($numero) > 11) {
            throw new ValidationException(
                "Telefone deve ter 10 ou 11 dígitos. Fornecido: {$numero}"
            );
        }

        return $numero;
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function formatado(): string
    {
        if (strlen($this->numero) === 11) {
            return sprintf(
                '(%s) %s-%s',
                substr($this->numero, 0, 2),
                substr($this->numero, 2, 5),
                substr($this->numero, 7, 4)
            );
        }

        return sprintf(
            '(%s) %s-%s',
            substr($this->numero, 0, 2),
            substr($this->numero, 2, 4),
            substr($this->numero, 6, 4)
        );
    }

    public function __toString(): string
    {
        return $this->numero;
    }
}
