<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoClassificacaoTributaria;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoSituacaoTributaria;

class IbsCbsTribRegular
{
    public function __construct(
        private CodigoSituacaoTributaria $cstReg,
        private CodigoClassificacaoTributaria $cClassTribReg,
    ) {
    }

    public function getCstReg(): CodigoSituacaoTributaria
    {
        return $this->cstReg;
    }

    public function getCClassTribReg(): CodigoClassificacaoTributaria
    {
        return $this->cClassTribReg;
    }
}
