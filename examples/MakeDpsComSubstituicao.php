<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 'On');
date_default_timezone_set('America/Manaus');

require __DIR__ . '/../vendor/autoload.php';

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\DpsRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\PrestadorRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\ServicoRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\SubstituicaoRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\TomadorRequest;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\RegimeTributario;
use MarcelaBeh\EmissorNfseNacional\Presentation\Facade\NfseNacionalFacade;

$config = [
    'tpAmb' => 2,
    'prefeitura' => '3501608',
];

$certContent = file_get_contents('certificado.pfx');
$certPassword = 'senha_certificado';
$cert = \NFePHP\Common\Certificate::readPfx($certContent, $certPassword);

$facade = NfseNacionalFacade::create($config, $cert);

$request = new DpsRequest(
    tipoAmbiente: 2,
    dataEmissao: (new DateTimeImmutable())->format('Y-m-d\TH:i:sP'),
    versaoAplicacao: 'SistemaERP_v2.0',
    serie: 1,
    numero: 1,
    dataCompetencia: (new DateTimeImmutable())->format('Y-m-d'),
    tipoEmissao: 1,
    codigoMunicipioEmissor: '3550308',
    prestador: new PrestadorRequest(
        documento: '11444777000161',
        isCnpj: true,
        inscricaoMunicipal: '123456',
        razaoSocial: 'Prestador Ltda',
        telefone: null,
        email: null,
        logradouro: 'Rua A',
        numero: '100',
        complemento: null,
        bairro: 'Centro',
        codigoMunicipio: '3550308',
        uf: 'SP',
        cep: '01001001',
        regimeTributario: RegimeTributario::SIMPLES_NACIONAL->value,
    ),
    tomador: new TomadorRequest(
        documento: '33444555000181',
        isCnpj: true,
        razaoSocial: 'Tomador Ltda',
        telefone: null,
        email: null,
        logradouro: 'Rua B',
        numero: '200',
        complemento: null,
        bairro: 'Centro',
        codigoMunicipio: '3550308',
        uf: 'SP',
        cep: '02002002',
    ),
    servico: new ServicoRequest(
        discriminacao: 'Prestacao de servicos de consultoria',
        codigoTributacao: '010101',
        codigoMunicipioPrestacao: '3550308',
        valorServicos: 1500.00,
        tribISSQN: '1',
        tpRetISSQN: '1',
    ),
    substituicao: new SubstituicaoRequest(
        chaveSubstituida: '12345678901234567890123456789012345678901234567890',
        codigoMotivo: '99',
        descricaoMotivo: 'Substituicao por reclassificacao tributaria do servico prestado',
    ),
);

try {
    $response = $facade->emitirDps($request);
    echo 'Chave de Acesso: ' . $response->chaveAcesso . PHP_EOL;
    echo 'Sucesso: ' . ($response->success ? 'Sim' : 'Nao') . PHP_EOL;

    if ($response->xml !== null) {
        file_put_contents('dps-substituicao-retorno.xml', $response->xml);
        echo 'XML salvo em dps-substituicao-retorno.xml' . PHP_EOL;
    }
} catch (\Throwable $e) {
    echo 'ERRO: ' . $e->getMessage() . PHP_EOL;
}
