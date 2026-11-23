<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\Entity;

/**
 * Bem móvel objeto de locação (`IBSCBS/bensMoveis`, TCRTCInfoBensMoveis, NT 009 item 2.7).
 */
class IbsCbsBemMovel
{
    public function __construct(
        private string $cNCMBemMovel,
        private string $xNCMBemMovel,
        private string $qtdNCMBemMovel,
    ) {
    }

    public function getCNCMBemMovel(): string
    {
        return $this->cNCMBemMovel;
    }

    public function getXNCMBemMovel(): string
    {
        return $this->xNCMBemMovel;
    }

    public function getQtdNCMBemMovel(): string
    {
        return $this->qtdNCMBemMovel;
    }
}
