<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoRetencaoIssqn;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TributacaoIssqn;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoMunicipio;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Money;

class Servico
{
    /** Valor bruto do serviço (vServ da DPS), antes de descontos e ajustes. */
    private Money $valorServicos;
    /** Valor líquido dos descontos (vServ - vDescIncond - vDescCond). Não é o vServ do XML. */
    private Money $valorTotal;
    private Money $baseCalculo;
    private Money $valorIss;

    /**
     * @param DocAjusteBC[]|null $documentosAjusteBc
     */
    public function __construct(
        private string $discriminacao,
        private string $codigoTributacao,
        Money $valorServicos,
        private ?Money $descontoIncondicionado = null,
        private ?Money $descontoCondicionado = null,
        private ?float $aliquotaIss = null,
        private ?CodigoMunicipio $localPrestacao = null,
        private ?string $codigoNbs = null,
        private ?Obra $obra = null,
        private TributacaoIssqn $tribISSQN = TributacaoIssqn::OPERACAO_TRIBUTAVEL,
        private TipoRetencaoIssqn $tpRetISSQN = TipoRetencaoIssqn::NAO_RETIDO,
        private ?string $codigoPaisPrestacao = null,
        private ?string $codigoPaisResultado = null,
        private ?string $codigoTributacaoMunicipal = null,
        private ?string $codigoInternoContribuinte = null,
        private ?float $valorRecebido = null,
        private ?ComExterior $comExterior = null,
        private ?AtvEvento $atvEvento = null,
        private ?InfoCompl $infoCompl = null,
        private ?array $documentosAjusteBc = null,
        private ?float $percentualAjusteBcIssqn = null,
        private ?float $valorAjusteBcIssqn = null,
        private ?int $tipoImunidade = null,
        private ?ExigSusp $exigSusp = null,
        private ?BeneficioMunicipal $beneficioMunicipal = null,
        private ?TribFederal $tribFederal = null,
        private ?string $totTribTipo = null,
        private ?float $pTotTribFed = null,
        private ?float $pTotTribEst = null,
        private ?float $pTotTribMun = null,
        private ?string $indTotTrib = null,
        private ?float $pTotTribSN = null,
        private ?float $vTotTribFed = null,
        private ?float $vTotTribEst = null,
        private ?float $vTotTribMun = null,
    ) {
        $this->calcularValores($valorServicos);
        $this->validate();
    }

    private function calcularValores(Money $valorServicos): void
    {
        $valorDeducoes = $this->calcularAjusteBaseIssqn($valorServicos);
        $reducaoBM = $this->calcularReducaoBeneficioMunicipal($valorServicos);
        $descontoIncond = $this->descontoIncondicionado ?? new Money(0);
        $descontoCond = $this->descontoCondicionado ?? new Money(0);

        $this->valorServicos = $valorServicos;
        // Fórmula oficial (RN E1295): vBC = vServ - vDescIncond - ajustes - benefício municipal.
        $this->baseCalculo = $valorServicos
            ->subtract($descontoIncond)
            ->subtract($valorDeducoes)
            ->subtract($reducaoBM);
        $this->valorIss = $this->aliquotaIss !== null
            ? $this->baseCalculo->percentage($this->aliquotaIss)
            : new Money(0);
        $this->valorTotal = $valorServicos
            ->subtract($descontoIncond)
            ->subtract($descontoCond);
    }

    /**
     * Valor monetário do ajuste (dedução/redução) que reduz a base de cálculo do ISSQN,
     * derivado do grupo oficial <vAjusteBC>: percentual OU valor padrão (choice) somado aos
     * documentos cujo tipo repercute no ISSQN (tabela AJUSTE_BC_REPERCUSSÃO; o tipo 105,
     * glosa de serviços de saúde, afeta apenas o IBS/CBS).
     *
     * É uma estimativa local: a repercussão efetiva depende de o município de incidência
     * autorizar o ajuste, e o valor oficial vem em `vCalcAjusteBCISSQN` na NFS-e.
     */
    private function calcularAjusteBaseIssqn(Money $valorServicos): Money
    {
        $total = new Money(0);

        if ($this->percentualAjusteBcIssqn !== null) {
            $total = $total->add($valorServicos->percentage($this->percentualAjusteBcIssqn));
        } elseif ($this->valorAjusteBcIssqn !== null) {
            $total = $total->add(new Money($this->valorAjusteBcIssqn));
        }

        foreach ($this->documentosAjusteBc ?? [] as $doc) {
            if ($doc->getTpAjusteBC()->ajustaBaseIssqn()) {
                $total = $total->add(new Money($doc->getVAjusteAplic()));
            }
        }

        return $total;
    }

    /**
     * Valor monetário da redução da base de cálculo por Benefício Municipal (BM),
     * derivado do choice vRedBCBM | pRedBCBM. Conforme o XSD:
     * vBC = vServ - descIncond - deduções - benefício municipal.
     */
    private function calcularReducaoBeneficioMunicipal(Money $valorServicos): Money
    {
        if ($this->beneficioMunicipal === null) {
            return new Money(0);
        }

        $valor = $this->beneficioMunicipal->getValorReducaoBC();
        if ($valor !== null) {
            return new Money($valor);
        }

        $percentual = $this->beneficioMunicipal->getPercentualReducaoBC();
        if ($percentual !== null) {
            return $valorServicos->percentage($percentual);
        }

        return new Money(0);
    }

    private function validate(): void
    {
        if (empty($this->discriminacao)) {
            throw new \InvalidArgumentException('Discriminação do serviço é obrigatória');
        }

        if (mb_strlen($this->discriminacao, 'UTF-8') > 2000) {
            throw new \InvalidArgumentException('Discriminação deve ter no máximo 2000 caracteres');
        }

        if ($this->aliquotaIss !== null && ($this->aliquotaIss < 0 || $this->aliquotaIss > 100)) {
            throw new \InvalidArgumentException('Alíquota ISS deve estar entre 0 e 100');
        }

        // XSD TCLocPrest exige xs:choice minOccurs="1": exatamente um de cLocPrestacao ou cPaisPrestacao.
        if ($this->localPrestacao === null && $this->codigoPaisPrestacao === null) {
            throw new \InvalidArgumentException(
                'Local de prestação é obrigatório: informe cLocPrestacao (município IBGE) ou cPaisPrestacao (código ISO do país)'
            );
        }

        if (!$this->valorTotal->isPositive()) {
            throw new \InvalidArgumentException('Valor total deve ser positivo');
        }
    }

    public function getDiscriminacao(): string
    {
        return $this->discriminacao;
    }

    public function getCodigoTributacao(): string
    {
        return $this->codigoTributacao;
    }

    public function getLocalPrestacao(): ?CodigoMunicipio
    {
        return $this->localPrestacao;
    }

    /** Valor bruto do serviço: é o que vai no campo vServ da DPS. */
    public function getValorServicos(): Money
    {
        return $this->valorServicos;
    }

    /** Valor líquido dos descontos incondicional e condicional (não usar como vServ). */
    public function getValorTotal(): Money
    {
        return $this->valorTotal;
    }

    public function getBaseCalculo(): Money
    {
        return $this->baseCalculo;
    }

    public function getValorIss(): Money
    {
        return $this->valorIss;
    }

    public function getDescontoIncondicionado(): ?Money
    {
        return $this->descontoIncondicionado;
    }

    public function getDescontoCondicionado(): ?Money
    {
        return $this->descontoCondicionado;
    }

    public function getAliquotaIss(): ?float
    {
        return $this->aliquotaIss;
    }

    public function getCodigoNbs(): ?string
    {
        return $this->codigoNbs;
    }

    public function getObra(): ?Obra
    {
        return $this->obra;
    }

    public function getTribISSQN(): TributacaoIssqn
    {
        return $this->tribISSQN;
    }

    public function getTpRetISSQN(): TipoRetencaoIssqn
    {
        return $this->tpRetISSQN;
    }

    public function getCodigoPaisPrestacao(): ?string
    {
        return $this->codigoPaisPrestacao;
    }

    public function getCodigoPaisResultado(): ?string
    {
        return $this->codigoPaisResultado;
    }

    public function getCodigoTributacaoMunicipal(): ?string
    {
        return $this->codigoTributacaoMunicipal;
    }

    public function getCodigoInternoContribuinte(): ?string
    {
        return $this->codigoInternoContribuinte;
    }

    public function getValorRecebido(): ?float
    {
        return $this->valorRecebido;
    }

    public function getComExterior(): ?ComExterior
    {
        return $this->comExterior;
    }

    public function getAtvEvento(): ?AtvEvento
    {
        return $this->atvEvento;
    }

    public function getInfoCompl(): ?InfoCompl
    {
        return $this->infoCompl;
    }

    /** @return DocAjusteBC[]|null */
    public function getDocumentosAjusteBc(): ?array
    {
        return $this->documentosAjusteBc;
    }

    public function getPercentualAjusteBcIssqn(): ?float
    {
        return $this->percentualAjusteBcIssqn;
    }

    public function getValorAjusteBcIssqn(): ?float
    {
        return $this->valorAjusteBcIssqn;
    }

    public function temAjusteBC(): bool
    {
        return $this->percentualAjusteBcIssqn !== null
            || $this->valorAjusteBcIssqn !== null
            || $this->documentosAjusteBc !== null;
    }

    public function getTipoImunidade(): ?int
    {
        return $this->tipoImunidade;
    }

    public function getExigSusp(): ?ExigSusp
    {
        return $this->exigSusp;
    }

    public function getBeneficioMunicipal(): ?BeneficioMunicipal
    {
        return $this->beneficioMunicipal;
    }

    public function getTribFederal(): ?TribFederal
    {
        return $this->tribFederal;
    }

    public function getTotTribTipo(): ?string
    {
        return $this->totTribTipo;
    }

    public function getPTotTribFed(): ?float
    {
        return $this->pTotTribFed;
    }

    public function getPTotTribEst(): ?float
    {
        return $this->pTotTribEst;
    }

    public function getPTotTribMun(): ?float
    {
        return $this->pTotTribMun;
    }

    public function getIndTotTrib(): ?string
    {
        return $this->indTotTrib;
    }

    public function getPTotTribSN(): ?float
    {
        return $this->pTotTribSN;
    }

    public function getVTotTribFed(): ?float
    {
        return $this->vTotTribFed;
    }

    public function getVTotTribEst(): ?float
    {
        return $this->vTotTribEst;
    }

    public function getVTotTribMun(): ?float
    {
        return $this->vTotTribMun;
    }
}
