<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Security;

use MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\Exception\CertificateExpiredException;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\Exception\CertificateExpiringException;
use NFePHP\Common\Certificate;

class CertificateManager implements Contract\CertificateManagerInterface
{
    private Certificate $certificate;
    private string $tempDir;
    /** @var list<string> arquivos temporários criados, removidos no destrutor */
    private array $tempFiles = [];

    public function __construct(Certificate $certificate)
    {
        $this->certificate = $certificate;
        $this->validate();
        $this->setupTempDirectory();
    }

    public function __destruct()
    {
        // Os arquivos de certificado/chave precisam existir durante toda a vida do cliente HTTP
        // (são lidos a cada request via CURLOPT_SSLCERT/SSLKEY), então só podem ser limpos aqui.
        $this->removeFiles($this->tempFiles);
    }

    private function validate(): void
    {
        if ($this->certificate->isExpired()) {
            $expiry = $this->certificate->getValidTo();
            throw new CertificateExpiredException(
                "Certificado expirado em {$expiry->format('d/m/Y')}"
            );
        }

        $daysToExpire = $this->certificate->getValidTo()->diff(new \DateTime())->days;
        if ($daysToExpire <= 30) {
            throw new CertificateExpiringException(
                "Certificado expira em {$daysToExpire} dias"
            );
        }
    }

    private function setupTempDirectory(): void
    {
        $cnpj = $this->certificate->getCnpj() ?: $this->certificate->getCpf();

        $this->tempDir = sys_get_temp_dir()
            . '/nfse-nacional-'
            . (function_exists('posix_getuid') ? posix_getuid() : getmyuid())
            . '/'
            . $cnpj
            . '/certs/';

        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0o700, true);
        }
    }

    #[\Override]
    /**
     * @return array{private: string, public: string, cert: string}
     */
    public function saveTemporaryFiles(): array
    {
        return [
            'private' => $this->writeSecureTemp((string) $this->certificate->privateKey),
            'public'  => $this->writeSecureTemp((string) $this->certificate->publicKey),
            'cert'    => $this->writeSecureTemp((string) $this->certificate),
        ];
    }

    private function writeSecureTemp(string $content): string
    {
        // tempnam() cria o arquivo atomicamente com permissão 0600 (owner-only),
        // eliminando a janela TOCTOU entre file_put_contents e chmod.
        $path = tempnam($this->tempDir, 'nfse_');
        if ($path === false) {
            throw new \RuntimeException('Falha ao criar arquivo temporário seguro');
        }

        $this->tempFiles[] = $path;

        if (file_put_contents($path, $content) === false) {
            throw new \RuntimeException('Falha ao gravar arquivo temporário do certificado');
        }

        return $path;
    }

    #[\Override]
    /**
     * @param array<string, string> $files
     */
    public function cleanTemporaryFiles(array $files): void
    {
        $this->removeFiles(array_values($files));
    }

    /**
     * @param list<string> $files
     */
    private function removeFiles(array $files): void
    {
        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }

            // Sobrescreve o conteúdo antes de deletar para reduzir recuperabilidade dos dados.
            $size = filesize($file);
            if ($size !== false && $size > 0) {
                file_put_contents($file, str_repeat("\0", $size));
            }

            unlink($file);
        }
    }

    #[\Override]
    public function getCertificate(): Certificate
    {
        return $this->certificate;
    }

    public function getTempDir(): string
    {
        return $this->tempDir;
    }
}
