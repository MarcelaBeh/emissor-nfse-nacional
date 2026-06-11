<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Response;

final readonly class NfseResponse
{
    /**
     * @param bool $success reflete o sucesso HTTP da requisição (status 2xx), NÃO necessariamente o
     *        sucesso de negócio na SEFIN. Para confirmar a emissão, inspecione `xml`/`dados`.
     * @param string|null $chaveAcesso chave de acesso da NFS-e. Quando a SEFIN retorna o XML da
     *        NFS-e autorizada, é a chave REAL extraída da resposta (atributo `Id` do `infNFSe`);
     *        em sucesso HTTP sem XML, cai para a chave calculada localmente a partir do DPS.
     * @param string|null $numero número da NFS-e (`nNFSe`), extraído da resposta da SEFIN.
     * @param array<string, mixed>|null $dados
     */
    public function __construct(
        public bool $success,
        public ?string $chaveAcesso = null,
        public ?string $numero = null,
        public ?string $codigoVerificacao = null,
        public ?string $mensagem = null,
        public ?array $dados = null,
        public ?string $xml = null,
    ) {
    }
}
