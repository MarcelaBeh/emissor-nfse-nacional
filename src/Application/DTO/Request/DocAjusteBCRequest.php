<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Request;

/**
 * Documento de ajuste (dedução/redução/reembolso) da base de cálculo: `vAjusteBC/documentos/docAjusteBC`.
 *
 * Substitui `DocDedRedRequest` (ISSQN) e `IbsCbsDocumentoReeRepResRequest` (IBS/CBS) a partir da NT 009.
 * Os campos seguem os nomes do XSD. `tipoDocumento` escolhe o grupo do documento comprobatório:
 *  - `dFeNacional`: tipoChaveDFe, xTipoChaveDFe (só se tipoChaveDFe = 9), chaveDFe
 *  - `docFiscalOutro`: cMunDocFiscal, nDocFiscal, xDocFiscal
 *  - `docOutro`: nDoc, xDoc
 *
 * A biblioteca não presume nenhum valor: todos os campos obrigatórios devem vir do chamador.
 */
final readonly class DocAjusteBCRequest
{
    public function __construct(
        public ?string $tipoDocumento = null,
        public ?string $tpAjusteBC = null,
        public ?string $vTotDoc = null,
        public ?string $vAjusteAplic = null,
        public ?string $xTpAjusteBC = null,
        public ?string $dtEmiDoc = null,
        public ?string $dtCompDoc = null,
        public ?string $tipoChaveDFe = null,
        public ?string $xTipoChaveDFe = null,
        public ?string $chaveDFe = null,
        public ?string $cMunDocFiscal = null,
        public ?string $nDocFiscal = null,
        public ?string $xDocFiscal = null,
        public ?string $nDoc = null,
        public ?string $xDoc = null,
        public ?IbsCbsFornecedorRequest $fornec = null,
    ) {
    }
}
