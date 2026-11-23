<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoAjusteBC;

/**
 * Documento de ajuste (dedução/redução/reembolso) das bases de cálculo do ISSQN e/ou do IBS/CBS
 * (`vAjusteBC/documentos/docAjusteBC`, NT 009 item 2.3).
 *
 * Unifica o antigo `docDedRed` (ISSQN) e o `gReeRepRes/documentos` (IBS/CBS). O documento que
 * comprova o ajuste é exatamente um de: `dFeNacional`, `docFiscalOutro` ou `docOutro`.
 */
class DocAjusteBC
{
    public const DOC_DFE_NACIONAL = 'dFeNacional';
    public const DOC_FISCAL_OUTRO = 'docFiscalOutro';
    public const DOC_OUTRO = 'docOutro';

    public function __construct(
        private TipoAjusteBC $tpAjusteBC,
        private string $vTotDoc,
        private string $vAjusteAplic,
        private string $tipoDocumento,
        private ?string $xTpAjusteBC = null,
        private ?\DateTimeInterface $dtEmiDoc = null,
        private ?\DateTimeInterface $dtCompDoc = null,
        private ?IbsCbsFornecedor $fornec = null,
        private ?string $tipoChaveDFe = null,
        private ?string $xTipoChaveDFe = null,
        private ?string $chaveDFe = null,
        private ?string $cMunDocFiscal = null,
        private ?string $nDocFiscal = null,
        private ?string $xDocFiscal = null,
        private ?string $nDoc = null,
        private ?string $xDoc = null,
    ) {
    }

    public function getTpAjusteBC(): TipoAjusteBC
    {
        return $this->tpAjusteBC;
    }

    public function getXTpAjusteBC(): ?string
    {
        return $this->xTpAjusteBC;
    }

    public function getVTotDoc(): string
    {
        return $this->vTotDoc;
    }

    public function getVAjusteAplic(): string
    {
        return $this->vAjusteAplic;
    }

    public function getTipoDocumento(): string
    {
        return $this->tipoDocumento;
    }

    public function getDtEmiDoc(): ?\DateTimeInterface
    {
        return $this->dtEmiDoc;
    }

    public function getDtCompDoc(): ?\DateTimeInterface
    {
        return $this->dtCompDoc;
    }

    public function getFornec(): ?IbsCbsFornecedor
    {
        return $this->fornec;
    }

    public function getTipoChaveDFe(): ?string
    {
        return $this->tipoChaveDFe;
    }

    public function getXTipoChaveDFe(): ?string
    {
        return $this->xTipoChaveDFe;
    }

    public function getChaveDFe(): ?string
    {
        return $this->chaveDFe;
    }

    public function getCMunDocFiscal(): ?string
    {
        return $this->cMunDocFiscal;
    }

    public function getNDocFiscal(): ?string
    {
        return $this->nDocFiscal;
    }

    public function getXDocFiscal(): ?string
    {
        return $this->xDocFiscal;
    }

    public function getNDoc(): ?string
    {
        return $this->nDoc;
    }

    public function getXDoc(): ?string
    {
        return $this->xDoc;
    }
}
