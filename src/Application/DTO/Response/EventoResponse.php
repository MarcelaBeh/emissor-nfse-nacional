<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Application\DTO\Response;

use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\EventoProcessado;

final readonly class EventoResponse
{
    /**
     * @param array<string, mixed>|null $dados
     * @param list<array{codigo: string|null, descricao: string}> $erros lista completa dos erros
     *        estruturados da SEFIN; `mensagem` traz apenas o primeiro. Vazia em caso de sucesso.
     * @param EventoProcessado|null $evento evento registrado, lido do XML que a SEFIN devolve (`eventoXmlGZipB64`):
     *        o comprovante, com data e hora do registro e sequencial. Fica null se a resposta não o trouxer ou se
     *        não puder ser lido; o evento está registrado de qualquer forma e `dados` mantém a resposta original.
     */
    public function __construct(
        public bool $success,
        public ?string $mensagem = null,
        public ?array $dados = null,
        public array $erros = [],
        public ?EventoProcessado $evento = null,
    ) {
    }
}
