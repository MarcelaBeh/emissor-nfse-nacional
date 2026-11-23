<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Enum;

/**
 * Tipo de ajuste da base de cálculo informado por documento (TSRTCTpAjusteBC),
 * em `vAjusteBC/documentos/docAjusteBC/tpAjusteBC` (NT 009, item 2.3).
 *
 * Substitui o antigo TSIdeDedRed (`tpDedRed`) e o TSRTCTpReeRepRes (`tpReeRepRes`).
 * Os tipos antigos 1 (alimentação/frigobar), 3 (produção externa) e 4 (reembolso de
 * despesas) foram extintos; os dois últimos migram para 103/104/199.
 *
 * A repercussão de cada tipo segue a planilha oficial AJUSTE_BC_REPERCUSSÃO.
 */
enum TipoAjusteBC: string
{
    case MATERIAIS = '2';
    case REPASSE_CONSORCIADO = '5';
    case REPASSE_PLANO_SAUDE = '6';
    case SERVICOS = '7';
    case SUBEMPREITADA_MAO_DE_OBRA = '8';
    case PROFISSIONAL_PARCEIRO = '9';
    case OUTRAS_DEDUCOES_ISSQN = '99';
    case REPASSE_INTERMEDIACAO_IMOVEIS = '101';
    case REPASSE_AGENCIA_TURISMO = '102';
    case REEMBOLSO_PUBLICIDADE_PRODUCAO_EXTERNA = '103';
    case REEMBOLSO_PUBLICIDADE_MIDIA = '104';
    case GLOSA_SERVICOS_SAUDE = '105';
    case OUTROS_REEMBOLSOS = '199';

    public function descricao(): string
    {
        return match ($this) {
            self::MATERIAIS => 'Materiais',
            self::REPASSE_CONSORCIADO => 'Repasse consorciado',
            self::REPASSE_PLANO_SAUDE => 'Repasse plano de saúde',
            self::SERVICOS => 'Serviços',
            self::SUBEMPREITADA_MAO_DE_OBRA => 'Subempreitada de mão de obra',
            self::PROFISSIONAL_PARCEIRO => 'Profissional parceiro',
            self::OUTRAS_DEDUCOES_ISSQN => 'Outras deduções exclusivas do ISSQN',
            self::REPASSE_INTERMEDIACAO_IMOVEIS => 'Repasse de remuneração por intermediação de imóveis a demais corretores envolvidos na operação',
            self::REPASSE_AGENCIA_TURISMO => 'Repasse de valores a fornecedor relativo a fornecimento intermediado por agência de turismo',
            self::REEMBOLSO_PUBLICIDADE_PRODUCAO_EXTERNA => 'Reembolso ou ressarcimento recebido por agência de propaganda e publicidade por serviços de produção externa por conta e ordem de terceiro',
            self::REEMBOLSO_PUBLICIDADE_MIDIA => 'Reembolso ou ressarcimento recebido por agência de propaganda e publicidade por serviços de mídia por conta e ordem de terceiro',
            self::GLOSA_SERVICOS_SAUDE => 'Glosa de Serviços de Saúde',
            self::OUTROS_REEMBOLSOS => 'Outros reembolsos ou ressarcimentos recebidos por valores pagos relativos a operações por conta e ordem de terceiro',
        };
    }

    /**
     * Ajusta a base de cálculo do ISSQN (sujeito à autorização do município de incidência).
     * Só a glosa de serviços de saúde (105) não repercute no ISSQN.
     */
    public function ajustaBaseIssqn(): bool
    {
        return $this !== self::GLOSA_SERVICOS_SAUDE;
    }

    /** Ajusta a base de cálculo do IBS/CBS (tipos 101 a 105 e 199). */
    public function ajustaBaseIbsCbs(): bool
    {
        return (int) $this->value >= 101;
    }

    /** Exige a descrição `xTpAjusteBC` (tipos 99 e 199); para os demais ela é proibida (RN E0464). */
    public function exigeDescricao(): bool
    {
        return $this === self::OUTRAS_DEDUCOES_ISSQN || $this === self::OUTROS_REEMBOLSOS;
    }

    /**
     * Tipos que exigem documento fiscal (dFeNacional ou docFiscalOutro), nunca docOutro (RN E0479):
     * 101, 103, 104 e 199. Confirmado em produção restrita: o tipo 199 com docOutro é rejeitado.
     */
    public function exigeDocumentoFiscal(): bool
    {
        return in_array($this, [
            self::REPASSE_INTERMEDIACAO_IMOVEIS,
            self::REEMBOLSO_PUBLICIDADE_PRODUCAO_EXTERNA,
            self::REEMBOLSO_PUBLICIDADE_MIDIA,
            self::OUTROS_REEMBOLSOS,
        ], true);
    }

    /**
     * Ajustes exclusivos do ISSQN (2, 5 a 9, 99): não podem coexistir com ajuste por
     * percentual/valor padrão (`pAjusteBCISSQN`/`vAjusteBCISSQN`), conforme a RN E0462.
     */
    public function exclusivoIssqn(): bool
    {
        return !$this->ajustaBaseIbsCbs();
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
