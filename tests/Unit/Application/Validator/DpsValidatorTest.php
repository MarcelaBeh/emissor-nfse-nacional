<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Application\Validator;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\DpsRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsDestRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsDiferimentoRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsDocumentoReeRepResRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsEnderecoObraRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsImovelRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsReeRepResRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsTribRegularRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\ObraRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\PrestadorRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\ServicoRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\TomadorRequest;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\DpsValidator;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Repository\InMemoryCstClassTribRepository;
use PHPUnit\Framework\TestCase;

final class DpsValidatorTest extends TestCase
{
    private DpsValidator $validator;
    private DpsValidator $validatorWithRepo;

    protected function setUp(): void
    {
        $this->validator = new DpsValidator();
        $this->validatorWithRepo = new DpsValidator(
            new InMemoryCstClassTribRepository(),
        );
    }

    public function test_valid_basic_dps_passes(): void
    {
        $request = $this->createValidDpsRequest();

        $this->validator->validate($request);

        $this->expectNotToPerformAssertions();
    }

    public function test_invalid_tipo_ambiente_throws(): void
    {
        $request = $this->createValidDpsRequest(tipoAmbiente: 3);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Tipo de ambiente inválido');
        $this->validator->validate($request);
    }

    public function test_invalid_serie_throws(): void
    {
        $request = $this->createValidDpsRequest(serie: 0);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Série deve ser maior que zero');
        $this->validator->validate($request);
    }

    public function test_invalid_numero_throws(): void
    {
        $request = $this->createValidDpsRequest(numero: 0);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Número deve ser maior que zero');
        $this->validator->validate($request);
    }

    public function test_empty_versao_aplicacao_throws(): void
    {
        $request = $this->createValidDpsRequest(versaoAplicacao: '');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Versão da aplicação é obrigatória');
        $this->validator->validate($request);
    }

    public function test_empty_prestador_razao_social_throws(): void
    {
        $request = $this->createValidDpsRequest(prestadorRazaoSocial: '');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Razão social do prestador é obrigatória');
        $this->validator->validate($request);
    }

    public function test_empty_tomador_razao_social_throws(): void
    {
        $request = $this->createValidDpsRequest(tomadorRazaoSocial: '');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Razão social do tomador é obrigatória');
        $this->validator->validate($request);
    }

    public function test_empty_discriminacao_throws(): void
    {
        $request = $this->createValidDpsRequest(discriminacao: '');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Discriminação do serviço é obrigatória');
        $this->validator->validate($request);
    }

    public function test_aliquota_iss_above_100_throws(): void
    {
        $request = $this->createValidDpsRequest(aliquotaIss: 101);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Alíquota ISS deve estar entre 0 e 100');
        $this->validator->validate($request);
    }

    public function test_aliquota_iss_below_0_throws(): void
    {
        $request = $this->createValidDpsRequest(aliquotaIss: -1);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Alíquota ISS deve estar entre 0 e 100');
        $this->validator->validate($request);
    }

    public function test_valor_servicos_zero_throws(): void
    {
        $request = $this->createValidDpsRequest(valorServicos: 0);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Valor dos serviços deve ser maior que zero');
        $this->validator->validate($request);
    }

    public function test_multiple_basic_errors(): void
    {
        $request = $this->createValidDpsRequest(
            tipoAmbiente: 3,
            serie: 0,
            numero: 0,
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(
            'Tipo de ambiente inválido; Série deve ser maior que zero; Número deve ser maior que zero'
        );
        $this->validator->validate($request);
    }

    // --- IBS/CBS validation tests ---

    public function test_valid_ibscbs_passes(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs();

        $this->validator->validate($request);

        $this->expectNotToPerformAssertions();
    }

    public function test_ibscbs_data_competencia_before_2026_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            dataCompetencia: '2025-06-01'
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E0850');
        $this->validator->validate($request);
    }

    public function test_ibscbs_data_competencia_2026_01_01_passes(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            dataCompetencia: '2026-01-01'
        );

        $this->validator->validate($request);

        $this->expectNotToPerformAssertions();
    }

    public function test_ibscbs_without_nbs_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(codigoNbs: null);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E1508');
        $this->validator->validate($request);
    }

    public function test_ibscbs_fin_nfse_empty_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(finNFSe: '');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Finalidade da NFS-e (finNFSe) é obrigatória');
        $this->validator->validate($request);
    }

    public function test_ibscbs_fin_nfse_not_zero_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(finNFSe: '1');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Finalidade da NFS-e deve ser 0');
        $this->validator->validate($request);
    }

    public function test_ibscbs_cindop_empty_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(cIndOp: '');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cIndOp');
        $this->validator->validate($request);
    }

    public function test_ibscbs_cindop_invalid_format_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(cIndOp: '12345');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cIndOp deve ter exatamente 6 dígitos');
        $this->validator->validate($request);
    }

    public function test_ibscbs_inddest_empty_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(indDest: '');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Indicador de destinação (indDest)');
        $this->validator->validate($request);
    }

    public function test_ibscbs_cst_empty_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(cst: '');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('CST');
        $this->validator->validate($request);
    }

    public function test_ibscbs_cst_invalid_format_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(cst: '12');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('CST deve ter exatamente 3 dígitos');
        $this->validator->validate($request);
    }

    public function test_ibscbs_cclasstrib_empty_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(cClassTrib: '');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cClassTrib');
        $this->validator->validate($request);
    }

    public function test_ibscbs_cclasstrib_invalid_format_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(cClassTrib: '12345');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cClassTrib deve ter exatamente 6 dígitos');
        $this->validator->validate($request);
    }

    public function test_ibscbs_ccredpres_invalid_format_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(cCredPres: '123');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cCredPres deve ter exatamente 2 dígitos');
        $this->validator->validate($request);
    }

    public function test_ibscbs_ccredpres_valid_format_passes(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(cCredPres: '01');

        $this->validator->validate($request);

        $this->expectNotToPerformAssertions();
    }

    // E0959: cClassTrib first 3 digits must equal CST
    public function test_ibscbs_cclasstrib_mismatch_cst_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            cst: '100',
            cClassTrib: '200123'
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E0959');
        $this->validator->validate($request);
    }

    public function test_ibscbs_cclasstrib_matches_cst_passes(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            cst: '100',
            cClassTrib: '100123'
        );

        $this->validator->validate($request);

        $this->expectNotToPerformAssertions();
    }

    // E0970: cClassTribReg first 3 digits must equal CSTReg
    public function test_ibscbs_cclasstribreg_mismatch_cstreg_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            cst: '100',
            cClassTrib: '100123',
            tribRegular: new IbsCbsTribRegularRequest(
                cstReg: '200',
                cClassTribReg: '300456',
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E0970');
        $this->validator->validate($request);
    }

    public function test_ibscbs_cclasstribreg_matches_cstreg_passes(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            cst: '100',
            cClassTrib: '100123',
            tribRegular: new IbsCbsTribRegularRequest(
                cstReg: '200',
                cClassTribReg: '200456',
            ),
        );

        $this->validator->validate($request);

        $this->expectNotToPerformAssertions();
    }

    // E0910: indDest=0 → dest must be null
    public function test_ibscbs_inddest_zero_with_dest_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            indDest: '0',
            dest: new IbsCbsDestRequest(
                xNome: 'Destinatário Teste',
                cnpj: '11444777000161',
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E0910');
        $this->validator->validate($request);
    }

    // E0910: indDest=1 → dest must be provided
    public function test_ibscbs_inddest_one_without_dest_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            indDest: '1',
            dest: null,
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E0910');
        $this->validator->validate($request);
    }

    public function test_ibscbs_inddest_zero_without_dest_passes(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            indDest: '0',
            dest: null,
        );

        $this->validator->validate($request);

        $this->expectNotToPerformAssertions();
    }

    public function test_ibscbs_inddest_one_with_dest_passes(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            indDest: '1',
            dest: new IbsCbsDestRequest(
                xNome: 'Destinatário Teste',
                cnpj: '11444777000161',
            ),
        );

        $this->validator->validate($request);

        $this->expectNotToPerformAssertions();
    }

    public function test_ibscbs_dest_without_xnome_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            indDest: '1',
            dest: new IbsCbsDestRequest(
                xNome: '',
                cnpj: '11444777000161',
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Nome do destinatário');
        $this->validator->validate($request);
    }

    public function test_ibscbs_with_diferimento_passes(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            diferimento: new IbsCbsDiferimentoRequest(
                pDifUF: 10.0,
                pDifMun: 5.0,
                pDifCBS: 8.0,
            ),
        );

        $this->validator->validate($request);

        $this->expectNotToPerformAssertions();
    }

    public function test_ibscbs_with_all_optional_fields_passes(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            indDest: '1',
            indFinal: '1',
            tpOper: '1',
            tpEnteGov: '1',
            cCredPres: '01',
            dest: new IbsCbsDestRequest(
                xNome: 'Destinatário Teste',
                cnpj: '11444777000161',
                logradouro: 'Rua Teste',
                numero: '123',
                bairro: 'Centro',
                codigoMunicipio: '3550308',
                uf: 'SP',
                cep: '01001001',
                fone: '11999999999',
                email: 'teste@teste.com',
            ),
            tribRegular: new IbsCbsTribRegularRequest(
                cstReg: '100',
                cClassTribReg: '100456',
            ),
            diferimento: new IbsCbsDiferimentoRequest(
                pDifUF: 10.0,
                pDifMun: 5.0,
                pDifCBS: 8.0,
            ),
        );

        $this->validator->validate($request);

        $this->expectNotToPerformAssertions();
    }

    public function test_ibscbs_without_ibscbs_does_not_validate_ibscbs_rules(): void
    {
        $request = $this->createValidDpsRequest(ibscbs: null);

        $this->validator->validate($request);

        $this->expectNotToPerformAssertions();
    }

    // --- cClassTrib repository validation tests ---

    public function test_ibscbs_cclasstrib_not_found_in_table_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            cClassTrib: '999999',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('não encontrado na tabela oficial');
        $this->validatorWithRepo->validate($request);
    }

    public function test_ibscbs_cclasstrib_not_valid_for_nfse_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            cst: '620',
            cClassTrib: '620001',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('não é suportado para operações de prestação de serviços');
        $this->validatorWithRepo->validate($request);
    }

    public function test_ibscbs_cclasstrib_with_diferimento_when_not_allowed_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            cClassTrib: '000001',
            diferimento: new IbsCbsDiferimentoRequest(
                pDifUF: 10.0,
                pDifMun: 5.0,
                pDifCBS: 8.0,
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('não deve ser informado');
        $this->validatorWithRepo->validate($request);
    }

    public function test_ibscbs_cclasstrib_without_diferimento_when_required_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            cst: '510',
            cClassTrib: '510001',
            diferimento: null,
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('deve ser informado');
        $this->validatorWithRepo->validate($request);
    }

    public function test_ibscbs_cclasstrib_with_tribregular_when_not_required_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            cClassTrib: '000001',
            tribRegular: new IbsCbsTribRegularRequest(
                cstReg: '100',
                cClassTribReg: '100001',
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('não deve ser informado');
        $this->validatorWithRepo->validate($request);
    }

    public function test_ibscbs_cclasstrib_without_tribregular_when_required_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            cClassTrib: '100002',
            tribRegular: null,
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('deve ser informado');
        $this->validatorWithRepo->validate($request);
    }

    public function test_ibscbs_cclasstribreg_not_found_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            cClassTrib: '100001',
            cst: '100',
            tribRegular: new IbsCbsTribRegularRequest(
                cstReg: '100',
                cClassTribReg: '999999',
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('não encontrado na tabela oficial');
        $this->validatorWithRepo->validate($request);
    }

    public function test_ibscbs_with_repo_valid_data_passes(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            cst: '100',
            cClassTrib: '100001',
        );

        $this->validatorWithRepo->validate($request);

        $this->expectNotToPerformAssertions();
    }

    public function test_ibscbs_with_repo_diferimento_and_tribregular_passes(): void
    {
        $customRepo = new InMemoryCstClassTribRepository([
            '555001' => new \MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CstClassTribProperties(
                cClassTrib: '555001',
                cst: '555',
                descricao: 'Test code with both flags',
                validoParaNfse: true,
                permiteDiferimento: true,
                exigeGrupoTributacaoRegular: true,
            ),
            '100001' => new \MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CstClassTribProperties(
                cClassTrib: '100001',
                cst: '100',
                descricao: 'Regular test code',
                validoParaNfse: true,
                permiteDiferimento: false,
                exigeGrupoTributacaoRegular: false,
            ),
        ]);

        $validator = new DpsValidator($customRepo);
        $request = $this->createValidDpsRequestWithIbscbs(
            cst: '555',
            cClassTrib: '555001',
            diferimento: new IbsCbsDiferimentoRequest(
                pDifUF: 10.0,
                pDifMun: 5.0,
                pDifCBS: 8.0,
            ),
            tribRegular: new IbsCbsTribRegularRequest(
                cstReg: '100',
                cClassTribReg: '100001',
            ),
        );

        $validator->validate($request);

        $this->expectNotToPerformAssertions();
    }

    public function test_ibscbs_without_repo_still_passes(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            cst: '999',
            cClassTrib: '999999',
        );

        $this->validator->validate($request);

        $this->expectNotToPerformAssertions();
    }

    // --- gRefNFSe tests ---

    public function test_ibscbs_ref_nfse_with_tpoper_2_passes(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            tpOper: '2',
            refNFSeList: ['12345678901234567890123456789012345678901234567890'],
        );

        $this->validator->validate($request);

        $this->expectNotToPerformAssertions();
    }

    public function test_ibscbs_ref_nfse_with_tpoper_3_passes(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            tpOper: '3',
            refNFSeList: ['12345678901234567890123456789012345678901234567890'],
        );

        $this->validator->validate($request);

        $this->expectNotToPerformAssertions();
    }

    public function test_ibscbs_ref_nfse_missing_when_tpoper_2_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            tpOper: '2',
            refNFSeList: null,
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('gRefNFSe deve ser informado');
        $this->validator->validate($request);
    }

    public function test_ibscbs_ref_nfse_present_when_tpoper_1_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            tpOper: '1',
            refNFSeList: ['12345678901234567890123456789012345678901234567890'],
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('não pode ser informado');
        $this->validator->validate($request);
    }

    public function test_ibscbs_ref_nfse_present_without_tpoper_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            tpOper: null,
            refNFSeList: ['12345678901234567890123456789012345678901234567890'],
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('não pode ser informado se tpOper não foi informado');
        $this->validator->validate($request);
    }

    public function test_ibscbs_ref_nfse_invalid_format_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            tpOper: '2',
            refNFSeList: ['invalid'],
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('E0907');
        $this->validator->validate($request);
    }

    public function test_ibscbs_ref_nfse_multiple_valid_passes(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            tpOper: '3',
            refNFSeList: [
                '12345678901234567890123456789012345678901234567890',
                '22345678901234567890123456789012345678901234567890',
            ],
        );

        $this->validator->validate($request);

        $this->expectNotToPerformAssertions();
    }

    private function createValidDpsRequest(
        int $tipoAmbiente = 1,
        string $dataEmissao = '2026-06-15T10:00:00',
        string $versaoAplicacao = '1.0.0',
        int $serie = 1,
        int $numero = 123,
        string $dataCompetencia = '2026-06-01',
        int $tipoEmissao = 1,
        string $codigoMunicipioEmissor = '3550308',
        string $prestadorRazaoSocial = 'Prestador Ltda',
        string $tomadorRazaoSocial = 'Tomador Ltda',
        string $discriminacao = 'Serviço de teste',
        float $aliquotaIss = 5.0,
        float $valorServicos = 1000.0,
        ?string $codigoNbs = '12345678',
        ?IbsCbsRequest $ibscbs = null,
        ?ObraRequest $obra = null,
    ): DpsRequest {
        return new DpsRequest(
            tipoAmbiente: $tipoAmbiente,
            dataEmissao: $dataEmissao,
            versaoAplicacao: $versaoAplicacao,
            serie: $serie,
            numero: $numero,
            dataCompetencia: $dataCompetencia,
            tipoEmissao: $tipoEmissao,
            codigoMunicipioEmissor: $codigoMunicipioEmissor,
            prestador: new PrestadorRequest(
                documento: '11444777000161',
                isCnpj: true,
                inscricaoMunicipal: '123456',
                razaoSocial: $prestadorRazaoSocial,
                nomeFantasia: 'Prestador',
                telefone: null,
                email: null,
                logradouro: 'Rua A',
                numero: '100',
                complemento: null,
                bairro: 'Centro',
                codigoMunicipio: '3550308',
                uf: 'SP',
                cep: '01001001',
                regimeTributario: 1,
            ),
            tomador: new TomadorRequest(
                documento: '33444555000181',
                isCnpj: true,
                razaoSocial: $tomadorRazaoSocial,
                nomeFantasia: null,
                telefone: null,
                email: null,
                logradouro: 'Rua B',
                numero: '200',
                complemento: null,
                bairro: 'Centro',
                codigoMunicipio: '3550308',
                uf: 'SP',
                cep: '02002002',
            ),
            servico: new ServicoRequest(
                discriminacao: $discriminacao,
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: $valorServicos,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: $aliquotaIss,
                codigoNbs: $codigoNbs,
                obra: $obra,
            ),
            ibscbs: $ibscbs,
        );
    }

    public function test_obra_with_cobra_passes(): void
    {
        $request = $this->createValidDpsRequest(
            obra: new ObraRequest(cObra: 'CNO123456789'),
        );
        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    public function test_obra_with_cib_passes(): void
    {
        $request = $this->createValidDpsRequest(
            obra: new ObraRequest(cCIB: '12345678'),
        );
        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    public function test_obra_with_endereco_passes(): void
    {
        $request = $this->createValidDpsRequest(
            obra: new ObraRequest(
                endereco: new IbsCbsEnderecoObraRequest(
                    cep: '01001001',
                    xLgr: 'Rua da Obra',
                    nro: '100',
                    xBairro: 'Industrial',
                ),
            ),
        );
        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    public function test_obra_without_any_choice_throws(): void
    {
        $request = $this->createValidDpsRequest(
            obra: new ObraRequest(),
        );
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cObra, cCIB ou endereço');
        $this->validator->validate($request);
    }

    public function test_obra_with_multiple_choices_throws(): void
    {
        $request = $this->createValidDpsRequest(
            obra: new ObraRequest(
                cObra: 'CNO123',
                cCIB: '12345678',
            ),
        );
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('mutuamente exclusivos');
        $this->validator->validate($request);
    }

    public function test_obra_invalid_cib_format_throws(): void
    {
        $request = $this->createValidDpsRequest(
            obra: new ObraRequest(cCIB: '1234567'),
        );
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('8 dígitos');
        $this->validator->validate($request);
    }

    public function test_ibscbs_imovel_with_cib_passes(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            imovel: new IbsCbsImovelRequest(
                inscImobFisc: '12345',
                cCIB: '12345678',
            ),
        );
        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    public function test_ibscbs_imovel_with_endereco_passes(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            imovel: new IbsCbsImovelRequest(
                endereco: new IbsCbsEnderecoObraRequest(
                    cep: '01001001',
                    xLgr: 'Rua Teste',
                    nro: '123',
                    xBairro: 'Centro',
                ),
            ),
        );
        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    public function test_ibscbs_imovel_without_cib_or_endereco_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            imovel: new IbsCbsImovelRequest(),
        );
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cCIB ou endereço');
        $this->validator->validate($request);
    }

    public function test_ibscbs_imovel_with_cib_and_endereco_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            imovel: new IbsCbsImovelRequest(
                cCIB: '12345678',
                endereco: new IbsCbsEnderecoObraRequest(
                    xLgr: 'Rua',
                    nro: '1',
                    xBairro: 'Centro',
                ),
            ),
        );
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('mutuamente exclusivos');
        $this->validator->validate($request);
    }

    public function test_ibscbs_imovel_invalid_cib_format_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            imovel: new IbsCbsImovelRequest(cCIB: '1234567'),
        );
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('8 dígitos');
        $this->validator->validate($request);
    }

    public function test_ibscbs_ree_rep_res_valid_passes(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            reeRepRes: new IbsCbsReeRepResRequest([
                new IbsCbsDocumentoReeRepResRequest(
                    tipoDocumento: 'dFeNacional',
                    dtEmiDoc: '2026-01-15',
                    dtCompDoc: '2026-01-15',
                    tpReeRepRes: '01',
                    vlrReeRepRes: 1000.00,
                    tipoChaveDFe: '1',
                    chaveDFe: '12345678901234567890123456789012345678901234567890',
                ),
            ]),
        );
        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    public function test_ibscbs_ree_rep_res_empty_documentos_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            reeRepRes: new IbsCbsReeRepResRequest([]),
        );
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('ao menos um documento');
        $this->validator->validate($request);
    }

    public function test_ibscbs_ree_rep_res_tp99_with_descricao_passes(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            reeRepRes: new IbsCbsReeRepResRequest([
                new IbsCbsDocumentoReeRepResRequest(
                    tipoDocumento: 'dFeNacional',
                    dtEmiDoc: '2026-01-15',
                    dtCompDoc: '2026-01-15',
                    tpReeRepRes: '99',
                    vlrReeRepRes: 1000.00,
                    tipoChaveDFe: '1',
                    chaveDFe: '11111111111111111111111111111111111111111111111111',
                    xTpReeRepRes: 'Outros reembolsos diversos',
                ),
            ]),
        );
        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    public function test_ibscbs_ree_rep_res_invalid_invalid_date_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            reeRepRes: new IbsCbsReeRepResRequest([
                new IbsCbsDocumentoReeRepResRequest(
                    tipoDocumento: 'dFeNacional',
                    dtEmiDoc: '2026/01/15',
                    dtCompDoc: '2026-01-15',
                    tpReeRepRes: '01',
                    vlrReeRepRes: 1000.00,
                    tipoChaveDFe: '1',
                    chaveDFe: '11111111111111111111111111111111111111111111111111',
                ),
            ]),
        );
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('AAAA-MM-DD');
        $this->validator->validate($request);
    }

    public function test_ibscbs_ree_rep_res_vlr_negativo_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            reeRepRes: new IbsCbsReeRepResRequest([
                new IbsCbsDocumentoReeRepResRequest(
                    tipoDocumento: 'dFeNacional',
                    dtEmiDoc: '2026-01-15',
                    dtCompDoc: '2026-01-15',
                    tpReeRepRes: '01',
                    vlrReeRepRes: -100.00,
                    tipoChaveDFe: '1',
                    chaveDFe: '11111111111111111111111111111111111111111111111111',
                ),
            ]),
        );
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('maior que zero');
        $this->validator->validate($request);
    }

    public function test_ibscbs_ree_rep_res_tp99_requires_descricao(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            reeRepRes: new IbsCbsReeRepResRequest([
                new IbsCbsDocumentoReeRepResRequest(
                    tipoDocumento: 'dFeNacional',
                    dtEmiDoc: '2026-01-15',
                    dtCompDoc: '2026-01-15',
                    tpReeRepRes: '99',
                    vlrReeRepRes: 500.00,
                    tipoChaveDFe: '1',
                    chaveDFe: '11111111111111111111111111111111111111111111111111',
                ),
            ]),
        );
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('xTpReeRepRes');
        $this->validator->validate($request);
    }

    public function test_ibscbs_ree_rep_res_doc_fiscal_outro_valid(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            reeRepRes: new IbsCbsReeRepResRequest([
                new IbsCbsDocumentoReeRepResRequest(
                    tipoDocumento: 'docFiscalOutro',
                    dtEmiDoc: '2026-01-15',
                    dtCompDoc: '2026-01-15',
                    tpReeRepRes: '02',
                    vlrReeRepRes: 750.00,
                    cMunDocFiscal: '3550308',
                    nDocFiscal: 'NF-123',
                    xDocFiscal: 'Nota fiscal',
                ),
            ]),
        );
        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    public function test_ibscbs_ree_rep_res_doc_outro_valid(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            reeRepRes: new IbsCbsReeRepResRequest([
                new IbsCbsDocumentoReeRepResRequest(
                    tipoDocumento: 'docOutro',
                    dtEmiDoc: '2026-01-15',
                    dtCompDoc: '2026-01-15',
                    tpReeRepRes: '03',
                    vlrReeRepRes: 300.00,
                    nDoc: 'REC-001',
                    xDoc: 'Recibo',
                ),
            ]),
        );
        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    private function createValidDpsRequestWithIbscbs(
        string $dataCompetencia = '2026-06-01',
        ?string $codigoNbs = '12345678',
        string $finNFSe = '0',
        string $cIndOp = '100001',
        string $indDest = '0',
        string $cst = '100',
        string $cClassTrib = '100123',
        ?string $indFinal = null,
        ?string $tpOper = null,
        ?string $tpEnteGov = null,
        ?string $cCredPres = null,
        ?IbsCbsDestRequest $dest = null,
        ?IbsCbsTribRegularRequest $tribRegular = null,
        ?IbsCbsDiferimentoRequest $diferimento = null,
        ?array $refNFSeList = null,
        ?IbsCbsImovelRequest $imovel = null,
        ?IbsCbsReeRepResRequest $reeRepRes = null,
    ): DpsRequest {
        return $this->createValidDpsRequest(
            dataCompetencia: $dataCompetencia,
            codigoNbs: $codigoNbs,
            ibscbs: new IbsCbsRequest(
                finNFSe: $finNFSe,
                cIndOp: $cIndOp,
                indDest: $indDest,
                cst: $cst,
                cClassTrib: $cClassTrib,
                indFinal: $indFinal,
                tpOper: $tpOper,
                tpEnteGov: $tpEnteGov,
                cCredPres: $cCredPres,
                dest: $dest,
                tribRegular: $tribRegular,
                diferimento: $diferimento,
                refNFSeList: $refNFSeList,
                imovel: $imovel,
                reeRepRes: $reeRepRes,
            ),
        );
    }
}
