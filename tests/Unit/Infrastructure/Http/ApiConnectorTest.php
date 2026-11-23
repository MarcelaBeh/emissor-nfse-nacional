<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Infrastructure\Http;

use MarcelaBeh\EmissorNfseNacional\Infrastructure\Config\Configuration;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\ApiConnector;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\Contract\HttpClientInterface;
use PHPUnit\Framework\TestCase;

final class ApiConnectorTest extends TestCase
{
    private const XML = '<NFSe xmlns="http://www.sped.fazenda.gov.br/nfse" versao="1.01"/>';

    /**
     * @param array{status: int, body: string} $resposta
     */
    private function conector(array $resposta): ApiConnector
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('post')->willReturn($resposta);

        return new ApiConnector(new Configuration(['tpAmb' => 2]), $http);
    }

    private function gzipB64(string $xml): string
    {
        return base64_encode((string) gzencode($xml));
    }

    public function test_resposta_201_decodifica_o_xml_e_preserva_os_alertas(): void
    {
        $alertas = [['codigo' => 'A0001', 'descricao' => 'Aviso', 'complemento' => 'detalhe']];
        $body = (string) json_encode([
            'nfseXmlGZipB64' => $this->gzipB64(self::XML),
            'alertas' => $alertas,
        ]);

        $resultado = $this->conector(['status' => 201, 'body' => $body])->post('nfse', []);

        $this->assertTrue($resultado['success']);
        $this->assertSame(self::XML, $resultado['data']);
        $this->assertSame($alertas, $resultado['alertas']);
    }

    public function test_resposta_201_sem_alertas_nao_traz_a_chave(): void
    {
        foreach ([null, []] as $alertas) {
            $body = (string) json_encode(['nfseXmlGZipB64' => $this->gzipB64(self::XML), 'alertas' => $alertas]);

            $resultado = $this->conector(['status' => 201, 'body' => $body])->post('nfse', []);

            $this->assertSame(self::XML, $resultado['data']);
            $this->assertArrayNotHasKey('alertas', $resultado);
        }
    }

    public function test_resposta_de_erro_continua_sendo_o_json_decodificado(): void
    {
        $body = (string) json_encode(['erros' => [['Codigo' => 'E0432', 'Descricao' => 'Desconto']]]);

        $resultado = $this->conector(['status' => 400, 'body' => $body])->post('nfse', []);

        $this->assertFalse($resultado['success']);
        $this->assertSame('E0432', $resultado['data']['erros'][0]['Codigo']);
        $this->assertArrayNotHasKey('alertas', $resultado);
    }
}
