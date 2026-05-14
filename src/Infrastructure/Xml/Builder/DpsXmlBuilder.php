<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Builder;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Dps;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Endereco;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDest;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsInfo;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Intermediario;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Prestador;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Servico;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Substituicao;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Tomador;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\ChaveAcesso;
use NFePHP\Common\DOMImproved as Dom;

class DpsXmlBuilder implements Contract\XmlBuilderInterface
{
    private Dom $dom;

    public function __construct()
    {
        $this->dom = new Dom('1.0', 'UTF-8');
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = false;
    }

    #[\Override]
    public function build(object $entity): string
    {
        if (!$entity instanceof Dps) {
            throw new \InvalidArgumentException('Entity must be an instance of Dps');
        }

        $this->reset();

        $dpsNode = $this->dom->createElement('DPS');
        $dpsNode->setAttribute('versao', $entity->getVersao()->value);
        $dpsNode->setAttribute('xmlns', 'http://www.sped.fazenda.gov.br/nfse');

        $infDpsNode = $this->dom->createElement('infDPS');
        $infDpsNode->setAttribute('Id', 'DPS' . $entity->getChaveAcesso()->getChave());

        $this->addChild($infDpsNode, 'tpAmb', $entity->getTipoAmbiente()->value);
        $this->addChild($infDpsNode, 'dhEmi', $entity->getDataEmissao()->format('Y-m-d\TH:i:sP'));
        $this->addChild($infDpsNode, 'verAplic', $entity->getVersaoAplicacao());
        $this->addChild($infDpsNode, 'serie', $entity->getSerie());
        $this->addChild($infDpsNode, 'nDPS', $entity->getNumero());
        $this->addChild($infDpsNode, 'dCompet', $entity->getDataCompetencia()->format('Y-m-d'));
        $this->addChild($infDpsNode, 'tpEmit', $entity->getTipoEmissao()->value);
        $this->addChild($infDpsNode, 'cLocEmi', $entity->getCodigoMunicipioEmissor()->getCodigo());

        if ($entity->getSubstituicao() !== null) {
            $this->buildSubstituicao($infDpsNode, $entity->getSubstituicao());
        }

        $this->buildPrestador($infDpsNode, $entity->getPrestador());
        $this->buildPessoa($infDpsNode, $entity->getTomador(), 'tomador');

        if ($entity->getIntermediario() !== null) {
            $this->buildPessoa($infDpsNode, $entity->getIntermediario(), 'intermediario');
        }

        $this->buildServico($infDpsNode, $entity->getServico());

        if ($entity->getIbsCbs() !== null) {
            $this->buildIbscbs($infDpsNode, $entity->getIbsCbs());
        }

        $dpsNode->appendChild($infDpsNode);
        $this->dom->appendChild($dpsNode);

        return $this->dom->saveXML();
    }

    private function reset(): void
    {
        $this->dom = new Dom('1.0', 'UTF-8');
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = false;
    }

    private function addChild(\DOMNode $parent, string $name, mixed $value, bool $required = true): ?\DOMElement
    {
        if (!$required && ($value === null || $value === '')) {
            return null;
        }

        $element = $this->dom->createElement($name, htmlspecialchars((string) $value));
        $parent->appendChild($element);

        return $element;
    }

    private function buildSubstituicao(\DOMNode $parent, Substituicao $substituicao): void
    {
        $substNode = $this->dom->createElement('subst');
        $parent->appendChild($substNode);

        $this->addChild($substNode, 'chSubstda', $substituicao->getChaveSubstituida()->getChave(), true);
        $this->addChild($substNode, 'cMotivo', $substituicao->getCodigoMotivo(), true);
        $this->addChild($substNode, 'xMotivo', $substituicao->getDescricaoMotivo(), true);
    }

    private function buildPrestador(\DOMNode $parent, Prestador $prestador): void
    {
        $prestNode = $this->dom->createElement('prest');
        $parent->appendChild($prestNode);

        if ($prestador->getCnpj()) {
            $this->addChild($prestNode, 'CNPJ', $prestador->getCnpj()->getNumero(), false);
        } elseif ($prestador->getCpf()) {
            $this->addChild($prestNode, 'CPF', $prestador->getCpf()->getNumero(), false);
        }

        if ($prestador->getNif()) {
            $this->addChild($prestNode, 'NIF', $prestador->getNif(), false);
        }

        if ($prestador->getCaepf()) {
            $this->addChild($prestNode, 'CAEPF', $prestador->getCaepf(), false);
        }

        if ($prestador->getInscricaoMunicipal()) {
            $this->addChild($prestNode, 'IM', $prestador->getInscricaoMunicipal(), false);
        }

        $this->addChild($prestNode, 'xNome', $prestador->getRazaoSocial(), true);

        $this->buildEndereco($prestNode, $prestador->getEndereco());

        if ($prestador->getTelefone()) {
            $this->addChild($prestNode, 'fone', $prestador->getTelefone()->getNumero(), false);
        }

        if ($prestador->getEmail()) {
            $this->addChild($prestNode, 'email', $prestador->getEmail()->getEmail(), false);
        }
    }

    private function buildPessoa(\DOMNode $parent, Tomador|Intermediario $pessoa, string $tagName): void
    {
        $node = $this->dom->createElement($tagName);
        $parent->appendChild($node);

        if ($pessoa->getCnpj()) {
            $this->addChild($node, 'CNPJ', $pessoa->getCnpj()->getNumero(), false);
        } elseif ($pessoa->getCpf()) {
            $this->addChild($node, 'CPF', $pessoa->getCpf()->getNumero(), false);
        }

        if ($pessoa->getNif()) {
            $this->addChild($node, 'NIF', $pessoa->getNif(), false);
        }

        if ($pessoa->getInscricaoMunicipal()) {
            $this->addChild($node, 'IM', $pessoa->getInscricaoMunicipal(), false);
        }

        $this->addChild($node, 'xNome', $pessoa->getRazaoSocial(), true);

        $this->buildEndereco($node, $pessoa->getEndereco());

        if ($pessoa->getTelefone()) {
            $this->addChild($node, 'fone', $pessoa->getTelefone()->getNumero(), false);
        }

        if ($pessoa->getEmail()) {
            $this->addChild($node, 'email', $pessoa->getEmail()->getEmail(), false);
        }
    }

    private function buildEndereco(\DOMNode $parent, Endereco $endereco): void
    {
        $endNode = $this->dom->createElement('end');
        $parent->appendChild($endNode);

        if ($endereco->isExterior()) {
            $extNode = $this->dom->createElement('endExt');
            $endNode->appendChild($extNode);
            $this->addChild($extNode, 'cPais', $endereco->getCodigoPais(), true);
            $this->addChild($extNode, 'cEndPost', $endereco->getCodigoPostalExterior(), true);
            $this->addChild($extNode, 'xCidade', $endereco->getNomeCidadeExterior(), true);
            $this->addChild($extNode, 'xEstProvReg', $endereco->getEstadoProvinciaExterior(), true);
        } else {
            $nacNode = $this->dom->createElement('endNac');
            $endNode->appendChild($nacNode);
            $this->addChild($nacNode, 'cMun', $endereco->getCodigoMunicipio()->getCodigo(), true);
            $this->addChild($nacNode, 'CEP', $endereco->getCep()->getCep(), true);
        }

        $this->addChild($endNode, 'xLgr', $endereco->getLogradouro(), true);
        $this->addChild($endNode, 'nro', $endereco->getNumero(), true);

        if ($endereco->getComplemento()) {
            $this->addChild($endNode, 'xCpl', $endereco->getComplemento(), false);
        }

        $this->addChild($endNode, 'xBairro', $endereco->getBairro(), true);
    }

    private function buildServico(\DOMNode $parent, Servico $servico): void
    {
        $servNode = $this->dom->createElement('serv');
        $parent->appendChild($servNode);

        $locPrest = $this->dom->createElement('locPrest');
        $servNode->appendChild($locPrest);
        $this->addChild($locPrest, 'cLocPrestacao', $servico->getLocalPrestacao()->getCodigo(), true);

        $cServ = $this->dom->createElement('cServ');
        $servNode->appendChild($cServ);
        $this->addChild($cServ, 'cTribNac', $servico->getCodigoTributacao(), true);
        $this->addChild($cServ, 'xDescServ', $servico->getDiscriminacao(), true);

        if ($servico->getCodigoNbs()) {
            $this->addChild($cServ, 'cNBS', $servico->getCodigoNbs(), false);
        }

        if ($servico->getObra() !== null) {
            $this->buildObra($servNode, $servico->getObra());
        }
    }

    private function buildObra(\DOMNode $parent, \MarcelaBeh\EmissorNfseNacional\Domain\Entity\Obra $obra): void
    {
        $node = $this->dom->createElement('obra');
        $parent->appendChild($node);

        $this->addChild($node, 'inscImobFisc', $obra->getInscImobFisc(), false);

        if ($obra->getCObra() !== null) {
            $this->addChild($node, 'cObra', $obra->getCObra(), true);
        } elseif ($obra->getCCIB() !== null) {
            $this->addChild($node, 'cCIB', $obra->getCCIB()->getCodigo(), true);
        } elseif ($obra->getEndereco() !== null) {
            $this->buildObraEndereco($node, $obra->getEndereco());
        }
    }

    private function buildObraEndereco(\DOMNode $parent, \MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsEnderecoObra $end): void
    {
        $endNode = $this->dom->createElement('end');
        $parent->appendChild($endNode);

        if ($end->getCEp() !== null) {
            $this->addChild($endNode, 'CEP', $end->getCEp(), true);
        } elseif ($end->getEndExt() !== null) {
            $ext = $end->getEndExt();
            $extNode = $this->dom->createElement('endExt');
            $endNode->appendChild($extNode);
            $this->addChild($extNode, 'cEndPost', $ext->getCEndPost(), true);
            $this->addChild($extNode, 'xCidade', $ext->getXCidade(), true);
            $this->addChild($extNode, 'xEstProvReg', $ext->getXEstProvReg(), true);
        }

        $this->addChild($endNode, 'xLgr', $end->getXLgr(), true);
        $this->addChild($endNode, 'nro', $end->getNro(), true);
        $this->addChild($endNode, 'xCpl', $end->getXCpl(), false);
        $this->addChild($endNode, 'xBairro', $end->getXBairro(), true);
    }

    private function buildIbscbs(\DOMNode $parent, IbsCbsInfo $ibscbs): void
    {
        $node = $this->dom->createElement('IBSCBS');
        $parent->appendChild($node);

        $this->addChild($node, 'finNFSe', $ibscbs->getFinNFSe()->value, true);
        $this->addChild($node, 'indFinal', $ibscbs->getIndFinal()?->value, false);
        $this->addChild($node, 'cIndOp', $ibscbs->getCIndOp()->getCodigo(), true);
        $this->addChild($node, 'tpOper', $ibscbs->getTpOper()?->value, false);

        if ($ibscbs->hasRefNFSe()) {
            $this->buildGRefNFSe($node, $ibscbs->getRefNFSeList());
        }

        $this->addChild($node, 'tpEnteGov', $ibscbs->getTpEnteGov()?->value, false);
        $this->addChild($node, 'indDest', $ibscbs->getIndDest()->value, true);

        if ($ibscbs->getDest() !== null) {
            $this->buildIbscbsDest($node, $ibscbs->getDest());
        }

        if ($ibscbs->getImovel() !== null) {
            $this->buildIbscbsImovel($node, $ibscbs->getImovel());
        }

        $this->buildIbscbsValores($node, $ibscbs);
    }

    /** @param ChaveAcesso[] $refList */
    private function buildGRefNFSe(\DOMNode $parent, array $refList): void
    {
        $gRefNode = $this->dom->createElement('gRefNFSe');
        $parent->appendChild($gRefNode);

        foreach ($refList as $ref) {
            $this->addChild($gRefNode, 'refNFSe', $ref->getChave(), true);
        }
    }

    private function buildIbscbsDest(\DOMNode $parent, IbsCbsDest $dest): void
    {
        $destNode = $this->dom->createElement('dest');
        $parent->appendChild($destNode);

        if ($dest->getCnpj()) {
            $this->addChild($destNode, 'CNPJ', $dest->getCnpj()->getNumero(), false);
        } elseif ($dest->getCpf()) {
            $this->addChild($destNode, 'CPF', $dest->getCpf()->getNumero(), false);
        } elseif ($dest->getNif()) {
            $this->addChild($destNode, 'NIF', $dest->getNif(), false);
        }

        $this->addChild($destNode, 'cNaoNIF', $dest->getCodigoNaoNif(), false);
        $this->addChild($destNode, 'xNome', $dest->getXNome(), true);
        $this->addChild($destNode, 'fone', $dest->getFone(), false);
        $this->addChild($destNode, 'email', $dest->getEmail(), false);

        if ($dest->getEndereco() !== null) {
            $this->buildEndereco($destNode, $dest->getEndereco());
        }
    }

    private function buildIbscbsImovel(\DOMNode $parent, \MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsImovel $imovel): void
    {
        $node = $this->dom->createElement('imovel');
        $parent->appendChild($node);

        $this->addChild($node, 'inscImobFisc', $imovel->getInscImobFisc(), false);

        if ($imovel->getCCIB() !== null) {
            $this->addChild($node, 'cCIB', $imovel->getCCIB()->getCodigo(), true);
        } elseif ($imovel->getEndereco() !== null) {
            $this->buildIbscbsEnderecoObra($node, $imovel->getEndereco());
        }

        if ($imovel->getCCIB() !== null && $imovel->getEndereco() !== null) {
            trigger_error('Ambos cCIB e endereco informados em imovel — apenas um deve ser usado (XSD choice)', E_USER_WARNING);
        }
    }

    private function buildIbscbsEnderecoObra(\DOMNode $parent, \MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsEnderecoObra $end): void
    {
        $endNode = $this->dom->createElement('end');
        $parent->appendChild($endNode);

        if ($end->getCEp() !== null) {
            $this->addChild($endNode, 'CEP', $end->getCEp(), true);
        } elseif ($end->getEndExt() !== null) {
            $ext = $end->getEndExt();
            $extNode = $this->dom->createElement('endExt');
            $endNode->appendChild($extNode);
            $this->addChild($extNode, 'cEndPost', $ext->getCEndPost(), true);
            $this->addChild($extNode, 'xCidade', $ext->getXCidade(), true);
            $this->addChild($extNode, 'xEstProvReg', $ext->getXEstProvReg(), true);
        }

        $this->addChild($endNode, 'xLgr', $end->getXLgr(), true);
        $this->addChild($endNode, 'nro', $end->getNro(), true);
        $this->addChild($endNode, 'xCpl', $end->getXCpl(), false);
        $this->addChild($endNode, 'xBairro', $end->getXBairro(), true);
    }

    private function buildIbscbsValores(\DOMNode $parent, IbsCbsInfo $ibscbs): void
    {
        $valNode = $this->dom->createElement('valores');
        $parent->appendChild($valNode);

        if ($ibscbs->getReeRepRes() !== null) {
            $this->buildGReeRepRes($valNode, $ibscbs->getReeRepRes());
        }

        $tribNode = $this->dom->createElement('trib');
        $valNode->appendChild($tribNode);

        $gIbscbs = $this->dom->createElement('gIBSCBS');
        $tribNode->appendChild($gIbscbs);
        $this->addChild($gIbscbs, 'CST', $ibscbs->getCst()->getCodigo(), true);
        $this->addChild($gIbscbs, 'cClassTrib', $ibscbs->getCClassTrib()->getCodigo(), true);
        $this->addChild($gIbscbs, 'cCredPres', $ibscbs->getCCredPres()?->getCodigo(), false);

        if ($ibscbs->getTribRegular() !== null) {
            $gReg = $this->dom->createElement('gTribRegular');
            $gIbscbs->appendChild($gReg);
            $this->addChild($gReg, 'CSTReg', $ibscbs->getTribRegular()->getCstReg()->getCodigo(), true);
            $this->addChild($gReg, 'cClassTribReg', $ibscbs->getTribRegular()->getCClassTribReg()->getCodigo(), true);
        }

        if ($ibscbs->getDiferimento() !== null) {
            $gDif = $this->dom->createElement('gDif');
            $gIbscbs->appendChild($gDif);
            $this->addChild($gDif, 'pDifUF', $ibscbs->getDiferimento()->getPDifUF(), true);
            $this->addChild($gDif, 'pDifMun', $ibscbs->getDiferimento()->getPDifMun(), true);
            $this->addChild($gDif, 'pDifCBS', $ibscbs->getDiferimento()->getPDifCBS(), true);
        }
    }

    private function buildGReeRepRes(\DOMNode $parent, \MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsReeRepRes $reeRepRes): void
    {
        $gNode = $this->dom->createElement('gReeRepRes');
        $parent->appendChild($gNode);

        foreach ($reeRepRes->getDocumentos() as $doc) {
            $docNode = $this->dom->createElement('documentos');
            $gNode->appendChild($docNode);

            match ($doc->getTipo()) {
                'dFeNacional' => $this->buildDocDFeNacional($docNode, $doc),
                'docFiscalOutro' => $this->buildDocFiscalOutro($docNode, $doc),
                'docOutro' => $this->buildDocOutro($docNode, $doc),
                default => throw new \InvalidArgumentException('Tipo de documento inválido: ' . $doc->getTipo()),
            };

            if ($doc->getFornec() !== null) {
                $this->buildDocFornec($docNode, $doc->getFornec());
            }

            $this->addChild($docNode, 'dtEmiDoc', $doc->getDtEmiDoc()->format('Y-m-d'), true);
            $this->addChild($docNode, 'dtCompDoc', $doc->getDtCompDoc()->format('Y-m-d'), true);
            $this->addChild($docNode, 'tpReeRepRes', $doc->getTpReeRepRes()->value, true);
            $this->addChild($docNode, 'xTpReeRepRes', $doc->getXTpReeRepRes(), false);
            $this->addChild($docNode, 'vlrReeRepRes', $doc->getVlrReeRepRes(), true);
        }
    }

    private function buildDocDFeNacional(\DOMNode $parent, \MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDocumentoReeRepRes $doc): void
    {
        $node = $this->dom->createElement('dFeNacional');
        $parent->appendChild($node);
        $this->addChild($node, 'tipoChaveDFe', $doc->getTipoChaveDFe(), true);
        $this->addChild($node, 'xTipoChaveDFe', $doc->getXTipoChaveDFe(), false);
        $this->addChild($node, 'chaveDFe', $doc->getChaveDFe(), true);
    }

    private function buildDocFiscalOutro(\DOMNode $parent, \MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDocumentoReeRepRes $doc): void
    {
        $node = $this->dom->createElement('docFiscalOutro');
        $parent->appendChild($node);
        $this->addChild($node, 'cMunDocFiscal', $doc->getCMunDocFiscal(), true);
        $this->addChild($node, 'nDocFiscal', $doc->getNDocFiscal(), true);
        $this->addChild($node, 'xDocFiscal', $doc->getXDocFiscal(), true);
    }

    private function buildDocOutro(\DOMNode $parent, \MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDocumentoReeRepRes $doc): void
    {
        $node = $this->dom->createElement('docOutro');
        $parent->appendChild($node);
        $this->addChild($node, 'nDoc', $doc->getNDoc(), true);
        $this->addChild($node, 'xDoc', $doc->getXDoc(), true);
    }

    private function buildDocFornec(\DOMNode $parent, \MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsFornecedor $fornec): void
    {
        $node = $this->dom->createElement('fornec');
        $parent->appendChild($node);

        if ($fornec->getCnpj()) {
            $this->addChild($node, 'CNPJ', $fornec->getCnpj()->getNumero(), true);
        } elseif ($fornec->getCpf()) {
            $this->addChild($node, 'CPF', $fornec->getCpf()->getNumero(), true);
        } elseif ($fornec->getNif()) {
            $this->addChild($node, 'NIF', $fornec->getNif()->getNif(), true);
        } else {
            $this->addChild($node, 'cNaoNIF', $fornec->getCodigoNaoNif(), true);
        }

        $this->addChild($node, 'xNome', $fornec->getXNome(), true);
    }
}
