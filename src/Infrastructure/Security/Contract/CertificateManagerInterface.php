<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\Contract;

interface CertificateManagerInterface
{
    public function getCertificate(): \NFePHP\Common\Certificate;
    public function saveTemporaryFiles(): array;
    public function cleanTemporaryFiles(array $files): void;
}
