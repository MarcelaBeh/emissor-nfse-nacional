<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Infrastructure\Xml\Parser;

use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Parser\ErrorXmlParser;
use PHPUnit\Framework\TestCase;

final class ErrorXmlParserTest extends TestCase
{
    private ErrorXmlParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ErrorXmlParser();
    }

    public function test_parse_error_xml(): void
    {
        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <Erro>
                <cErro>E100</cErro>
                <xMsgErro>Erro de validacao</xMsgErro>
                <xDetalhe>Detalhe do erro</xDetalhe>
            </Erro>
            XML;

        $result = $this->parser->parse($xml);

        $this->assertCount(1, $result);
        $this->assertSame('E100', $result[0]['codigo']);
        $this->assertSame('Erro de validacao', $result[0]['mensagem']);
        $this->assertSame('Detalhe do erro', $result[0]['detalhes']);
    }

    public function test_parse_non_error_xml(): void
    {
        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <CompNFSe>
                <NFSe>
                    <infNFSe>
                        <chNFSe>12345678901234567890123456789012345678901234567890</chNFSe>
                    </infNFSe>
                </NFSe>
            </CompNFSe>
            XML;

        $result = $this->parser->parse($xml);

        $this->assertSame([], $result);
    }
}
