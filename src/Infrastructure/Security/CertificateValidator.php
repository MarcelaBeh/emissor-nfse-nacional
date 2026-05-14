<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Infrastructure\Security;

use NFePHP\Common\Certificate;
use emissorNfseNacional\NfseNacional\Infrastructure\Security\Exception\CertificateExpiredException;

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
        return $certificate->getValidTo()->diff(new \DateTime())->days;
    }

    public function isAboutToExpire(Certificate $certificate, int $days = 30): bool
    {
        return $this->getDaysToExpire($certificate) <= $days;
    }
}
