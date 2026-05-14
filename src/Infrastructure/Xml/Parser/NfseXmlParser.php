<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Infrastructure\Xml\Parser;

class NfseXmlParser
{
    public function parse(string $xml): array
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($xml);

        $data = [];

        $nfseNodes = $dom->getElementsByTagName('CompNFSe');
        foreach ($nfseNodes as $compNfse) {
            $nfse = $compNfse->getElementsByTagName('NFSe')->item(0);
            if ($nfse === null) {
                continue;
            }

            $infNfse = $nfse->getElementsByTagName('infNFSe')->item(0);
            if ($infNfse === null) {
                continue;
            }

            $data[] = $this->parseInfNfse($infNfse);
        }

        return $data;
    }

    private function parseInfNfse(\DOMElement $infNfse): array
    {
        return [
            'chaveAcesso' => $this->getNodeValue($infNfse, 'chNFSe'),
            'numero' => $this->getNodeValue($infNfse, 'nNFSe'),
            'codigoVerificacao' => $this->getNodeValue($infNfse, 'cVerif'),
            'serie' => $this->getNodeValue($infNfse, 'serie'),
            'dataEmissao' => $this->getNodeValue($infNfse, 'dhEmi'),
            'prestador' => [
                'cnpj' => $this->getNodeValue($infNfse, 'CNPJ'),
                'nome' => $this->getNodeValue($infNfse, 'xNome'),
            ],
            'tomador' => [
                'nome' => $this->getNodeValue($infNfse, 'xNome', 1),
            ],
            'valorServicos' => $this->getNodeValue($infNfse, 'vServ'),
            'valorIss' => $this->getNodeValue($infNfse, 'vISS'),
        ];
    }

    private function getNodeValue(\DOMElement $parent, string $tagName, int $occurrence = 0): ?string
    {
        $nodes = $parent->getElementsByTagName($tagName);
        if ($nodes->length > $occurrence) {
            return trim($nodes->item($occurrence)->textContent);
        }

        return null;
    }
}
