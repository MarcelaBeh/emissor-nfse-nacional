<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Integration;

use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\Client\CurlHttpClient;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\CertificateManager;
use NFePHP\Common\Certificate;
use PHPUnit\Framework\TestCase;

/**
 * Regressão do bug de coleta de lixo prematura do certificado (v2.2.1).
 *
 * O cURL relê os PEMs do disco a cada request via CURLOPT_SSLCERT/SSLKEY, e o
 * __destruct do CertificateManager os apaga. Se nada mantiver o manager vivo
 * enquanto o cliente HTTP existir, os arquivos somem e a emissão falha com
 * "could not load PEM client certificate". A correção dá a posse do manager ao
 * CurlHttpClient — único consumidor dos PEMs.
 *
 * @requires extension openssl
 */
final class CertificateLifetimeTest extends TestCase
{
    /**
     * Host reservado e porta inválida: a request falha rapidamente, mas o cURL
     * ainda materializa os PEMs antes de tentar a conexão, que é o que importa.
     */
    private const UNREACHABLE = 'https://127.0.0.1:1/';

    public function test_pems_persistem_enquanto_o_cliente_http_vive(): void
    {
        $manager = new CertificateManager($this->certificadoDeTeste());
        $tempDir = $manager->getTempDir();

        // Lazy: nenhum segredo é escrito em disco antes da primeira request.
        self::assertCount(0, $this->pems($tempDir));

        $client = new CurlHttpClient(
            timeout: 2,
            connectTimeout: 1,
            certificateManager: $manager,
        );

        $this->dispararRequest($client);

        // Os 3 PEMs (private, public, cert) existem e continuam existindo
        // enquanto o cliente — que possui o manager — estiver vivo.
        self::assertCount(3, $this->pems($tempDir));

        $depois = $this->dispararRequestEContar($client, $tempDir);
        self::assertSame(3, $depois, 'A segunda request não deve reescrever nem acumular arquivos.');

        // Os arquivos só são removidos quando o cliente (e o manager) morrem.
        unset($client, $manager);
        gc_collect_cycles();
        self::assertCount(0, $this->pems($tempDir));
    }

    private function dispararRequest(CurlHttpClient $client): void
    {
        try {
            $client->get(self::UNREACHABLE);
        } catch (\Throwable) {
            // Falha de conexão é esperada e irrelevante: só queremos forçar a
            // materialização lazy dos PEMs.
        }
    }

    /**
     * @return int número de PEMs no diretório após a request
     */
    private function dispararRequestEContar(CurlHttpClient $client, string $tempDir): int
    {
        $this->dispararRequest($client);

        return count($this->pems($tempDir));
    }

    /**
     * @return list<string>
     */
    private function pems(string $tempDir): array
    {
        return glob($tempDir . '*') ?: [];
    }

    /**
     * Gera um certificado X.509 autoassinado, válido por 365 dias, para não
     * disparar CertificateExpiredException (vencido) nem
     * CertificateExpiringException (vence em < 30 dias).
     */
    private function certificadoDeTeste(): Certificate
    {
        $dn = [
            'countryName' => 'BR',
            'organizationName' => 'Teste Regressao',
            'commonName' => 'EMPRESA TESTE:99999999000191',
        ];

        $privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        self::assertNotFalse($privateKey, 'Falha ao gerar chave privada de teste.');

        $csr = openssl_csr_new($dn, $privateKey, ['digest_alg' => 'sha256']);
        self::assertNotFalse($csr, 'Falha ao gerar CSR de teste.');

        $x509 = openssl_csr_sign($csr, null, $privateKey, 365, ['digest_alg' => 'sha256']);
        self::assertNotFalse($x509, 'Falha ao assinar certificado de teste.');

        $pfx = '';
        self::assertTrue(
            openssl_pkcs12_export($x509, $pfx, $privateKey, '1234'),
            'Falha ao exportar PFX de teste.'
        );

        return Certificate::readPfx($pfx, '1234');
    }
}
