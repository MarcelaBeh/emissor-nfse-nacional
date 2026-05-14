<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Parser;

class NfseXmlParser
{
    public function parse(string $xml): array
    {
        if (empty(trim($xml))) {
            return [];
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $loaded = @$dom->loadXML($xml);
        if (!$loaded) {
            return [];
        }

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
        $data = [
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

        $ibscbs = $this->parseIbscbs($infNfse);
        if ($ibscbs !== null) {
            $data['ibscbs'] = $ibscbs;
        }

        return $data;
    }

    private function parseIbscbs(\DOMElement $infNfse): ?array
    {
        $ibscbsNodes = $infNfse->getElementsByTagName('IBSCBS');
        if ($ibscbsNodes->length === 0) {
            return null;
        }

        $ibscbs = $ibscbsNodes->item(0);

        $valores = $ibscbs->getElementsByTagName('valores')->item(0);

        $uf = null;
        $ufNode = $valores?->getElementsByTagName('uf')->item(0);
        if ($ufNode) {
            $uf = [
                'pIBSUF' => $this->getNodeValue($ufNode, 'pIBSUF'),
                'pRedAliqUF' => $this->getNodeValue($ufNode, 'pRedAliqUF'),
                'pAliqEfetUF' => $this->getNodeValue($ufNode, 'pAliqEfetUF'),
            ];
        }

        $mun = null;
        $munNode = $valores?->getElementsByTagName('mun')->item(0);
        if ($munNode) {
            $mun = [
                'pIBSMun' => $this->getNodeValue($munNode, 'pIBSMun'),
                'pRedAliqMun' => $this->getNodeValue($munNode, 'pRedAliqMun'),
                'pAliqEfetMun' => $this->getNodeValue($munNode, 'pAliqEfetMun'),
            ];
        }

        $fed = null;
        $fedNode = $valores?->getElementsByTagName('fed')->item(0);
        if ($fedNode) {
            $fed = [
                'pCBS' => $this->getNodeValue($fedNode, 'pCBS'),
                'pRedAliqCBS' => $this->getNodeValue($fedNode, 'pRedAliqCBS'),
                'pAliqEfetCBS' => $this->getNodeValue($fedNode, 'pAliqEfetCBS'),
            ];
        }

        $totCibs = null;
        $totNode = $ibscbs->getElementsByTagName('totCIBS')->item(0);
        if ($totNode) {
            $gIbsNode = $totNode->getElementsByTagName('gIBS')->item(0);
            $gCbsNode = $totNode->getElementsByTagName('gCBS')->item(0);

            $gIbs = null;
            if ($gIbsNode) {
                $gIbsCredPresNode = $gIbsNode->getElementsByTagName('gIBSCredPres')->item(0);
                $gIbsUfTotNode = $gIbsNode->getElementsByTagName('gIBSUFTot')->item(0);
                $gIbsMunTotNode = $gIbsNode->getElementsByTagName('gIBSMunTot')->item(0);

                $gIbs = [
                    'vIBSTot' => $this->getNodeValue($gIbsNode, 'vIBSTot'),
                    'gIBSCredPres' => $gIbsCredPresNode ? [
                        'pCredPresIBS' => $this->getNodeValue($gIbsCredPresNode, 'pCredPresIBS'),
                        'vCredPresIBS' => $this->getNodeValue($gIbsCredPresNode, 'vCredPresIBS'),
                    ] : null,
                    'gIBSUFTot' => $gIbsUfTotNode ? [
                        'vDifUF' => $this->getNodeValue($gIbsUfTotNode, 'vDifUF'),
                        'vIBSUF' => $this->getNodeValue($gIbsUfTotNode, 'vIBSUF'),
                    ] : null,
                    'gIBSMunTot' => $gIbsMunTotNode ? [
                        'vDifMun' => $this->getNodeValue($gIbsMunTotNode, 'vDifMun'),
                        'vIBSMun' => $this->getNodeValue($gIbsMunTotNode, 'vIBSMun'),
                    ] : null,
                ];
            }

            $gCbs = null;
            if ($gCbsNode) {
                $gCbsCredPresNode = $gCbsNode->getElementsByTagName('gCBSCredPres')->item(0);

                $gCbs = [
                    'gCBSCredPres' => $gCbsCredPresNode ? [
                        'pCredPresCBS' => $this->getNodeValue($gCbsCredPresNode, 'pCredPresCBS'),
                        'vCredPresCBS' => $this->getNodeValue($gCbsCredPresNode, 'vCredPresCBS'),
                    ] : null,
                    'vDifCBS' => $this->getNodeValue($gCbsNode, 'vDifCBS'),
                    'vCBS' => $this->getNodeValue($gCbsNode, 'vCBS'),
                ];
            }

            $gTribRegularNode = $totNode->getElementsByTagName('gTribRegular')->item(0);
            $gTribCompraGovNode = $totNode->getElementsByTagName('gTribCompraGov')->item(0);

            $totCibs = [
                'vTotNF' => $this->getNodeValue($totNode, 'vTotNF'),
                'gIBS' => $gIbs,
                'gCBS' => $gCbs,
                'gTribRegular' => $gTribRegularNode ? [
                    'pAliqEfeRegIBSUF' => $this->getNodeValue($gTribRegularNode, 'pAliqEfeRegIBSUF'),
                    'vTribRegIBSUF' => $this->getNodeValue($gTribRegularNode, 'vTribRegIBSUF'),
                    'pAliqEfeRegIBSMun' => $this->getNodeValue($gTribRegularNode, 'pAliqEfeRegIBSMun'),
                    'vTribRegIBSMun' => $this->getNodeValue($gTribRegularNode, 'vTribRegIBSMun'),
                    'pAliqEfeRegCBS' => $this->getNodeValue($gTribRegularNode, 'pAliqEfeRegCBS'),
                    'vTribRegCBS' => $this->getNodeValue($gTribRegularNode, 'vTribRegCBS'),
                ] : null,
                'gTribCompraGov' => $gTribCompraGovNode ? [
                    'pIBSUF' => $this->getNodeValue($gTribCompraGovNode, 'pIBSUF'),
                    'vIBSUF' => $this->getNodeValue($gTribCompraGovNode, 'vIBSUF'),
                    'pIBSMun' => $this->getNodeValue($gTribCompraGovNode, 'pIBSMun'),
                    'vIBSMun' => $this->getNodeValue($gTribCompraGovNode, 'vIBSMun'),
                    'pCBS' => $this->getNodeValue($gTribCompraGovNode, 'pCBS'),
                    'vCBS' => $this->getNodeValue($gTribCompraGovNode, 'vCBS'),
                ] : null,
            ];
        }

        return [
            'cLocalidadeIncid' => $this->getNodeValue($ibscbs, 'cLocalidadeIncid'),
            'xLocalidadeIncid' => $this->getNodeValue($ibscbs, 'xLocalidadeIncid'),
            'pRedutor' => $this->getNodeValue($ibscbs, 'pRedutor'),
            'valores' => [
                'vBC' => $valores ? $this->getNodeValue($valores, 'vBC') : null,
                'vCalcReeRepRes' => $valores ? $this->getNodeValue($valores, 'vCalcReeRepRes') : null,
                'uf' => $uf,
                'mun' => $mun,
                'fed' => $fed,
            ],
            'totCIBS' => $totCibs,
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
