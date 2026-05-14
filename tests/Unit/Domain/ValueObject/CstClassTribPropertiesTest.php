<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\ValueObject;

use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CstClassTribProperties;
use PHPUnit\Framework\TestCase;

final class CstClassTribPropertiesTest extends TestCase
{
    public function test_create_with_all_fields(): void
    {
        $props = new CstClassTribProperties(
            cClassTrib: '200001',
            cst: '200',
            descricao: 'Alíquota zero',
            validoParaNfse: true,
            permiteDiferimento: false,
            exigeGrupoTributacaoRegular: false,
            pRedIBS: 100.0,
            pRedCBS: 100.0,
        );

        $this->assertSame('200001', $props->getCClassTrib());
        $this->assertSame('200', $props->getCst());
        $this->assertSame('Alíquota zero', $props->getDescricao());
        $this->assertTrue($props->isValidoParaNfse());
        $this->assertFalse($props->isPermiteDiferimento());
        $this->assertFalse($props->isExigeGrupoTributacaoRegular());
        $this->assertSame(100.0, $props->getPRedIBS());
        $this->assertSame(100.0, $props->getPRedCBS());
        $this->assertTrue($props->hasReducaoIBS());
        $this->assertTrue($props->hasReducaoCBS());
    }

    public function test_create_without_reduction(): void
    {
        $props = new CstClassTribProperties(
            cClassTrib: '000001',
            cst: '000',
            descricao: 'Tributação integral',
            validoParaNfse: true,
            permiteDiferimento: false,
            exigeGrupoTributacaoRegular: false,
        );

        $this->assertFalse($props->hasReducaoIBS());
        $this->assertFalse($props->hasReducaoCBS());
        $this->assertNull($props->getPRedIBS());
        $this->assertNull($props->getPRedCBS());
    }

    public function test_is_readonly(): void
    {
        $props = new CstClassTribProperties(
            cClassTrib: '100001',
            cst: '100',
            descricao: 'Test',
            validoParaNfse: true,
            permiteDiferimento: true,
            exigeGrupoTributacaoRegular: true,
        );

        $this->assertTrue($props->isPermiteDiferimento());
        $this->assertTrue($props->isExigeGrupoTributacaoRegular());
    }
}
