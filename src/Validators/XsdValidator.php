<?php

declare(strict_types=1);

namespace Hadder\NfseNacional\Validators;

use DOMDocument;
use InvalidArgumentException;
use LibXMLError;
use RuntimeException;

class XsdValidator
{
    private const SCHEMAS = [
        'DPS' => 'DPS_v1.01.xsd',
        'NFSe' => 'NFSe_v1.01.xsd',
        'pedRegEvento' => 'pedRegEvento_v1.01.xsd',
    ];

    /**
     * Valida XML contra schema XSD
     *
     * @param string $xml Conteúdo XML
     * @param string $tipo Tipo do documento (DPS, NFSe, pedRegEvento)
     * @throws InvalidArgumentException Se XML inválido
     * @throws RuntimeException Se schema não encontrado
     */
    public static function validate(string $xml, string $tipo): void
    {
        if (!isset(self::SCHEMAS[$tipo])) {
            throw new InvalidArgumentException(
                sprintf(
                    'Tipo de schema desconhecido: %s. Tipos válidos: %s',
                    $tipo,
                    implode(', ', array_keys(self::SCHEMAS))
                )
            );
        }

        $schemaFile = __DIR__ . '/../../storage/schemes/' . self::SCHEMAS[$tipo];

        if (!file_exists($schemaFile)) {
            throw new RuntimeException(
                sprintf('Schema XSD não encontrado: %s', $schemaFile)
            );
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        // Desabilita warnings externos
        libxml_use_internal_errors(true);
        libxml_clear_errors();

        if (!$dom->loadXML($xml)) {
            $errors = self::formatLibXmlErrors(libxml_get_errors());
            libxml_clear_errors();
            throw new InvalidArgumentException(
                'XML malformado: ' . $errors
            );
        }

        if (!$dom->schemaValidate($schemaFile)) {
            $errors = self::formatLibXmlErrors(libxml_get_errors());
            libxml_clear_errors();
            throw new InvalidArgumentException(
                sprintf('XML não conforme com schema %s: %s', self::SCHEMAS[$tipo], $errors)
            );
        }

        libxml_clear_errors();
    }

    /**
     * Formata erros do libxml para mensagem legível
     *
     * @param array<LibXMLError> $errors
     */
    private static function formatLibXmlErrors(array $errors): string
    {
        $messages = [];
        foreach ($errors as $error) {
            $level = match ($error->level) {
                LIBXML_ERR_WARNING => 'Warning',
                LIBXML_ERR_ERROR => 'Erro',
                LIBXML_ERR_FATAL => 'Erro Fatal',
                default => 'Desconhecido',
            };
            $messages[] = sprintf(
                '[%s] Linha %d: %s',
                $level,
                $error->line,
                trim($error->message)
            );
        }
        return implode(' | ', $messages);
    }
}
