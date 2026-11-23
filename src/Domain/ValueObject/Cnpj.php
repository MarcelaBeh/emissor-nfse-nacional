<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\ValueObject;

use MarcelaBeh\EmissorNfseNacional\Domain\Exception\InvalidCnpjException;

/**
 * CNPJ numérico ou alfanumérico (NT 009, item 2.1; vigente desde julho de 2026).
 *
 * São 14 posições: 12 de raiz e ordem, com dígitos 0-9 e letras A-Z, mais 2 dígitos
 * verificadores sempre numéricos. O DV é módulo 11 sobre o valor de cada caractere
 * (código ASCII menos 48), o que reproduz exatamente o cálculo do CNPJ numérico.
 */
final readonly class Cnpj
{
    /** Formato de um CNPJ normalizado (maiúsculas, sem máscara). */
    public const PADRAO = '/^[0-9A-Z]{12}[0-9]{2}$/';

    private string $numero;

    public function __construct(string $cnpj)
    {
        $this->numero = $this->validate($cnpj);
    }

    /** Indica se a string, ignorada a máscara, está no formato e tem DV válido. */
    public static function isValido(string $cnpj): bool
    {
        try {
            new self($cnpj);

            return true;
        } catch (InvalidCnpjException) {
            return false;
        }
    }

    private function validate(string $cnpj): string
    {
        // Remove apenas a máscara (pontos, barra, hífen e espaços); letras são significativas.
        $cnpj = strtoupper(preg_replace('/[.\/\-\s]/', '', $cnpj) ?? '');

        if (strlen($cnpj) !== 14) {
            throw new InvalidCnpjException('CNPJ deve ter 14 caracteres. Fornecido: ' . strlen($cnpj));
        }
        if (preg_match(self::PADRAO, $cnpj) !== 1) {
            throw new InvalidCnpjException(
                'CNPJ inválido: raiz e ordem aceitam dígitos e letras A-Z; os 2 dígitos verificadores são numéricos'
            );
        }
        if (preg_match('/^(.)\1+$/', $cnpj) === 1) {
            throw new InvalidCnpjException('CNPJ inválido: sequência repetida');
        }
        if (!$this->validarDigitoVerificador($cnpj)) {
            throw new InvalidCnpjException('CNPJ com dígito verificador inválido');
        }

        return $cnpj;
    }

    private function validarDigitoVerificador(string $cnpj): bool
    {
        return (int) $cnpj[12] === $this->calcularDigito(substr($cnpj, 0, 12))
            && (int) $cnpj[13] === $this->calcularDigito(substr($cnpj, 0, 13));
    }

    /** Dígito verificador (módulo 11, pesos 2 a 9 da direita para a esquerda) da base informada. */
    private function calcularDigito(string $base): int
    {
        $soma = 0;
        $peso = 2;
        for ($i = strlen($base) - 1; $i >= 0; $i--) {
            $soma += (ord($base[$i]) - 48) * $peso;
            $peso = $peso === 9 ? 2 : $peso + 1;
        }

        $resto = $soma % 11;

        return $resto < 2 ? 0 : 11 - $resto;
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function isAlfanumerico(): bool
    {
        return preg_match('/[A-Z]/', $this->numero) === 1;
    }

    public function formatado(): string
    {
        return sprintf(
            '%s.%s.%s/%s-%s',
            substr($this->numero, 0, 2),
            substr($this->numero, 2, 3),
            substr($this->numero, 5, 3),
            substr($this->numero, 8, 4),
            substr($this->numero, 12, 2)
        );
    }

    public function equals(self $other): bool
    {
        return $this->numero === $other->numero;
    }

    public function __toString(): string
    {
        return $this->numero;
    }
}
