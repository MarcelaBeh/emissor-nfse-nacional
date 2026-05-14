<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Security;

use MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\Exception\CertificateExpiredException;
use NFePHP\Common\Certificate;

class CertificateValidator
{
    public function validate(Certificate $certificate, bool $checkExpiration = true): void
    {
        if ($checkExpiration && $certificate->isExpired()) {
            $expiry = $certificate->getValidTo();
            throw new CertificateExpiredException(
                "Certificado expirado em {$expiry->format('d/m/Y')}"
            );
        }
    }

    public function getDaysToExpire(Certificate $certificate): int
    {
        $days = $certificate->getValidTo()->diff(new \DateTime())->days;
        if ($days === false) {
            throw new \RuntimeException('Failed to calculate days to expire');
        }
        return $days;
    }

    public function isAboutToExpire(Certificate $certificate, int $days = 30): bool
    {
        return $this->getDaysToExpire($certificate) <= $days;
    }
}
