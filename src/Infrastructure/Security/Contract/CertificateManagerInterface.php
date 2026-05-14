<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\Contract;

interface CertificateManagerInterface
{
    public function getCertificate(): \NFePHP\Common\Certificate;
    /**
     * @return array{private: string, public: string, cert: string}
     */
    public function saveTemporaryFiles(): array;
    /**
     * @param array<string, string> $files
     */
    public function cleanTemporaryFiles(array $files): void;
}
