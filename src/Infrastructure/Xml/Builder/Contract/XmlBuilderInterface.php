<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Infrastructure\Xml\Builder\Contract;

interface XmlBuilderInterface
{
    public function build(object $entity): string;
}
