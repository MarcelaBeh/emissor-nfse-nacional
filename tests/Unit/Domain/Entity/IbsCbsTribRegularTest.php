<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsTribRegular;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoClassificacaoTributaria;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoSituacaoTributaria;
use PHPUnit\Framework\TestCase;

final class IbsCbsTribRegularTest extends TestCase
{
    public function test_create(): void
    {
        $cstReg = new CodigoSituacaoTributaria('100');
        $cClassTribReg = new CodigoClassificacaoTributaria('100456');

        $entity = new IbsCbsTribRegular(
            cstReg: $cstReg,
            cClassTribReg: $cClassTribReg,
        );

        $this->assertSame($cstReg, $entity->getCstReg());
        $this->assertSame($cClassTribReg, $entity->getCClassTribReg());
    }

    public function test_create_different_values(): void
    {
        $entity = new IbsCbsTribRegular(
            cstReg: new CodigoSituacaoTributaria('200'),
            cClassTribReg: new CodigoClassificacaoTributaria('200789'),
        );

        $this->assertSame('200', $entity->getCstReg()->getCodigo());
        $this->assertSame('200789', $entity->getCClassTribReg()->getCodigo());
    }
}
