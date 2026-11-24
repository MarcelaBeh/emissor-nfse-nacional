<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\ValueObject;

use MarcelaBeh\EmissorNfseNacional\Domain\Exception\InvalidChaveAcessoException;

final readonly class ChaveAcesso
{
    private string $chave;

    public function __construct(string $chave)
    {
        $this->chave = $this->validate($chave);
    }

    /**
     * Extrai a chave do atributo `Id` de uma NFS-e (TSIdNFSe: "NFS" + chave de 50 posições). Devolve null se o valor
     * não tiver esse formato, em vez de produzir uma chave inválida.
     */
    public static function doIdNfse(mixed $id): ?string
    {
        if (!is_string($id) || !str_starts_with($id, 'NFS')) {
            return null;
        }

        $chave = substr($id, 3);

        return preg_match('/^[0-9]{50}$/', $chave) === 1 ? $chave : null;
    }

    private function validate(string $chave): string
    {
        $chave = preg_replace('/[^0-9]/', '', $chave) ?? '';

        if (strlen($chave) !== 50) {
            throw new InvalidChaveAcessoException(
                'Chave de acesso deve ter 50 dígitos. Fornecido: ' . strlen($chave)
            );
        }

        return $chave;
    }

    public function getChave(): string
    {
        return $this->chave;
    }

    public function formatada(): string
    {
        return implode(' ', str_split($this->chave, 4));
    }

    public function __toString(): string
    {
        return $this->chave;
    }
}
