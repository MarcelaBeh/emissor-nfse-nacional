<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Builder\Contract;

interface XmlBuilderInterface
{
    public function build(object $entity): string;
}
