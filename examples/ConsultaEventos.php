<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 'On');
date_default_timezone_set('America/Manaus');

require __DIR__ . '/../vendor/autoload.php';

use MarcelaBeh\EmissorNfseNacional\Presentation\Facade\NfseNacionalFacade;

$config = [
    'tpAmb' => 2,
    'prefeitura' => '3501608',
];

$certContent = file_get_contents('certificado.pfx');
$certPassword = 'senha_certificado';
$cert = \NFePHP\Common\Certificate::readPfx($certContent, $certPassword);

$facade = NfseNacionalFacade::create($config, $cert);

$chave = '12345678901234567890123456789012345678901234567890';

try {
    // A SEFIN consulta por tipo e sequencial: o cancelamento é o tipo 101101, sequencial 1.
    $eventos = $facade->consultarEventosProcessados($chave, '101101', 1);

    if ($eventos === []) {
        echo 'Nenhum evento de cancelamento registrado para essa NFS-e.' . PHP_EOL;
    }
    foreach ($eventos as $evento) {
        echo 'Evento ' . $evento->pedido->tipoEvento->value . ' registrado em ' . $evento->dhProc->format('d/m/Y H:i:s')
            . ' (sequencial ' . $evento->nSeqEvento . ')' . PHP_EOL;
        // $evento->xml é o XML assinado do evento, para guardar como comprovante.
    }
} catch (\Throwable $e) {
    echo 'ERRO: ' . $e->getMessage() . PHP_EOL;
}
