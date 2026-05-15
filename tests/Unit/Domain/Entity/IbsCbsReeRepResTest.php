<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDocumentoReeRepRes;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsReeRepRes;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoReembolsoRepasseRessarcimento;
use PHPUnit\Framework\TestCase;

final class IbsCbsReeRepResTest extends TestCase
{
    private function createDocumento(): IbsCbsDocumentoReeRepRes
    {
        return new IbsCbsDocumentoReeRepRes(
            tipo: '01',
            dtEmiDoc: new \DateTimeImmutable('2026-05-15'),
            dtCompDoc: new \DateTimeImmutable('2026-05-01'),
            tpReeRepRes: TipoReembolsoRepasseRessarcimento::REPASSE_IMOVEIS_CORRETORES,
            vlrReeRepRes: '1000.00',
        );
    }

    public function test_create_with_documentos(): void
    {
        $doc1 = $this->createDocumento();
        $doc2 = new IbsCbsDocumentoReeRepRes(
            tipo: '02',
            dtEmiDoc: new \DateTimeImmutable('2026-05-16'),
            dtCompDoc: new \DateTimeImmutable('2026-05-01'),
            tpReeRepRes: TipoReembolsoRepasseRessarcimento::REPASSE_FORNECEDOR_TURISMO,
            vlrReeRepRes: '2000.00',
        );

        $reeRepRes = new IbsCbsReeRepRes(documentos: [$doc1, $doc2]);

        $this->assertCount(2, $reeRepRes->getDocumentos());
        $this->assertSame($doc1, $reeRepRes->getDocumentos()[0]);
        $this->assertSame($doc2, $reeRepRes->getDocumentos()[1]);
    }

    public function test_create_with_empty_documentos(): void
    {
        $reeRepRes = new IbsCbsReeRepRes(documentos: []);
        $this->assertEmpty($reeRepRes->getDocumentos());
    }

    public function test_get_documentos_returns_array(): void
    {
        $doc = $this->createDocumento();
        $reeRepRes = new IbsCbsReeRepRes(documentos: [$doc]);

        $this->assertIsArray($reeRepRes->getDocumentos());
    }
}
