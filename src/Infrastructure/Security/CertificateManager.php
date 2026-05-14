<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Infrastructure\Security;

use NFePHP\Common\Certificate;
use emissorNfseNacional\NfseNacional\Infrastructure\Security\Exception\CertificateExpiredException;

class CertificateManager implements Contract\CertificateManagerInterface
{
    private Certificate $certificate;
    private string $tempDir;

    public function __construct(Certificate $certificate)
    {
        $this->certificate = $certificate;
        $this->validate();
        $this->setupTempDirectory();
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
            trigger_error(
                "Certificado expira em {$daysToExpire} dias",
                E_USER_WARNING
            );
        }
    }

    private function setupTempDirectory(): void
    {
        $cnpj = $this->certificate->getCnpj() ?? $this->certificate->getCpf();

        $this->tempDir = sys_get_temp_dir()
            . '/nfse-nacional-'
            . (function_exists('posix_getuid') ? posix_getuid() : getmyuid())
            . '/'
            . $cnpj
            . '/certs/';

        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0700, true);
        }
    }

    public function saveTemporaryFiles(): array
    {
        $files = [
            'private' => $this->tempDir . bin2hex(random_bytes(8)) . '.pem',
            'public' => $this->tempDir . bin2hex(random_bytes(8)) . '.pem',
            'cert' => $this->tempDir . bin2hex(random_bytes(8)) . '.pem',
        ];

        file_put_contents($files['private'], $this->certificate->privateKey);
        file_put_contents($files['public'], $this->certificate->publicKey);
        file_put_contents($files['cert'], $this->certificate);

        chmod($files['private'], 0600);
        chmod($files['public'], 0600);
        chmod($files['cert'], 0600);

        return $files;
    }

    public function cleanTemporaryFiles(array $files): void
    {
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function getCertificate(): Certificate
    {
        return $this->certificate;
    }

    public function getTempDir(): string
    {
        return $this->tempDir;
    }
}
