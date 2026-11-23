<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Application\Validator;

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\DocAjusteBCRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\DpsRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsAjusteBCLocImoveisRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsBemMovelRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsEnderecoImovelRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsFornecedorRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsImovelRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsLocacaoImovelRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsUnidadeImobiliariaRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\PrestadorRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\ServicoRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\TomadorRequest;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\DpsValidator;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\RegimeTributario;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Regras da NT 009 v1.01 (release 1): finNFSe em infDPS, vAjusteBC, grupo de tributação IBS/CBS,
 * imóveis (locação), bens móveis e CNPJ alfanumérico. As mensagens com código (E0xxx) são as da
 * planilha oficial "RN DPS_NFS-e" do anexo de 20/10/2026.
 */
final class DpsValidatorNt009Test extends TestCase
{
    private DpsValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new DpsValidator();
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

    // ===== finNFSe em infDPS =====

    public function test_fin_nfse_regular_passa_sem_grupo_ibscbs(): void
    {
        $this->assertAceita($this->dps(finNFSe: '0'));
    }

    public function test_fin_nfse_vazia_e_rejeitada(): void
    {
        $this->assertRejeita($this->dps(finNFSe: ''), 'finNFSe é obrigatória');
    }

    #[DataProvider('finalidadesNaoSuportadas')]
    public function test_fin_nfse_de_ajuste_ainda_nao_e_suportada(string $fin): void
    {
        // Notas de ajuste (1 = crédito, 2 = débito) não têm leiaute publicado nesta release.
        $this->assertRejeita($this->dps(finNFSe: $fin), 'finNFSe inválido');
    }

    /** @return array<string, array{string}> */
    public static function finalidadesNaoSuportadas(): array
    {
        return ['credito' => ['1'], 'debito' => ['2'], 'lixo' => ['x']];
    }

    // ===== grupo de tributação IBS/CBS (CST 400/410/820) =====

    #[DataProvider('cstSemGrupo')]
    public function test_cst_sem_grupo_aceita_ibscbs_sem_cindop(string $cst): void
    {
        $this->assertAceita($this->dps(ibscbs: $this->ibscbs(cst: $cst, cClassTrib: $cst . '001', cIndOp: null)));
    }

    #[DataProvider('cstSemGrupo')]
    public function test_cst_sem_grupo_rejeita_cindop(string $cst): void
    {
        $this->assertRejeita(
            $this->dps(ibscbs: $this->ibscbs(cst: $cst, cClassTrib: $cst . '001', cIndOp: '100001')),
            'E0859'
        );
    }

    #[DataProvider('cstSemGrupo')]
    public function test_cst_sem_grupo_rejeita_credito_presumido(string $cst): void
    {
        $this->assertRejeita(
            $this->dps(ibscbs: $this->ibscbs(cst: $cst, cClassTrib: $cst . '001', cIndOp: null, cCredPres: '01')),
            'E0961'
        );
    }

    /** @return array<string, array{string}> */
    public static function cstSemGrupo(): array
    {
        return ['isencao' => ['400'], 'imunidade' => ['410'], 'documento especifico' => ['820']];
    }

    public function test_cst_com_grupo_exige_cindop(): void
    {
        $this->assertRejeita($this->dps(ibscbs: $this->ibscbs(cIndOp: null)), 'E0860');
    }

    public function test_informar_gibscbs_false_permite_cst_fora_da_lista_sem_cindop(): void
    {
        // Saída explícita para um CST com ind_gIBSCBS = 0 que não está na lista da NT 009.
        $this->assertAceita($this->dps(ibscbs: $this->ibscbs(cIndOp: null, informarGIBSCBS: false)));
    }

    // ===== vAjusteBC =====

    public function test_ajuste_por_documento_valido_passa(): void
    {
        $this->assertAceita($this->dps(servico: $this->servico(docs: [$this->doc()])));
    }

    public function test_ajuste_por_percentual_valido_passa(): void
    {
        $this->assertAceita($this->dps(servico: $this->servico(pct: 10.0)));
    }

    public function test_ajuste_por_valor_valido_passa(): void
    {
        $this->assertAceita($this->dps(servico: $this->servico(valor: 100.0)));
    }

    public function test_percentual_e_valor_sao_mutuamente_exclusivos(): void
    {
        $this->assertRejeita($this->dps(servico: $this->servico(pct: 10.0, valor: 100.0)), 'mutuamente exclusivos');
    }

    #[DataProvider('percentuaisInvalidos')]
    public function test_percentual_fora_de_0_a_100_e_rejeitado(float $pct): void
    {
        $this->assertRejeita($this->dps(servico: $this->servico(pct: $pct)), 'E0453');
    }

    /** @return array<string, array{float}> */
    public static function percentuaisInvalidos(): array
    {
        return ['zero' => [0.0], 'negativo' => [-1.0], 'acima de 100' => [100.01]];
    }

    public function test_valor_padrao_acima_de_vserv_e_rejeitado(): void
    {
        $this->assertRejeita($this->dps(servico: $this->servico(valor: 1500.0)), 'não pode exceder vServ');
    }

    public function test_ajuste_somado_ao_desconto_incondicional_nao_pode_exceder_vserv(): void
    {
        $servico = $this->servico(valor: 600.0, descIncond: 500.0);
        $this->assertRejeita($this->dps(servico: $servico), 'desconto incondicionado');
    }

    public function test_lista_de_documentos_vazia_e_rejeitada(): void
    {
        $this->assertRejeita($this->dps(servico: $this->servico(docs: [])), 'ao menos um docAjusteBC');
    }

    public function test_mais_de_1000_documentos_e_rejeitado(): void
    {
        $doc = $this->doc(['vAjusteAplic' => '0.00', 'vTotDoc' => '0.00']);
        $this->assertRejeita($this->dps(servico: $this->servico(docs: array_fill(0, 1001, $doc))), 'no máximo 1000');
    }

    public function test_tp_ajuste_ausente_e_invalido(): void
    {
        $this->assertRejeita($this->dps(servico: $this->servico(docs: [$this->doc(['tpAjusteBC' => null])])), 'tpAjusteBC é obrigatório');
        $this->assertRejeita($this->dps(servico: $this->servico(docs: [$this->doc(['tpAjusteBC' => '3'])])), 'tpAjusteBC inválido');
    }

    #[DataProvider('tiposExtintos')]
    public function test_tipos_extintos_nao_sao_aceitos(string $tipo): void
    {
        // 1 (alimentação/frigobar), 3 (produção externa) e 4 (reembolso de despesas) deixaram de existir.
        $this->assertRejeita($this->dps(servico: $this->servico(docs: [$this->doc(['tpAjusteBC' => $tipo])])), 'tpAjusteBC inválido');
    }

    /** @return array<string, array{string}> */
    public static function tiposExtintos(): array
    {
        return ['alimentacao' => ['1'], 'producao externa' => ['3'], 'reembolso' => ['4']];
    }

    #[DataProvider('tiposComDescricaoObrigatoria')]
    public function test_descricao_obrigatoria_para_99_e_199(string $tipo): void
    {
        $doc = $this->doc(['tpAjusteBC' => $tipo, 'xTpAjusteBC' => null]);
        $this->assertRejeita($this->dps(servico: $this->servico(docs: [$doc])), 'xTpAjusteBC é obrigatório');
    }

    public function test_descricao_informada_para_99_passa(): void
    {
        $doc = $this->doc(['tpAjusteBC' => '99', 'xTpAjusteBC' => 'Outros ajustes autorizados']);
        $this->assertAceita($this->dps(servico: $this->servico(docs: [$doc])));
    }

    public function test_descricao_informada_para_199_passa_com_documento_fiscal(): void
    {
        $doc = $this->docFiscalOutro(['tpAjusteBC' => '199', 'xTpAjusteBC' => 'Outros reembolsos autorizados']);
        $this->assertAceita($this->dps(servico: $this->servico(docs: [$doc])));
    }

    /** @return array<string, array{string}> */
    public static function tiposComDescricaoObrigatoria(): array
    {
        return ['99' => ['99'], '199' => ['199']];
    }

    #[DataProvider('tiposQueExigemDocumentoFiscal')]
    public function test_tipos_101_103_104_e_199_com_doc_outro_sao_rejeitados(string $tipo): void
    {
        // Confirmado em produção restrita: tpAjusteBC 199 com docOutro volta E0479.
        $doc = $this->doc(['tpAjusteBC' => $tipo, 'xTpAjusteBC' => $tipo === '199' ? 'Reembolso' : null]);
        $this->assertRejeita($this->dps(servico: $this->servico(docs: [$doc])), 'E0479');
    }

    #[DataProvider('tiposQueExigemDocumentoFiscal')]
    public function test_tipos_101_103_104_e_199_com_documento_fiscal_passam(string $tipo): void
    {
        $doc = $this->docFiscalOutro(['tpAjusteBC' => $tipo, 'xTpAjusteBC' => $tipo === '199' ? 'Reembolso' : null]);
        $this->assertAceita($this->dps(servico: $this->servico(docs: [$doc])));
    }

    /** @return array<string, array{string}> */
    public static function tiposQueExigemDocumentoFiscal(): array
    {
        return ['101' => ['101'], '103' => ['103'], '104' => ['104'], '199' => ['199']];
    }

    public function test_tipos_sem_exigencia_de_documento_fiscal_aceitam_doc_outro(): void
    {
        foreach (['2', '5', '6', '7', '8', '9', '102', '105'] as $tipo) {
            $this->assertAceita($this->dps(servico: $this->servico(docs: [$this->doc(['tpAjusteBC' => $tipo])])), "tipo $tipo");
        }
    }

    public function test_descricao_proibida_fora_de_99_e_199(): void
    {
        $doc = $this->doc(['tpAjusteBC' => '2', 'xTpAjusteBC' => 'Materiais']);
        $this->assertRejeita($this->dps(servico: $this->servico(docs: [$doc])), 'E0464');
    }

    public function test_documento_exclusivo_issqn_nao_convive_com_ajuste_padrao(): void
    {
        $this->assertRejeita($this->dps(servico: $this->servico(docs: [$this->doc(['tpAjusteBC' => '2'])], pct: 10.0)), 'E0462');
    }

    public function test_documento_que_repercute_no_ibscbs_convive_com_ajuste_padrao(): void
    {
        $doc = $this->doc([
            'tpAjusteBC' => '103',
            'tipoDocumento' => 'dFeNacional',
            'tipoChaveDFe' => '1',
            'chaveDFe' => '35503082211222333000181' . str_repeat('1', 27),
            'nDoc' => null,
            'xDoc' => null,
        ]);
        $this->assertAceita($this->dps(servico: $this->servico(docs: [$doc], pct: 10.0)));
    }

    #[DataProvider('decimaisInvalidos')]
    public function test_valores_do_documento_seguem_tsdec15v2(string $campo, string $valor): void
    {
        $this->assertRejeita($this->dps(servico: $this->servico(docs: [$this->doc([$campo => $valor])])), $campo);
    }

    /** @return array<string, array{string, string}> */
    public static function decimaisInvalidos(): array
    {
        return [
            'vTotDoc com 1 casa' => ['vTotDoc', '1000.5'],
            'vAjusteAplic com 3 casas' => ['vAjusteAplic', '10.123'],
            'vAjusteAplic com virgula' => ['vAjusteAplic', '10,00'],
        ];
    }

    public function test_ajuste_aplicado_nao_pode_exceder_o_total_do_documento(): void
    {
        $doc = $this->doc(['vTotDoc' => '100.00', 'vAjusteAplic' => '150.00']);
        $this->assertRejeita($this->dps(servico: $this->servico(docs: [$doc])), 'vAjusteAplic deve ser menor ou igual a vTotDoc');
    }

    public function test_ajuste_aplicado_nao_pode_exceder_vserv(): void
    {
        $doc = $this->doc(['vTotDoc' => '5000.00', 'vAjusteAplic' => '1500.00']);
        $this->assertRejeita($this->dps(servico: $this->servico(docs: [$doc])), 'E0467');
    }

    public function test_emissao_anterior_a_competencia_do_documento_e_rejeitada(): void
    {
        $doc = $this->doc(['dtEmiDoc' => '2026-05-01', 'dtCompDoc' => '2026-05-20']);
        $this->assertRejeita($this->dps(servico: $this->servico(docs: [$doc])), 'E0472');
    }

    public function test_data_invalida_do_documento_e_rejeitada(): void
    {
        $doc = $this->doc(['dtEmiDoc' => '2026-02-30']);
        $this->assertRejeita($this->dps(servico: $this->servico(docs: [$doc])), 'dtEmiDoc deve ser uma data válida');
    }

    public function test_doc_fiscal_outro_exige_competencia_anterior_a_2026(): void
    {
        $doc = $this->doc([
            'tipoDocumento' => 'docFiscalOutro',
            'dtCompDoc' => '2026-03-01',
            'dtEmiDoc' => '2026-03-02',
            'cMunDocFiscal' => '3550308',
            'nDocFiscal' => '123',
            'xDocFiscal' => 'NF municipal',
            'nDoc' => null,
            'xDoc' => null,
        ]);
        $this->assertRejeita($this->dps(servico: $this->servico(docs: [$doc])), 'E0476');
    }

    public function test_doc_fiscal_outro_so_com_emissao_em_2026_e_rejeitado(): void
    {
        // Confirmado em produção restrita: sem dtCompDoc, um documento emitido em 2026 volta E0476.
        $doc = $this->docFiscalOutro(['dtEmiDoc' => '2026-11-22']);
        $this->assertRejeita($this->dps(servico: $this->servico(docs: [$doc])), 'E0476');
    }

    public function test_doc_fiscal_outro_so_com_emissao_em_2025_passa(): void
    {
        $doc = $this->docFiscalOutro(['dtEmiDoc' => '2025-12-05']);
        $this->assertAceita($this->dps(servico: $this->servico(docs: [$doc])));
    }

    public function test_doc_fiscal_outro_com_competencia_de_2025_passa(): void
    {
        $doc = $this->doc([
            'tipoDocumento' => 'docFiscalOutro',
            'dtCompDoc' => '2025-12-01',
            'dtEmiDoc' => '2025-12-02',
            'cMunDocFiscal' => '3550308',
            'nDocFiscal' => '123',
            'xDocFiscal' => 'NF municipal',
            'nDoc' => null,
            'xDoc' => null,
        ]);
        $this->assertAceita($this->dps(servico: $this->servico(docs: [$doc])));
    }

    public function test_tipo_de_documento_invalido(): void
    {
        $this->assertRejeita($this->dps(servico: $this->servico(docs: [$this->doc(['tipoDocumento' => 'chNFe'])])), 'tipoDocumento inválido');
    }

    public function test_dfe_nacional_com_tipo_9_exige_descricao(): void
    {
        $doc = $this->doc([
            'tipoDocumento' => 'dFeNacional',
            'tipoChaveDFe' => '9',
            'chaveDFe' => 'ABC123',
            'nDoc' => null,
            'xDoc' => null,
        ]);
        $this->assertRejeita($this->dps(servico: $this->servico(docs: [$doc])), 'xTipoChaveDFe é obrigatório');
    }

    public function test_dfe_nacional_nfse_com_tamanho_errado_e_rejeitada(): void
    {
        $doc = $this->doc([
            'tipoDocumento' => 'dFeNacional',
            'tipoChaveDFe' => '1',
            'chaveDFe' => '12345',
            'nDoc' => null,
            'xDoc' => null,
        ]);
        $this->assertRejeita($this->dps(servico: $this->servico(docs: [$doc])), 'E0460');
    }

    public function test_dfe_nacional_nfe_com_cnpj_alfanumerico_passa(): void
    {
        // Chave de NF-e (44): UF(2) + AAMM(4) + CNPJ(14, alfanumérico) + 24 dígitos.
        $doc = $this->doc([
            'tipoDocumento' => 'dFeNacional',
            'tipoChaveDFe' => '2',
            'chaveDFe' => '352610' . '12ABC34501DE35' . str_repeat('1', 24),
            'nDoc' => null,
            'xDoc' => null,
        ]);
        $this->assertAceita($this->dps(servico: $this->servico(docs: [$doc])));
    }

    public function test_doc_outro_sem_campos_e_rejeitado(): void
    {
        $this->assertRejeita($this->dps(servico: $this->servico(docs: [$this->doc(['nDoc' => null, 'xDoc' => null])])), 'nDoc é obrigatório');
    }

    public function test_fornecedor_sem_identificador_e_rejeitado(): void
    {
        $doc = $this->doc(['fornec' => new IbsCbsFornecedorRequest(xNome: 'Fornecedor sem documento')]);
        $this->assertRejeita($this->dps(servico: $this->servico(docs: [$doc])), 'fornecedor deve ter CNPJ, CPF, NIF ou cNaoNIF');
    }

    public function test_fornecedor_com_cnpj_alfanumerico_passa(): void
    {
        $doc = $this->doc(['fornec' => new IbsCbsFornecedorRequest(cnpj: '12ABC34501DE35', xNome: 'Fornecedor SA')]);
        $this->assertAceita($this->dps(servico: $this->servico(docs: [$doc])));
    }

    // ===== imóvel: locação (99.03) =====

    public function test_locacao_valida_passa(): void
    {
        $this->assertAceita($this->dpsLocacao());
    }

    public function test_locacao_sem_imovel_e_rejeitada(): void
    {
        $this->assertRejeita($this->dpsLocacao(imovel: false), 'E0937');
    }

    public function test_glocacao_fora_do_subitem_9903_e_rejeitado(): void
    {
        $imovel = new IbsCbsImovelRequest(cMun: '3550308', locacao: new IbsCbsLocacaoImovelRequest('100.00', '1000.00'));
        $this->assertRejeita($this->dps(ibscbs: $this->ibscbs(imovel: $imovel)), 'E0936');
    }

    #[DataProvider('percentuaisCopropriedadeInvalidos')]
    public function test_percentual_de_copropriedade_invalido(string $p): void
    {
        $this->assertRejeita($this->dpsLocacao(pCopropriedade: $p), 'pCopropriedade');
    }

    /** @return array<string, array{string}> */
    public static function percentuaisCopropriedadeInvalidos(): array
    {
        return ['acima de 100' => ['100.01'], 'uma casa' => ['50.5'], 'negativo' => ['-1.00']];
    }

    public function test_vserv_deve_ser_percentual_vezes_total_da_operacao(): void
    {
        // 50% de 1000,00 = 500,00; vServ informado 1000,00.
        $this->assertRejeita($this->dpsLocacao(pCopropriedade: '50.00', vServ: 1000.0), 'vServ deve ser igual a pCopropriedade');
    }

    public function test_vserv_proporcional_a_copropriedade_passa(): void
    {
        $this->assertAceita($this->dpsLocacao(pCopropriedade: '50.00', vServ: 500.0));
    }

    public function test_desconto_incondicional_proporcional_a_copropriedade(): void
    {
        $this->assertRejeita(
            $this->dpsLocacao(pCopropriedade: '50.00', vServ: 500.0, vDescIncondTot: '100.00', descIncond: 100.0),
            'vDescIncond deve ser igual a pCopropriedade'
        );
        $this->assertAceita(
            $this->dpsLocacao(pCopropriedade: '50.00', vServ: 500.0, vDescIncondTot: '100.00', descIncond: 50.0)
        );
    }

    public function test_data_de_vencimento_invalida(): void
    {
        $this->assertRejeita($this->dpsLocacao(dVencOrig: '2026-13-01'), 'dVencOrig');
    }

    public function test_unidade_exige_cib_ou_endereco(): void
    {
        $this->assertRejeita($this->dpsLocacao(unidade: new IbsCbsUnidadeImobiliariaRequest()), 'cCIB ou end');
    }

    public function test_unidade_nao_aceita_cib_e_endereco_juntos(): void
    {
        $unidade = new IbsCbsUnidadeImobiliariaRequest(
            cCIB: 'AB12CD34',
            endereco: new IbsCbsEnderecoImovelRequest(cep: '01001000', xLgr: 'Rua A', nro: '10'),
        );
        $this->assertRejeita($this->dpsLocacao(unidade: $unidade), 'mutuamente exclusivos');
    }

    public function test_unidade_com_cib_alfanumerico_de_8_caracteres_passa(): void
    {
        $this->assertAceita($this->dpsLocacao(unidade: new IbsCbsUnidadeImobiliariaRequest(cCIB: 'AB12CD34')));
    }

    public function test_unidade_com_cib_de_tamanho_errado_e_rejeitada(): void
    {
        $this->assertRejeita($this->dpsLocacao(unidade: new IbsCbsUnidadeImobiliariaRequest(cCIB: '1234567')), 'cCIB deve ter exatamente 8');
    }

    public function test_endereco_do_imovel_com_cep_invalido(): void
    {
        $unidade = new IbsCbsUnidadeImobiliariaRequest(
            endereco: new IbsCbsEnderecoImovelRequest(cep: '123', xLgr: 'Rua A', nro: '10'),
        );
        $this->assertRejeita($this->dpsLocacao(unidade: $unidade), 'CEP do endereço do imóvel');
    }

    public function test_endereco_do_imovel_sem_bairro_passa(): void
    {
        // xBairro é opcional em TCEnderImovel (diferente das demais estruturas de endereço).
        $unidade = new IbsCbsUnidadeImobiliariaRequest(
            endereco: new IbsCbsEnderecoImovelRequest(cep: '01001000', xLgr: 'Rua A', nro: '10'),
        );
        $this->assertAceita($this->dpsLocacao(unidade: $unidade));
    }

    public function test_mais_de_99_unidades_e_rejeitado(): void
    {
        $imovel = new IbsCbsImovelRequest(
            cMun: '3550308',
            locacao: new IbsCbsLocacaoImovelRequest('100.00', '1000.00'),
            unidades: array_fill(0, 100, new IbsCbsUnidadeImobiliariaRequest(cCIB: 'AB12CD34')),
        );
        $this->assertRejeita($this->dps(servico: $this->servicoLocacao(), ibscbs: $this->ibscbs(imovel: $imovel, cIndOp: '020101')), 'no máximo 99');
    }

    public function test_cindop_de_imovel_exige_unidade(): void
    {
        $imovel = new IbsCbsImovelRequest(cMun: '3550308', locacao: new IbsCbsLocacaoImovelRequest('100.00', '1000.00'));
        $this->assertRejeita(
            $this->dps(servico: $this->servicoLocacao(), ibscbs: $this->ibscbs(imovel: $imovel, cIndOp: '020101')),
            'E0938'
        );
    }

    // ===== imóvel: ajustes da base de cálculo de locação =====

    public function test_ajuste_de_locacao_valido_passa(): void
    {
        $ajuste = new IbsCbsAjusteBCLocImoveisRequest('01', '100.00');
        $this->assertAceita($this->dpsLocacao(unidade: new IbsCbsUnidadeImobiliariaRequest(cCIB: 'AB12CD34', ajustes: [$ajuste])));
    }

    public function test_ajuste_de_locacao_fora_de_9903_e_rejeitado(): void
    {
        $unidade = new IbsCbsUnidadeImobiliariaRequest(cCIB: 'AB12CD34', ajustes: [new IbsCbsAjusteBCLocImoveisRequest('01', '10.00')]);
        $imovel = new IbsCbsImovelRequest(cMun: '3550308', unidades: [$unidade]);
        $this->assertRejeita($this->dps(ibscbs: $this->ibscbs(imovel: $imovel)), 'E0939');
    }

    public function test_ajuste_99_exige_descricao_e_os_demais_a_proibem(): void
    {
        $semDescricao = new IbsCbsAjusteBCLocImoveisRequest('99', '10.00');
        $this->assertRejeita($this->dpsLocacao(unidade: new IbsCbsUnidadeImobiliariaRequest(cCIB: 'AB12CD34', ajustes: [$semDescricao])), 'E0949');

        $comDescricaoIndevida = new IbsCbsAjusteBCLocImoveisRequest('01', '10.00', 'texto');
        $this->assertRejeita($this->dpsLocacao(unidade: new IbsCbsUnidadeImobiliariaRequest(cCIB: 'AB12CD34', ajustes: [$comDescricaoIndevida])), 'E0954');
    }

    public function test_tipo_de_ajuste_de_locacao_invalido(): void
    {
        $ajuste = new IbsCbsAjusteBCLocImoveisRequest('05', '10.00');
        $this->assertRejeita($this->dpsLocacao(unidade: new IbsCbsUnidadeImobiliariaRequest(cCIB: 'AB12CD34', ajustes: [$ajuste])), 'tpAjusteBCLocImoveis inválido');
    }

    public function test_redutor_social_so_vale_para_locacao_residencial(): void
    {
        $ajuste = new IbsCbsAjusteBCLocImoveisRequest('04', '10.00');
        $unidade = new IbsCbsUnidadeImobiliariaRequest(cCIB: 'AB12CD34', ajustes: [$ajuste]);

        $this->assertRejeita($this->dpsLocacao(unidade: $unidade, nbs: '123456789'), 'E0944');
        $this->assertAceita($this->dpsLocacao(unidade: $unidade, nbs: '110021000'));
    }

    public function test_ajuste_de_locacao_nao_pode_exceder_o_total_da_operacao(): void
    {
        $ajuste = new IbsCbsAjusteBCLocImoveisRequest('01', '1500.00');
        $this->assertRejeita($this->dpsLocacao(unidade: new IbsCbsUnidadeImobiliariaRequest(cCIB: 'AB12CD34', ajustes: [$ajuste])), 'E0966');
    }

    // ===== bens móveis (99.04.01) =====

    public function test_bens_moveis_validos_passam(): void
    {
        $this->assertAceita($this->dpsBensMoveis([new IbsCbsBemMovelRequest('84713012', 'Notebook', '2')]));
    }

    public function test_bens_moveis_obrigatorios_para_990401(): void
    {
        $this->assertRejeita($this->dpsBensMoveis(null), 'E0963');
    }

    public function test_bens_moveis_proibidos_fora_de_990401(): void
    {
        $this->assertRejeita(
            $this->dps(ibscbs: $this->ibscbs(bensMoveis: [new IbsCbsBemMovelRequest('84713012', 'Notebook', '2')])),
            'E0967'
        );
    }

    #[DataProvider('bensMoveisInvalidos')]
    public function test_bem_movel_invalido(IbsCbsBemMovelRequest $bem, string $trecho): void
    {
        $this->assertRejeita($this->dpsBensMoveis([$bem]), $trecho);
    }

    /** @return array<string, array{IbsCbsBemMovelRequest, string}> */
    public static function bensMoveisInvalidos(): array
    {
        return [
            'NCM curto' => [new IbsCbsBemMovelRequest('8471', 'Notebook', '1'), 'TSNCM'],
            'sem descricao' => [new IbsCbsBemMovelRequest('84713012', '  ', '1'), 'xNCMBemMovel'],
            'quantidade zero' => [new IbsCbsBemMovelRequest('84713012', 'Notebook', '0'), 'E0974'],
            'quantidade com 4 digitos' => [new IbsCbsBemMovelRequest('84713012', 'Notebook', '1000'), 'TSNum3Dig'],
        ];
    }

    // ===== CNPJ alfanumérico =====

    public function test_prestador_com_cnpj_alfanumerico_passa(): void
    {
        $this->assertAceita($this->dps(prestadorDocumento: '12ABC34501DE35'));
    }

    public function test_tomador_com_cnpj_alfanumerico_passa(): void
    {
        $this->assertAceita($this->dps(tomadorDocumento: '12ABC34501DE35'));
    }

    public function test_cnpj_com_letra_minuscula_e_rejeitado_no_request(): void
    {
        // O request já deve trazer o documento normalizado (maiúsculas, sem máscara).
        $this->assertRejeita($this->dps(prestadorDocumento: '12abc34501de35'), 'CNPJ do prestador');
    }

    public function test_cnpj_com_letra_nos_digitos_verificadores_e_rejeitado(): void
    {
        $this->assertRejeita($this->dps(prestadorDocumento: '12ABC34501DEAB'), 'CNPJ do prestador');
    }

    // ===== apoio =====

    /**
     * @param array<string, mixed> $override
     */
    private function doc(array $override = []): DocAjusteBCRequest
    {
        $padrao = [
            'tipoDocumento' => 'docOutro',
            'tpAjusteBC' => '2',
            'vTotDoc' => '1000.00',
            'vAjusteAplic' => '500.00',
            'nDoc' => 'REC-1',
            'xDoc' => 'Recibo de materiais',
        ];

        return new DocAjusteBCRequest(...array_merge($padrao, $override));
    }

    /** Documento com comprobatório fiscal (docFiscalOutro), aceito por todos os tipos de ajuste. */
    private function docFiscalOutro(array $override = []): DocAjusteBCRequest
    {
        $padrao = [
            'tipoDocumento' => 'docFiscalOutro',
            'tpAjusteBC' => '2',
            'vTotDoc' => '1000.00',
            'vAjusteAplic' => '500.00',
            'cMunDocFiscal' => '3550308',
            'nDocFiscal' => 'NF-123',
            'xDocFiscal' => 'Nota fiscal de materiais',
            'nDoc' => null,
            'xDoc' => null,
        ];

        return new DocAjusteBCRequest(...array_merge($padrao, $override));
    }

    /**
     * @param list<DocAjusteBCRequest>|null $docs
     */
    private function servico(
        ?array $docs = null,
        ?float $pct = null,
        ?float $valor = null,
        float $descIncond = 0.0,
        string $cTribNac = '010101',
        float $vServ = 1000.0,
        ?string $nbs = '123456789',
    ): ServicoRequest {
        return new ServicoRequest(
            discriminacao: 'Serviço de teste',
            codigoTributacao: $cTribNac,
            valorServicos: $vServ,
            descontoIncondicionado: $descIncond,
            descontoCondicionado: 0.0,
            aliquotaIss: 5.0,
            codigoMunicipioPrestacao: '3550308',
            codigoNbs: $nbs,
            documentosAjusteBc: $docs,
            percentualAjusteBcIssqn: $pct,
            valorAjusteBcIssqn: $valor,
            tribISSQN: '1',
            tpRetISSQN: '1',
            totTribTipo: 'indTotTrib',
            indTotTrib: '0',
        );
    }

    private function servicoLocacao(float $vServ = 1000.0, float $descIncond = 0.0, ?string $nbs = '123456789'): ServicoRequest
    {
        return $this->servico(descIncond: $descIncond, cTribNac: '990301', vServ: $vServ, nbs: $nbs);
    }

    private function ibscbs(
        string $cst = '100',
        string $cClassTrib = '100123',
        ?string $cIndOp = '100001',
        ?string $cCredPres = null,
        ?IbsCbsImovelRequest $imovel = null,
        ?array $bensMoveis = null,
        ?bool $informarGIBSCBS = null,
    ): IbsCbsRequest {
        return new IbsCbsRequest(
            indDest: '0',
            cst: $cst,
            cClassTrib: $cClassTrib,
            cIndOp: $cIndOp,
            cCredPres: $cCredPres,
            imovel: $imovel,
            bensMoveis: $bensMoveis,
            informarGIBSCBS: $informarGIBSCBS,
        );
    }

    private function dps(
        string $finNFSe = '0',
        ?ServicoRequest $servico = null,
        ?IbsCbsRequest $ibscbs = null,
        string $prestadorDocumento = '11444777000161',
        string $tomadorDocumento = '33444555000181',
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
                isCnpj: true,
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
            servico: $servico ?? $this->servico(),
            tomador: new TomadorRequest(
                documento: $tomadorDocumento,
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
            ibscbs: $ibscbs,
            finNFSe: $finNFSe,
        );
    }

    private function dpsLocacao(
        string $pCopropriedade = '100.00',
        float $vServ = 1000.0,
        ?string $vDescIncondTot = null,
        float $descIncond = 0.0,
        ?string $dVencOrig = null,
        ?IbsCbsUnidadeImobiliariaRequest $unidade = null,
        bool $imovel = true,
        ?string $nbs = '123456789',
    ): DpsRequest {
        $imovelRequest = $imovel
            ? new IbsCbsImovelRequest(
                cMun: '3550308',
                locacao: new IbsCbsLocacaoImovelRequest(
                    pCopropriedade: $pCopropriedade,
                    vTotOper: '1000.00',
                    vDescIncondTot: $vDescIncondTot,
                    dVencOrig: $dVencOrig,
                ),
                unidades: [$unidade ?? new IbsCbsUnidadeImobiliariaRequest(cCIB: 'AB12CD34')],
            )
            : null;

        return $this->dps(
            servico: $this->servicoLocacao($vServ, $descIncond, $nbs),
            ibscbs: $this->ibscbs(cIndOp: '020101', imovel: $imovelRequest),
        );
    }

    /**
     * @param list<IbsCbsBemMovelRequest>|null $bens
     */
    private function dpsBensMoveis(?array $bens): DpsRequest
    {
        return $this->dps(
            servico: $this->servico(cTribNac: '990401'),
            ibscbs: $this->ibscbs(bensMoveis: $bens),
        );
    }
}
