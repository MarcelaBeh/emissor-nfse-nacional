# Guia de Integração - NFSe Nacional

Biblioteca PHP para emissão de NFS-e no padrão nacional.

---

## 🚀 Quick Start

```php
use NFePHP\Common\Certificate;
use MarcelaBeh\EmissorNfseNacional\Presentation\Facade\NfseNacionalFacade;
use MarcelaBeh\EmissorNfseNacional\Application\DTO\Request\DpsRequest;

// 1. Configuração
$config = [
    'tpAmb' => 2,              // 1=Produção, 2=Homologação
    'prefeitura' => '3550308', // IBGE do município
];

// 2. Certificado
$cert = Certificate::readPfx($certPath, $password);

// 3. Criar façade
$nfse = NfseNacionalFacade::create($config, $cert);

// 4. Emitir
$response = $nfse->emitirDps($request);
```

---

## 📋 Operações

### 1. Emitir DPS/NFSe

```php
$request = new DpsRequest(
    tipoAmbiente: 2,
    dataEmissao: '2026-06-15T10:00:00-03:00',
    versaoAplicacao: 'ERP_v1.0',
    serie: 1,
    numero: 123,
    dataCompetencia: '2026-06-01',
    tipoEmissao: 1,
    codigoMunicipioEmissor: '3550308',
    prestador: new PrestadorRequest(
        documento: '11444777000161',
        isCnpj: true,
        inscricaoMunicipal: '123456',
        razaoSocial: 'Empresa Ltda',
        telefone: '11999999999',
        email: 'contato@empresa.com',
        logradouro: 'Rua A',
        numero: '100',
        bairro: 'Centro',
        codigoMunicipio: '3550308',
        uf: 'SP',
        cep: '01001001',
        regimeTributario: RegimeTributario::ME_EPP->value,
    ),
    tomador: new TomadorRequest(
        documento: '33444555000181',
        isCnpj: true,
        razaoSocial: 'Cliente Ltda',
        logradouro: 'Rua B',
        numero: '200',
        bairro: 'Centro',
        codigoMunicipio: '3550308',
        uf: 'SP',
        cep: '02002002',
    ),
    servico: new ServicoRequest(
        discriminacao: 'Serviço de consultoria',
        codigoTributacao: '010101',
        codigoMunicipioPrestacao: '3550308',
        valorServicos: 1000.00,
        tribISSQN: '1',  // Obrigatório: 1=Operação tributável
        tpRetISSQN: '1', // Obrigatório: 1=Não retido
    ),
);

$response = $nfse->emitirDps($request);

// Response
// $response->success (bool)
// $response->chaveAcesso (string)
// $response->numero (string)
// $response->codigoVerificacao (string)
// $response->dataEmissao (string)
```

### 2. Cancelar NFSe

```php
$cancelamento = new CancelamentoRequest(
    chaveNFSe: '35260611444777000161550010000001231000000001',
    codigoMotivo: '99', // 99=Outros
    descricaoMotivo: 'Erro na emissão',
);

$response = $nfse->cancelarNfse($cancelamento);
```

### 3. Consultar NFSe por Chave

```php
$response = $nfse->consultarNfsePorChave('35260611444777000161550010000001231000000001');
// Retorna dados da NFSe
```

### 4. Consultar DPS por Chave

```php
$response = $nfse->consultarDpsPorChave('35260611444777000161550010000001231000000001');
// Retorna dados da DPS
```

### 5. Consultar Eventos

```php
$response = $nfse->consultarEventos('35260611444777000161550010000001231000000001');
// Retorna histórico de eventos (cancelamento, etc)
```

---

## 🔧 Campos Obrigatórios

### Prestador
- `documento` (CNPJ/CPF) - Obrigatório
- `razaoSocial` - Obrigatório
- `codigoMunicipio` - Obrigatório
- `uf` - Obrigatório

### Tomador (se informado)
- `documento` OU `razaoSocial` - Pelo menos um
- Se informar documento, informar também xNome

### Servico
- `discriminacao` - Obrigatório
- `codigoTributacao` - Obrigatório (6 dígitos)
- `valorServicos` - Obrigatório (>0)
- `tribISSQN` - Obrigatório (1=Tributável, 2=Imune, 3=Exportação, 4=Não incidência)
- `tpRetISSQN` - Obrigatório (1=Não retido, 2=Retido tomador, 3=Retido intermediário)

---

## 📝 Exemplos Completos

Ver pasta `examples/`:
- `MakeDps.php` - Emissão básica e avançada
- `MakeDpsComSubstituicao.php` - Substituição de NFSe
- `CancelaNfse.php` - Cancelamento
- `ConsultaNfseChave.php` - Consulta por chave
- `ConsultaDpsChave.php` - Consulta DPS
- `ConsultaDanfse.php` - Download DANFSE
- `ConsultaEventos.php` - Histórico de eventos

---

## ⚠️ Tratamento de Erros

```php
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ValidationException;
use MarcelaBeh\EmissorNfseNacional\Application\Exception\ServiceException;

try {
    $response = $nfse->emitirDps($request);
} catch (ValidationException $e) {
    // Erro de validação - dados incorretos
    echo "Erro de validação: " . $e->getMessage();
    // Corrigir dados e tentar novamente
} catch (ServiceException $e) {
    // Erro de comunicação/serviço
    echo "Erro no serviço: " . $e->getMessage();
    // Tentar novamente mais tarde
}
```

---

## 🔒 Certificação

A biblioteca assinará o XML automaticamente usando o certificado fornecido.

---

## 📦 Requisitos

- PHP 8.4+
- Certificado digital A1 (arquivo .pfx ou .p12)
- Extensões: openssl, curl, dom

---

## 📚 Links Úteis

- [Receita Federal - NFS-e Nacional](https://www.gov.br/nfse)
- [Manual Técnicob](https://www.gov.br/nfse/pt-br/manual)
- [Comandos](https://github.com/MarcelaBeh/emissor-nfse-nacional#comandos)

---

**Última atualização:** 16/05/2026