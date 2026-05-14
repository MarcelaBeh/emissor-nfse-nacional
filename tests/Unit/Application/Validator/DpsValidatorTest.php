<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Application\Validator;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\DpsRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsDestRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsDiferimentoRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsTribRegularRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\PrestadorRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\ServicoRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\TomadorRequest;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\DpsValidator;
use PHPUnit\Framework\TestCase;

final class DpsValidatorTest extends TestCase
{
    private DpsValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new DpsValidator();
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
            ),
            ibscbs: $ibscbs,
        );
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
            ),
        );
    }
}
