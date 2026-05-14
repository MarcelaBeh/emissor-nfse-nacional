<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Validators;

use InvalidArgumentException;

class MotivoSubstituicaoValidator
{
    private const CODIGOS_VALIDOS = ['01', '02', '03', '04', '05', '99'];

    /**
     * Valida e formata código de motivo de substituição (TSCodJustSubst)
     *
     * Conforme XSD: '01', '02', '03', '04', '05', '99' (sempre com zero à esquerda)
     *
     * @param string|int $codigo Código do motivo
     * @return string Código formatado com zero à esquerda
     * @throws InvalidArgumentException Se código inválido
     */
    public static function validateAndFormat(string|int $codigo): string
    {
        $codigoFormatado = str_pad((string)$codigo, 2, '0', STR_PAD_LEFT);

        if (!in_array($codigoFormatado, self::CODIGOS_VALIDOS, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Código de motivo de substituição inválido: "%s". Valores aceitos: %s',
                    $codigo,
                    implode(', ', self::CODIGOS_VALIDOS)
                )
            );
        }

        return $codigoFormatado;
    }
}
