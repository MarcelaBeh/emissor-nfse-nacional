<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Response;

final readonly class NfseResponse
{
    /**
     * @param bool $success reflete o sucesso HTTP da requisição (status 2xx), NÃO necessariamente o
     *        sucesso de negócio na SEFIN. Para confirmar a emissão, inspecione `xml`/`dados`.
     * @param string|null $chaveAcesso chave de acesso da NFS-e, extraída da resposta da SEFIN
     *        (atributo `Id` do `infNFSe`). É null quando a resposta não traz o XML da NFS-e
     *        autorizada: a chave só existe após a autorização e não é calculada localmente.
     * @param string|null $numero número da NFS-e (`nNFSe`), extraído da resposta da SEFIN.
     * @param array<string, mixed>|null $dados
     * @param list<array{codigo: string|null, descricao: string}> $erros lista completa dos erros
     *        estruturados da SEFIN (a SEFIN pode retornar mais de um, ex.: E0617 + E0625);
     *        `mensagem` traz apenas o primeiro. Vazia em caso de sucesso.
     * @param list<array{codigo: string|null, descricao: string, complemento: string|null}> $alertas
     *        avisos previstos no contrato da SEFIN para a NFS-e autorizada (`alertas[]` da resposta 201
     *        de `POST /nfse`). A nota foi emitida, mas o contribuinte deve avaliar o aviso. Vazia
     *        quando a SEFIN não envia nenhum.
     */
    public function __construct(
        public bool $success,
        public ?string $chaveAcesso = null,
        public ?string $numero = null,
        public ?string $mensagem = null,
        public ?array $dados = null,
        public ?string $xml = null,
        public array $erros = [],
        public array $alertas = [],
    ) {
    }
}
