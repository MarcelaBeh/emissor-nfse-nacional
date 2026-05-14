<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Infrastructure\Xml\Builder;

use emissorNfseNacional\NfseNacional\Domain\Entity\Evento;
use NFePHP\Common\DOMImproved as Dom;

class EventoXmlBuilder implements Contract\XmlBuilderInterface
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
        if (!$entity instanceof Evento) {
            throw new \InvalidArgumentException('Entity must be an instance of Evento');
        }

        $this->reset();

        $eventoNode = $this->dom->createElement('pedRegEvento');
        $eventoNode->setAttribute('versao', '1.01');
        $eventoNode->setAttribute('xmlns', 'http://www.sped.fazenda.gov.br/nfse');

        $infPedReg = $this->dom->createElement('infPedReg');
        $infPedReg->setAttribute('Id', 'PRE' . $entity->getChaveNfse());

        $this->addChild($infPedReg, 'tpAmb', $entity->getTipo()->value === 'CANCELAMENTO' ? '1' : '2');
        $this->addChild($infPedReg, 'verAplic', $entity->getVersaoAplicacao());
        $this->addChild($infPedReg, 'dhEvento', $entity->getDataEvento()->format('Y-m-d\TH:i:sP'));

        if ($entity->getCnpjAutor()) {
            $this->addChild($infPedReg, 'CNPJAutor', $entity->getCnpjAutor(), false);
        } elseif ($entity->getCpfAutor()) {
            $this->addChild($infPedReg, 'CPFAutor', $entity->getCpfAutor(), false);
        }

        $this->addChild($infPedReg, 'chNFSe', $entity->getChaveNfse(), true);

        $evtNode = $this->dom->createElement('e101101');
        $infPedReg->appendChild($evtNode);

        if ($entity->getDescricaoMotivo()) {
            $this->addChild($evtNode, 'xDesc', $entity->getDescricaoMotivo(), true);
        }

        if ($entity->getCodigoMotivo()) {
            $this->addChild($evtNode, 'cMotivo', $entity->getCodigoMotivo(), true);
            $this->addChild($evtNode, 'xMotivo', $entity->getDescricaoMotivo(), true);
        }

        $eventoNode->appendChild($infPedReg);
        $this->dom->appendChild($eventoNode);

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
}
