<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Infrastructure\Http;

use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\GzipBase64Decoder;
use PHPUnit\Framework\TestCase;

final class GzipBase64DecoderTest extends TestCase
{
    private const XML = '<evento versao="1.01"><infEvento/></evento>';

    public function test_decodifica_base64_simples_de_gzip(): void
    {
        $this->assertSame(self::XML, GzipBase64Decoder::decode(base64_encode((string) gzencode(self::XML))));
    }

    public function test_decodifica_base64_duplo_de_gzip(): void
    {
        $duplo = base64_encode(base64_encode((string) gzencode(self::XML)));

        $this->assertSame(self::XML, GzipBase64Decoder::decode($duplo));
    }

    public function test_ignora_quebras_de_linha_no_base64(): void
    {
        $simples = base64_encode((string) gzencode(self::XML));

        $this->assertSame(self::XML, GzipBase64Decoder::decode(chunk_split($simples, 20, "\n")));
    }

    public function test_base64_invalido_lanca_excecao(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Falha ao decodificar base64');

        GzipBase64Decoder::decode('isto nao e base64 !!!');
    }

    public function test_vazio_lanca_excecao(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Falha ao decodificar base64');

        GzipBase64Decoder::decode('');
    }

    public function test_base64_que_nao_e_gzip_lanca_excecao(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Falha ao descomprimir gzip');

        GzipBase64Decoder::decode(base64_encode(self::XML));
    }

    public function test_tres_camadas_de_base64_nao_sao_aceitas(): void
    {
        $triplo = base64_encode(base64_encode(base64_encode((string) gzencode(self::XML))));

        $this->expectException(\RuntimeException::class);

        GzipBase64Decoder::decode($triplo);
    }
}
