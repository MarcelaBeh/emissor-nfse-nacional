<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 'On');
date_default_timezone_set('America/Manaus');

require __DIR__ . '/../vendor/autoload.php';

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\EventoRequest;
use MarcelaBeh\EmissorNfseNacional\Presentation\Facade\NfseNacionalFacade;

$config = [
    'tpAmb' => 2,
    'prefeitura' => '3501608',
];

$certContent = file_get_contents('certificado.pfx');
$certPassword = 'senha_certificado';
$cert = \NFePHP\Common\Certificate::readPfx($certContent, $certPassword);

$facade = NfseNacionalFacade::create($config, $cert);

$request = new EventoRequest(
    tipoAmbiente: 2,
    versaoAplicacao: 'SistemaERP_v2.0',
    dataEvento: (new DateTimeImmutable())->format('Y-m-d\TH:i:sP'),
    chaveNfse: '12345678901234567890123456789012345678901234567890',
    tipoEvento: 'cancelamento',
    cnpjAutor: '11444777000161',
    codigoMotivo: '05',
    descricaoMotivo: 'Rejeicao pelo tomador',
);

try {
    $response = $facade->cancelar($request);
    echo 'Sucesso: ' . ($response->success ? 'Sim' : 'Nao') . PHP_EOL;
    echo 'Mensagem: ' . ($response->mensagem ?? '-') . PHP_EOL;
} catch (\Throwable $e) {
    echo 'ERRO: ' . $e->getMessage() . PHP_EOL;
}
