<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoRetencaoIssqn;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TributacaoIssqn;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoMunicipio;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Money;

class Servico
{
    private Money $valorTotal;
    private Money $baseCalculo;
    private Money $valorIss;

    /**
     * @param DocDedRed[]|null $documentosDeducao
     */
    public function __construct(
        private string $discriminacao,
        private string $codigoTributacao,
        Money $valorServicos,
        private ?Money $valorDeducoes = null,
        private ?Money $descontoIncondicionado = null,
        private ?Money $descontoCondicionado = null,
        private ?float $aliquotaIss = null,
        private ?CodigoMunicipio $localPrestacao = null,
        private ?string $codigoNbs = null,
        private ?string $codigoCnae = null,
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
        private ?array $documentosDeducao = null,
        private ?float $percentualDeducao = null,
        private ?float $valorDeducaoPadrao = null,
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
    ) {
        $this->calcularValores($valorServicos);
        $this->validate();
    }

    private function calcularValores(Money $valorServicos): void
    {
        $valorDeducoes = $this->valorDeducoes ?? new Money(0);
        $descontoIncond = $this->descontoIncondicionado ?? new Money(0);
        $descontoCond = $this->descontoCondicionado ?? new Money(0);

        $this->baseCalculo = $valorServicos->subtract($valorDeducoes);
        $this->valorIss = $this->aliquotaIss !== null
            ? $this->baseCalculo->percentage($this->aliquotaIss)
            : new Money(0);
        $this->valorTotal = $valorServicos
            ->subtract($descontoIncond)
            ->subtract($descontoCond);
    }

    private function validate(): void
    {
        if (empty($this->discriminacao)) {
            throw new \InvalidArgumentException('Discriminação do serviço é obrigatória');
        }

        if (strlen($this->discriminacao) > 2000) {
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

    public function getValorDeducoes(): ?Money
    {
        return $this->valorDeducoes;
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

    public function getCodigoCnae(): ?string
    {
        return $this->codigoCnae;
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

    /** @return DocDedRed[]|null */
    public function getDocumentosDeducao(): ?array
    {
        return $this->documentosDeducao;
    }

    public function getPercentualDeducao(): ?float
    {
        return $this->percentualDeducao;
    }

    public function getValorDeducaoPadrao(): ?float
    {
        return $this->valorDeducaoPadrao;
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
}
