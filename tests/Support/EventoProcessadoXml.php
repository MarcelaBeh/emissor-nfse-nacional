<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Support;

/**
 * Monta o XML do evento processado (`evento_v1.01.xsd`) com a estrutura que a SEFIN devolve em produção restrita
 * (verificada em 23/11/2026), com CNPJ, chave e assinaturas fictícios: as assinaturas só têm o formato válido, e o
 * parser não as verifica. Passe `false` em uma opção para omitir o elemento.
 */
final class EventoProcessadoXml
{
    public const CHAVE = '35503082211444777000161000000000012326110123456789';

    /**
     * @param array{verAplic?: string|false, ambGer?: string|false, nSeqEvento?: string|false, dhProc?: string|false, nDFSe?: string|false, autor?: string, grupo?: string, versao?: string|false} $o
     */
    public static function build(array $o = []): string
    {
        $op = static fn (string $nome, string|false $padrao): string|false => array_key_exists($nome, $o) ? $o[$nome] : $padrao;
        $el = static fn (string $tag, string|false $valor): string => $valor === false ? '' : "<{$tag}>{$valor}</{$tag}>";

        $versao = $op('versao', '1.01');
        $versaoAttr = $versao === false ? '' : " versao=\"{$versao}\"";
        $autor = $o['autor'] ?? '<CNPJAutor>11444777000161</CNPJAutor>';
        $grupo = $o['grupo'] ?? '<e101101><xDesc>Cancelamento de NFS-e</xDesc><cMotivo>1</cMotivo><xMotivo>Erro na emissao da nota</xMotivo></e101101>';

        return '<?xml version="1.0" encoding="utf-8"?>'
            . "<evento{$versaoAttr} xmlns=\"http://www.sped.fazenda.gov.br/nfse\">"
            . '<infEvento Id="EVT' . self::CHAVE . '101101001">'
            . $el('verAplic', $op('verAplic', 'SefinNacional_1.6.0'))
            . $el('ambGer', $op('ambGer', '2'))
            . $el('nSeqEvento', $op('nSeqEvento', '1'))
            . $el('dhProc', $op('dhProc', '2026-11-23T17:13:38-03:00'))
            . $el('nDFSe', $op('nDFSe', '0'))
            . '<pedRegEvento xmlns="http://www.sped.fazenda.gov.br/nfse" versao="1.01">'
            . '<infPedReg Id="PRE' . self::CHAVE . '101101">'
            . '<tpAmb>2</tpAmb><verAplic>ERP_1.0</verAplic><dhEvento>2026-11-23T17:11:45-03:00</dhEvento>'
            . $autor
            . '<chNFSe>' . self::CHAVE . '</chNFSe>'
            . $grupo
            . '</infPedReg>'
            . self::assinatura('PRE' . self::CHAVE . '101101', 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315')
            . '</pedRegEvento></infEvento>'
            . self::assinatura('EVT' . self::CHAVE . '101101001', 'http://www.w3.org/2001/10/xml-exc-c14n#WithComments')
            . '</evento>';
    }

    private static function assinatura(string $referencia, string $canonicalizacao): string
    {
        return '<Signature xmlns="http://www.w3.org/2000/09/xmldsig#"><SignedInfo>'
            . "<CanonicalizationMethod Algorithm=\"{$canonicalizacao}\" />"
            . '<SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256" />'
            . "<Reference URI=\"#{$referencia}\"><Transforms>"
            . '<Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature" />'
            . "<Transform Algorithm=\"{$canonicalizacao}\" />"
            . '</Transforms><DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256" />'
            . '<DigestValue>AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=</DigestValue></Reference></SignedInfo>'
            . '<SignatureValue>AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA</SignatureValue>'
            . '<KeyInfo><X509Data><X509Certificate>AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA</X509Certificate></X509Data></KeyInfo>'
            . '</Signature>';
    }

    /** Como a resposta do POST de evento devolve (`eventoXmlGZipB64`): gzip em base64 simples. */
    public static function gzipBase64(string $xml): string
    {
        return base64_encode((string) gzencode($xml));
    }

    /** Como a consulta de eventos devolve (`arquivoXml`): o mesmo conteúdo com o base64 aplicado duas vezes. */
    public static function gzipBase64Duplo(string $xml): string
    {
        return base64_encode(self::gzipBase64($xml));
    }
}
