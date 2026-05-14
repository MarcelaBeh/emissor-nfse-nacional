<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Validators;

use InvalidArgumentException;

class CpfValidator
{
    /**
     * Valida um CPF com verificação de dígitos verificadores
     *
     * @param string $cpf CPF com ou sem formatação
     * @throws InvalidArgumentException Se CPF inválido
     */
    public static function validate(string $cpf): void
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) !== 11) {
            throw new InvalidArgumentException('CPF deve conter 11 dígitos');
        }

        // Rejeita CPFs com todos os dígitos iguais
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            throw new InvalidArgumentException('CPF inválido: todos os dígitos são iguais');
        }

        // Valida primeiro dígito verificador
        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += ((int)$cpf[$i]) * (10 - $i);
        }
        $resto = $soma % 11;
        $digito1 = $resto < 2 ? 0 : 11 - $resto;

        if ((int)$cpf[9] !== $digito1) {
            throw new InvalidArgumentException('CPF inválido: primeiro dígito verificador incorreto');
        }

        // Valida segundo dígito verificador
        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += ((int)$cpf[$i]) * (11 - $i);
        }
        $resto = $soma % 11;
        $digito2 = $resto < 2 ? 0 : 11 - $resto;

        if ((int)$cpf[10] !== $digito2) {
            throw new InvalidArgumentException('CPF inválido: segundo dígito verificador incorreto');
        }
    }

    /**
     * Remove formatação e retorna apenas dígitos
     */
    public static function clean(string $cpf): string
    {
        return preg_replace('/[^0-9]/', '', $cpf);
    }
}
