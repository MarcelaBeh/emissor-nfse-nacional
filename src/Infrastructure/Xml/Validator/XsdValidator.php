<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Validator;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\VersaoSchema;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Exception\XmlValidationException;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Validator\Contract\XsdValidatorInterface;

class XsdValidator implements XsdValidatorInterface
{
    private string $schemasDir;

    private const SCHEMAS = [
        VersaoSchema::V1_01->value => [
            'DPS'          => 'DPS_v1.01.xsd',
            'NFSe'         => 'NFSe_v1.01.xsd',
            'pedRegEvento' => 'pedRegEvento_v1.01.xsd',
        ],
        VersaoSchema::V1_00->value => [
            'DPS'          => 'DPS_v1.00.xsd',
            'NFSe'         => 'NFSe_v1.00.xsd',
            'pedRegEvento' => 'pedRegEvento_v1.00.xsd',
        ],
    ];

    public function __construct(?string $schemasDir = null)
    {
        $this->schemasDir = $schemasDir
            ?? __DIR__ . '/../../../../storage/schemes/';
    }

    public function validate(string $xml, string $tipo, VersaoSchema $versao = VersaoSchema::V1_01): void
    {
        $schemas = self::SCHEMAS[$versao->value];

        $xsdFile = $schemas[$tipo] ?? null;

        if ($xsdFile === null) {
            throw new \InvalidArgumentException(
                "Tipo de schema desconhecido: {$tipo}. Tipos válidos: " . implode(', ', array_keys($schemas))
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

        // LIBXML_NONET bloqueia requisições de rede durante o parse (previne SSRF/XXE via entidades remotas).
        // LIBXML_NOENT substitui entidades por seus valores literais sem resolver externas.
        if (!$dom->loadXML($xml, LIBXML_NONET | LIBXML_NOENT)) {
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
