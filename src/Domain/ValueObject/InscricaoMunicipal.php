<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Domain\ValueObject;

use emissorNfseNacional\NfseNacional\Domain\Exception\ValidationException;

final readonly class InscricaoMunicipal
{
    private string $inscricao;

    public function __construct(string $inscricao)
    {
        $this->inscricao = $this->validate($inscricao);
    }

    private function validate(string $inscricao): string
    {
        $inscricao = preg_replace('/[^0-9]/', '', $inscricao);

        if (empty($inscricao)) {
            throw new ValidationException("Inscrição municipal não pode ser vazia");
        }

        return $inscricao;
    }

    public function getInscricao(): string
    {
        return $this->inscricao;
    }

    public function __toString(): string
    {
        return $this->inscricao;
    }
}
