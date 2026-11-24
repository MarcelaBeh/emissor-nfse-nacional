<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\ValueObject;

use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEvento;

/**
 * Pedido de registro do evento (`pedRegEvento/infPedReg`) como a SEFIN o devolve dentro do evento processado.
 */
final readonly class PedidoRegistroEvento
{
    /**
     * @param string $id atributo `Id` do `infPedReg` (`PRE` + chave + tipo)
     * @param int $tpAmb 1 produção, 2 homologação
     * @param string|null $cnpjAutor CNPJ do autor do evento (o XSD exige um dos dois, CNPJ ou CPF)
     * @param string|null $cpfAutor CPF do autor do evento
     * @param string $chNFSe chave da NFS-e à qual o evento está vinculado
     * @param array<string, string> $campos elementos do grupo do evento pelo nome no XSD, sem interpretação. Para o
     *        cancelamento (`e101101`): `xDesc`, `cMotivo` e `xMotivo`; para a substituição (`e105102`), também
     *        `chSubstituta`.
     */
    public function __construct(
        public string $id,
        public int $tpAmb,
        public string $verAplic,
        public \DateTimeImmutable $dhEvento,
        public ?string $cnpjAutor,
        public ?string $cpfAutor,
        public string $chNFSe,
        public TipoEvento $tipoEvento,
        public array $campos,
    ) {
    }
}
