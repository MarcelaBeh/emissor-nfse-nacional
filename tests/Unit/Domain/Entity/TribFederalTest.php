<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\TribFederal;
use PHPUnit\Framework\TestCase;

final class TribFederalTest extends TestCase
{
    public function test_create_empty(): void
    {
        $tf = new TribFederal();

        $this->assertNull($tf->getPisCofinsCst());
        $this->assertNull($tf->getPisCofinsTipo());
        $this->assertNull($tf->getPisCofinsAliquotaPis());
        $this->assertNull($tf->getPisCofinsAliquotaCofins());
        $this->assertNull($tf->getValorRetidoCP());
        $this->assertNull($tf->getValorRetidoIRRF());
        $this->assertNull($tf->getValorRetidoCSLL());
    }

    public function test_create_with_pis_cofins(): void
    {
        $tf = new TribFederal(
            pisCofinsCst: '100',
            pisCofinsTipo: '01',
            pisCofinsAliquotaPis: 1.65,
            pisCofinsAliquotaCofins: 7.60,
        );

        $this->assertSame('100', $tf->getPisCofinsCst());
        $this->assertSame('01', $tf->getPisCofinsTipo());
        $this->assertSame(1.65, $tf->getPisCofinsAliquotaPis());
        $this->assertSame(7.60, $tf->getPisCofinsAliquotaCofins());
        $this->assertNull($tf->getValorRetidoCP());
    }

    public function test_create_with_all_retentions(): void
    {
        $tf = new TribFederal(
            valorRetidoCP: '500.00',
            valorRetidoIRRF: '300.00',
            valorRetidoCSLL: '200.00',
        );

        $this->assertSame('500.00', $tf->getValorRetidoCP());
        $this->assertSame('300.00', $tf->getValorRetidoIRRF());
        $this->assertSame('200.00', $tf->getValorRetidoCSLL());
        $this->assertNull($tf->getPisCofinsCst());
    }

    public function test_create_with_zero_aliquota(): void
    {
        $tf = new TribFederal(
            pisCofinsCst: '200',
            pisCofinsTipo: '02',
            pisCofinsAliquotaPis: 0.0,
            pisCofinsAliquotaCofins: 0.0,
        );

        $this->assertSame(0.0, $tf->getPisCofinsAliquotaPis());
        $this->assertSame(0.0, $tf->getPisCofinsAliquotaCofins());
    }
}
