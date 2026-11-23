<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Request;

final readonly class IbsCbsRequest
{
    /**
     * @param string[]|null $refNFSeList
     * @param IbsCbsBemMovelRequest[]|null $bensMoveis
     * @param string|null $cIndOp Código do indicador da operação. Não deve ser informado quando o
     *                            CST não tem grupo de tributação IBS/CBS (400, 410, 820).
     * @param bool|null $informarGIBSCBS Força (true) ou suprime (false) o grupo `gIBSCBS`.
     *                                   Padrão (null): decide pelo CST (omitido em 400/410/820).
     */
    public function __construct(
        public string $indDest,
        public string $cst,
        public string $cClassTrib,
        public ?string $cIndOp = null,
        public ?string $indFinal = null,
        public ?string $tpOper = null,
        public ?string $tpEnteGov = null,
        public ?string $cCredPres = null,
        public ?IbsCbsDestRequest $dest = null,
        public ?IbsCbsTribRegularRequest $tribRegular = null,
        public ?IbsCbsDiferimentoRequest $diferimento = null,
        public ?array $refNFSeList = null,
        public ?IbsCbsImovelRequest $imovel = null,
        public ?array $bensMoveis = null,
        public ?bool $informarGIBSCBS = null,
    ) {
    }
}
