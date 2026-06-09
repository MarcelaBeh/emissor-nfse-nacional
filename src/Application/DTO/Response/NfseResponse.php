<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Response;

final readonly class NfseResponse
{
    /**
     * @param bool $success reflete o sucesso HTTP da requisição (status 2xx), NÃO necessariamente o
     *        sucesso de negócio na SEFIN. Numa resposta 2xx sem XML de retorno, `xml` será null e
     *        `chaveAcesso` conterá a chave gerada localmente (ainda não confirmada pela API).
     *        Para confirmar a emissão, inspecione `xml`/`dados`, não apenas `success`.
     * @param string|null $chaveAcesso chave de acesso da NFS-e. Em sucesso HTTP sem XML retornado,
     *        é a chave calculada localmente a partir do DPS (não confirmada pela SEFIN).
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
