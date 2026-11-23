<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\ValueObject;

use MarcelaBeh\EmissorNfseNacional\Domain\Exception\InvalidChaveAcessoException;

/**
 * Chave de acesso da NFS-e (50 posições, sem o prefixo "NFS"), conforme o TSIdNFSe:
 * município (7) + ambiente gerador (1) + tipo de inscrição (1) + inscrição federal (14,
 * alfanumérica a partir da NT 009) + número, ano/mês, código numérico e DV (27).
 */
final readonly class ChaveAcesso
{
    /** Formato de uma chave normalizada: 9 dígitos, inscrição alfanumérica (14) e 27 dígitos. */
    public const PADRAO = '/^[0-9]{9}[0-9A-Z]{14}[0-9]{27}$/';

    private string $chave;

    public function __construct(string $chave)
    {
        $this->chave = $this->validate($chave);
    }

    /** Indica se a string, ignorados espaços, está no formato de uma chave de acesso. */
    public static function isValida(string $chave): bool
    {
        return preg_match(self::PADRAO, strtoupper(preg_replace('/\s+/', '', $chave) ?? '')) === 1;
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

        return preg_match(self::PADRAO, $chave) === 1 ? $chave : null;
    }

    private function validate(string $chave): string
    {
        // Aceita a forma impressa em blocos (formatada()); remove só os espaços.
        $chave = strtoupper(preg_replace('/\s+/', '', $chave) ?? '');

        if (strlen($chave) !== 50) {
            throw new InvalidChaveAcessoException(
                'Chave de acesso deve ter 50 posições. Fornecido: ' . strlen($chave)
            );
        }
        if (preg_match(self::PADRAO, $chave) !== 1) {
            throw new InvalidChaveAcessoException(
                'Chave de acesso inválida: apenas as posições 10 a 23 (inscrição federal) aceitam letras A-Z; as demais são dígitos'
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
