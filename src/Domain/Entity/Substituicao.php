<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\ChaveAcesso;

class Substituicao
{
    public function __construct(
        private ChaveAcesso $chaveSubstituida,
        private string $codigoMotivo,
        private string $descricaoMotivo,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        $codigosValidos = ['01', '02', '03', '04', '05', '06', '07', '99'];

        if (!in_array($this->codigoMotivo, $codigosValidos, true)) {
            throw new \InvalidArgumentException(
                "Código de motivo inválido: {$this->codigoMotivo}"
            );
        }

        if (empty($this->descricaoMotivo)) {
            throw new \InvalidArgumentException('Descrição do motivo é obrigatória');
        }
    }

    public function getChaveSubstituida(): ChaveAcesso
    {
        return $this->chaveSubstituida;
    }

    public function getCodigoMotivo(): string
    {
        return $this->codigoMotivo;
    }

    public function getDescricaoMotivo(): string
    {
        return $this->descricaoMotivo;
    }
}
