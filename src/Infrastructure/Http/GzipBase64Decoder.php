<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Http;

/**
 * Decodifica o XML que a SEFIN devolve compactado em gzip e codificado em base64.
 *
 * O contrato descreve base64 simples de gzip, que é o que as respostas de POST trazem (`nfseXmlGZipB64`,
 * `eventoXmlGZipB64`). A consulta de eventos devolve `arquivoXml` com o base64 aplicado duas vezes (verificado em
 * produção restrita). Por isso o base64 é desfeito até aparecer o cabeçalho do gzip, no máximo duas vezes.
 */
final class GzipBase64Decoder
{
    private const MAX_CAMADAS_BASE64 = 2;

    /**
     * @throws \RuntimeException se o conteúdo não for base64 ou não resultar em gzip válido
     */
    public static function decode(string $dados): string
    {
        $atual = preg_replace('/\s+/', '', $dados) ?? '';

        for ($camada = 0; $camada < self::MAX_CAMADAS_BASE64; $camada++) {
            $binario = base64_decode($atual, true);
            if ($binario === false || $binario === '') {
                // Na primeira camada o problema é o base64. Nas seguintes, o que sobrou da camada anterior não é
                // base64 nem gzip, ou seja, o conteúdo decodificado não era um gzip.
                throw new \RuntimeException($camada === 0 ? 'Falha ao decodificar base64' : 'Falha ao descomprimir gzip');
            }

            if (str_starts_with($binario, "\x1f\x8b")) {
                $descomprimido = gzdecode($binario);
                if ($descomprimido === false) {
                    throw new \RuntimeException('Falha ao descomprimir gzip');
                }

                return $descomprimido;
            }

            $atual = $binario;
        }

        throw new \RuntimeException('Falha ao descomprimir gzip');
    }
}
