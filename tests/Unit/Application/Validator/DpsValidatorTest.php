<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Application\Validator;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\AtvEventoRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\BeneficioMunicipalRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\ComExteriorRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\DocDedRedRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\DpsRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\ExigSuspRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsDestRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsDiferimentoRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsDocumentoReeRepResRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsEnderecoExteriorRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsEnderecoObraRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsFornecedorRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsImovelRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsReeRepResRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsTribRegularRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\ObraRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\PrestadorRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\ServicoRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\TomadorRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\TribFederalRequest;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\DpsValidator;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\RegimeTributario;
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
        $this->expectExceptionMessage('tpAmb inválido');
        $this->validator->validate($request);
    }

    public function test_invalid_serie_throws(): void
    {
        $request = $this->createValidDpsRequest(serie: 100000);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('serie deve ser numérico de 1 a 5 dígitos');
        $this->validator->validate($request);
    }

    public function test_invalid_numero_throws(): void
    {
        $request = $this->createValidDpsRequest(numero: 0);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('nDPS deve ser numérico de 1 a 15 dígitos');
        $this->validator->validate($request);
    }

    public function test_empty_versao_aplicacao_throws(): void
    {
        $request = $this->createValidDpsRequest(versaoAplicacao: '');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('verAplic é obrigatória');
        $this->validator->validate($request);
    }

    public function test_empty_prestador_razao_social_passes(): void
    {
        $request = $this->createValidDpsRequest(prestadorRazaoSocial: '');

        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    public function test_empty_tomador_razao_social_throws(): void
    {
        $request = $this->createValidDpsRequest(tomadorRazaoSocial: '');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('xNome do tomador é obrigatória');
        $this->validator->validate($request);
    }

    public function test_empty_discriminacao_throws(): void
    {
        $request = $this->createValidDpsRequest(discriminacao: '');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('xDescServ é obrigatória');
        $this->validator->validate($request);
    }

    public function test_aliquota_iss_above_max_tsdec1v2_throws(): void
    {
        // pAliq é TSDec1V2 (máx 9.99): 10.00 tem 2 dígitos inteiros e é rejeitado pelo XSD.
        $request = $this->createValidDpsRequest(aliquotaIss: 10.0);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('pAliq deve estar entre 0 e 9.99 (TSDec1V2)');
        $this->validator->validate($request);
    }

    public function test_aliquota_iss_below_0_throws(): void
    {
        $request = $this->createValidDpsRequest(aliquotaIss: -1);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('pAliq deve estar entre 0 e 9.99 (TSDec1V2)');
        $this->validator->validate($request);
    }

    public function test_ptottribsn_acima_de_99_99_throws(): void
    {
        // pTotTribSN é TSDec2V2 (máx 99.99): 100 tem 3 dígitos inteiros e é rejeitado pelo XSD.
        $servico = new ServicoRequest(
            discriminacao: 'Serviço de teste',
            codigoTributacao: '010101',
            valorServicos: 1000.0,
            valorDeducoes: 0,
            descontoIncondicionado: 0,
            descontoCondicionado: 0,
            aliquotaIss: 5.0,
            codigoNbs: '123456789',
            totTribTipo: 'pTotTribSN',
            pTotTribSN: 100.0,
        );
        $request = $this->createValidDpsRequest(servico: $servico);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('pTotTribSN deve estar entre 0 e 99.99 (TSDec2V2)');
        $this->validator->validate($request);
    }

    public function test_tomador_exterior_sem_campos_obrigatorios_throws(): void
    {
        // Tomador com codigoPais setado (exterior) exige cEndPost/xCidade/xEstProvReg (TCEnderExt).
        $request = $this->createValidDpsRequest();
        $request = new DpsRequest(
            tipoAmbiente: $request->tipoAmbiente,
            dataEmissao: $request->dataEmissao,
            versaoAplicacao: $request->versaoAplicacao,
            serie: $request->serie,
            numero: $request->numero,
            dataCompetencia: $request->dataCompetencia,
            tipoEmissao: $request->tipoEmissao,
            codigoMunicipioEmissor: $request->codigoMunicipioEmissor,
            prestador: $request->prestador,
            servico: $request->servico,
            tomador: new TomadorRequest(
                documento: '33444555000181',
                isCnpj: true,
                razaoSocial: 'Tomador Exterior',
                telefone: null,
                email: null,
                logradouro: 'Main Street',
                numero: '200',
                complemento: null,
                bairro: 'Downtown',
                codigoMunicipio: '0000000',
                uf: 'SP',
                cep: '00000000',
                codigoPais: 'US',
                // cEndPost/xCidade/xEstProvReg ausentes de propósito
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('para endereço exterior');
        $this->validator->validate($request);
    }

    public function test_tomador_exterior_completo_passa(): void
    {
        $request = $this->createValidDpsRequest();
        $request = new DpsRequest(
            tipoAmbiente: $request->tipoAmbiente,
            dataEmissao: $request->dataEmissao,
            versaoAplicacao: $request->versaoAplicacao,
            serie: $request->serie,
            numero: $request->numero,
            dataCompetencia: $request->dataCompetencia,
            tipoEmissao: $request->tipoEmissao,
            codigoMunicipioEmissor: $request->codigoMunicipioEmissor,
            prestador: $request->prestador,
            servico: $request->servico,
            tomador: new TomadorRequest(
                documento: '33444555000181',
                isCnpj: true,
                razaoSocial: 'Tomador Exterior',
                telefone: null,
                email: null,
                logradouro: 'Main Street',
                numero: '200',
                complemento: null,
                bairro: 'Downtown',
                codigoMunicipio: '0000000',
                uf: 'SP',
                cep: '00000000',
                codigoPais: 'US',
                codigoPostalExterior: '10001',
                nomeCidadeExterior: 'New York',
                estadoProvinciaExterior: 'NY',
            ),
        );

        $this->validator->validate($request);

        $this->assertTrue(true);
    }

    public function test_valor_servicos_zero_throws(): void
    {
        $request = $this->createValidDpsRequest(valorServicos: 0);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('vServ deve ser maior que zero');
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
        $this->expectExceptionMessage('tpAmb inválido');
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
        $this->expectExceptionMessage('finNFSe é obrigatória');
        $this->validator->validate($request);
    }

    public function test_ibscbs_fin_nfse_not_zero_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(finNFSe: '1');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('finNFSe inválido');
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
        $this->expectExceptionMessage('cIndOp deve ter 6 dígitos numéricos');
        $this->validator->validate($request);
    }

    public function test_ibscbs_inddest_empty_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(indDest: '');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('indDest é obrigatório');
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
        $this->expectExceptionMessage('CST deve ter 3 dígitos numéricos');
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
        $this->expectExceptionMessage('cClassTrib deve ter 6 dígitos numéricos');
        $this->validator->validate($request);
    }

    public function test_ibscbs_ccredpres_invalid_format_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(cCredPres: '123');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cCredPres deve ter 2 dígitos numéricos');
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

    public function test_ibscbs_dest_terceiro_exterior_completo_passa(): void
    {
        // Destinatário terceiro (indDest=1) no exterior: ramo endExt do TCEndereco.
        $request = $this->createValidDpsRequestWithIbscbs(
            indDest: '1',
            dest: new IbsCbsDestRequest(
                xNome: 'Acme Inc.',
                nif: 'US-987654',
                logradouro: '5th Avenue',
                numero: '100',
                bairro: 'Manhattan',
                codigoPais: 'US',
                codigoPostalExterior: '10001',
                nomeCidadeExterior: 'New York',
                estadoProvinciaExterior: 'NY',
            ),
        );

        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    public function test_ibscbs_dest_terceiro_exterior_incompleto_throws(): void
    {
        // Exterior sem xCidade/xEstProvReg: antes escapava toda validação de endereço; agora falha.
        $request = $this->createValidDpsRequestWithIbscbs(
            indDest: '1',
            dest: new IbsCbsDestRequest(
                xNome: 'Acme Inc.',
                nif: 'US-987654',
                logradouro: '5th Avenue',
                numero: '100',
                bairro: 'Manhattan',
                codigoPais: 'US',
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('endExt');
        $this->validator->validate($request);
    }

    public function test_ibscbs_dest_exterior_e_nacional_mutuamente_exclusivos_throws(): void
    {
        // codigoPais + cMun/CEP simultâneos violam o choice endNac|endExt do TCEndereco.
        $request = $this->createValidDpsRequestWithIbscbs(
            indDest: '1',
            dest: new IbsCbsDestRequest(
                xNome: 'Acme Inc.',
                nif: 'US-987654',
                logradouro: '5th Avenue',
                numero: '100',
                bairro: 'Manhattan',
                codigoMunicipio: '3550308',
                cep: '01310100',
                codigoPais: 'US',
                codigoPostalExterior: '10001',
                nomeCidadeExterior: 'New York',
                estadoProvinciaExterior: 'NY',
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('mutuamente exclusivos');
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

    public function test_ibscbs_diferimento_todos_zero_throws(): void
    {
        // gDif com todos pDif* = 0 é semanticamente vazio: deve ser omitido, não enviado zerado.
        $request = $this->createValidDpsRequestWithIbscbs(
            diferimento: new IbsCbsDiferimentoRequest(
                pDifUF: 0.0,
                pDifMun: 0.0,
                pDifCBS: 0.0,
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('todos os percentuais');
        $this->validator->validate($request);
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
            cst: '820',
            cClassTrib: '820007',
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('não é suportado para NFS-e');
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
        $customRepo = new InMemoryCstClassTribRepository([
            '511001' => new \MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CstClassTribProperties(
                cClassTrib: '511001',
                cst: '511',
                descricao: 'Test diferimento required',
                validoParaNfse: true,
                permiteDiferimento: true,
                exigeGrupoTributacaoRegular: false,
            ),
        ]);

        $validator = new DpsValidator($customRepo);
        $request = $this->createValidDpsRequestWithIbscbs(
            cst: '511',
            cClassTrib: '511001',
            diferimento: null,
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('deve ser informado');
        $validator->validate($request);
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
        $customRepo = new InMemoryCstClassTribRepository([
            '511002' => new \MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CstClassTribProperties(
                cClassTrib: '511002',
                cst: '511',
                descricao: 'Test trib regular required',
                validoParaNfse: true,
                permiteDiferimento: false,
                exigeGrupoTributacaoRegular: true,
            ),
        ]);

        $validator = new DpsValidator($customRepo);
        $request = $this->createValidDpsRequestWithIbscbs(
            cst: '511',
            cClassTrib: '511002',
            tribRegular: null,
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('deve ser informado');
        $validator->validate($request);
    }

    public function test_ibscbs_cclasstribreg_not_found_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            cClassTrib: '000001',
            cst: '000',
            tribRegular: new IbsCbsTribRegularRequest(
                cstReg: '200',
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
            cst: '000',
            cClassTrib: '000001',
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

    // --- Novos campos Servico validation tests ---

    private function createBaseServicoRequest(
        ?string $discriminacao = 'Serviço de teste',
        ?string $codigoTributacao = '010101',
        ?string $codigoMunicipioPrestacao = '3550308',
        ?float $valorServicos = 1000.0,
        ?string $codigoNbs = '123456789',
    ): ServicoRequest {
        return new ServicoRequest(
            discriminacao: $discriminacao ?? 'Serviço de teste',
            codigoTributacao: $codigoTributacao ?? '010101',
            codigoMunicipioPrestacao: $codigoMunicipioPrestacao ?? '3550308',
            valorServicos: $valorServicos ?? 1000.0,
            codigoNbs: $codigoNbs,
            tribISSQN: '1',
            tpRetISSQN: '1',
        );
    }

    private function createServicoWith(?array $overrides = []): ServicoRequest
    {
        $s = new ServicoRequest(
            discriminacao: 'Serviço de teste',
            codigoTributacao: '010101',
            codigoMunicipioPrestacao: '3550308',
            valorServicos: 1000.0,
            codigoNbs: '123456789',
            tribISSQN: '1',
            tpRetISSQN: '1',
        );

        foreach ($overrides as $key => $value) {
            $s->$key = $value;
        }

        return $s;
    }

    public function test_codigo_pais_prestacao_invalid_format_throws(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                codigoPaisPrestacao: '12',
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cPaisPrestacao');
        $this->validator->validate($request);
    }

    public function test_codigo_pais_prestacao_valid_passes(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                codigoPaisPrestacao: 'US',
                totTribTipo: 'vTotTrib',
                tribISSQN: '1',
                tpRetISSQN: '1',
            ),
        );

        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    public function test_codigo_tributacao_municipal_invalid_format_throws(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                codigoTributacaoMunicipal: '12',
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cTribMun');
        $this->validator->validate($request);
    }

    public function test_codigo_tributacao_municipal_valid_passes(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                codigoTributacaoMunicipal: '123',
                totTribTipo: 'vTotTrib',
                tribISSQN: '1',
                tpRetISSQN: '1',
            ),
        );

        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    public function test_codigo_interno_contribuinte_too_long_throws(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                codigoInternoContribuinte: 'ABC-123',
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cIntContrib');
        $this->validator->validate($request);
    }

    public function test_valor_recebido_zero_throws(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                valorRecebido: 0,
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('vReceb');
        $this->validator->validate($request);
    }

    public function test_valor_recebido_positive_passes(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                valorRecebido: 500.0,
                totTribTipo: 'vTotTrib',
                tribISSQN: '1',
                tpRetISSQN: '1',
            ),
        );

        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    public function test_com_exterior_valid_passes(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                comExterior: new ComExteriorRequest(
                    modoPrestacao: 1,
                    vinculoPrestador: 2,
                    codigoMoeda: '840',
                    valorServicoMoeda: 1000.00,
                    mecanismoApoioPrestador: '01',
                    mecanismoApoioTomador: '01',
                    movimentacaoTemporaria: '1',
                    enviarMDIC: '0',
                ),
                totTribTipo: 'vTotTrib',
                tribISSQN: '1',
                tpRetISSQN: '1',
            ),
        );

        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    public function test_com_exterior_without_modo_throws(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                comExterior: new ComExteriorRequest(
                    vinculoPrestador: 2,
                ),
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('mdPrestacao');
        $this->validator->validate($request);
    }

    public function test_com_exterior_invalid_moeda_throws(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                comExterior: new ComExteriorRequest(
                    modoPrestacao: 1,
                    vinculoPrestador: 2,
                    codigoMoeda: 'US',
                ),
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('tpMoeda');
        $this->validator->validate($request);
    }

    public function test_atv_evento_valid_passes(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                atvEvento: new AtvEventoRequest(
                    descricao: 'Feira Tecnológica',
                    dataInicio: '2026-06-01',
                    dataFim: '2026-06-10',
                    identificacaoEvento: 'EVENTO12345678901234567890',
                ),
                totTribTipo: 'vTotTrib',
                tribISSQN: '1',
                tpRetISSQN: '1',
            ),
        );

        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    public function test_atv_evento_without_descricao_throws(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                atvEvento: new AtvEventoRequest(
                    dataInicio: '2026-06-01',
                    dataFim: '2026-06-10',
                ),
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('xNome');
        $this->validator->validate($request);
    }

    public function test_atv_evento_data_fim_before_inicio_throws(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                atvEvento: new AtvEventoRequest(
                    descricao: 'Teste',
                    dataInicio: '2026-06-10',
                    dataFim: '2026-06-01',
                ),
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('dtIni não pode ser posterior');
        $this->validator->validate($request);
    }

    public function test_info_compl_with_itens_pedido_too_long_throws(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                infoCompl: new \MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\InfoComplRequest(
                    itensPedido: [str_repeat('x', 256)],
                ),
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('xItemPed');
        $this->validator->validate($request);
    }

    public function test_info_compl_item_pedido_61_chars_viola_tsnumeroendereco(): void
    {
        // TSNumeroEndereco tem maxLength=60. 61 chars antes passava (limite errado de 255).
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                tribISSQN: '1',
                tpRetISSQN: '1',
                infoCompl: new \MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\InfoComplRequest(
                    itensPedido: [str_repeat('x', 61)],
                ),
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('xItemPed #0 deve ter 1 a 60 caracteres');
        $this->validator->validate($request);
    }

    public function test_info_compl_mais_de_99_itens_pedido_viola_maxoccurs(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                tribISSQN: '1',
                tpRetISSQN: '1',
                infoCompl: new \MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\InfoComplRequest(
                    itensPedido: array_fill(0, 100, 'item'),
                ),
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('no máximo 99 itens');
        $this->validator->validate($request);
    }

    public function test_documentos_deducao_valid_passes(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                documentosDeducao: [
                    new DocDedRedRequest(
                        tipoDocumento: 'chNFe',
                        chaveNFe: '12345678901234567890123456789012345678901234',
                        tipoDeducaoReducao: '1',
                        dataEmissaoDoc: '2026-05-15',
                        valorDedutivel: '1000.00',
                        valorDeducao: '1000.00',
                    ),
                ],
                totTribTipo: 'vTotTrib',
                tribISSQN: '1',
                tpRetISSQN: '1',
            ),
        );

        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    public function test_documentos_deducao_invalid_tipo_throws(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                documentosDeducao: [
                    new DocDedRedRequest(tipoDocumento: 'invalid'),
                ],
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('tipoDocumento inválido');
        $this->validator->validate($request);
    }

    public function test_documentos_deducao_ch_nfse_missing_chave_throws(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                documentosDeducao: [
                    new DocDedRedRequest(tipoDocumento: 'chNFSe'),
                ],
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('chNFSe');
        $this->validator->validate($request);
    }

    public function test_documentos_deducao_ch_nfse_invalid_pattern_throws(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                documentosDeducao: [
                    new DocDedRedRequest(
                        tipoDocumento: 'chNFSe',
                        chaveNFSe: '12345', // too short, needs 50 digits
                    ),
                ],
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('chNFSe deve ter 50 dígitos numéricos');
        $this->validator->validate($request);
    }

    public function test_documentos_deducao_ch_nfe_invalid_pattern_throws(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                documentosDeducao: [
                    new DocDedRedRequest(
                        tipoDocumento: 'chNFe',
                        chaveNFe: '12345', // too short, needs 44 digits
                    ),
                ],
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('chNFe deve ter 44 dígitos numéricos');
        $this->validator->validate($request);
    }

    public function test_documentos_deducao_n_doc_fisc_missing_throws(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                documentosDeducao: [
                    new DocDedRedRequest(tipoDocumento: 'nDocFisc'),
                ],
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('nDocFisc');
        $this->validator->validate($request);
    }

    public function test_documentos_deducao_tp_ded_red_invalid_throws(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                documentosDeducao: [
                    new DocDedRedRequest(
                        tipoDocumento: 'nDoc',
                        numeroDoc: 'REC-001',
                        tipoDeducaoReducao: 'invalid',
                    ),
                ],
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('tpDedRed');
        $this->validator->validate($request);
    }

    public function test_documentos_deducao_tp99_missing_desc_throws(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                documentosDeducao: [
                    new DocDedRedRequest(
                        tipoDocumento: 'nDoc',
                        numeroDoc: 'REC-001',
                        tipoDeducaoReducao: '99',
                    ),
                ],
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('xDescOutDed');
        $this->validator->validate($request);
    }

    public function test_exig_susp_valid_passes(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                exigSusp: new ExigSuspRequest(
                    tipoSuspensao: 1,
                    numeroProcesso: '123456789012345678901234567890',
                ),
                totTribTipo: 'vTotTrib',
                tribISSQN: '1',
                tpRetISSQN: '1',
            ),
        );

        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    public function test_exig_susp_invalid_tipo_throws(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                exigSusp: new ExigSuspRequest(tipoSuspensao: 99),
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('tpSusp');
        $this->validator->validate($request);
    }

    public function test_beneficio_municipal_valid_passes(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                beneficioMunicipal: new BeneficioMunicipalRequest(
                    numeroBeneficio: '12345678901234',
                ),
                totTribTipo: 'vTotTrib',
                tribISSQN: '1',
                tpRetISSQN: '1',
            ),
        );

        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    public function test_beneficio_municipal_empty_throws(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                beneficioMunicipal: new BeneficioMunicipalRequest(),
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('nBM');
        $this->validator->validate($request);
    }

    public function test_trib_federal_valid_passes(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                tribFederal: new TribFederalRequest(
                    pisCofinsCst: '01',
                    pisCofinsTipo: '1',
                    pisCofinsAliquotaPis: 1.65,
                    pisCofinsAliquotaCofins: 7.60,
                ),
                totTribTipo: 'vTotTrib',
                tribISSQN: '1',
                tpRetISSQN: '1',
            ),
        );

        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    public function test_trib_federal_invalid_cst_throws(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                tribFederal: new TribFederalRequest(pisCofinsCst: 'X'),
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('CST do PIS/COFINS');
        $this->validator->validate($request);
    }

    public function test_trib_federal_missing_tipo_throws(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                tribFederal: new TribFederalRequest(pisCofinsCst: '01'),
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('tipo do PIS/COFINS é obrigatório');
        $this->validator->validate($request);
    }

    public function test_tot_trib_invalid_tipo_throws(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                totTribTipo: 'invalid',
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('totTribTipo');
        $this->validator->validate($request);
    }

    public function test_tot_trib_p_tot_trib_missing_fields_throws(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                totTribTipo: 'pTotTrib',
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('pTotTribFed');
        $this->validator->validate($request);
    }

    public function test_tot_trib_p_tot_trib_all_fields_passes(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                totTribTipo: 'pTotTrib',
                pTotTribFed: 10.0,
                pTotTribEst: 5.0,
                pTotTribMun: 3.0,
                tribISSQN: '1',
                tpRetISSQN: '1',
            ),
        );

        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    public function test_tot_trib_ind_tot_trib_without_value_throws(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                totTribTipo: 'indTotTrib',
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('indTotTrib é obrigatório');
        $this->validator->validate($request);
    }

    public function test_tot_trib_p_tot_trib_sn_without_value_throws(): void
    {
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                totTribTipo: 'pTotTribSN',
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('pTotTribSN é obrigatório');
        $this->validator->validate($request);
    }

    // ===== Regressão auditoria NT 004 (v2.2.3): conformidade IBS/CBS contra XSD v1.01 =====

    public function test_nt004_cib_aceita_alfanumerico_de_8_chars(): void
    {
        // TSCodCIB = string length=8 SEM pattern de dígitos: 'AB12CD34' é válido.
        $request = $this->createValidDpsRequestWithIbscbs(
            imovel: new IbsCbsImovelRequest(cCIB: 'AB12CD34'),
        );
        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    public function test_nt004_fornec_sem_identificador_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            reeRepRes: new IbsCbsReeRepResRequest([
                new IbsCbsDocumentoReeRepResRequest(
                    tipoDocumento: 'docOutro',
                    dtEmiDoc: '2026-01-15',
                    dtCompDoc: '2026-01-15',
                    tpReeRepRes: '01',
                    vlrReeRepRes: 100.00,
                    nDoc: 'DOC-1',
                    xDoc: 'Documento',
                    fornec: new IbsCbsFornecedorRequest(xNome: 'Fornecedor Sem Doc'),
                ),
            ]),
        );
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('fornecedor deve ter CNPJ, CPF, NIF ou cNaoNIF');
        $this->validator->validate($request);
    }

    public function test_nt004_fornec_cnaonif_invalido_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            reeRepRes: new IbsCbsReeRepResRequest([
                new IbsCbsDocumentoReeRepResRequest(
                    tipoDocumento: 'docOutro',
                    dtEmiDoc: '2026-01-15',
                    dtCompDoc: '2026-01-15',
                    tpReeRepRes: '01',
                    vlrReeRepRes: 100.00,
                    nDoc: 'DOC-1',
                    xDoc: 'Documento',
                    fornec: new IbsCbsFornecedorRequest(codigoNaoNif: '9', xNome: 'Forn'),
                ),
            ]),
        );
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('cNaoNIF do fornecedor inválido');
        $this->validator->validate($request);
    }

    public function test_nt004_fornec_valido_passa(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            reeRepRes: new IbsCbsReeRepResRequest([
                new IbsCbsDocumentoReeRepResRequest(
                    tipoDocumento: 'docOutro',
                    dtEmiDoc: '2026-01-15',
                    dtCompDoc: '2026-01-15',
                    tpReeRepRes: '01',
                    vlrReeRepRes: 100.00,
                    nDoc: 'DOC-1',
                    xDoc: 'Documento',
                    fornec: new IbsCbsFornecedorRequest(cnpj: '11444777000161', xNome: 'Fornecedor Ltda'),
                ),
            ]),
        );
        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    public function test_nt004_dest_endereco_sem_cep_throws(): void
    {
        // Bug 7: antes a lib fabricava CEP '00000000'. Agora exige CEP no endNac.
        $request = $this->createValidDpsRequestWithIbscbs(
            dest: new IbsCbsDestRequest(
                cnpj: '11444777000161',
                xNome: 'Destinatário Ltda',
                logradouro: 'Rua X',
                numero: '10',
                bairro: 'Centro',
                codigoMunicipio: '3550308',
                uf: 'SP',
                // cep ausente
            ),
        );
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('CEP do destinatário é obrigatório');
        $this->validator->validate($request);
    }

    public function test_nt004_dest_endereco_sem_numero_throws(): void
    {
        // Bug 8: antes gerava <nro></nro> vazio (viola TSNumeroEndereco minLength=1).
        $request = $this->createValidDpsRequestWithIbscbs(
            dest: new IbsCbsDestRequest(
                cnpj: '11444777000161',
                xNome: 'Destinatário Ltda',
                logradouro: 'Rua X',
                bairro: 'Centro',
                codigoMunicipio: '3550308',
                uf: 'SP',
                cep: '01001000',
                // numero ausente
            ),
        );
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('nro do destinatário é obrigatório');
        $this->validator->validate($request);
    }

    public function test_nt004_imovel_endext_valido_passa(): void
    {
        // Bug 10: endExt do imóvel agora é validado; campos válidos devem passar.
        $request = $this->createValidDpsRequestWithIbscbs(
            imovel: new IbsCbsImovelRequest(
                endereco: new IbsCbsEnderecoObraRequest(
                    endExt: new IbsCbsEnderecoExteriorRequest(
                        cEndPost: 'AB-12345',
                        xCidade: 'Lisboa',
                        xEstProvReg: 'Lisboa',
                    ),
                    xLgr: 'Rua do Imóvel',
                    nro: '100',
                    xBairro: 'Baixa',
                ),
            ),
        );
        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    public function test_nt004_refnfse_acima_de_99_throws(): void
    {
        // XSD: refNFSe maxOccurs=99 (gRefNFSe). 100 chaves deve falhar.
        $chave = str_repeat('1', 50);
        $request = $this->createValidDpsRequestWithIbscbs(
            tpOper: '2',
            refNFSeList: array_fill(0, 100, $chave),
        );
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('no máximo 99 refNFSe');
        $this->validator->validate($request);
    }

    public function test_nt004_docdedred_acima_de_1000_throws(): void
    {
        // XSD: docDedRed maxOccurs=1000. 1001 documentos deve falhar.
        $doc = new DocDedRedRequest(
            tipoDocumento: 'chNFe',
            chaveNFe: '12345678901234567890123456789012345678901234',
            tipoDeducaoReducao: '1',
            dataEmissaoDoc: '2026-05-15',
            valorDedutivel: '1000.00',
            valorDeducao: '1000.00',
        );
        $request = $this->createValidDpsRequest(
            servico: new ServicoRequest(
                discriminacao: 'test',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                valorDeducoes: 0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                documentosDeducao: array_fill(0, 1001, $doc),
                totTribTipo: 'vTotTrib',
                tribISSQN: '1',
                tpRetISSQN: '1',
            ),
        );
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('no máximo 1000 documentos');
        $this->validator->validate($request);
    }

    public function test_nt004_imovel_endext_cidade_vazia_throws(): void
    {
        $request = $this->createValidDpsRequestWithIbscbs(
            imovel: new IbsCbsImovelRequest(
                endereco: new IbsCbsEnderecoObraRequest(
                    endExt: new IbsCbsEnderecoExteriorRequest(
                        cEndPost: 'AB-12345',
                        xCidade: '',
                        xEstProvReg: 'Lisboa',
                    ),
                    xLgr: 'Rua do Imóvel',
                    nro: '100',
                    xBairro: 'Baixa',
                ),
            ),
        );
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('xCidade do endExt do imóvel');
        $this->validator->validate($request);
    }

    public function test_tomador_sem_endereco_passa(): void
    {
        $request = $this->createValidDpsRequest(
            tomador: new TomadorRequest(
                documento: '52998224725',
                isCnpj: false,
                razaoSocial: 'Consumidor Final',
                telefone: null,
                email: null,
                logradouro: null,
                numero: null,
                complemento: null,
                bairro: null,
                codigoMunicipio: null,
                uf: null,
                cep: null,
            ),
        );

        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    public function test_tomador_com_endereco_parcial_sem_numero_falha(): void
    {
        $request = $this->createValidDpsRequest(
            tomador: new TomadorRequest(
                documento: '52998224725',
                isCnpj: false,
                razaoSocial: 'Tomador',
                telefone: null,
                email: null,
                logradouro: 'Rua B',
                numero: null,
                complemento: null,
                bairro: 'Centro',
                codigoMunicipio: '3550308',
                uf: 'SP',
                cep: '02002002',
            ),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('nro do tomador é obrigatório quando há endereço');
        $this->validator->validate($request);
    }

    public function test_cst_piscofins_invalido_falha(): void
    {
        $request = $this->createValidDpsRequest(
            servico: $this->servicoComCstPisCofins('57'),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('CST do PIS/COFINS inválido');
        $this->validator->validate($request);
    }

    public function test_cst_piscofins_valido_passa(): void
    {
        $request = $this->createValidDpsRequest(
            servico: $this->servicoComCstPisCofins('06'),
        );

        $this->validator->validate($request);
        $this->expectNotToPerformAssertions();
    }

    private function servicoComCstPisCofins(string $cst): ServicoRequest
    {
        return new ServicoRequest(
            discriminacao: 'Serviço de teste',
            codigoTributacao: '010101',
            codigoMunicipioPrestacao: '3550308',
            valorServicos: 1000.0,
            valorDeducoes: 0,
            descontoIncondicionado: 0,
            descontoCondicionado: 0,
            aliquotaIss: 5.0,
            codigoNbs: '123456789',
            totTribTipo: 'vTotTrib',
            tribISSQN: '1',
            tpRetISSQN: '1',
            tribFederal: new TribFederalRequest(
                pisCofinsCst: $cst,
                pisCofinsTipo: '0',
            ),
        );
    }

    private function createValidDpsRequest(
        int $tipoAmbiente = 1,
        string $dataEmissao = '2026-06-15T10:00:00-03:00',
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
        ?string $codigoNbs = '123456789',
        ?IbsCbsRequest $ibscbs = null,
        ?ObraRequest $obra = null,
        ?ServicoRequest $servico = null,
        ?TomadorRequest $tomador = null,
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
                telefone: null,
                email: null,
                logradouro: 'Rua A',
                numero: '100',
                complemento: null,
                bairro: 'Centro',
                codigoMunicipio: '3550308',
                uf: 'SP',
                cep: '01001001',
                regimeTributario: RegimeTributario::SIMPLES_NACIONAL->value,
            ),
            tomador: $tomador ?? new TomadorRequest(
                documento: '33444555000181',
                isCnpj: true,
                razaoSocial: $tomadorRazaoSocial,
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
            servico: $servico ?? new ServicoRequest(
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
                totTribTipo: 'vTotTrib',
                tribISSQN: '1',
                tpRetISSQN: '1',
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
        $this->expectExceptionMessage('cObra, cCIB ou end');
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
        $this->expectExceptionMessage('8 caracteres');
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
        $this->expectExceptionMessage('cCIB ou end');
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
        $this->expectExceptionMessage('8 caracteres');
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
        ?string $codigoNbs = '123456789',
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
