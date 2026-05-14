<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Domain\Entity;

use emissorNfseNacional\NfseNacional\Domain\ValueObject\Money;
use emissorNfseNacional\NfseNacional\Domain\ValueObject\CodigoMunicipio;

class Servico
{
    private Money $valorTotal;
    private Money $baseCalculo;
    private Money $valorIss;

    public function __construct(
        private string $discriminacao,
        private string $codigoTributacao,
        private CodigoMunicipio $localPrestacao,
        Money $valorServicos,
        private Money $valorDeducoes,
        private Money $descontoIncondicionado,
        private Money $descontoCondicionado,
        private float $aliquotaIss,
        private ?string $codigoNbs = null,
        private ?string $codigoCnae = null,
    ) {
        $this->calcularValores($valorServicos);
        $this->validate();
    }

    private function calcularValores(Money $valorServicos): void
    {
        $this->baseCalculo = $valorServicos->subtract($this->valorDeducoes);
        $this->valorIss = $this->baseCalculo->percentage($this->aliquotaIss);
        $this->valorTotal = $valorServicos
            ->subtract($this->descontoIncondicionado)
            ->subtract($this->descontoCondicionado);
    }

    private function validate(): void
    {
        if (empty($this->discriminacao)) {
            throw new \InvalidArgumentException('Discriminação do serviço é obrigatória');
        }

        if (strlen($this->discriminacao) > 2000) {
            throw new \InvalidArgumentException('Discriminação deve ter no máximo 2000 caracteres');
        }

        if ($this->aliquotaIss < 0 || $this->aliquotaIss > 100) {
            throw new \InvalidArgumentException('Alíquota ISS deve estar entre 0 e 100');
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

    public function getLocalPrestacao(): CodigoMunicipio
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

    public function getValorDeducoes(): Money
    {
        return $this->valorDeducoes;
    }

    public function getDescontoIncondicionado(): Money
    {
        return $this->descontoIncondicionado;
    }

    public function getDescontoCondicionado(): Money
    {
        return $this->descontoCondicionado;
    }

    public function getAliquotaIss(): float
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
}
