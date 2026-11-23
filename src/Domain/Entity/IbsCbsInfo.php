<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\IndicadorDestinacao;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\IndicadorFinal;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEnteGovernamental;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoOperacao;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\ChaveAcesso;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoClassificacaoTributaria;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoCreditoPresumido;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoIndicadorOperacao;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoSituacaoTributaria;

class IbsCbsInfo
{
    /**
     * CSTs para os quais o indicador `ind_gIBSCBS` é 0 (não há detalhamento de tributação IBS/CBS),
     * conforme a NT 009 v1.01, item 2.16: 400 (isenção), 410 (imunidade/não incidência) e
     * 820 (tributação em documento específico).
     *
     * A lista é a que a NT cita nominalmente; o indicador completo vem da Tabela CST/cClassTrib
     * (calculadora IBS/CBS). Para um CST fora desta lista, o chamador pode forçar a decisão com
     * `$informarGrupoTributacao`.
     */
    public const CST_SEM_GRUPO_TRIBUTACAO = ['400', '410', '820'];

    public const MAX_BENS_MOVEIS = 1000;

    /**
     * @param ChaveAcesso[]|null $refNFSeList
     * @param IbsCbsBemMovel[]|null $bensMoveis
     * @param bool|null $informarGrupoTributacao força (true) ou suprime (false) o grupo gIBSCBS;
     *                                           null decide pelo CST informado
     */
    public function __construct(
        private IndicadorDestinacao $indDest,
        private CodigoSituacaoTributaria $cst,
        private CodigoClassificacaoTributaria $cClassTrib,
        private ?CodigoIndicadorOperacao $cIndOp = null,
        private ?IndicadorFinal $indFinal = null,
        private ?TipoOperacao $tpOper = null,
        private ?TipoEnteGovernamental $tpEnteGov = null,
        private ?CodigoCreditoPresumido $cCredPres = null,
        private ?IbsCbsDest $dest = null,
        private ?IbsCbsTribRegular $tribRegular = null,
        private ?IbsCbsDiferimento $diferimento = null,
        private ?array $refNFSeList = null,
        private ?IbsCbsImovel $imovel = null,
        private ?array $bensMoveis = null,
        private ?bool $informarGrupoTributacao = null,
    ) {
        if ($bensMoveis !== null && count($bensMoveis) > self::MAX_BENS_MOVEIS) {
            throw new \InvalidArgumentException(
                'bensMoveis aceita no máximo ' . self::MAX_BENS_MOVEIS . ' ocorrências'
            );
        }

        if (!$this->temGrupoTributacao()
            && ($cCredPres !== null || $tribRegular !== null || $diferimento !== null)) {
            throw new \InvalidArgumentException(
                'cCredPres, gTribRegular e gDif não podem ser informados quando não há grupo de '
                . 'tributação IBS/CBS (CST ' . $cst->getCodigo() . ', ind_gIBSCBS = 0)'
            );
        }
    }

    /**
     * Indica se o grupo `valores/trib/gIBSCBS` deve ser emitido. Quando exigido (ind_gIBSCBS = 1)
     * ele é emitido ainda que vazio; quando vedado (ind_gIBSCBS = 0), é omitido (RN E0960/E0961).
     */
    public function temGrupoTributacao(): bool
    {
        if ($this->informarGrupoTributacao !== null) {
            return $this->informarGrupoTributacao;
        }

        return !in_array($this->cst->getCodigo(), self::CST_SEM_GRUPO_TRIBUTACAO, true);
    }

    public function getCIndOp(): ?CodigoIndicadorOperacao
    {
        return $this->cIndOp;
    }

    public function getIndDest(): IndicadorDestinacao
    {
        return $this->indDest;
    }

    public function getCst(): CodigoSituacaoTributaria
    {
        return $this->cst;
    }

    public function getCClassTrib(): CodigoClassificacaoTributaria
    {
        return $this->cClassTrib;
    }

    public function getIndFinal(): ?IndicadorFinal
    {
        return $this->indFinal;
    }

    public function getTpOper(): ?TipoOperacao
    {
        return $this->tpOper;
    }

    public function getTpEnteGov(): ?TipoEnteGovernamental
    {
        return $this->tpEnteGov;
    }

    public function getCCredPres(): ?CodigoCreditoPresumido
    {
        return $this->cCredPres;
    }

    public function getDest(): ?IbsCbsDest
    {
        return $this->dest;
    }

    public function getTribRegular(): ?IbsCbsTribRegular
    {
        return $this->tribRegular;
    }

    public function getDiferimento(): ?IbsCbsDiferimento
    {
        return $this->diferimento;
    }

    /** @return ChaveAcesso[]|null */
    public function getRefNFSeList(): ?array
    {
        return $this->refNFSeList;
    }

    public function hasRefNFSe(): bool
    {
        return $this->refNFSeList !== null && $this->refNFSeList !== [];
    }

    public function getImovel(): ?IbsCbsImovel
    {
        return $this->imovel;
    }

    /** @return IbsCbsBemMovel[]|null */
    public function getBensMoveis(): ?array
    {
        return $this->bensMoveis;
    }
}
