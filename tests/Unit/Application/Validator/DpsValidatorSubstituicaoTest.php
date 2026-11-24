<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Application\Validator;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\DpsRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\PrestadorRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\ServicoRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\SubstituicaoRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\TomadorRequest;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\DpsValidator;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\RegimeTributario;
use PHPUnit\Framework\TestCase;

/**
 * Grupo subst da DPS. As mensagens com código (E0xxx) são as da planilha oficial "RN DPS_NFS-e".
 * RN E0042, parte 2: a chave da NFS-e substituída deve ter o município, o tipo de inscrição e a inscrição desta DPS.
 */
final class DpsValidatorSubstituicaoTest extends TestCase
{
    /** Município 3550308, ambiente gerador 2, tipo de inscrição 2 (CNPJ), CNPJ do prestador e o restante da chave. */
    private const CHAVE_DO_PRESTADOR = '3550308' . '2' . '2' . '11444777000161' . '0000000000123' . '2611' . '012345678' . '9';

    private DpsValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new DpsValidator();
    }

    private function substituicao(string $chave, string $motivo = '99', ?string $descricao = 'Substituicao de teste de validacao'): SubstituicaoRequest
    {
        return new SubstituicaoRequest(chaveSubstituida: $chave, codigoMotivo: $motivo, descricaoMotivo: $descricao);
    }

    private function assertRejeita(DpsRequest $request, string $trecho): void
    {
        try {
            $this->validator->validate($request);
        } catch (ValidationException $e) {
            $this->assertStringContainsString($trecho, $e->getMessage());

            return;
        }

        $this->fail("Era esperada ValidationException contendo: {$trecho}");
    }

    private function assertAceita(DpsRequest $request): void
    {
        $this->validator->validate($request);
        $this->addToAssertionCount(1);
    }

    public function test_substituicao_com_chave_do_mesmo_prestador_passa(): void
    {
        $this->assertAceita($this->dps($this->substituicao(self::CHAVE_DO_PRESTADOR)));
    }

    public function test_substituicao_com_chave_de_outro_municipio_e_rejeitada(): void
    {
        $chave = '1302603' . substr(self::CHAVE_DO_PRESTADOR, 7);

        $this->assertRejeita(
            $this->dps($this->substituicao($chave)),
            'E0042: a chave da NFS-e a ser substituída (chSubstda) não corresponde a esta DPS em: município;'
        );
    }

    public function test_substituicao_com_chave_de_outro_cnpj_e_rejeitada(): void
    {
        $chave = substr(self::CHAVE_DO_PRESTADOR, 0, 9) . '33444555000181' . substr(self::CHAVE_DO_PRESTADOR, 23);

        $this->assertRejeita($this->dps($this->substituicao($chave)), 'em: inscrição;');
    }

    public function test_substituicao_com_tipo_de_inscricao_diferente_e_rejeitada(): void
    {
        $chave = substr(self::CHAVE_DO_PRESTADOR, 0, 8) . '1' . substr(self::CHAVE_DO_PRESTADOR, 9);

        $this->assertRejeita($this->dps($this->substituicao($chave)), 'em: tipo de inscrição;');
    }

    public function test_substituicao_aponta_todas_as_divergencias_de_uma_vez(): void
    {
        $chave = '1302603' . '2' . '1' . '33444555000181' . substr(self::CHAVE_DO_PRESTADOR, 23);

        $this->assertRejeita($this->dps($this->substituicao($chave)), 'em: município, tipo de inscrição, inscrição;');
    }

    public function test_substituicao_de_prestador_cpf_compara_a_inscricao_com_zeros_a_esquerda(): void
    {
        // TSIdDPS: o CPF é completado com 000 à esquerda para ter 14 posições.
        $do_cpf = '3550308' . '2' . '1' . '000' . '52998224725' . substr(self::CHAVE_DO_PRESTADOR, 23);
        $de_outro = '3550308' . '2' . '1' . '000' . '12345678909' . substr(self::CHAVE_DO_PRESTADOR, 23);

        $this->assertAceita($this->dps($this->substituicao($do_cpf), prestadorDocumento: '52998224725', prestadorIsCnpj: false));
        $this->assertRejeita(
            $this->dps($this->substituicao($de_outro), prestadorDocumento: '52998224725', prestadorIsCnpj: false),
            'em: inscrição;'
        );
    }

    public function test_chave_substituida_com_formato_invalido_nao_gera_a_e0042(): void
    {
        try {
            $this->validator->validate($this->dps($this->substituicao('123')));
            $this->fail('A chave de 3 posições deveria ser recusada');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('chSubstda deve ter 50', $e->getMessage());
            $this->assertStringNotContainsString('E0042', $e->getMessage());
        }
    }

    public function test_xmotivo_so_e_obrigatorio_para_o_motivo_99(): void
    {
        // TCSubstituicao/xMotivo é opcional no XSD; a RN exige a descrição quando cMotivo = 99.
        $this->assertAceita($this->dps($this->substituicao(self::CHAVE_DO_PRESTADOR, motivo: '01', descricao: null)));
        $this->assertRejeita(
            $this->dps($this->substituicao(self::CHAVE_DO_PRESTADOR, motivo: '99', descricao: null)),
            'xMotivo é obrigatório quando cMotivo=99'
        );
    }

    public function test_motivo_fora_do_dominio_e_rejeitado(): void
    {
        $this->assertRejeita($this->dps($this->substituicao(self::CHAVE_DO_PRESTADOR, motivo: '06')), 'cMotivo inválido');
    }

    private function dps(
        SubstituicaoRequest $substituicao,
        string $prestadorDocumento = '11444777000161',
        bool $prestadorIsCnpj = true,
    ): DpsRequest {
        return new DpsRequest(
            tipoAmbiente: 2,
            dataEmissao: '2026-06-15T10:00:00-03:00',
            versaoAplicacao: '1.0.0',
            serie: 1,
            numero: 123,
            dataCompetencia: '2026-06-01',
            tipoEmissao: 1,
            codigoMunicipioEmissor: '3550308',
            prestador: new PrestadorRequest(
                documento: $prestadorDocumento,
                isCnpj: $prestadorIsCnpj,
                inscricaoMunicipal: '123456',
                razaoSocial: 'Prestador Ltda',
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
            servico: new ServicoRequest(
                discriminacao: 'Serviço de teste',
                codigoTributacao: '010101',
                codigoMunicipioPrestacao: '3550308',
                valorServicos: 1000.0,
                descontoIncondicionado: 0,
                descontoCondicionado: 0,
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
                totTribTipo: 'indTotTrib',
                indTotTrib: '0',
                tribISSQN: '1',
                tpRetISSQN: '1',
            ),
            tomador: new TomadorRequest(
                documento: '33444555000181',
                isCnpj: true,
                razaoSocial: 'Tomador Ltda',
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
            substituicao: $substituicao,
        );
    }
}
