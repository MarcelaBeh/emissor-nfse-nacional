<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Security;

use NFePHP\Common\Certificate;
use NFePHP\Common\Signer;

class XmlSigner implements Contract\XmlSignerInterface
{
    private Certificate $certificate;
    /** @var array{0: bool, 1: bool, 2: null, 3: null} */
    private array $canonical = [true, false, null, null];

    public function __construct(Certificate $certificate)
    {
        $this->certificate = $certificate;
    }

    #[\Override]
    public function sign(string $xml, string $tagname, string $rootname): string
    {
        return Signer::sign(
            $this->certificate,
            $xml,
            $tagname,
            'Id',
            OPENSSL_ALGO_SHA1,
            $this->canonical,
            $rootname
        );
    }
}
