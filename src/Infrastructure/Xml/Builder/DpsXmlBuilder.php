<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Infrastructure\Xml\Builder;

use emissorNfseNacional\NfseNacional\Domain\Entity\Dps;
use emissorNfseNacional\NfseNacional\Domain\Entity\Prestador;
use emissorNfseNacional\NfseNacional\Domain\Entity\Tomador;
use emissorNfseNacional\NfseNacional\Domain\Entity\Intermediario;
use emissorNfseNacional\NfseNacional\Domain\Entity\Endereco;
use emissorNfseNacional\NfseNacional\Domain\Entity\Servico;
use emissorNfseNacional\NfseNacional\Domain\Entity\Substituicao;
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
    }
}
