<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Infrastructure\Xml\Parser;

class DpsXmlParser
{
    public function parse(string $xml): array
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($xml);

        $infDps = $dom->getElementsByTagName('infDPS')->item(0);
        if ($infDps === null) {
            return [];
        }

        return [
            'chaveAcesso' => $infDps->getAttribute('Id'),
            'tipoAmbiente' => $this->getNodeValue($infDps, 'tpAmb'),
            'dataEmissao' => $this->getNodeValue($infDps, 'dhEmi'),
            'serie' => $this->getNodeValue($infDps, 'serie'),
            'numero' => $this->getNodeValue($infDps, 'nDPS'),
            'dataCompetencia' => $this->getNodeValue($infDps, 'dCompet'),
            'tipoEmissao' => $this->getNodeValue($infDps, 'tpEmit'),
            'codigoMunicipio' => $this->getNodeValue($infDps, 'cLocEmi'),
        ];
    }

    private function getNodeValue(\DOMElement $parent, string $tagName): ?string
    {
        $nodes = $parent->getElementsByTagName($tagName);
        if ($nodes->length > 0) {
            return trim($nodes->item(0)->textContent);
        }

        return null;
    }
}
