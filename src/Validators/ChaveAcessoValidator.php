<?php

declare(strict_types=1);

namespace Hadder\NfseNacional\Validators;

use InvalidArgumentException;

class ChaveAcessoValidator
{
    /**
     * Valida chave de acesso da NFSe Nacional (50 dígitos)
     *
     * @param string $chave Chave de acesso
     * @throws InvalidArgumentException Se chave inválida
     */
    public static function validate(string $chave): void
    {
        if (!preg_match('/^[0-9]{50}$/', $chave)) {
            throw new InvalidArgumentException(
                'Chave de acesso inválida: deve conter exatamente 50 dígitos numéricos'
            );
        }
    }
}
