<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Contract\EventoInterface;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoAmbiente;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEvento;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\ChaveAcesso;

class Evento implements EventoInterface
{
    public function __construct(
        private TipoEvento $tipo,
        private ChaveAcesso $chaveNfse,
        private \DateTimeImmutable $dataEvento,
        private string $versaoAplicacao,
        private int $tipoAmbiente = 2,
        private ?string $cnpjAutor = null,
        private ?string $cpfAutor = null,
        private ?string $codigoMotivo = null,
        private ?string $descricaoMotivo = null,
        private ?string $cpfAgTrib = null,
        private ?string $nProcAdm = null,
        private ?string $xProcAdm = null,
        private ?string $idEvManifRej = null,
        private ?string $codEventoBloqueio = null,
        private ?string $idBloqOfic = null,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->versaoAplicacao)) {
            throw new \InvalidArgumentException('Versão da aplicação é obrigatória');
        }

        if (!in_array($this->tipoAmbiente, TipoAmbiente::valores(), true)) {
            throw new \InvalidArgumentException('Tipo de ambiente deve ser 1 (Produção) ou 2 (Homologação)');
        }

        if ($this->cnpjAutor !== null && $this->cpfAutor !== null) {
            throw new \InvalidArgumentException('Informar apenas CNPJ Autor ou CPF Autor, não ambos');
        }

        // A SEFIN registra o cancelamento por substituição ao receber a DPS substituta (grupo subst). O pedido de evento
        // enviado pelo emitente é recusado (E1861, confirmado em produção restrita).
        if ($this->tipo === TipoEvento::SUBSTITUICAO) {
            throw new \InvalidArgumentException(
                'O cancelamento por substituição não é um pedido de evento (E1861): emita a NFS-e substituta com o grupo subst na DPS'
            );
        }
    }

    public function getTipo(): TipoEvento
    {
        return $this->tipo;
    }

    public function getChaveNfse(): string
    {
        return $this->chaveNfse->getChave();
    }

    public function getChaveAcesso(): ChaveAcesso
    {
        return $this->chaveNfse;
    }

    public function getDataEvento(): \DateTimeImmutable
    {
        return $this->dataEvento;
    }

    public function getVersaoAplicacao(): string
    {
        return $this->versaoAplicacao;
    }

    public function getTipoAmbiente(): int
    {
        return $this->tipoAmbiente;
    }

    public function getCnpjAutor(): ?string
    {
        return $this->cnpjAutor;
    }

    public function getCpfAutor(): ?string
    {
        return $this->cpfAutor;
    }

    public function getCodigoMotivo(): ?string
    {
        return $this->codigoMotivo;
    }

    public function getDescricaoMotivo(): ?string
    {
        return $this->descricaoMotivo;
    }

    public function getCpfAgTrib(): ?string
    {
        return $this->cpfAgTrib;
    }

    public function getNProcAdm(): ?string
    {
        return $this->nProcAdm;
    }

    public function getXProcAdm(): ?string
    {
        return $this->xProcAdm;
    }

    public function getIdEvManifRej(): ?string
    {
        return $this->idEvManifRej;
    }

    public function getCodEventoBloqueio(): ?string
    {
        return $this->codEventoBloqueio;
    }

    public function getIdBloqOfic(): ?string
    {
        return $this->idBloqOfic;
    }
}
