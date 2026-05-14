<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDocumentoReeRepRes;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsReeRepRes;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoReembolsoRepasseRessarcimento;
use PHPUnit\Framework\TestCase;

final class IbsCbsDocumentoReeRepResTest extends TestCase
{
    public function test_create_dfe_nacional(): void
    {
        $doc = new IbsCbsDocumentoReeRepRes(
            tipo: 'dFeNacional',
            dtEmiDoc: new \DateTimeImmutable('2026-01-15'),
            dtCompDoc: new \DateTimeImmutable('2026-01-15'),
            tpReeRepRes: TipoReembolsoRepasseRessarcimento::REPASSE_IMOVEIS_CORRETORES,
            vlrReeRepRes: '1500.00',
            tipoChaveDFe: '1',
            chaveDFe: '12345678901234567890123456789012345678901234567890',
        );

        $this->assertSame('dFeNacional', $doc->getTipo());
        $this->assertSame('1', $doc->getTipoChaveDFe());
        $this->assertSame('12345678901234567890123456789012345678901234567890', $doc->getChaveDFe());
        $this->assertSame('1500.00', $doc->getVlrReeRepRes());
        $this->assertSame(TipoReembolsoRepasseRessarcimento::REPASSE_IMOVEIS_CORRETORES, $doc->getTpReeRepRes());
    }

    public function test_create_doc_fiscal_outro(): void
    {
        $doc = new IbsCbsDocumentoReeRepRes(
            tipo: 'docFiscalOutro',
            dtEmiDoc: new \DateTimeImmutable('2026-02-10'),
            dtCompDoc: new \DateTimeImmutable('2026-02-10'),
            tpReeRepRes: TipoReembolsoRepasseRessarcimento::OUTROS,
            vlrReeRepRes: '800.00',
            cMunDocFiscal: '3550308',
            nDocFiscal: 'NF-e 12345',
            xDocFiscal: 'Nota Fiscal de compra',
            xTpReeRepRes: 'Outros reembolsos',
        );

        $this->assertSame('docFiscalOutro', $doc->getTipo());
        $this->assertSame('3550308', $doc->getCMunDocFiscal());
        $this->assertSame('NF-e 12345', $doc->getNDocFiscal());
        $this->assertSame('Nota Fiscal de compra', $doc->getXDocFiscal());
        $this->assertSame('Outros reembolsos', $doc->getXTpReeRepRes());
    }

    public function test_create_doc_outro(): void
    {
        $doc = new IbsCbsDocumentoReeRepRes(
            tipo: 'docOutro',
            dtEmiDoc: new \DateTimeImmutable('2026-03-01'),
            dtCompDoc: new \DateTimeImmutable('2026-03-01'),
            tpReeRepRes: TipoReembolsoRepasseRessarcimento::REEMBOLSO_PUBLICIDADE_PROD_EXTERNA,
            vlrReeRepRes: '500.00',
            nDoc: 'REC-2026-001',
            xDoc: 'Recibo de despesas',
        );

        $this->assertSame('docOutro', $doc->getTipo());
        $this->assertSame('REC-2026-001', $doc->getNDoc());
        $this->assertSame('Recibo de despesas', $doc->getXDoc());
    }

    public function test_ree_rep_res_container(): void
    {
        $doc1 = new IbsCbsDocumentoReeRepRes(
            tipo: 'dFeNacional',
            dtEmiDoc: new \DateTimeImmutable('2026-01-15'),
            dtCompDoc: new \DateTimeImmutable('2026-01-15'),
            tpReeRepRes: TipoReembolsoRepasseRessarcimento::REPASSE_IMOVEIS_CORRETORES,
            vlrReeRepRes: '1000.00',
            tipoChaveDFe: '1',
            chaveDFe: '11111111111111111111111111111111111111111111111111',
        );
        $doc2 = new IbsCbsDocumentoReeRepRes(
            tipo: 'docOutro',
            dtEmiDoc: new \DateTimeImmutable('2026-02-20'),
            dtCompDoc: new \DateTimeImmutable('2026-02-20'),
            tpReeRepRes: TipoReembolsoRepasseRessarcimento::OUTROS,
            vlrReeRepRes: '2000.00',
            nDoc: 'REC-002',
            xDoc: 'Segundo recibo',
        );

        $container = new IbsCbsReeRepRes([$doc1, $doc2]);

        $this->assertCount(2, $container->getDocumentos());
        $this->assertSame('dFeNacional', $container->getDocumentos()[0]->getTipo());
        $this->assertSame('docOutro', $container->getDocumentos()[1]->getTipo());
    }
}
