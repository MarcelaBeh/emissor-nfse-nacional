<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\Contract;

interface XmlSignerInterface
{
    public function sign(string $xml, string $tagname, string $rootname): string;
}
