<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\FinalidadeNfse;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\IndicadorDestinacao;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\IndicadorFinal;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEnteGovernamental;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoOperacao;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoClassificacaoTributaria;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoCreditoPresumido;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoIndicadorOperacao;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoSituacaoTributaria;

class IbsCbsInfo
{
    public function __construct(
        private FinalidadeNfse $finNFSe,
        private CodigoIndicadorOperacao $cIndOp,
        private IndicadorDestinacao $indDest,
        private CodigoSituacaoTributaria $cst,
        private CodigoClassificacaoTributaria $cClassTrib,
        private ?IndicadorFinal $indFinal = null,
        private ?TipoOperacao $tpOper = null,
        private ?TipoEnteGovernamental $tpEnteGov = null,
        private ?CodigoCreditoPresumido $cCredPres = null,
        private ?IbsCbsDest $dest = null,
        private ?IbsCbsTribRegular $tribRegular = null,
        private ?IbsCbsDiferimento $diferimento = null,
    ) {
    }

    public function getFinNFSe(): FinalidadeNfse
    {
        return $this->finNFSe;
    }

    public function getCIndOp(): CodigoIndicadorOperacao
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
}
