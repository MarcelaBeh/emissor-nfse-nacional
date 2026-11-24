<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Parser;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEvento;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\EventoProcessado;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\PedidoRegistroEvento;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Exception\XmlException;

/**
 * Lê o XML do evento processado (`evento_v1.01.xsd`, tipo `TCEvento`) que a SEFIN devolve ao registrar ou consultar um
 * evento. Só lê o que o XSD define como obrigatório, sem interpretar o conteúdo do grupo do evento, e não verifica as
 * assinaturas.
 */
class EventoXmlParser
{
    private const COM_FUSO = '/(Z|[+-]\d{2}:\d{2})$/';

    /**
     * @throws XmlException se o XML não for um evento processado válido
     */
    public function parse(string $xml): EventoProcessado
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $carregado = $dom->loadXML($xml, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        $evento = $dom->documentElement;
        if (!$carregado || $evento === null || $evento->localName !== 'evento') {
            throw new XmlException('XML do evento processado inválido: elemento raiz <evento> não encontrado');
        }

        $versao = trim($evento->getAttribute('versao'));
        if ($versao === '') {
            throw new XmlException('Evento processado sem o atributo versao');
        }

        $infEvento = $this->filhoObrigatorio($evento, 'infEvento');
        $pedRegEvento = $this->filhoObrigatorio($infEvento, 'pedRegEvento');

        return new EventoProcessado(
            id: $this->atributoObrigatorio($infEvento, 'Id'),
            versao: $versao,
            verAplic: $this->textoObrigatorio($infEvento, 'verAplic'),
            ambGer: $this->inteiro($this->textoObrigatorio($infEvento, 'ambGer'), 'ambGer'),
            nSeqEvento: $this->textoObrigatorio($infEvento, 'nSeqEvento'),
            dhProc: $this->dataComFuso($this->textoObrigatorio($infEvento, 'dhProc'), 'dhProc'),
            nDFSe: $this->textoObrigatorio($infEvento, 'nDFSe'),
            pedido: $this->parsePedido($pedRegEvento),
            xml: $xml,
        );
    }

    private function parsePedido(\DOMElement $pedRegEvento): PedidoRegistroEvento
    {
        $infPedReg = $this->filhoObrigatorio($pedRegEvento, 'infPedReg');

        $cnpj = $this->texto($infPedReg, 'CNPJAutor');
        $cpf = $this->texto($infPedReg, 'CPFAutor');
        if ($cnpj === null && $cpf === null) {
            throw new XmlException('Evento processado sem CNPJAutor nem CPFAutor');
        }

        $grupo = null;
        foreach ($infPedReg->childNodes as $no) {
            if ($no instanceof \DOMElement && preg_match('/^e\d{6}$/', (string) $no->localName) === 1) {
                $grupo = $no;
                break;
            }
        }
        if ($grupo === null) {
            throw new XmlException('Evento processado sem o grupo do evento (eNNNNNN)');
        }
        $tipo = TipoEvento::tryFrom(substr((string) $grupo->localName, 1));
        if ($tipo === null) {
            throw new XmlException("Tipo de evento desconhecido: {$grupo->localName}");
        }

        $campos = [];
        foreach ($grupo->childNodes as $campo) {
            if ($campo instanceof \DOMElement) {
                $campos[(string) $campo->localName] = trim($campo->textContent);
            }
        }

        return new PedidoRegistroEvento(
            id: $this->atributoObrigatorio($infPedReg, 'Id'),
            tpAmb: $this->inteiro($this->textoObrigatorio($infPedReg, 'tpAmb'), 'tpAmb'),
            verAplic: $this->textoObrigatorio($infPedReg, 'verAplic'),
            dhEvento: $this->dataComFuso($this->textoObrigatorio($infPedReg, 'dhEvento'), 'dhEvento'),
            cnpjAutor: $cnpj,
            cpfAutor: $cpf,
            chNFSe: $this->textoObrigatorio($infPedReg, 'chNFSe'),
            tipoEvento: $tipo,
            campos: $campos,
        );
    }

    private function filho(\DOMElement $pai, string $nome): ?\DOMElement
    {
        foreach ($pai->childNodes as $no) {
            if ($no instanceof \DOMElement && $no->localName === $nome) {
                return $no;
            }
        }

        return null;
    }

    private function filhoObrigatorio(\DOMElement $pai, string $nome): \DOMElement
    {
        return $this->filho($pai, $nome) ?? throw new XmlException("Evento processado sem o elemento <{$nome}>");
    }

    private function texto(\DOMElement $pai, string $nome): ?string
    {
        $valor = $this->filho($pai, $nome)?->textContent;
        $valor = $valor !== null ? trim($valor) : '';

        return $valor === '' ? null : $valor;
    }

    private function textoObrigatorio(\DOMElement $pai, string $nome): string
    {
        return $this->texto($pai, $nome) ?? throw new XmlException("Evento processado sem o elemento <{$nome}>");
    }

    private function atributoObrigatorio(\DOMElement $no, string $atributo): string
    {
        $valor = trim($no->getAttribute($atributo));
        if ($valor === '') {
            throw new XmlException("Evento processado sem o atributo {$atributo} em <{$no->localName}>");
        }

        return $valor;
    }

    private function inteiro(string $valor, string $campo): int
    {
        if (!ctype_digit($valor)) {
            throw new XmlException("Valor inválido em <{$campo}>: {$valor}");
        }

        return (int) $valor;
    }

    /** O XSD (`TSDateTimeUTC`) exige o fuso, então um valor sem ele não é aceito em vez de assumir um. */
    private function dataComFuso(string $valor, string $campo): \DateTimeImmutable
    {
        if (preg_match(self::COM_FUSO, $valor) !== 1) {
            throw new XmlException("Data sem fuso horário em <{$campo}>: {$valor}");
        }

        try {
            return new \DateTimeImmutable($valor);
        } catch (\Exception) {
            throw new XmlException("Data inválida em <{$campo}>: {$valor}");
        }
    }
}
