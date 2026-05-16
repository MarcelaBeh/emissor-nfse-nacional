<?php

declare(strict_types=1);

// Configurações apenas para exemplo (remover em produção)
error_reporting(E_ALL);
ini_set('display_errors', 'On');
date_default_timezone_set('America/Manaus');

require __DIR__ . '/../vendor/autoload.php';

use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\DpsRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsDestRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsDiferimentoRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsDocumentoReeRepResRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsEnderecoObraRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsImovelRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsReeRepResRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\IbsCbsTribRegularRequest;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\RegimeTributario;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\ObraRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\PrestadorRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\AtvEventoRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\BeneficioMunicipalRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\ComExteriorRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\DocDedRedRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\ExigSuspRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\InfoComplRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\ServicoRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\TomadorRequest;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\TribFederalRequest;
use MarcelaBeh\EmissorNfseNacional\Application\Service\EmitirDpsService;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\DpsValidator;
use MarcelaBeh\EmissorNfseNacional\Application\Validator\IbscbsResponseValidator;
use MarcelaBeh\EmissorNfseNacional\Domain\Contract\CstClassTribRepository;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Config\Configuration;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\ApiConnector;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\Client\CurlHttpClient;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Http\RequestBuilder;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Repository\FileCstClassTribRepository;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\XmlSigner;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Builder\DpsXmlBuilder;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Parser\NfseXmlParser;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Validator\XsdValidator;

// ─── 1. Config ───────────────────────────────────────────────────────────────
$config = new stdClass();
$config->tpamb = 2; // 1-Produção, 2-Homologação
$configJson = json_encode($config);

$certContent = file_get_contents('certificado.pfx');
$certPassword = 'senha_certificado';
$cert = \NFePHP\Common\Certificate::readPfx($certContent, $certPassword);

// ─── 2. Infra ────────────────────────────────────────────────────────────────
$configuration = new Configuration([
    'tpAmb' => $config->tpamb,
    'prefeitura' => '3501608', // Americana-SP (substituir pelo município do prestador)
]);

// Extrair cert/key do PFX para CurlHttpClient (arquivos .pem)
$pemCert = __DIR__ . '/cert.pem';
$pemKey  = __DIR__ . '/key.pem';
if (!file_exists($pemCert) || !file_exists($pemKey)) {
    openssl_pkcs12_read($certContent, $certs, $certPassword);
    file_put_contents($pemCert, ($certs['extracerts'] ?? '') . "\n" . $certs['cert']);
    file_put_contents($pemKey, $certs['pkey']);
}

$httpClient     = new CurlHttpClient(certPath: $pemCert, privateKeyPath: $pemKey, keyPassword: $certPassword);
$apiConnector   = new ApiConnector($configuration, $httpClient);
$xmlSigner      = new XmlSigner($cert);
$xsdValidator   = new XsdValidator();
$requestBuilder = new RequestBuilder();

// Tabela cClassTrib (baixada da URL oficial e convertida para o formato da biblioteca)
$repo = new FileCstClassTribRepository(__DIR__ . '/../storage/cClassTrib.json');

// ─── 3. Request DTO ──────────────────────────────────────────────────────────

// 3a. IBS/CBS básico (obrigatório para contribuintes IBS/CBS)
$ibscbs = new IbsCbsRequest(
    finNFSe: '0',
    cIndOp: '100001',
    indDest: '0',
    cst: '100',
    cClassTrib: '100123',
    indFinal: '1',
    tpOper: '1',
    tpEnteGov: null,
    cCredPres: null,
    dest: null,
    tribRegular: null,
    diferimento: null,
    refNFSeList: null,
    imovel: null,
    reeRepRes: null,
);

// 3b. Com destinatário terceiro (indDest=1)
$ibscbsComDest = new IbsCbsRequest(
    finNFSe: '0',
    cIndOp: '100001',
    indDest: '1',
    cst: '100',
    cClassTrib: '100123',
    indFinal: '1',
    tpOper: '1',
    dest: new IbsCbsDestRequest(
        cnpj: '11444777000161',
        xNome: 'Destinatário Terceiro Ltda',
        logradouro: 'Rua B',
        numero: '200',
        bairro: 'Jardim',
        codigoMunicipio: '3550308',
        uf: 'SP',
        cep: '02002002',
    ),
);

// 3c. Com gRefNFSe (tpOper=2 ou 3)
$ibscbsComRef = new IbsCbsRequest(
    finNFSe: '0',
    cIndOp: '100001',
    indDest: '0',
    cst: '100',
    cClassTrib: '100123',
    tpOper: '2',
    refNFSeList: [
        '12345678901234567890123456789012345678901234567890',
    ],
);

// 3d. Com gTribRegular e gDif
$ibscbsCompleto = new IbsCbsRequest(
    finNFSe: '0',
    cIndOp: '100001',
    indDest: '0',
    cst: '100',
    cClassTrib: '100123',
    tribRegular: new IbsCbsTribRegularRequest(
        cstReg: '200',
        cClassTribReg: '200456',
    ),
    diferimento: new IbsCbsDiferimentoRequest(
        pDifUF: 10.0,
        pDifMun: 5.0,
        pDifCBS: 8.0,
    ),
);

// 3e. Com imóvel (cCIB)
$ibscbsComImovel = new IbsCbsRequest(
    finNFSe: '0',
    cIndOp: '100001',
    indDest: '0',
    cst: '100',
    cClassTrib: '100123',
    imovel: new IbsCbsImovelRequest(
        inscImobFisc: '12345',
        cCIB: '12345678',
    ),
);

// 3f. Com imóvel (endereço)
$ibscbsComImovelEnd = new IbsCbsRequest(
    finNFSe: '0',
    cIndOp: '100001',
    indDest: '0',
    cst: '100',
    cClassTrib: '100123',
    imovel: new IbsCbsImovelRequest(
        endereco: new IbsCbsEnderecoObraRequest(
            cep: '01001001',
            xLgr: 'Rua do Imóvel',
            nro: '100',
            xCpl: 'Apto 42',
            xBairro: 'Centro',
        ),
    ),
);

// 3g. Com gReeRepRes (reembolso/repasse/ressarcimento)
$ibscbsComReeRepRes = new IbsCbsRequest(
    finNFSe: '0',
    cIndOp: '100001',
    indDest: '0',
    cst: '100',
    cClassTrib: '100123',
    reeRepRes: new IbsCbsReeRepResRequest([
        new IbsCbsDocumentoReeRepResRequest(
            tipoDocumento: 'dFeNacional',
            dtEmiDoc: '2026-01-15',
            dtCompDoc: '2026-01-15',
            tpReeRepRes: '01',
            vlrReeRepRes: 1500.00,
            tipoChaveDFe: '1',
            chaveDFe: '12345678901234567890123456789012345678901234567890',
        ),
        new IbsCbsDocumentoReeRepResRequest(
            tipoDocumento: 'docOutro',
            dtEmiDoc: '2026-02-01',
            dtCompDoc: '2026-02-01',
            tpReeRepRes: '99',
            vlrReeRepRes: 800.00,
            nDoc: 'REC-2026-001',
            xDoc: 'Reembolso de despesas diversas',
            xTpReeRepRes: 'Outros reembolsos',
        ),
    ]),
);

// ─── 4. DPS Request completo ─────────────────────────────────────────────────

$request = new DpsRequest(
    tipoAmbiente: 1,
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
        nif: null,
        caepf: null,
    ),
    tomador: new TomadorRequest(
        documento: '33444555000181',
        isCnpj: true,
        razaoSocial: 'Tomador Ltda',
        nomeFantasia: null,
        telefone: null,
        email: null,
        logradouro: 'Rua B',
        numero: '200',
        complemento: null,
        bairro: 'Centro',
        codigoMunicipio: '3550308',
        uf: 'SP',
        cep: '02002002',
        nif: null,
        inscricaoMunicipal: null,
    ),
    servico: new ServicoRequest(
        discriminacao: 'Prestação de serviços de consultoria',
        codigoTributacao: '010101',
        codigoMunicipioPrestacao: '3550308',
        valorServicos: 1500.00,
        valorDeducoes: 0,
        descontoIncondicionado: 0,
        descontoCondicionado: 0,
        aliquotaIss: 5.0,
        codigoNbs: '12345678',
        codigoCnae: null,
        obra: null,
    ),
    ibscbs: $ibscbs,
    substituicao: null,
);

// ─── 5. DPS Request COM obra ─────────────────────────────────────────────────

$requestComObra = new DpsRequest(
    tipoAmbiente: 1,
    dataEmissao: (new DateTimeImmutable())->format('Y-m-d\TH:i:sP'),
    versaoAplicacao: 'SistemaERP_v2.0',
    serie: 2,
    numero: 1,
    dataCompetencia: (new DateTimeImmutable())->format('Y-m-d'),
    tipoEmissao: 1,
    codigoMunicipioEmissor: '3550308',
    prestador: new PrestadorRequest(
        documento: '11444777000161',
        isCnpj: true,
        inscricaoMunicipal: '123456',
        razaoSocial: 'Construtora Ltda',
        telefone: null,
        email: null,
        logradouro: 'Av. das Obras',
        numero: '500',
        complemento: null,
        bairro: 'Industrial',
        codigoMunicipio: '3550308',
        uf: 'SP',
        cep: '01001001',
        regimeTributario: RegimeTributario::SIMPLES_NACIONAL->value,
    ),
    tomador: new TomadorRequest(
        documento: '33444555000181',
        isCnpj: true,
        razaoSocial: 'Contratante Ltda',
        nomeFantasia: null,
        telefone: null,
        email: null,
        logradouro: 'Rua C',
        numero: '300',
        complemento: null,
        bairro: 'Centro',
        codigoMunicipio: '3550308',
        uf: 'SP',
        cep: '02002002',
    ),
    servico: new ServicoRequest(
        discriminacao: 'Execução de obra civil',
        codigoTributacao: '101010',
        codigoMunicipioPrestacao: '3550308',
        valorServicos: 50000.00,
        valorDeducoes: 0,
        descontoIncondicionado: 0,
        descontoCondicionado: 0,
        aliquotaIss: 5.0,
        codigoNbs: '87654321',
        obra: new ObraRequest(
            inscImobFisc: '98765',
            cObra: 'CNO123456789012',
        ),
    ),
    ibscbs: $ibscbs,
);

// 3h. Com todos os novos campos do Servico (comExterior, atvEvento, infoCompl, deducao, tributos)
$ibscbsAvancado = new IbsCbsRequest(
    finNFSe: '0',
    cIndOp: '100001',
    indDest: '0',
    cst: '100',
    cClassTrib: '100123',
);

$requestCompleto = new DpsRequest(
    tipoAmbiente: 1,
    dataEmissao: (new DateTimeImmutable())->format('Y-m-d\TH:i:sP'),
    versaoAplicacao: 'SistemaERP_v3.0',
    serie: 3,
    numero: 1,
    dataCompetencia: (new DateTimeImmutable())->format('Y-m-d'),
    tipoEmissao: 1,
    codigoMunicipioEmissor: '3550308',
    prestador: new PrestadorRequest(
        documento: '11444777000161',
        isCnpj: true,
        inscricaoMunicipal: '123456',
        razaoSocial: 'Prestador Comércio Exterior Ltda',
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
        documento: null,
        isCnpj: false,
        razaoSocial: 'Tomador Exterior Corp',
        nomeFantasia: null,
        telefone: null,
        email: null,
        logradouro: 'Main Street',
        numero: '500',
        complemento: null,
        bairro: 'Downtown',
        codigoMunicipio: '3550308',
        uf: 'SP',
        cep: '00000000',
        codigoPais: '049',
        codigoPostalExterior: '10001-123',
        nomeCidadeExterior: 'New York',
        estadoProvinciaExterior: 'NY',
    ),
    servico: new ServicoRequest(
        discriminacao: 'Consultoria internacional e suporte técnico especializado',
        codigoTributacao: '010101',
        codigoMunicipioPrestacao: '3550308',
        valorServicos: 25000.00,
        valorDeducoes: 3000.00,
        descontoIncondicionado: 500.00,
        descontoCondicionado: 200.00,
        aliquotaIss: 5.0,
        codigoNbs: '12345678',
        codigoTributacaoMunicipal: '123',
        codigoInternoContribuinte: 'INT001',
        valorRecebido: 24300.00,
        comExterior: new ComExteriorRequest(
            modoPrestacao: 1,
            vinculoPrestador: 2,
            codigoMoeda: '840',
            valorServicoMoeda: 5000.00,
            mecanismoApoioPrestador: '01',
            mecanismoApoioTomador: '01',
            movimentacaoTemporaria: '0',
            enviarMDIC: '0',
            numeroDeclaracaoImportacao: '25DI1234567',
        ),
        atvEvento: new AtvEventoRequest(
            descricao: 'Feira Tecnológica Internacional',
            dataInicio: '2026-06-01',
            dataFim: '2026-06-10',
            identificacaoEvento: 'EVT-2026-001',
        ),
        infoCompl: new InfoComplRequest(
            idDocTecnico: 'CONTR-2026-001',
            docReferencia: 'PROP-2026-001',
            numeroPedido: 'PED-2026-001',
            itensPedido: ['Item 1 - Consultoria', 'Item 2 - Suporte'],
            infoComplementar: 'Serviço prestado parcialmente no exterior',
        ),
        documentosDeducao: [
            new DocDedRedRequest(
                tipoDocumento: 'chNFe',
                chaveNFe: '12345678901234567890123456789012345678901234',
                tipoDeducaoReducao: '1',
                dataEmissaoDoc: '2026-05-15',
                valorDedutivel: '3000.00',
                valorDeducao: '3000.00',
            ),
            new DocDedRedRequest(
                tipoDocumento: 'nDoc',
                numeroDoc: 'REC-2026-001',
                tipoDeducaoReducao: '99',
                descricaoOutrasDeducoes: 'Materiais de consumo',
                dataEmissaoDoc: '2026-05-20',
                valorDedutivel: '500.00',
                valorDeducao: '500.00',
            ),
        ],
        tipoImunidade: null,
        exigSusp: new ExigSuspRequest(
            tipoSuspensao: 1,
            numeroProcesso: 'PROC-2026-12345',
        ),
        beneficioMunicipal: new BeneficioMunicipalRequest(
            numeroBeneficio: 'BM-2026-001',
        ),
        tribFederal: new TribFederalRequest(
            pisCofinsCst: '01',
            pisCofinsTipo: '1',
            pisCofinsAliquotaPis: 1.65,
            pisCofinsAliquotaCofins: 7.60,
            valorRetidoCP: '250.00',
            valorRetidoIRRF: '150.00',
            valorRetidoCSLL: '100.00',
        ),
        totTribTipo: 'pTotTrib',
        pTotTribFed: 10.50,
        pTotTribEst: 5.25,
        pTotTribMun: 3.00,
    ),
    ibscbs: $ibscbsAvancado,
);

// ─── 6. Executar ─────────────────────────────────────────────────────────────

$service = new EmitirDpsService(
    apiConnector: $apiConnector,
    xmlBuilder: new DpsXmlBuilder(),
    xmlSigner: $xmlSigner,
    xsdValidator: $xsdValidator,
    validator: new DpsValidator($repo),
    requestBuilder: $requestBuilder,
    nfseXmlParser: new NfseXmlParser(),
    ibscbsResponseValidator: new IbscbsResponseValidator($repo),
);

try {
    $response = $service->executar($request);
    echo 'Chave de Acesso: ' . $response->chaveAcesso . PHP_EOL;
    echo 'Sucesso: ' . ($response->success ? 'Sim' : 'Não') . PHP_EOL;

    if ($response->xml !== null) {
        file_put_contents('nfse-retorno.xml', $response->xml);
        echo 'XML salvo em nfse-retorno.xml' . PHP_EOL;
    }
} catch (\Throwable $e) {
    echo 'ERRO: ' . $e->getMessage() . PHP_EOL;
}
