<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Validator\Contract;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\VersaoSchema;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Exception\XmlValidationException;

interface XsdValidatorInterface
{
    /** @throws XmlValidationException */
    public function validate(string $xml, string $tipo, VersaoSchema $versao = VersaoSchema::V1_01): void;
}
