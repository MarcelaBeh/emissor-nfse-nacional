<?php

declare(strict_types=1);

namespace Hadder\NfseNacional\Common;

use DateTime;
use DateInterval;
use NFePHP\Common\Certificate;
use NFePHP\Common\Certificate\Exception\Expired;
use NFePHP\Common\Exception\RuntimeException;
use NFePHP\Common\Files;
use NFePHP\Common\Strings;
use Throwable;

class RestBase
{
    protected Certificate $certificate;
    protected bool $disableCertValidation = false;
    protected string $tempdir;
    private Files $filesystem;
    private string $certsdir;
    protected string $prifile;
    protected string $pubfile;
    protected string $certfile;
    public int $waitingTime = 45;

    public function __construct(?Certificate $certificate = null)
    {
        $this->loadCertificate($certificate);
    }

    public function loadCertificate(?Certificate $certificate = null): void
    {
        $this->isCertificateExpired($certificate);
        if ($certificate !== null) {
            $this->certificate = $certificate;
        }
    }

    private function isCertificateExpired(?Certificate $certificate = null): void
    {
        if (!$this->disableCertValidation && $certificate !== null && $certificate->isExpired()) {
            throw new Expired($certificate);
        }
    }

    public function disableCertValidation(bool $flag = true): bool
    {
        $this->disableCertValidation = $flag;
        return $this->disableCertValidation;
    }

    public function setTemporaryFolder(?string $folderRealPath = null): void
    {
        $mapto = $this->certificate->getCnpj() ?? $this->certificate->getCpf();
        if (empty($mapto)) {
            throw new RuntimeException('Foi impossivel identificar o OID do CNPJ ou do CPF.');
        }
        if (empty($folderRealPath)) {
            $folderRealPath = sys_get_temp_dir()
                . '/nfse-' . $this->uid() . '/'
                . $mapto . '/';
        }
        if (!str_ends_with($folderRealPath, '/')) {
            $folderRealPath .= '/';
        }
        $this->tempdir = $folderRealPath;
        $this->setLocalFolder($folderRealPath);
    }

    public function saveTemporarilyKeyFiles(): void
    {
        if (!empty($this->certsdir)) {
            return;
        }
        if (!isset($this->certificate)) {
            throw new RuntimeException('Certificate not found.');
        }
        if (!isset($this->filesystem)) {
            $this->setTemporaryFolder();
        }
        $this->removeTemporarilyFiles();
        $this->certsdir = 'certs/';
        $this->prifile = $this->randomName();
        $this->pubfile = $this->randomName();
        $this->certfile = $this->randomName();
        $private = $this->certificate->privateKey;
        
        // Salvar arquivos
        $this->filesystem->put($this->prifile, $private);
        $this->filesystem->put($this->pubfile, $this->certificate->publicKey);
        $this->filesystem->put($this->certfile, $private . $this->certificate);
        
        // Aplicar permissões restritas (0600 - somente dono lê/escreve)
        // Crítico para segurança: impede outros usuários de lerem chaves privadas
        chmod($this->tempdir . $this->prifile, 0600);
        chmod($this->tempdir . $this->pubfile, 0600);
        chmod($this->tempdir . $this->certfile, 0600);
    }

    protected function uid(): string
    {
        return (string)(function_exists('posix_getuid') ? posix_getuid() : getmyuid());
    }

    protected function setLocalFolder(string $folder = ''): void
    {
        $this->filesystem = new Files($folder);
    }

    public function removeTemporarilyFiles(): void
    {
        try {
            if (!isset($this->filesystem) || empty($this->certsdir)) {
                return;
            }
            $this->filesystem->delete($this->certfile);
            $this->filesystem->delete($this->prifile);
            $this->filesystem->delete($this->pubfile);
            $contents = $this->filesystem->listContents($this->certsdir, true);
            $dt = new DateTime();
            $tint = new DateInterval('PT' . $this->waitingTime . 'M');
            $tint->invert = 1;
            $tsLimit = $dt->add($tint)->getTimestamp();
            foreach ($contents as $item) {
                if ($item['type'] === 'file' && $this->filesystem->has($item['path'])) {
                    $timestamp = $this->filesystem->getTimestamp($item['path']);
                    if ($timestamp < $tsLimit) {
                        $this->filesystem->delete($item['path']);
                    }
                }
            }
        } catch (Throwable) {
        }
    }

    protected function randomName(int $n = 10): string
    {
        $name = $this->certsdir . Strings::randomString($n) . '.pem';
        if (!$this->filesystem->has($name)) {
            return $name;
        }
        return $this->randomName($n + 5);
    }
}