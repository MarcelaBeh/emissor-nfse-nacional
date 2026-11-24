<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Infrastructure\Xml\Parser;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEvento;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Exception\XmlException;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Parser\EventoXmlParser;
use MarcelaBeh\EmissorNfseNacional\Tests\Support\EventoProcessadoXml;
use PHPUnit\Framework\TestCase;

final class EventoXmlParserTest extends TestCase
{
    private EventoXmlParser $parser;

    protected function setUp(): void
    {
        $this->parser = new EventoXmlParser();
    }

    public function test_o_xml_de_teste_valida_contra_o_xsd_do_evento_processado(): void
    {
        // Garante que o modelo lido pelo parser e o XML dos testes seguem o evento_v1.01.xsd oficial.
        $dom = new \DOMDocument();
        $dom->loadXML(EventoProcessadoXml::build());
        libxml_use_internal_errors(true);
        $valido = $dom->schemaValidate(__DIR__ . '/../../../../../storage/schemes/evento_v1.01.xsd');
        $erros = array_map(static fn (\LibXMLError $e): string => trim($e->message), libxml_get_errors());
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        $this->assertTrue($valido, implode('; ', $erros));
    }

    public function test_le_o_cancelamento(): void
    {
        $xml = EventoProcessadoXml::build();

        $evento = $this->parser->parse($xml);

        $this->assertSame('EVT' . EventoProcessadoXml::CHAVE . '101101001', $evento->id);
        $this->assertSame('1.01', $evento->versao);
        $this->assertSame('SefinNacional_1.6.0', $evento->verAplic);
        $this->assertSame(2, $evento->ambGer);
        $this->assertSame('1', $evento->nSeqEvento);
        $this->assertSame('0', $evento->nDFSe);
        $this->assertSame('2026-11-23T17:13:38-03:00', $evento->dhProc->format('Y-m-d\TH:i:sP'));
        $this->assertSame($xml, $evento->xml);

        $pedido = $evento->pedido;
        $this->assertSame('PRE' . EventoProcessadoXml::CHAVE . '101101', $pedido->id);
        $this->assertSame(2, $pedido->tpAmb);
        $this->assertSame('ERP_1.0', $pedido->verAplic);
        $this->assertSame('2026-11-23T17:11:45-03:00', $pedido->dhEvento->format('Y-m-d\TH:i:sP'));
        $this->assertSame('11444777000161', $pedido->cnpjAutor);
        $this->assertNull($pedido->cpfAutor);
        $this->assertSame(EventoProcessadoXml::CHAVE, $pedido->chNFSe);
        $this->assertSame(TipoEvento::CANCELAMENTO, $pedido->tipoEvento);
        $this->assertSame(
            ['xDesc' => 'Cancelamento de NFS-e', 'cMotivo' => '1', 'xMotivo' => 'Erro na emissao da nota'],
            $pedido->campos
        );
    }

    public function test_le_a_substituicao_com_autor_cpf(): void
    {
        $substituta = '35503082211444777000161000000000012426110123456789';
        $xml = EventoProcessadoXml::build([
            'autor' => '<CPFAutor>52998224725</CPFAutor>',
            'grupo' => '<e105102><xDesc>Cancelamento de NFS-e por Substituicao</xDesc><cMotivo>01</cMotivo>'
                . '<xMotivo>Dados do tomador incorretos</xMotivo><chSubstituta>' . $substituta . '</chSubstituta></e105102>',
        ]);

        $pedido = $this->parser->parse($xml)->pedido;

        $this->assertSame(TipoEvento::SUBSTITUICAO, $pedido->tipoEvento);
        $this->assertNull($pedido->cnpjAutor);
        $this->assertSame('52998224725', $pedido->cpfAutor);
        $this->assertSame($substituta, $pedido->campos['chSubstituta']);
    }

    public function test_aceita_data_em_utc(): void
    {
        $evento = $this->parser->parse(EventoProcessadoXml::build(['dhProc' => '2026-11-23T20:13:38Z']));

        $this->assertSame('2026-11-23T20:13:38+00:00', $evento->dhProc->format('Y-m-d\TH:i:sP'));
    }

    public function test_data_sem_fuso_nao_e_aceita(): void
    {
        $this->expectException(XmlException::class);
        $this->expectExceptionMessage('dhProc');

        $this->parser->parse(EventoProcessadoXml::build(['dhProc' => '2026-11-23T17:13:38']));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function elementosObrigatorios(): array
    {
        return [['verAplic'], ['ambGer'], ['nSeqEvento'], ['dhProc'], ['nDFSe']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('elementosObrigatorios')]
    public function test_elemento_obrigatorio_ausente_lanca_excecao(string $elemento): void
    {
        $this->expectException(XmlException::class);
        $this->expectExceptionMessage("<{$elemento}>");

        $this->parser->parse(EventoProcessadoXml::build([$elemento => false]));
    }

    public function test_sem_atributo_versao_lanca_excecao(): void
    {
        $this->expectException(XmlException::class);
        $this->expectExceptionMessage('versao');

        $this->parser->parse(EventoProcessadoXml::build(['versao' => false]));
    }

    public function test_sem_autor_lanca_excecao(): void
    {
        $this->expectException(XmlException::class);
        $this->expectExceptionMessage('CNPJAutor nem CPFAutor');

        $this->parser->parse(EventoProcessadoXml::build(['autor' => '']));
    }

    public function test_grupo_de_evento_desconhecido_lanca_excecao(): void
    {
        $this->expectException(XmlException::class);
        $this->expectExceptionMessage('Tipo de evento desconhecido');

        $this->parser->parse(EventoProcessadoXml::build(['grupo' => '<e999999><xDesc>x</xDesc></e999999>']));
    }

    public function test_sem_grupo_de_evento_lanca_excecao(): void
    {
        $this->expectException(XmlException::class);
        $this->expectExceptionMessage('grupo do evento');

        $this->parser->parse(EventoProcessadoXml::build(['grupo' => '']));
    }

    public function test_raiz_diferente_de_evento_lanca_excecao(): void
    {
        $this->expectException(XmlException::class);

        $this->parser->parse('<NFSe xmlns="http://www.sped.fazenda.gov.br/nfse"/>');
    }

    public function test_xml_malformado_lanca_excecao(): void
    {
        $this->expectException(XmlException::class);

        $this->parser->parse('<evento><infEvento>');
    }

    public function test_entidade_externa_nao_e_resolvida(): void
    {
        $xml = '<?xml version="1.0"?><!DOCTYPE evento [<!ENTITY x SYSTEM "file:///etc/passwd">]>'
            . '<evento versao="1.01"><infEvento Id="EVT1"><verAplic>&x;</verAplic></infEvento></evento>';

        $this->expectException(XmlException::class);

        $this->parser->parse($xml);
    }
}
