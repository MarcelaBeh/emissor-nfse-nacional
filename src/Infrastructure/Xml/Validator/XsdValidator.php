<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Validator;

use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Exception\XmlValidationException;

class XsdValidator
{
    private string $schemasDir;

    private const SCHEMAS = [
        'DPS' => 'DPS_v1.01.xsd',
        'NFSe' => 'NFSe_v1.01.xsd',
        'pedRegEvento' => 'pedRegEvento_v1.01.xsd',
    ];

    public function __construct(?string $schemasDir = null)
    {
        $this->schemasDir = $schemasDir
            ?? __DIR__ . '/../../../../storage/schemes/';
    }

    public function validate(string $xml, string $tipo): void
    {
        $xsdFile = self::SCHEMAS[$tipo] ?? null;

        if ($xsdFile === null) {
            throw new \InvalidArgumentException(
                "Tipo de schema desconhecido: {$tipo}. Tipos válidos: " . implode(', ', array_keys(self::SCHEMAS))
            );
        }

        $xsdPath = $this->schemasDir . $xsdFile;

        if (!file_exists($xsdPath)) {
            throw new \InvalidArgumentException("Schema XSD não encontrado: {$xsdFile}");
        }

        libxml_use_internal_errors(true);
        libxml_clear_errors();

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        if (!$dom->loadXML($xml)) {
            $errors = $this->getLibxmlErrors();
            throw new XmlValidationException('XML malformado: ' . implode('; ', $errors));
        }

        if (!$dom->schemaValidate($xsdPath)) {
            $errors = $this->getLibxmlErrors();
            throw new XmlValidationException(
                "XML não válido segundo XSD {$xsdFile}: " . implode('; ', $errors)
            );
        }

        libxml_clear_errors();
        libxml_use_internal_errors(false);
    }

    /**
     * @return array<int, string>
     */
    private function getLibxmlErrors(): array
    {
        $errors = [];

        foreach (libxml_get_errors() as $error) {
            $errors[] = sprintf(
                '[%s] Linha %d: %s',
                $error->level === LIBXML_ERR_WARNING ? 'AVISO' : 'ERRO',
                $error->line,
                trim($error->message)
            );
        }

        return $errors;
    }
}
