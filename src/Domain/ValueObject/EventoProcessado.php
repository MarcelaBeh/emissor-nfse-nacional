<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Domain\ValueObject;

/**
 * Evento já registrado pela SEFIN (`evento/infEvento`, `TCInfEvento` do XSD). É o comprovante oficial do evento: vem na
 * resposta do pedido de registro (`eventoXmlGZipB64`) e na consulta de eventos (`arquivoXml`).
 *
 * A assinatura digital do XML não é verificada pela lib.
 */
final readonly class EventoProcessado
{
    /**
     * @param string $id atributo `Id` do `infEvento` (`EVT` + chave + tipo + sequencial com 3 dígitos)
     * @param string $versao atributo `versao` do `evento`
     * @param string $verAplic versão do aplicativo que gerou o evento
     * @param int $ambGer ambiente gerador do evento, conforme o XSD: 1 prefeitura, 2 Sefin Nacional, 3 Ambiente Nacional
     * @param string $nSeqEvento sequencial do evento para o mesmo tipo, como vem no XML (por exemplo `1`); o
     *        cancelamento, que só ocorre uma vez, é o sequencial 1
     * @param \DateTimeImmutable $dhProc data e hora do registro do evento
     * @param string $nDFSe número sequencial do documento gerado pelo ambiente gerador
     * @param PedidoRegistroEvento $pedido pedido de registro que originou o evento
     * @param string $xml XML do evento processado, assinado, como a SEFIN o enviou (para guardar como comprovante)
     */
    public function __construct(
        public string $id,
        public string $versao,
        public string $verAplic,
        public int $ambGer,
        public string $nSeqEvento,
        public \DateTimeImmutable $dhProc,
        public string $nDFSe,
        public PedidoRegistroEvento $pedido,
        public string $xml,
    ) {
    }
}
