<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDest;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDiferimento;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDocumentoReeRepRes;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsImovel;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsInfo;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsReeRepRes;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsTribRegular;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\FinalidadeNfse;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\IndicadorDestinacao;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\IndicadorFinal;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEnteGovernamental;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoOperacao;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoReembolsoRepasseRessarcimento;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\ChaveAcesso;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoCIB;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoClassificacaoTributaria;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoCreditoPresumido;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoIndicadorOperacao;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoSituacaoTributaria;
use PHPUnit\Framework\TestCase;

final class IbsCbsInfoTest extends TestCase
{
    public function test_create_with_required_fields_only(): void
    {
        $entity = new IbsCbsInfo(
            finNFSe: FinalidadeNfse::REGULAR,
            cIndOp: new CodigoIndicadorOperacao('100001'),
            indDest: IndicadorDestinacao::TOMADOR,
            cst: new CodigoSituacaoTributaria('100'),
            cClassTrib: new CodigoClassificacaoTributaria('100123'),
        );

        $this->assertSame(FinalidadeNfse::REGULAR, $entity->getFinNFSe());
        $this->assertSame('100001', $entity->getCIndOp()->getCodigo());
        $this->assertSame(IndicadorDestinacao::TOMADOR, $entity->getIndDest());
        $this->assertSame('100', $entity->getCst()->getCodigo());
        $this->assertSame('100123', $entity->getCClassTrib()->getCodigo());
        $this->assertNull($entity->getIndFinal());
        $this->assertNull($entity->getTpOper());
        $this->assertNull($entity->getTpEnteGov());
        $this->assertNull($entity->getCCredPres());
        $this->assertNull($entity->getDest());
        $this->assertNull($entity->getTribRegular());
        $this->assertNull($entity->getDiferimento());
    }

    public function test_create_with_all_fields(): void
    {
        $dest = new IbsCbsDest(xNome: 'Destinatário Teste');
        $tribRegular = new IbsCbsTribRegular(
            cstReg: new CodigoSituacaoTributaria('200'),
            cClassTribReg: new CodigoClassificacaoTributaria('200456'),
        );
        $diferimento = new IbsCbsDiferimento(
            pDifUF: 10.0,
            pDifMun: 5.0,
            pDifCBS: 8.0,
        );

        $entity = new IbsCbsInfo(
            finNFSe: FinalidadeNfse::REGULAR,
            cIndOp: new CodigoIndicadorOperacao('100001'),
            indDest: IndicadorDestinacao::TERCEIRO,
            cst: new CodigoSituacaoTributaria('100'),
            cClassTrib: new CodigoClassificacaoTributaria('100123'),
            indFinal: IndicadorFinal::SIM,
            tpOper: TipoOperacao::FORNECIMENTO_POSTERIOR,
            tpEnteGov: TipoEnteGovernamental::MUNICIPIO,
            cCredPres: new CodigoCreditoPresumido('01'),
            dest: $dest,
            tribRegular: $tribRegular,
            diferimento: $diferimento,
        );

        $this->assertSame(IndicadorFinal::SIM, $entity->getIndFinal());
        $this->assertSame(TipoOperacao::FORNECIMENTO_POSTERIOR, $entity->getTpOper());
        $this->assertSame(TipoEnteGovernamental::MUNICIPIO, $entity->getTpEnteGov());
        $this->assertSame('01', $entity->getCCredPres()->getCodigo());
        $this->assertSame($dest, $entity->getDest());
        $this->assertSame($tribRegular, $entity->getTribRegular());
        $this->assertSame($diferimento, $entity->getDiferimento());
    }

    public function test_create_with_indfinal_sim(): void
    {
        $entity = new IbsCbsInfo(
            finNFSe: FinalidadeNfse::REGULAR,
            cIndOp: new CodigoIndicadorOperacao('100001'),
            indDest: IndicadorDestinacao::TOMADOR,
            cst: new CodigoSituacaoTributaria('100'),
            cClassTrib: new CodigoClassificacaoTributaria('100123'),
            indFinal: IndicadorFinal::SIM,
        );

        $this->assertSame(IndicadorFinal::SIM, $entity->getIndFinal());
    }

    public function test_create_with_indfinal_nao(): void
    {
        $entity = new IbsCbsInfo(
            finNFSe: FinalidadeNfse::REGULAR,
            cIndOp: new CodigoIndicadorOperacao('100001'),
            indDest: IndicadorDestinacao::TOMADOR,
            cst: new CodigoSituacaoTributaria('100'),
            cClassTrib: new CodigoClassificacaoTributaria('100123'),
            indFinal: IndicadorFinal::NAO,
        );

        $this->assertSame(IndicadorFinal::NAO, $entity->getIndFinal());
    }

    public function test_create_with_destinatario_intermediario(): void
    {
        $dest = new IbsCbsDest(xNome: 'Intermediário Teste');
        $entity = new IbsCbsInfo(
            finNFSe: FinalidadeNfse::REGULAR,
            cIndOp: new CodigoIndicadorOperacao('100001'),
            indDest: IndicadorDestinacao::TERCEIRO,
            cst: new CodigoSituacaoTributaria('100'),
            cClassTrib: new CodigoClassificacaoTributaria('100123'),
            dest: $dest,
        );

        $this->assertSame($dest, $entity->getDest());
        $this->assertSame('Intermediário Teste', $entity->getDest()->getXNome());
    }

    public function test_create_with_ref_nfse_list(): void
    {
        $refs = [
            new ChaveAcesso('12345678901234567890123456789012345678901234567890'),
            new ChaveAcesso('22345678901234567890123456789012345678901234567890'),
        ];

        $entity = new IbsCbsInfo(
            finNFSe: FinalidadeNfse::REGULAR,
            cIndOp: new CodigoIndicadorOperacao('100001'),
            indDest: IndicadorDestinacao::TOMADOR,
            cst: new CodigoSituacaoTributaria('100'),
            cClassTrib: new CodigoClassificacaoTributaria('100123'),
            refNFSeList: $refs,
        );

        $this->assertTrue($entity->hasRefNFSe());
        $this->assertSame($refs, $entity->getRefNFSeList());
        $this->assertCount(2, $entity->getRefNFSeList());
    }

    public function test_create_without_ref_nfse_list(): void
    {
        $entity = new IbsCbsInfo(
            finNFSe: FinalidadeNfse::REGULAR,
            cIndOp: new CodigoIndicadorOperacao('100001'),
            indDest: IndicadorDestinacao::TOMADOR,
            cst: new CodigoSituacaoTributaria('100'),
            cClassTrib: new CodigoClassificacaoTributaria('100123'),
        );

        $this->assertFalse($entity->hasRefNFSe());
        $this->assertNull($entity->getRefNFSeList());
    }

    public function test_create_with_imovel(): void
    {
        $imovel = new IbsCbsImovel(
            inscImobFisc: '12345',
            cCIB: new CodigoCIB('12345678'),
        );
        $entity = new IbsCbsInfo(
            finNFSe: FinalidadeNfse::REGULAR,
            cIndOp: new CodigoIndicadorOperacao('100001'),
            indDest: IndicadorDestinacao::TOMADOR,
            cst: new CodigoSituacaoTributaria('100'),
            cClassTrib: new CodigoClassificacaoTributaria('100123'),
            imovel: $imovel,
        );

        $this->assertSame($imovel, $entity->getImovel());
        $this->assertSame('12345', $entity->getImovel()->getInscImobFisc());
        $this->assertSame('12345678', $entity->getImovel()->getCCIB()->getCodigo());
    }

    public function test_create_with_ree_rep_res(): void
    {
        $doc = new IbsCbsDocumentoReeRepRes(
            tipo: 'dFeNacional',
            dtEmiDoc: new \DateTimeImmutable('2026-01-15'),
            dtCompDoc: new \DateTimeImmutable('2026-01-15'),
            tpReeRepRes: TipoReembolsoRepasseRessarcimento::REPASSE_IMOVEIS_CORRETORES,
            vlrReeRepRes: '1000.00',
            tipoChaveDFe: '1',
            chaveDFe: '11111111111111111111111111111111111111111111111111',
        );
        $reeRepRes = new IbsCbsReeRepRes([$doc]);
        $entity = new IbsCbsInfo(
            finNFSe: FinalidadeNfse::REGULAR,
            cIndOp: new CodigoIndicadorOperacao('100001'),
            indDest: IndicadorDestinacao::TOMADOR,
            cst: new CodigoSituacaoTributaria('100'),
            cClassTrib: new CodigoClassificacaoTributaria('100123'),
            reeRepRes: $reeRepRes,
        );

        $this->assertSame($reeRepRes, $entity->getReeRepRes());
        $this->assertCount(1, $entity->getReeRepRes()->getDocumentos());
    }
}
