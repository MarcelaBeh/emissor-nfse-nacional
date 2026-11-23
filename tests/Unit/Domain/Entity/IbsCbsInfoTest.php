<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsBemMovel;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDest;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsDiferimento;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsImovel;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsInfo;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsTribRegular;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\IndicadorDestinacao;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\IndicadorFinal;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEnteGovernamental;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoOperacao;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\ChaveAcesso;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoClassificacaoTributaria;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoCreditoPresumido;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoIndicadorOperacao;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoMunicipio;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoSituacaoTributaria;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IbsCbsInfoTest extends TestCase
{
    private function info(
        string $cst = '100',
        ?string $cIndOp = '100001',
        ?CodigoCreditoPresumido $cCredPres = null,
        ?IbsCbsTribRegular $tribRegular = null,
        ?IbsCbsDiferimento $diferimento = null,
        ?bool $informarGrupoTributacao = null,
        ?array $bensMoveis = null,
    ): IbsCbsInfo {
        return new IbsCbsInfo(
            indDest: IndicadorDestinacao::TOMADOR,
            cst: new CodigoSituacaoTributaria($cst),
            cClassTrib: new CodigoClassificacaoTributaria($cst . '123'),
            cIndOp: $cIndOp !== null ? new CodigoIndicadorOperacao($cIndOp) : null,
            cCredPres: $cCredPres,
            tribRegular: $tribRegular,
            diferimento: $diferimento,
            bensMoveis: $bensMoveis,
            informarGrupoTributacao: $informarGrupoTributacao,
        );
    }

    public function test_create_with_required_fields_only(): void
    {
        $entity = new IbsCbsInfo(
            indDest: IndicadorDestinacao::TOMADOR,
            cst: new CodigoSituacaoTributaria('100'),
            cClassTrib: new CodigoClassificacaoTributaria('100123'),
            cIndOp: new CodigoIndicadorOperacao('100001'),
        );

        $this->assertSame('100001', $entity->getCIndOp()?->getCodigo());
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
        $this->assertNull($entity->getImovel());
        $this->assertNull($entity->getBensMoveis());
    }

    public function test_create_with_all_fields(): void
    {
        $dest = new IbsCbsDest(xNome: 'Destinatário Teste');
        $tribRegular = new IbsCbsTribRegular(
            cstReg: new CodigoSituacaoTributaria('200'),
            cClassTribReg: new CodigoClassificacaoTributaria('200456'),
        );
        $diferimento = new IbsCbsDiferimento(pDifUF: 10.0, pDifMun: 5.0, pDifCBS: 8.0);

        $entity = new IbsCbsInfo(
            indDest: IndicadorDestinacao::TERCEIRO,
            cst: new CodigoSituacaoTributaria('100'),
            cClassTrib: new CodigoClassificacaoTributaria('100123'),
            cIndOp: new CodigoIndicadorOperacao('100001'),
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
        $this->assertSame('01', $entity->getCCredPres()?->getCodigo());
        $this->assertSame($dest, $entity->getDest());
        $this->assertSame($tribRegular, $entity->getTribRegular());
        $this->assertSame($diferimento, $entity->getDiferimento());
    }

    public function test_create_with_ref_nfse_list(): void
    {
        $refs = [
            new ChaveAcesso('12345678901234567890123456789012345678901234567890'),
            new ChaveAcesso('22345678901234567890123456789012345678901234567890'),
        ];

        $entity = new IbsCbsInfo(
            indDest: IndicadorDestinacao::TOMADOR,
            cst: new CodigoSituacaoTributaria('100'),
            cClassTrib: new CodigoClassificacaoTributaria('100123'),
            cIndOp: new CodigoIndicadorOperacao('100001'),
            refNFSeList: $refs,
        );

        $this->assertTrue($entity->hasRefNFSe());
        $this->assertSame($refs, $entity->getRefNFSeList());
    }

    public function test_create_without_ref_nfse_list(): void
    {
        $entity = $this->info();

        $this->assertFalse($entity->hasRefNFSe());
        $this->assertNull($entity->getRefNFSeList());
    }

    public function test_create_with_imovel(): void
    {
        $imovel = new IbsCbsImovel(cMun: new CodigoMunicipio('3550308'));
        $entity = new IbsCbsInfo(
            indDest: IndicadorDestinacao::TOMADOR,
            cst: new CodigoSituacaoTributaria('100'),
            cClassTrib: new CodigoClassificacaoTributaria('100123'),
            cIndOp: new CodigoIndicadorOperacao('100001'),
            imovel: $imovel,
        );

        $this->assertSame($imovel, $entity->getImovel());
    }

    public function test_create_with_bens_moveis(): void
    {
        $bens = [new IbsCbsBemMovel('84713012', 'Notebook', '2')];
        $entity = $this->info(bensMoveis: $bens);

        $this->assertSame($bens, $entity->getBensMoveis());
    }

    public function test_mais_de_1000_bens_moveis_lanca_excecao(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no máximo 1000');

        $this->info(bensMoveis: array_fill(0, 1001, new IbsCbsBemMovel('84713012', 'Notebook', '1')));
    }

    // ===== grupo de tributação IBS/CBS (NT 009, item 2.16) =====

    public function test_cst_comum_tem_grupo_de_tributacao(): void
    {
        $this->assertTrue($this->info(cst: '000')->temGrupoTributacao());
        $this->assertTrue($this->info(cst: '200')->temGrupoTributacao());
    }

    #[DataProvider('cstSemGrupo')]
    public function test_cst_sem_grupo_de_tributacao(string $cst): void
    {
        $this->assertFalse($this->info(cst: $cst, cIndOp: null)->temGrupoTributacao());
    }

    /** @return array<string, array{string}> */
    public static function cstSemGrupo(): array
    {
        return ['isencao' => ['400'], 'imunidade' => ['410'], 'documento especifico' => ['820']];
    }

    public function test_informar_grupo_true_forca_o_grupo_mesmo_em_cst_da_lista(): void
    {
        $this->assertTrue($this->info(cst: '400', cIndOp: null, informarGrupoTributacao: true)->temGrupoTributacao());
    }

    public function test_informar_grupo_false_suprime_o_grupo_em_cst_fora_da_lista(): void
    {
        $this->assertFalse($this->info(cst: '000', cIndOp: null, informarGrupoTributacao: false)->temGrupoTributacao());
    }

    public function test_cst_sem_grupo_nao_admite_credito_presumido(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('não podem ser informados');

        $this->info(cst: '400', cIndOp: null, cCredPres: new CodigoCreditoPresumido('01'));
    }

    public function test_cst_sem_grupo_nao_admite_diferimento(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->info(cst: '410', cIndOp: null, diferimento: new IbsCbsDiferimento(pDifUF: 10.0, pDifMun: 5.0, pDifCBS: 8.0));
    }

    public function test_cst_sem_grupo_nao_admite_tributacao_regular(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->info(cst: '820', cIndOp: null, tribRegular: new IbsCbsTribRegular(
            cstReg: new CodigoSituacaoTributaria('200'),
            cClassTribReg: new CodigoClassificacaoTributaria('200456'),
        ));
    }
}
