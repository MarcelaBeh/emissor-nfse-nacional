<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Validators;

use InvalidArgumentException;

class CodigoIbgeValidator
{
    /**
     * Valida código IBGE de município (7 dígitos)
     *
     * @param string $codigoIbge Código IBGE
     * @throws InvalidArgumentException Se código inválido
     */
    public static function validate(string $codigoIbge): void
    {
        if (!preg_match('/^[0-9]{7}$/', $codigoIbge)) {
            throw new InvalidArgumentException(
                'Código IBGE inválido: deve conter exatamente 7 dígitos numéricos'
            );
        }
    }
}
