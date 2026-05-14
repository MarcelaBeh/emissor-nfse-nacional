<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Builder;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Evento;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEvento;
use NFePHP\Common\DOMImproved as Dom;

class EventoEnvelopeBuilder implements Contract\XmlBuilderInterface
{
    private Dom $dom;
    private EventoXmlBuilder $pedRegBuilder;

    public function __construct()
    {
        $this->dom = new Dom('1.0', 'UTF-8');
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = false;
        $this->pedRegBuilder = new EventoXmlBuilder();
    }

    #[\Override]
    public function build(object $entity): string
    {
        if (!$entity instanceof Evento) {
            throw new \InvalidArgumentException('Entity must be an instance of Evento');
        }

        $this->reset();

        $eventoNode = $this->dom->createElement('evento');
        $eventoNode->setAttribute('versao', '1.01');
        $eventoNode->setAttribute('xmlns', 'http://www.sped.fazenda.gov.br/nfse');

        $infEvento = $this->dom->createElement('infEvento');
        $infEvento->setAttribute('Id', $this->gerarIdEvento($entity));

        $this->addChild($infEvento, 'verAplic', $entity->getVersaoAplicacao());
        $this->addChild($infEvento, 'ambGer', (string) ($entity->getAmbGer() ?? 2));
        $this->addChild($infEvento, 'nSeqEvento', str_pad($entity->getNSeqEvento() ?? '1', 3, '0', STR_PAD_LEFT));
        $this->addChild($infEvento, 'dhProc', ($entity->getDhProc() ?? $entity->getDataEvento())->format('Y-m-d\TH:i:sP'));
        $this->addChild($infEvento, 'nDFSe', $entity->getNDFSe() ?? $this->extrairNumeroDfse($entity->getChaveNfse()));

        $pedRegXml = $this->pedRegBuilder->build($entity);
        $pedRegDom = new Dom('1.0', 'UTF-8');
        $pedRegDom->loadXML($pedRegXml);
        $pedRegNode = $this->dom->importNode($pedRegDom->documentElement, true);
        $infEvento->appendChild($pedRegNode);

        $eventoNode->appendChild($infEvento);

        $placeholder = $this->dom->createElement('ds', 'SignaturePlaceholder');
        $placeholder->setAttribute('xmlns', 'http://www.w3.org/2000/09/xmldsig#');
        $eventoNode->appendChild($placeholder);

        $this->dom->appendChild($eventoNode);

        return $this->dom->saveXML();
    }

    private function gerarIdEvento(Evento $evento): string
    {
        return 'EVT'
            . $evento->getChaveNfse()
            . $evento->getTipo()->value
            . str_pad($evento->getNSeqEvento() ?? '1', 3, '0', STR_PAD_LEFT);
    }

    private function extrairNumeroDfse(string $chaveNfse): string
    {
        $chaveLimpa = preg_replace('/[^0-9]/', '', $chaveNfse);
        if (strlen($chaveLimpa) === 50) {
            return substr($chaveLimpa, 25, 9);
        }
        return '0';
    }

    private function reset(): void
    {
        $this->dom = new Dom('1.0', 'UTF-8');
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = false;
    }

    private function addChild(\DOMNode $parent, string $name, string $value, bool $required = true): ?\DOMElement
    {
        if (!$required && $value === '') {
            return null;
        }

        $element = $this->dom->createElement($name, htmlspecialchars($value));
        $parent->appendChild($element);

        return $element;
    }
}
