<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Parser;

class ErrorXmlParser
{
    /**
     * @return array<int, array{codigo: ?string, mensagem: ?string, detalhes: ?string}>
     */
    public function parse(string $xml): array
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($xml);

        $errors = [];

        $erroNodes = $dom->getElementsByTagName('Erro');
        foreach ($erroNodes as $erroNode) {
            $errors[] = [
                'codigo' => $this->getNodeValue($erroNode, 'cErro'),
                'mensagem' => $this->getNodeValue($erroNode, 'xMsgErro'),
                'detalhes' => $this->getNodeValue($erroNode, 'xDetalhe'),
            ];
        }

        return $errors;
    }

    private function getNodeValue(\DOMElement $parent, string $tagName): ?string
    {
        $nodes = $parent->getElementsByTagName($tagName);
        if ($nodes->length > 0) {
            $node = $nodes->item(0);
            if ($node === null) {
                return null;
            }
            return trim($node->textContent);
        }

        return null;
    }
}
