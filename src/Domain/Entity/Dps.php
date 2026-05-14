<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoAmbiente;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEmissao;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\VersaoSchema;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\ChaveAcesso;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoMunicipio;

class Dps
{
    private ?ChaveAcesso $chaveAcesso = null;

    public function __construct(
        private TipoAmbiente $tipoAmbiente,
        private \DateTimeImmutable $dataEmissao,
        private string $versaoAplicacao,
        private int $serie,
        private int $numero,
        private \DateTimeImmutable $dataCompetencia,
        private TipoEmissao $tipoEmissao,
        private CodigoMunicipio $codigoMunicipioEmissor,
        private Prestador $prestador,
        private Tomador $tomador,
        private Servico $servico,
        private VersaoSchema $versao = VersaoSchema::V1_01,
        private ?Intermediario $intermediario = null,
        private ?Substituicao $substituicao = null,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if ($this->numero <= 0) {
            throw new \InvalidArgumentException('Número da DPS deve ser maior que zero');
        }

        if ($this->serie <= 0) {
            throw new \InvalidArgumentException('Série da DPS deve ser maior que zero');
        }
    }

    public function gerarChaveAcesso(): ChaveAcesso
    {
        if ($this->chaveAcesso !== null) {
            return $this->chaveAcesso;
        }

        $codigo = sprintf(
            '%s%s%02d%015d',
            $this->prestador->getDocumento()->__toString(),
            $this->dataEmissao->format('YmdHis'),
            $this->serie,
            $this->numero
        );

        $this->chaveAcesso = new ChaveAcesso($codigo);
        return $this->chaveAcesso;
    }

    public function getChaveAcesso(): ?ChaveAcesso
    {
        return $this->chaveAcesso;
    }

    public function getTipoAmbiente(): TipoAmbiente
    {
        return $this->tipoAmbiente;
    }

    public function getDataEmissao(): \DateTimeImmutable
    {
        return $this->dataEmissao;
    }

    public function getVersaoAplicacao(): string
    {
        return $this->versaoAplicacao;
    }

    public function getSerie(): int
    {
        return $this->serie;
    }

    public function getNumero(): int
    {
        return $this->numero;
    }

    public function getDataCompetencia(): \DateTimeImmutable
    {
        return $this->dataCompetencia;
    }

    public function getTipoEmissao(): TipoEmissao
    {
        return $this->tipoEmissao;
    }

    public function getCodigoMunicipioEmissor(): CodigoMunicipio
    {
        return $this->codigoMunicipioEmissor;
    }

    public function getPrestador(): Prestador
    {
        return $this->prestador;
    }

    public function getTomador(): Tomador
    {
        return $this->tomador;
    }

    public function getServico(): Servico
    {
        return $this->servico;
    }

    public function getVersao(): VersaoSchema
    {
        return $this->versao;
    }

    public function getIntermediario(): ?Intermediario
    {
        return $this->intermediario;
    }

    public function getSubstituicao(): ?Substituicao
    {
        return $this->substituicao;
    }
}
