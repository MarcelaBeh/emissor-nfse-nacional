<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Security;

final class SensitiveDataSanitizer
{
    private const SENSITIVE_KEYS = [
        'senha', 'password', 'privatekey', 'pfx', 'certificado',
        'certificate', 'token', 'authorization', 'secret', 'apikey',
    ];

    /** @var array<string, string> */
    private const PATTERNS = [
        // CPF sem máscara: 11 dígitos isolados
        '/(?<!\d)\d{11}(?!\d)/' => '***********',
        // CNPJ numérico sem máscara: 14 dígitos isolados
        '/(?<!\d)\d{14}(?!\d)/' => '**************',
        // CNPJ alfanumérico sem máscara (a partir de julho de 2026): 12 dígitos/letras A-Z + 2 dígitos, com ao menos uma letra
        '/(?<![0-9A-Za-z])(?=[0-9A-Z]*[A-Z])[0-9A-Z]{12}\d{2}(?![0-9A-Za-z])/' => '**************',
        // CPF com máscara: 000.000.000-00
        '/\b\d{3}\.\d{3}\.\d{3}-\d{2}\b/' => '***.***.***-**',
        // CNPJ com máscara, numérico ou alfanumérico: 00.000.000/0000-00
        '/\b[0-9A-Z]{2}\.[0-9A-Z]{3}\.[0-9A-Z]{3}\/[0-9A-Z]{4}-\d{2}\b/' => '**.***.***/****-**',
        // Chave de acesso NFS-e (50 posições; a inscrição federal, nas posições 10 a 23, pode ter letras)
        '/(?<![0-9A-Za-z])\d{9}[0-9A-Z]{14}\d{27}(?![0-9A-Za-z])/' => '**************************************************',
        // E-mail
        '/\b[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}\b/' => '***@***',
    ];

    public function sanitize(mixed $data): mixed
    {
        if (is_string($data)) {
            return $this->sanitizeString($data);
        }

        if (is_array($data)) {
            return $this->sanitizeArray($data);
        }

        return $data;
    }

    private function sanitizeString(string $value): string
    {
        foreach (self::PATTERNS as $pattern => $replacement) {
            $value = (string) preg_replace($pattern, $replacement, $value);
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    private function sanitizeArray(array $data): array
    {
        /** @var array<array-key, mixed> $result */
        $result = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
                $result[$key] = '[REDACTED]';
            } else {
                $result[$key] = $this->sanitize($value);
            }
        }

        return $result;
    }
}
