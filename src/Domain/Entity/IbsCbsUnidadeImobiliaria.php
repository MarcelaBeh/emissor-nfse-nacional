<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoCIB;

/**
 * Unidade imobiliária objeto da operação (`imovel/gUnidImob`, TCRTCUnidadesImobiliarias).
 * Identificada por exatamente um de: CIB ou endereço.
 */
class IbsCbsUnidadeImobiliaria
{
    private ?CodigoCIB $cCIB;
    private ?IbsCbsEnderecoImovel $endereco;

    /** @param IbsCbsAjusteBCLocImoveis[] $ajustes */
    public function __construct(
        private ?string $inscImobFisc = null,
        ?CodigoCIB $cCIB = null,
        ?IbsCbsEnderecoImovel $endereco = null,
        private array $ajustes = [],
    ) {
        if (($cCIB === null) === ($endereco === null)) {
            throw new \InvalidArgumentException(
                'Unidade imobiliária deve informar exatamente um dos campos: cCIB ou endereco'
            );
        }

        $this->cCIB = $cCIB;
        $this->endereco = $endereco;
    }

    public function getInscImobFisc(): ?string
    {
        return $this->inscImobFisc;
    }

    public function getCCIB(): ?CodigoCIB
    {
        return $this->cCIB;
    }

    public function getEndereco(): ?IbsCbsEnderecoImovel
    {
        return $this->endereco;
    }

    /** @return IbsCbsAjusteBCLocImoveis[] */
    public function getAjustes(): array
    {
        return $this->ajustes;
    }
}
