<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Validators;

use InvalidArgumentException;

class CnpjValidator
{
    /**
     * Valida um CNPJ com verificação de dígitos verificadores
     *
     * @param string $cnpj CNPJ com ou sem formatação
     * @throws InvalidArgumentException Se CNPJ inválido
     */
    public static function validate(string $cnpj): void
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        if (strlen($cnpj) !== 14) {
            throw new InvalidArgumentException('CNPJ deve conter 14 dígitos');
        }

        // Rejeita CNPJs com todos os dígitos iguais
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            throw new InvalidArgumentException('CNPJ inválido: todos os dígitos são iguais');
        }

        // Valida primeiro dígito verificador
        $multiplicadores1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma = 0;
        for ($i = 0; $i < 12; $i++) {
            $soma += ((int)$cnpj[$i]) * $multiplicadores1[$i];
        }
        $resto = $soma % 11;
        $digito1 = $resto < 2 ? 0 : 11 - $resto;

        if ((int)$cnpj[12] !== $digito1) {
            throw new InvalidArgumentException('CNPJ inválido: primeiro dígito verificador incorreto');
        }

        // Valida segundo dígito verificador
        $multiplicadores2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma = 0;
        for ($i = 0; $i < 13; $i++) {
            $soma += ((int)$cnpj[$i]) * $multiplicadores2[$i];
        }
        $resto = $soma % 11;
        $digito2 = $resto < 2 ? 0 : 11 - $resto;

        if ((int)$cnpj[13] !== $digito2) {
            throw new InvalidArgumentException('CNPJ inválido: segundo dígito verificador incorreto');
        }
    }

    /**
     * Remove formatação e retorna apenas dígitos
     */
    public static function clean(string $cnpj): string
    {
        return preg_replace('/[^0-9]/', '', $cnpj);
    }
}
