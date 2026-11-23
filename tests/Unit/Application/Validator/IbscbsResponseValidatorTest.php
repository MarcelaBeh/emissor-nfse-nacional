<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Application\Validator;

use MarcelaBeh\EmissorNfseNacional\Application\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\IbscbsResponseValidator;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Repository\FileCstClassTribRepository;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Repository\InMemoryCstClassTribRepository;
use PHPUnit\Framework\TestCase;

final class IbscbsResponseValidatorTest extends TestCase
{
    private IbscbsResponseValidator $validator;
    private IbscbsResponseValidator $validatorWithRepo;

    protected function setUp(): void
    {
        $this->validator = new IbscbsResponseValidator();
        $this->validatorWithRepo = new IbscbsResponseValidator(
            new InMemoryCstClassTribRepository(),
        );
    }

    // --- E1522/E1523: pRedutor ---

    public function test_p_redutor_ausente_sem_tp_ente_gov_passes(): void
    {
        $ibsData = ['tpEnteGov' => null];
        $nfseIbscbs = ['pRedutor' => null, 'valores' => [], 'totCIBS' => []];
        $this->validator->validate($ibsData, $nfseIbscbs);
        $this->expectNotToPerformAssertions();
    }

    public function test_p_redutor_presente_com_tp_ente_gov_passes(): void
    {
        $ibsData = ['tpEnteGov' => '1', 'cCredPres' => null, 'diferimento' => []];
        $nfseIbscbs = [
            'pRedutor' => '10.00',
            'valores' => ['vCalcAjusteBCIBSCBS' => null],
            'totCIBS' => [
                'gIBS' => null,
                'gCBS' => null,
                'gTribCompraGov' => ['pIBSUF' => '10.00', 'vIBSUF' => '100.00'],
            ],
        ];
        $this->validator->validate($ibsData, $nfseIbscbs);
        $this->expectNotToPerformAssertions();
    }

    public function test_p_redutor_presente_sem_tp_ente_gov_throws(): void
    {
        $ibsData = ['tpEnteGov' => null];
        $nfseIbscbs = ['pRedutor' => '10.00', 'valores' => [], 'totCIBS' => []];
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1522');
        $this->validator->validate($ibsData, $nfseIbscbs);
    }

    public function test_p_redutor_ausente_com_tp_ente_gov_throws(): void
    {
        $ibsData = ['tpEnteGov' => '1'];
        $nfseIbscbs = ['pRedutor' => null, 'valores' => [], 'totCIBS' => []];
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1523');
        $this->validator->validate($ibsData, $nfseIbscbs);
    }

    // --- E1560/E1561: gIBSCredPres ---

    public function test_g_ibs_cred_pres_ausente_sem_c_cred_pres_passes(): void
    {
        $ibsData = ['cCredPres' => null];
        $nfse = $this->makeNfse(gIbsCredPres: null);
        $this->validator->validate($ibsData, $nfse);
        $this->expectNotToPerformAssertions();
    }

    public function test_g_ibs_cred_pres_presente_com_c_cred_pres_passes(): void
    {
        $ibsData = ['cCredPres' => '01'];
        $nfse = $this->makeNfse(
            gIbsCredPres: ['pCredPresIBS' => '10.00', 'vCredPresIBS' => '100.00'],
            gCbsCredPres: ['pCredPresCBS' => '5.00', 'vCredPresCBS' => '50.00'],
        );
        $this->validator->validate($ibsData, $nfse);
        $this->expectNotToPerformAssertions();
    }

    public function test_g_ibs_cred_pres_presente_sem_c_cred_pres_throws(): void
    {
        $ibsData = ['cCredPres' => null];
        $nfse = $this->makeNfse(gIbsCredPres: ['pCredPresIBS' => '10.00', 'vCredPresIBS' => '100.00']);
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1560');
        $this->validator->validate($ibsData, $nfse);
    }

    public function test_g_ibs_cred_pres_ausente_com_c_cred_pres_throws(): void
    {
        $ibsData = ['cCredPres' => '01'];
        $nfse = $this->makeNfse(gIbsCredPres: null);
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1561');
        $this->validator->validate($ibsData, $nfse);
    }

    // --- E1575/E1576: gCBSCredPres ---

    public function test_g_cbs_cred_pres_ausente_sem_c_cred_pres_passes(): void
    {
        $ibsData = ['cCredPres' => null];
        $nfse = $this->makeNfse(gCbsCredPres: null);
        $this->validator->validate($ibsData, $nfse);
        $this->expectNotToPerformAssertions();
    }

    public function test_g_cbs_cred_pres_presente_com_c_cred_pres_passes(): void
    {
        $ibsData = ['cCredPres' => '01'];
        $nfse = $this->makeNfse(
            gIbsCredPres: ['pCredPresIBS' => '10.00', 'vCredPresIBS' => '100.00'],
            gCbsCredPres: ['pCredPresCBS' => '5.00', 'vCredPresCBS' => '50.00'],
        );
        $this->validator->validate($ibsData, $nfse);
        $this->expectNotToPerformAssertions();
    }

    public function test_g_cbs_cred_pres_presente_sem_c_cred_pres_throws(): void
    {
        $ibsData = ['cCredPres' => null];
        $nfse = $this->makeNfse(gCbsCredPres: ['pCredPresCBS' => '5.00', 'vCredPresCBS' => '50.00']);
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1575');
        $this->validator->validate($ibsData, $nfse);
    }

    public function test_g_cbs_cred_pres_ausente_com_c_cred_pres_throws(): void
    {
        $ibsData = ['cCredPres' => '01'];
        $nfse = $this->makeNfse(gCbsCredPres: null);
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1576');
        $this->validator->validate($ibsData, $nfse);
    }

    // --- E1565/E1566: vDifUF ---

    public function test_v_dif_uf_ausente_sem_p_dif_uf_passes(): void
    {
        $ibsData = ['diferimento' => []];
        $nfse = $this->makeNfse(vDifUF: null);
        $this->validator->validate($ibsData, $nfse);
        $this->expectNotToPerformAssertions();
    }

    public function test_v_dif_uf_presente_com_p_dif_uf_passes(): void
    {
        $ibsData = ['diferimento' => ['pDifUF' => 10.0]];
        $nfse = $this->makeNfse(vDifUF: '50.00');
        $this->validator->validate($ibsData, $nfse);
        $this->expectNotToPerformAssertions();
    }

    public function test_v_dif_uf_presente_sem_p_dif_uf_throws(): void
    {
        $ibsData = ['diferimento' => []];
        $nfse = $this->makeNfse(vDifUF: '50.00');
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1565');
        $this->validator->validate($ibsData, $nfse);
    }

    public function test_v_dif_uf_ausente_com_p_dif_uf_throws(): void
    {
        $ibsData = ['diferimento' => ['pDifUF' => 10.0]];
        $nfse = $this->makeNfse(vDifUF: null);
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1566');
        $this->validator->validate($ibsData, $nfse);
    }

    public function test_diferimento_parcial_por_esfera_nao_gera_falso_positivo(): void
    {
        // Regressão: diferimento só de UF (pDifUF=10, pDifMun=0, pDifCBS=0). buildIbsDataFromDps
        // preenche os 3 pDif* (float não-nulável), mas pDif=0 significa "não diferido" naquela esfera,
        // e a SEFIN não retorna vDifMun/vDifCBS. Antes da correção isso disparava E1570/E1580 falsos.
        $ibsData = ['diferimento' => ['pDifUF' => 10.0, 'pDifMun' => 0.0, 'pDifCBS' => 0.0]];
        $nfse = $this->makeNfse(vDifUF: '50.00', vDifMun: null, vDifCBS: null);

        $this->validator->validate($ibsData, $nfse);

        $this->expectNotToPerformAssertions();
    }

    // --- E1569/E1570: vDifMun ---

    public function test_v_dif_mun_ausente_sem_p_dif_mun_passes(): void
    {
        $ibsData = ['diferimento' => []];
        $nfse = $this->makeNfse(vDifMun: null);
        $this->validator->validate($ibsData, $nfse);
        $this->expectNotToPerformAssertions();
    }

    public function test_v_dif_mun_presente_com_p_dif_mun_passes(): void
    {
        $ibsData = ['diferimento' => ['pDifMun' => 5.0]];
        $nfse = $this->makeNfse(vDifMun: '25.00');
        $this->validator->validate($ibsData, $nfse);
        $this->expectNotToPerformAssertions();
    }

    public function test_v_dif_mun_presente_sem_p_dif_mun_throws(): void
    {
        $ibsData = ['diferimento' => []];
        $nfse = $this->makeNfse(vDifMun: '25.00');
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1569');
        $this->validator->validate($ibsData, $nfse);
    }

    public function test_v_dif_mun_ausente_com_p_dif_mun_throws(): void
    {
        $ibsData = ['diferimento' => ['pDifMun' => 5.0]];
        $nfse = $this->makeNfse(vDifMun: null);
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1570');
        $this->validator->validate($ibsData, $nfse);
    }

    // --- E1579/E1580: vDifCBS ---

    public function test_v_dif_cbs_ausente_sem_p_dif_cbs_passes(): void
    {
        $ibsData = ['diferimento' => []];
        $nfse = $this->makeNfse(vDifCBS: null);
        $this->validator->validate($ibsData, $nfse);
        $this->expectNotToPerformAssertions();
    }

    public function test_v_dif_cbs_presente_com_p_dif_cbs_passes(): void
    {
        $ibsData = ['diferimento' => ['pDifCBS' => 8.0]];
        $nfse = $this->makeNfse(vDifCBS: '40.00');
        $this->validator->validate($ibsData, $nfse);
        $this->expectNotToPerformAssertions();
    }

    public function test_v_dif_cbs_presente_sem_p_dif_cbs_throws(): void
    {
        $ibsData = ['diferimento' => []];
        $nfse = $this->makeNfse(vDifCBS: '40.00');
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1579');
        $this->validator->validate($ibsData, $nfse);
    }

    public function test_v_dif_cbs_ausente_com_p_dif_cbs_throws(): void
    {
        $ibsData = ['diferimento' => ['pDifCBS' => 8.0]];
        $nfse = $this->makeNfse(vDifCBS: null);
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1580');
        $this->validator->validate($ibsData, $nfse);
    }

    // --- E1600/E1601: gTribCompraGov ---

    public function test_g_trib_compra_gov_ausente_sem_tp_ente_gov_passes(): void
    {
        $ibsData = ['tpEnteGov' => null];
        $nfse = $this->makeNfse(gTribCompraGov: null);
        $this->validator->validate($ibsData, $nfse);
        $this->expectNotToPerformAssertions();
    }

    public function test_g_trib_compra_gov_presente_com_tp_ente_gov_passes(): void
    {
        $ibsData = ['tpEnteGov' => '1', 'cCredPres' => null, 'diferimento' => []];
        $nfse = $this->makeNfse(
            pRedutor: '10.00',
            gIbsCredPres: null,
            gCbsCredPres: null,
            gTribCompraGov: ['pIBSUF' => '10.00', 'vIBSUF' => '100.00'],
        );
        $this->validator->validate($ibsData, $nfse);
        $this->expectNotToPerformAssertions();
    }

    public function test_g_trib_compra_gov_presente_sem_tp_ente_gov_throws(): void
    {
        $ibsData = ['tpEnteGov' => null];
        $nfse = $this->makeNfse(gTribCompraGov: ['pIBSUF' => '10.00']);
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1600');
        $this->validator->validate($ibsData, $nfse);
    }

    public function test_g_trib_compra_gov_ausente_com_tp_ente_gov_throws(): void
    {
        $ibsData = ['tpEnteGov' => '1'];
        $nfse = $this->makeNfse(gTribCompraGov: null);
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1601');
        $this->validator->validate($ibsData, $nfse);
    }

    // --- vCalcAjusteBCIBSCBS: E1533, E1534, E1535 ---

    public function test_v_calc_ajuste_ibscbs_ausente_sem_documentos_passes(): void
    {
        $ibsData = ['vServ' => '1000.00', 'temAjusteIbsCbs' => false];
        $this->validator->validate($ibsData, $this->makeNfse(vCalcAjusteBCIBSCBS: null));
        $this->expectNotToPerformAssertions();
    }

    public function test_v_calc_ajuste_ibscbs_zero_sem_documentos_passes(): void
    {
        // A SEFIN devolve 0.00 por padrão quando não há ajuste: zero não conta como informado.
        $ibsData = ['vServ' => '1000.00', 'temAjusteIbsCbs' => false];
        $this->validator->validate($ibsData, $this->makeNfse(vCalcAjusteBCIBSCBS: '0.00'));
        $this->expectNotToPerformAssertions();
    }

    public function test_v_calc_ajuste_ibscbs_igual_a_soma_dos_documentos_passes(): void
    {
        $ibsData = ['vServ' => '1000.00', 'temAjusteIbsCbs' => true, 'somaAjusteIbsCbs' => 500.0];
        $this->validator->validate($ibsData, $this->makeNfse(vCalcAjusteBCIBSCBS: '500.00'));
        $this->expectNotToPerformAssertions();
    }

    public function test_v_calc_ajuste_ibscbs_aceita_diferenca_de_um_centavo(): void
    {
        $ibsData = ['vServ' => '1000.00', 'temAjusteIbsCbs' => true, 'somaAjusteIbsCbs' => 500.0];
        $this->validator->validate($ibsData, $this->makeNfse(vCalcAjusteBCIBSCBS: '500.01'));
        $this->expectNotToPerformAssertions();
    }

    public function test_v_calc_ajuste_ibscbs_ausente_com_documentos_throws(): void
    {
        $ibsData = ['vServ' => '1000.00', 'temAjusteIbsCbs' => true, 'somaAjusteIbsCbs' => 500.0];
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1533');
        $this->validator->validate($ibsData, $this->makeNfse(vCalcAjusteBCIBSCBS: null));
    }

    public function test_v_calc_ajuste_ibscbs_zero_com_documentos_throws(): void
    {
        // Com documentos de ajuste na DPS espera-se valor maior que zero; zero equivale a ausente.
        $ibsData = ['vServ' => '1000.00', 'temAjusteIbsCbs' => true, 'somaAjusteIbsCbs' => 500.0];
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1533');
        $this->validator->validate($ibsData, $this->makeNfse(vCalcAjusteBCIBSCBS: '0.00'));
    }

    public function test_v_calc_ajuste_ibscbs_diferente_da_soma_throws(): void
    {
        $ibsData = ['vServ' => '1000.00', 'temAjusteIbsCbs' => true, 'somaAjusteIbsCbs' => 500.0];
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1535');
        $this->validator->validate($ibsData, $this->makeNfse(vCalcAjusteBCIBSCBS: '400.00'));
    }

    public function test_v_calc_ajuste_ibscbs_igual_ou_maior_que_vserv_throws(): void
    {
        $ibsData = ['vServ' => '1000.00', 'temAjusteIbsCbs' => true, 'somaAjusteIbsCbs' => 1000.0];
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1534');
        $this->validator->validate($ibsData, $this->makeNfse(vCalcAjusteBCIBSCBS: '1000.00'));
    }

    // --- vCalcAjusteBCLocImoveis: E1537, E1543, E1547, E1579 ---

    public function test_v_calc_loc_imoveis_ausente_sem_ajustes_passes(): void
    {
        $ibsData = ['vServ' => '1000.00', 'temAjusteLocImoveis' => false];
        $this->validator->validate($ibsData, $this->makeNfse(vCalcAjusteBCLocImoveis: null));
        $this->expectNotToPerformAssertions();
    }

    public function test_v_calc_loc_imoveis_zero_sem_ajustes_passes(): void
    {
        $ibsData = ['vServ' => '1000.00', 'temAjusteLocImoveis' => false];
        $this->validator->validate($ibsData, $this->makeNfse(vCalcAjusteBCLocImoveis: '0.00'));
        $this->expectNotToPerformAssertions();
    }

    public function test_v_calc_loc_imoveis_igual_ao_esperado_passes(): void
    {
        $ibsData = ['vServ' => '500.00', 'temAjusteLocImoveis' => true, 'somaAjusteLocImoveis' => 50.0];
        $this->validator->validate($ibsData, $this->makeNfse(vCalcAjusteBCLocImoveis: '50.00'));
        $this->expectNotToPerformAssertions();
    }

    public function test_v_calc_loc_imoveis_igual_a_vserv_passes(): void
    {
        // E1547 admite igualdade (menor ou igual), diferente de E1534.
        $ibsData = ['vServ' => '500.00', 'temAjusteLocImoveis' => true, 'somaAjusteLocImoveis' => 500.0];
        $this->validator->validate($ibsData, $this->makeNfse(vCalcAjusteBCLocImoveis: '500.00'));
        $this->expectNotToPerformAssertions();
    }

    public function test_v_calc_loc_imoveis_sem_ajustes_na_dps_throws(): void
    {
        $ibsData = ['vServ' => '1000.00', 'temAjusteLocImoveis' => false];
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1537');
        $this->validator->validate($ibsData, $this->makeNfse(vCalcAjusteBCLocImoveis: '50.00'));
    }

    public function test_v_calc_loc_imoveis_ausente_com_ajustes_na_dps_throws(): void
    {
        $ibsData = ['vServ' => '1000.00', 'temAjusteLocImoveis' => true, 'somaAjusteLocImoveis' => 50.0];
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1543');
        $this->validator->validate($ibsData, $this->makeNfse(vCalcAjusteBCLocImoveis: null));
    }

    public function test_v_calc_loc_imoveis_maior_que_vserv_throws(): void
    {
        $ibsData = ['vServ' => '100.00', 'temAjusteLocImoveis' => true, 'somaAjusteLocImoveis' => 150.0];
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1547');
        $this->validator->validate($ibsData, $this->makeNfse(vCalcAjusteBCLocImoveis: '150.00'));
    }

    public function test_v_calc_loc_imoveis_diferente_do_esperado_throws(): void
    {
        $ibsData = ['vServ' => '500.00', 'temAjusteLocImoveis' => true, 'somaAjusteLocImoveis' => 50.0];
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1579');
        $this->validator->validate($ibsData, $this->makeNfse(vCalcAjusteBCLocImoveis: '75.00'));
    }

    // --- Múltiplos erros ---

    public function test_multiple_validation_errors(): void
    {
        $ibsData = [
            'tpEnteGov' => null,
            'cCredPres' => null,
            'diferimento' => [],
        ];
        $nfse = $this->makeNfse(
            pRedutor: '10.00',
            gIbsCredPres: ['pCredPresIBS' => '10.00', 'vCredPresIBS' => '100.00'],
            gCbsCredPres: ['pCredPresCBS' => '5.00', 'vCredPresCBS' => '50.00'],
            vDifUF: '50.00',
            vDifMun: '25.00',
            vDifCBS: '40.00',
            gTribCompraGov: ['pIBSUF' => '10.00'],
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1522');
        $this->expectExceptionMessage('E1560');
        $this->expectExceptionMessage('E1575');
        $this->expectExceptionMessage('E1565');
        $this->expectExceptionMessage('E1569');
        $this->expectExceptionMessage('E1579');
        $this->expectExceptionMessage('E1600');
        $this->validator->validate($ibsData, $nfse);
    }

    // --- E1540/E1541/E1545/E1546/E1550/E1551: pRedAliq ---

    public function test_p_red_aliq_ausente_quando_sem_reducao_passes(): void
    {
        $ibsData = ['cClassTrib' => '000001'];
        $nfse = $this->makeNfse(pRedAliqUF: null, pRedAliqMun: null, pRedAliqCBS: null);
        $this->validatorWithRepo->validate($ibsData, $nfse);
        $this->expectNotToPerformAssertions();
    }

    public function test_p_red_aliq_presente_quando_com_reducao_passes(): void
    {
        $ibsData = ['cClassTrib' => '200001'];
        $nfse = $this->makeNfse(
            pRedAliqUF: '100.00',
            pRedAliqMun: '100.00',
            pRedAliqCBS: '100.00',
        );
        $this->validatorWithRepo->validate($ibsData, $nfse);
        $this->expectNotToPerformAssertions();
    }

    public function test_p_red_aliq_uf_presente_sem_reducao_throws(): void
    {
        $ibsData = ['cClassTrib' => '000001'];
        $nfse = $this->makeNfse(pRedAliqUF: '50.00');
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1540');
        $this->validatorWithRepo->validate($ibsData, $nfse);
    }

    public function test_p_red_aliq_uf_ausente_com_reducao_throws(): void
    {
        $customRepo = new InMemoryCstClassTribRepository([
            '299001' => new \MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CstClassTribProperties(
                cClassTrib: '299001',
                cst: '299',
                descricao: 'Test code with reduction',
                validoParaNfse: true,
                permiteDiferimento: false,
                exigeGrupoTributacaoRegular: false,
                pRedIBS: 100.0,
                pRedCBS: 100.0,
            ),
        ]);
        $validator = new IbscbsResponseValidator($customRepo);
        $ibsData = ['cClassTrib' => '299001'];
        $nfse = $this->makeNfse(pRedAliqUF: null, pRedAliqMun: '100.00', pRedAliqCBS: '100.00');
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1541');
        $validator->validate($ibsData, $nfse);
    }

    public function test_p_red_aliq_mun_presente_sem_reducao_throws(): void
    {
        $ibsData = ['cClassTrib' => '000001'];
        $nfse = $this->makeNfse(pRedAliqMun: '50.00');
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1545');
        $this->validatorWithRepo->validate($ibsData, $nfse);
    }

    public function test_p_red_aliq_mun_ausente_com_reducao_throws(): void
    {
        $customRepo = new InMemoryCstClassTribRepository([
            '299002' => new \MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CstClassTribProperties(
                cClassTrib: '299002',
                cst: '299',
                descricao: 'Test code with reduction',
                validoParaNfse: true,
                permiteDiferimento: false,
                exigeGrupoTributacaoRegular: false,
                pRedIBS: 100.0,
                pRedCBS: 100.0,
            ),
        ]);
        $validator = new IbscbsResponseValidator($customRepo);
        $ibsData = ['cClassTrib' => '299002'];
        $nfse = $this->makeNfse(pRedAliqUF: '100.00', pRedAliqMun: null, pRedAliqCBS: '100.00');
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1546');
        $validator->validate($ibsData, $nfse);
    }

    public function test_p_red_aliq_cbs_presente_sem_reducao_throws(): void
    {
        $ibsData = ['cClassTrib' => '000001'];
        $nfse = $this->makeNfse(pRedAliqCBS: '50.00');
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1550');
        $this->validatorWithRepo->validate($ibsData, $nfse);
    }

    public function test_p_red_aliq_cbs_ausente_com_reducao_throws(): void
    {
        $customRepo = new InMemoryCstClassTribRepository([
            '299003' => new \MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CstClassTribProperties(
                cClassTrib: '299003',
                cst: '299',
                descricao: 'Test code with reduction',
                validoParaNfse: true,
                permiteDiferimento: false,
                exigeGrupoTributacaoRegular: false,
                pRedIBS: 100.0,
                pRedCBS: 100.0,
            ),
        ]);
        $validator = new IbscbsResponseValidator($customRepo);
        $ibsData = ['cClassTrib' => '299003'];
        $nfse = $this->makeNfse(pRedAliqUF: '100.00', pRedAliqMun: '100.00', pRedAliqCBS: null);
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1551');
        $validator->validate($ibsData, $nfse);
    }

    public function test_p_red_aliq_sem_repo_ignora_validacao(): void
    {
        $ibsData = ['cClassTrib' => '000001'];
        $nfse = $this->makeNfse(pRedAliqUF: '50.00');
        $this->validator->validate($ibsData, $nfse);
        $this->expectNotToPerformAssertions();
    }

    public function test_p_red_aliq_sem_cclass_trib_ignora(): void
    {
        $ibsData = [];
        $nfse = $this->makeNfse(pRedAliqUF: '50.00');
        $this->validatorWithRepo->validate($ibsData, $nfse);
        $this->expectNotToPerformAssertions();
    }

    // --- Regressão: emissão real (cClassTrib=010002, SEFIN cStat=100) ---

    public function test_emissao_real_cclasstrib_sem_reducao_e_vcalc_zero_passes(): void
    {
        // Reproduz a NFS-e real que a SEFIN aceitou (cStat=100) mas a lib rejeitava:
        // - cClassTrib=010002 tem pRedIBS/pRedCBS=0.0 na tabela oficial (SEM redução): SEFIN não retorna pRedAliq*
        // - DPS sem documentos de ajuste, mas SEFIN devolve vCalcAjusteBCIBSCBS=0.00 por padrão
        // - DPS sem tpEnteGov, sem cCredPres, sem diferimento
        $validator = new IbscbsResponseValidator(
            new FileCstClassTribRepository(__DIR__ . '/../../../../storage/cClassTrib.json'),
        );

        $ibsData = [
            'cClassTrib' => '010002',
            'tpEnteGov' => null,
            'cCredPres' => null,
            'vServ' => '1.00',
        ];

        $nfse = [
            'pRedutor' => null,
            'valores' => [
                'vBC' => '0.95',
                'vCalcAjusteBCIBSCBS' => '0.00',
                'uf' => ['pIBSUF' => '0.00', 'pAliqEfetUF' => '0.00'],
                'mun' => ['pIBSMun' => '0.00', 'pAliqEfetMun' => '0.00'],
                'fed' => ['pCBS' => '0.00', 'pAliqEfetCBS' => '0.00'],
            ],
            'totCIBS' => [
                'vTotNF' => '0.96',
                'gIBS' => [
                    'vIBSTot' => '0.00',
                    'gIBSUFTot' => ['vIBSUF' => '0.00'],
                    'gIBSMunTot' => ['vIBSMun' => '0.00'],
                ],
                'gCBS' => ['vCBS' => '0.00'],
            ],
        ];

        $validator->validate($ibsData, $nfse);
        $this->expectNotToPerformAssertions();
    }

    private function makeNfse(
        ?string $pRedutor = null,
        ?array $gIbsCredPres = null,
        ?array $gCbsCredPres = null,
        ?string $vDifUF = null,
        ?string $vDifMun = null,
        ?string $vDifCBS = null,
        ?array $gTribCompraGov = null,
        ?string $vCalcAjusteBCIBSCBS = null,
        ?string $vCalcAjusteBCLocImoveis = null,
        ?string $pRedAliqUF = null,
        ?string $pRedAliqMun = null,
        ?string $pRedAliqCBS = null,
    ): array {
        $gIbs = ['vIBSTot' => '500.00'];
        if ($gIbsCredPres !== null) {
            $gIbs['gIBSCredPres'] = $gIbsCredPres;
        }
        $gIbs['gIBSUFTot'] = ['vDifUF' => $vDifUF, 'vIBSUF' => '300.00'];
        $gIbs['gIBSMunTot'] = ['vDifMun' => $vDifMun, 'vIBSMun' => '200.00'];

        $gCbs = ['vCBS' => '100.00'];
        if ($gCbsCredPres !== null) {
            $gCbs['gCBSCredPres'] = $gCbsCredPres;
        }
        $gCbs['vDifCBS'] = $vDifCBS;

        $uf = ['pIBSUF' => '10.00'];
        if ($pRedAliqUF !== null) {
            $uf['pRedAliqUF'] = $pRedAliqUF;
        }

        $mun = ['pIBSMun' => '5.00'];
        if ($pRedAliqMun !== null) {
            $mun['pRedAliqMun'] = $pRedAliqMun;
        }

        $fed = ['pCBS' => '8.00'];
        if ($pRedAliqCBS !== null) {
            $fed['pRedAliqCBS'] = $pRedAliqCBS;
        }

        return [
            'pRedutor' => $pRedutor,
            'valores' => [
                'vBC' => '1000.00',
                'vCalcAjusteBCIBSCBS' => $vCalcAjusteBCIBSCBS,
                'vCalcAjusteBCLocImoveis' => $vCalcAjusteBCLocImoveis,
                'uf' => $uf,
                'mun' => $mun,
                'fed' => $fed,
            ],
            'totCIBS' => [
                'vTotNF' => '1500.00',
                'gIBS' => $gIbs,
                'gCBS' => $gCbs,
                'gTribCompraGov' => $gTribCompraGov,
            ],
        ];
    }
}
