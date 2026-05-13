# Arquitetura de Refatoração - NFSe Nacional

**Versão:** 2.0  
**Data:** 13 de maio de 2026  
**Status:** Proposta de Refatoração

---

## 📋 Sumário

1. [Visão Geral](#visão-geral)
2. [Objetivos da Refatoração](#objetivos-da-refatoração)
3. [Arquitetura Proposta](#arquitetura-proposta)
4. [Estrutura de Diretórios](#estrutura-de-diretórios)
5. [Camadas e Responsabilidades](#camadas-e-responsabilidades)
6. [Implementação Detalhada](#implementação-detalhada)
7. [Segurança e Compliance](#segurança-e-compliance)
8. [Plano de Migração](#plano-de-migração)
9. [Testes](#testes)
10. [Referências](#referências)

---

## 🎯 Visão Geral

Este documento descreve a refatoração completa do pacote `emissor-nfse-nacional` seguindo princípios de **Clean Architecture**, **SOLID** e **PSR Standards**, garantindo conformidade com as especificações técnicas da NFSe Nacional (v1.00 e v1.01).

### 📦 Contexto: Biblioteca Reutilizável

**IMPORTANTE:** Este é um **pacote Composer** (`marcelabeh/emissor-nfse-nacional`) - uma **biblioteca reutilizável** que será:

- ✅ **Instalada via Composer** em múltiplos projetos
- ✅ **Integrada com APIs do Governo** (NFSe Nacional)
- ✅ **Independente de framework** (Laravel, Symfony, etc.)
- ✅ **Publicada no Packagist** para uso público
- ✅ **Versionada semanticamente** (SemVer 2.0)

**Princípios de Design para Bibliotecas:**

1. **Zero acoplamento com frameworks** - Funciona em qualquer projeto PHP
2. **Configuração flexível** - Adaptável a diferentes ambientes
3. **Facade simples** - API pública clara e estável
4. **Backward compatibility** - Não quebrar projetos dependentes
5. **Minimal dependencies** - Apenas dependências essenciais
6. **Extensível** - Permitir customizações sem modificar a lib

### Contexto Atual

O pacote atual funciona mas apresenta oportunidades de melhoria:

- ❌ Classes com múltiplas responsabilidades
- ❌ Acoplamento forte entre camadas
- ❌ Dificuldade para testes unitários
- ❌ Validações dispersas no código
- ❌ Configuração pouco flexível

### Proposta

- ✅ Arquitetura em camadas bem definidas
- ✅ Separação clara de responsabilidades
- ✅ Injeção de dependências
- ✅ Testabilidade completa
- ✅ Extensibilidade para novos municípios
- ✅ Compliance total com especificações técnicas

---

## 🎯 Objetivos da Refatoração

### Objetivos Técnicos

1. **Manutenibilidade**: Código organizado e fácil de modificar
2. **Testabilidade**: Cobertura de testes >80%
3. **Extensibilidade**: Fácil adicionar novos serviços/municípios
4. **Performance**: Otimização de requests e processamento XML
5. **Segurança**: Gestão segura de certificados e validações

### Objetivos de Negócio

1. **Conformidade**: 100% aderente às especificações da NFSe Nacional
2. **Confiabilidade**: Tratamento robusto de erros
3. **Usabilidade**: API intuitiva e bem documentada
4. **Compatibilidade**: Manter retrocompatibilidade quando possível

---

## 📦 Design para Biblioteca Reutilizável

### Por que isso importa?

Como **biblioteca Composer**, este pacote será:
- Instalado em **diversos projetos** (Laravel, Symfony, CakePHP, vanilla PHP, etc.)
- Usado em **ambientes variados** (produção, homologação, desenvolvimento)
- Dependido por **outros desenvolvedores** que esperam estabilidade

### Princípios de Design Aplicados

#### 1. **Framework Agnostic (Zero Acoplamento)**

```php
// ✅ BOM - Não depende de framework específico
$facade = NfseNacionalFacade::create($config, $certificado);
$response = $facade->emitirDps($request);

// ❌ RUIM - Acoplado ao Laravel
$facade = app(NfseNacionalFacade::class); // Depende de service container
```

**Benefício:** Funciona em qualquer projeto PHP 8.1+

#### 2. **Configuração Flexível**

```php
// ✅ BOM - Aceita array simples ou objeto
$config = [
    'tpAmb' => 1,
    'prefeitura' => '3550308',
    'timeout' => 30,
    'retry' => 3,
];

$facade = NfseNacionalFacade::create($config, $cert);
```

**Benefício:** Cada projeto pode adaptar conforme necessidade

#### 3. **Minimal Dependencies**

**Dependências apenas essenciais:**
- `nfephp-org/sped-common` - Assinatura digital (essencial)
- `ext-dom`, `ext-curl`, `ext-openssl` - Funcionalidades core
- `symfony/var-dumper` - Debug (apenas dev)

**Evitamos:**
- Frameworks completos (Laravel, Symfony)
- ORMs específicos
- Bibliotecas HTTP pesadas (quando cURL nativo serve)

**Benefício:** Instalação rápida, poucos conflitos de versão

#### 4. **API Pública Estável (Facade Pattern)**

```php
// API pública - NUNCA muda (ou muda com deprecation)
class NfseNacionalFacade
{
    public function emitirDps(DpsRequest $request): NfseResponse;
    public function consultarPorChave(string $chave): ?NfseResponse;
    public function cancelar(EventoRequest $request): array;
}

// Implementação interna - PODE mudar livremente
class EmitirDpsService { ... }
class DpsXmlBuilder { ... }
```

**Benefício:** Projetos dependentes não quebram com atualizações internas

#### 5. **Versionamento Semântico (SemVer 2.0)**

```
v2.0.0 - Breaking changes (nova arquitetura)
v2.1.0 - Nova feature (consulta de eventos v2)
v2.1.1 - Bugfix (correção validação CPF)
```

**Compromisso:**
- **Major** (2.x): Breaking changes permitidos
- **Minor** (x.1.x): Apenas novas features (backward compatible)
- **Patch** (x.x.1): Apenas bugfixes

**Benefício:** Projetos sabem quando podem atualizar com segurança

#### 6. **Extensibilidade sem Modificação**

```php
// Permitir customização via interfaces
interface HttpClientInterface {
    public function post(string $url, array $data): array;
}

// Projeto pode injetar implementação própria
class CustomGuzzleClient implements HttpClientInterface { ... }

$facade = NfseNacionalFacade::create($config, $cert, $customClient);
```

**Benefício:** Projetos podem estender sem fazer fork da biblioteca

#### 7. **Zero State Global**

```php
// ✅ BOM - Sem estado global
$facade1 = NfseNacionalFacade::create($configProd, $certProd);
$facade2 = NfseNacionalFacade::create($configHomolog, $certHomolog);

// Ambos funcionam independentemente

// ❌ RUIM - Estado global
NfseConfig::set('ambiente', 'producao'); // Afeta toda aplicação
```

**Benefício:** Múltiplas instâncias podem coexistir

#### 8. **Documentação Clara**

```php
/**
 * Emite uma DPS (Declaração de Prestação de Serviço)
 *
 * @param DpsRequest $request Dados da DPS a ser emitida
 * @return NfseResponse Resposta contendo chave de acesso e número da NFSe
 * 
 * @throws ValidationException Se dados inválidos
 * @throws HttpException Se falha na comunicação com API
 * @throws CertificateException Se certificado inválido/expirado
 * 
 * @example
 * ```php
 * $request = new DpsRequest(...);
 * $response = $facade->emitirDps($request);
 * echo $response->chaveAcesso;
 * ```
 */
public function emitirDps(DpsRequest $request): NfseResponse;
```

**Benefício:** Desenvolvedores sabem exatamente como usar

### Compatibilidade entre Projetos

#### Cenário 1: Projeto Laravel
```php
// config/services.php
'nfse' => [
    'tpAmb' => env('NFSE_AMBIENTE', 1),
    'prefeitura' => env('NFSE_PREFEITURA'),
];

// Service Provider
$this->app->singleton(NfseNacionalFacade::class, function ($app) {
    $cert = Certificate::readPfx(
        storage_path('app/certificado.pfx'),
        config('nfse.senha')
    );
    
    return NfseNacionalFacade::create(config('services.nfse'), $cert);
});
```

#### Cenário 2: Projeto Symfony
```yaml
# config/services.yaml
services:
    Hadder\NfseNacional\Presentation\Facade\NfseNacionalFacade:
        factory: ['Hadder\NfseNacional\Presentation\Facade\NfseNacionalFacade', 'create']
        arguments:
            - tpAmb: '%env(NFSE_AMBIENTE)%'
              prefeitura: '%env(NFSE_PREFEITURA)%'
            - '@nfse.certificate'
```

#### Cenário 3: Projeto Vanilla PHP
```php
// bootstrap.php
$config = require __DIR__ . '/config/nfse.php';
$certContent = file_get_contents(__DIR__ . '/cert.pfx');
$cert = Certificate::readPfx($certContent, $config['senha']);

$nfseFacade = NfseNacionalFacade::create($config, $cert);

// Usar em qualquer lugar
$response = $nfseFacade->emitirDps($request);
```

**Resultado:** Mesma biblioteca funciona em todos os cenários!

### Publicação e Distribuição

#### 1. **Packagist**
```bash
composer require marcelabeh/emissor-nfse-nacional:^2.0
```

#### 2. **Instalação**
```json
{
    "require": {
        "marcelabeh/emissor-nfse-nacional": "^2.0"
    }
}
```

#### 3. **Autoloading PSR-4**
```json
{
    "autoload": {
        "psr-4": {
            "Hadder\\NfseNacional\\": "src/"
        }
    }
}
```

#### 4. **Changelog Público**
Sempre documentar mudanças para usuários saberem o que esperar em cada versão.

### Responsabilidade como Biblioteca

Como mantenedores, temos compromisso com:

1. **Estabilidade** - Não quebrar projetos dependentes sem aviso
2. **Segurança** - Patches rápidos para vulnerabilidades
3. **Suporte** - Responder issues e ajudar usuários
4. **Evolução** - Novas features sem quebrar código existente
5. **Documentação** - Manter exemplos e guias atualizados

---

## 🏗️ Arquitetura Proposta

### Princípios Arquiteturais

A arquitetura segue os princípios da **Clean Architecture** e **Hexagonal Architecture**:

```
┌─────────────────────────────────────────────────┐
│           PRESENTATION LAYER                    │
│   (Facades, Controllers, API Public)            │
└─────────────────┬───────────────────────────────┘
                  │
┌─────────────────▼───────────────────────────────┐
│         APPLICATION LAYER                       │
│   (Use Cases, Services, DTOs)                   │
└─────────────────┬───────────────────────────────┘
                  │
┌─────────────────▼───────────────────────────────┐
│            DOMAIN LAYER                         │
│   (Entities, Value Objects, Business Rules)     │
└─────────────────┬───────────────────────────────┘
                  │
┌─────────────────▼───────────────────────────────┐
│        INFRASTRUCTURE LAYER                     │
│   (HTTP, XML, Database, External Services)      │
└─────────────────────────────────────────────────┘
```

### Fluxo de Dados

```
Usuario/Cliente
      ↓
  [Facade] ← Interface pública
      ↓
  [Service] ← Lógica de aplicação
      ↓
  [Entity] ← Regras de negócio
      ↓
  [Infrastructure] ← Comunicação externa
      ↓
  API NFSe Nacional
```

---

## 📁 Estrutura de Diretórios

```
src/
├── Domain/                          # Camada de Domínio
│   ├── Entity/                      # Entidades de negócio
│   │   ├── Dps.php                  # Declaração de Prestação de Serviço
│   │   ├── Nfse.php                 # Nota Fiscal de Serviço Eletrônica
│   │   ├── Prestador.php            # Dados do Prestador
│   │   ├── Tomador.php              # Dados do Tomador
│   │   ├── Intermediario.php        # Dados do Intermediário
│   │   ├── Servico.php              # Dados do Serviço
│   │   ├── Evento.php               # Evento (cancelamento, substituição)
│   │   └── Endereco.php             # Endereço (nacional/exterior)
│   │
│   ├── ValueObject/                 # Objetos de Valor
│   │   ├── Cnpj.php                 # CNPJ validado
│   │   ├── Cpf.php                  # CPF validado
│   │   ├── ChaveAcesso.php          # Chave de 50 caracteres
│   │   ├── InscricaoMunicipal.php   # IM validada
│   │   ├── Nif.php                  # Número Identificação Fiscal
│   │   ├── CodigoMunicipio.php      # Código IBGE (7 dígitos)
│   │   ├── Cep.php                  # CEP validado
│   │   ├── Email.php                # Email validado
│   │   ├── Telefone.php             # Telefone formatado
│   │   └── Money.php                # Valores monetários
│   │
│   ├── Enum/                        # Enumerações
│   │   ├── TipoAmbiente.php         # Produção/Homologação
│   │   ├── TipoEmissao.php          # Prestador/Tomador/Intermediário
│   │   ├── RegimeTributario.php     # Simples/Normal/etc
│   │   ├── TipoEvento.php           # Cancelamento/Substituição/etc
│   │   ├── MotivoEvento.php         # Códigos de motivo
│   │   └── VersaoSchema.php         # v1.00, v1.01
│   │
│   ├── Contract/                    # Interfaces do domínio
│   │   ├── DpsInterface.php
│   │   ├── NfseInterface.php
│   │   └── EventoInterface.php
│   │
│   └── Exception/                   # Exceções de domínio
│       ├── DomainException.php
│       ├── InvalidCnpjException.php
│       ├── InvalidCpfException.php
│       ├── InvalidChaveAcessoException.php
│       └── ValidationException.php
│
├── Application/                     # Camada de Aplicação
│   ├── Service/                     # Serviços de aplicação
│   │   ├── EmitirDpsService.php     # Emisão de DPS
│   │   ├── ConsultarNfseService.php # Consulta NFSe
│   │   ├── ConsultarDpsService.php  # Consulta DPS
│   │   ├── CancelarNfseService.php  # Cancelamento
│   │   ├── ConsultarEventosService.php
│   │   └── ConsultarDanfseService.php
│   │
│   ├── UseCase/                     # Casos de uso específicos
│   │   ├── EmitirDpsUseCase.php
│   │   ├── CancelarNfseUseCase.php
│   │   └── ConsultarNfseUseCase.php
│   │
│   ├── DTO/                         # Data Transfer Objects
│   │   ├── Request/
│   │   │   ├── DpsRequest.php
│   │   │   ├── EventoRequest.php
│   │   │   ├── ConsultaRequest.php
│   │   │   └── CancelamentoRequest.php
│   │   │
│   │   └── Response/
│   │       ├── NfseResponse.php
│   │       ├── DpsResponse.php
│   │       ├── EventoResponse.php
│   │       └── ErrorResponse.php
│   │
│   ├── Validator/                   # Validadores de aplicação
│   │   ├── DpsValidator.php
│   │   ├── EventoValidator.php
│   │   └── ConsultaValidator.php
│   │
│   └── Exception/                   # Exceções de aplicação
│       ├── ApplicationException.php
│       ├── ValidationException.php
│       └── ServiceException.php
│
├── Infrastructure/                  # Camada de Infraestrutura
│   ├── Http/                        # Comunicação HTTP
│   │   ├── Contract/
│   │   │   └── HttpClientInterface.php
│   │   │
│   │   ├── Client/
│   │   │   ├── CurlHttpClient.php   # Implementação Curl
│   │   │   └── GuzzleHttpClient.php # Implementação Guzzle (opcional)
│   │   │
│   │   ├── ApiConnector.php         # Conector principal
│   │   ├── RequestBuilder.php       # Construtor de requests
│   │   ├── ResponseParser.php       # Parser de respostas
│   │   │
│   │   └── Exception/
│   │       ├── HttpException.php
│   │       ├── ConnectionException.php
│   │       └── TimeoutException.php
│   │
│   ├── Security/                    # Segurança e Certificados
│   │   ├── Contract/
│   │   │   ├── CertificateManagerInterface.php
│   │   │   └── XmlSignerInterface.php
│   │   │
│   │   ├── CertificateManager.php   # Gestão de certificados
│   │   ├── XmlSigner.php            # Assinatura XML (NFePHP)
│   │   ├── CertificateValidator.php # Validação de certificados
│   │   │
│   │   └── Exception/
│   │       ├── CertificateException.php
│   │       ├── CertificateExpiredException.php
│   │       └── SignatureException.php
│   │
│   ├── Xml/                         # Manipulação XML
│   │   ├── Builder/
│   │   │   ├── Contract/
│   │   │   │   └── XmlBuilderInterface.php
│   │   │   │
│   │   │   ├── DpsXmlBuilder.php    # Constrói XML da DPS
│   │   │   ├── EventoXmlBuilder.php # Constrói XML de eventos
│   │   │   ├── PrestadorBuilder.php # Construtor parcial
│   │   │   ├── TomadorBuilder.php   # Construtor parcial
│   │   │   └── ServicoBuilder.php   # Construtor parcial
│   │   │
│   │   ├── Parser/
│   │   │   ├── NfseXmlParser.php    # Parser XML NFSe
│   │   │   ├── DpsXmlParser.php     # Parser XML DPS
│   │   │   └── ErrorXmlParser.php   # Parser de erros
│   │   │
│   │   ├── Validator/
│   │   │   ├── XsdValidator.php     # Validação contra XSD
│   │   │   └── SchemaLoader.php     # Carregador de schemas
│   │   │
│   │   └── Exception/
│   │       ├── XmlException.php
│   │       ├── XmlBuildException.php
│   │       └── XmlValidationException.php
│   │
│   ├── Config/                      # Configuração
│   │   ├── Contract/
│   │   │   └── ConfigInterface.php
│   │   │
│   │   ├── Configuration.php        # Configuração principal
│   │   ├── MunicipioConfigLoader.php # Loader config municípios
│   │   ├── ApiEndpoints.php         # Endpoints das APIs
│   │   │
│   │   └── Exception/
│   │       └── ConfigException.php
│   │
│   ├── Repository/                  # Repositórios
│   │   ├── Contract/
│   │   │   └── PrefeituraRepositoryInterface.php
│   │   │
│   │   └── PrefeituraRepository.php # Repo de prefeituras
│   │
│   ├── Cache/                       # Cache (opcional)
│   │   ├── Contract/
│   │   │   └── CacheInterface.php
│   │   │
│   │   └── FileCache.php            # Cache em arquivo
│   │
│   └── Logger/                      # Logging
│       ├── Contract/
│       │   └── LoggerInterface.php
│       │
│       └── FileLogger.php           # Log em arquivo
│
├── Presentation/                    # Camada de Apresentação
│   ├── Facade/                      # Facades (API pública)
│   │   └── NfseNacionalFacade.php   # Facade principal
│   │
│   └── Factory/                     # Factories
│       ├── NfseFactory.php          # Factory de NFSe
│       ├── DpsFactory.php           # Factory de DPS
│       ├── ServiceFactory.php       # Factory de serviços
│       └── ConfigFactory.php        # Factory de config
│
└── Support/                         # Utilitários
    ├── Helper/                      # Helpers
    │   ├── DateHelper.php           # Manipulação de datas
    │   ├── StringHelper.php         # Manipulação de strings
    │   ├── XmlHelper.php            # Helpers XML
    │   └── ValidationHelper.php     # Validações genéricas
    │
    └── Trait/                       # Traits reutilizáveis
        ├── ValidatesTrait.php       # Trait de validação
        └── FormatsValuesTrait.php   # Trait de formatação
```

---

## 🔧 Camadas e Responsabilidades

### 1. Domain Layer (Camada de Domínio)

**Responsabilidade:** Regras de negócio puras, independentes de infraestrutura.

#### Entities (Entidades)

Objetos com identidade única que representam conceitos de negócio.

```php
<?php

namespace Hadder\NfseNacional\Domain\Entity;

use Hadder\NfseNacional\Domain\ValueObject\Cnpj;
use Hadder\NfseNacional\Domain\ValueObject\ChaveAcesso;
use Hadder\NfseNacional\Domain\Enum\TipoAmbiente;

class Dps
{
    private ?ChaveAcesso $chaveAcesso = null;
    
    public function __construct(
        private TipoAmbiente $tipoAmbiente,
        private \DateTimeImmutable $dataEmissao,
        private string $versaoAplicacao,
        private int $serie,
        private int $numero,
        private \DateTimeImmutable $dataCompetencia,
        private Prestador $prestador,
        private Tomador $tomador,
        private Servico $servico,
    ) {
        $this->validate();
    }
    
    private function validate(): void
    {
        if ($this->numero <= 0) {
            throw new \InvalidArgumentException('Número da DPS deve ser maior que zero');
        }
        
        if ($this->serie <= 0) {
            throw new \InvalidArgumentException('Série da DPS deve ser maior que zero');
        }
    }
    
    public function gerarChaveAcesso(): ChaveAcesso
    {
        // Lógica de geração da chave de acesso
        // Seguindo especificação NFSe Nacional
        $codigo = sprintf(
            '%s%s%02d%05d',
            $this->prestador->getCnpj()->getNumero(),
            $this->dataEmissao->format('YmdHis'),
            $this->serie,
            $this->numero
        );
        
        $this->chaveAcesso = new ChaveAcesso($codigo);
        return $this->chaveAcesso;
    }
    
    // Getters...
}
```

#### Value Objects (Objetos de Valor)

Objetos imutáveis que representam conceitos sem identidade.

```php
<?php

namespace Hadder\NfseNacional\Domain\ValueObject;

use Hadder\NfseNacional\Domain\Exception\InvalidCnpjException;

final readonly class Cnpj
{
    private string $numero;
    
    public function __construct(string $cnpj)
    {
        $this->numero = $this->validate($cnpj);
    }
    
    private function validate(string $cnpj): string
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        
        if (strlen($cnpj) !== 14) {
            throw new InvalidCnpjException("CNPJ inválido: {$cnpj}");
        }
        
        // Validação do dígito verificador
        if (!$this->validarDigitoVerificador($cnpj)) {
            throw new InvalidCnpjException("CNPJ com dígito verificador inválido: {$cnpj}");
        }
        
        return $cnpj;
    }
    
    private function validarDigitoVerificador(string $cnpj): bool
    {
        // Implementação completa da validação do CNPJ
        // ...
        return true;
    }
    
    public function getNumero(): string
    {
        return $this->numero;
    }
    
    public function formatado(): string
    {
        return sprintf(
            '%s.%s.%s/%s-%s',
            substr($this->numero, 0, 2),
            substr($this->numero, 2, 3),
            substr($this->numero, 5, 3),
            substr($this->numero, 8, 4),
            substr($this->numero, 12, 2)
        );
    }
    
    public function __toString(): string
    {
        return $this->numero;
    }
}
```

#### Enums (PHP 8.1+)

```php
<?php

namespace Hadder\NfseNacional\Domain\Enum;

enum TipoAmbiente: int
{
    case PRODUCAO = 1;
    case HOMOLOGACAO = 2;
    
    public function descricao(): string
    {
        return match($this) {
            self::PRODUCAO => 'Produção',
            self::HOMOLOGACAO => 'Homologação',
        };
    }
    
    public function isProducao(): bool
    {
        return $this === self::PRODUCAO;
    }
}

enum TipoEmissao: int
{
    case PRESTADOR = 1;
    case TOMADOR = 2;
    case INTERMEDIARIO = 3;
    
    public function descricao(): string
    {
        return match($this) {
            self::PRESTADOR => 'Emitente: Prestador',
            self::TOMADOR => 'Emitente: Tomador',
            self::INTERMEDIARIO => 'Emitente: Intermediário',
        };
    }
}

enum MotivoEvento: string
{
    case ERRO_EMISSAO = '01';
    case SERVICO_NAO_PRESTADO = '02';
    case DESENQUADRAMENTO_SIMPLES = '03';
    case ENQUADRAMENTO_SIMPLES = '04';
    case INCLUSAO_IMUNIDADE = '05';
    case EXCLUSAO_IMUNIDADE = '06';
    case REJEICAO_TOMADOR = '07';
    case OUTROS = '99';
    
    public function descricao(): string
    {
        return match($this) {
            self::ERRO_EMISSAO => 'Erro na Emissão',
            self::SERVICO_NAO_PRESTADO => 'Serviço não Prestado',
            self::DESENQUADRAMENTO_SIMPLES => 'Desenquadramento de NFS-e do Simples Nacional',
            self::ENQUADRAMENTO_SIMPLES => 'Enquadramento de NFS-e no Simples Nacional',
            self::INCLUSAO_IMUNIDADE => 'Inclusão Retroativa de Imunidade/Isenção',
            self::EXCLUSAO_IMUNIDADE => 'Exclusão Retroativa de Imunidade/Isenção',
            self::REJEICAO_TOMADOR => 'Rejeição pelo tomador/intermediário',
            self::OUTROS => 'Outros',
        };
    }
}
```

---

### 2. Application Layer (Camada de Aplicação)

**Responsabilidade:** Orquestrar casos de uso e lógica de aplicação.

#### Services

```php
<?php

namespace Hadder\NfseNacional\Application\Service;

use Hadder\NfseNacional\Application\DTO\Request\DpsRequest;
use Hadder\NfseNacional\Application\DTO\Response\NfseResponse;
use Hadder\NfseNacional\Application\Validator\DpsValidator;
use Hadder\NfseNacional\Domain\Entity\Dps;
use Hadder\NfseNacional\Infrastructure\Http\ApiConnector;
use Hadder\NfseNacional\Infrastructure\Security\XmlSigner;
use Hadder\NfseNacional\Infrastructure\Xml\Builder\DpsXmlBuilder;
use Hadder\NfseNacional\Infrastructure\Xml\Validator\XsdValidator;

class EmitirDpsService
{
    public function __construct(
        private ApiConnector $apiConnector,
        private DpsXmlBuilder $xmlBuilder,
        private XmlSigner $xmlSigner,
        private XsdValidator $xsdValidator,
        private DpsValidator $validator,
    ) {}
    
    public function executar(DpsRequest $request): NfseResponse
    {
        // 1. Validar dados de entrada
        $this->validator->validate($request);
        
        // 2. Criar entidade de domínio
        $dps = $this->criarDpsFromRequest($request);
        
        // 3. Gerar chave de acesso
        $dps->gerarChaveAcesso();
        
        // 4. Construir XML
        $xml = $this->xmlBuilder->build($dps);
        
        // 5. Validar contra XSD
        $this->xsdValidator->validate($xml, 'DPS_v1.01.xsd');
        
        // 6. Assinar XML
        $xmlAssinado = $this->xmlSigner->sign($xml, 'infDPS', 'DPS');
        
        // 7. Comprimir e codificar
        $payload = $this->prepararPayload($xmlAssinado);
        
        // 8. Enviar para API
        $response = $this->apiConnector->post('nfse', $payload);
        
        // 9. Processar resposta
        return $this->processarResposta($response);
    }
    
    private function criarDpsFromRequest(DpsRequest $request): Dps
    {
        // Lógica de conversão DTO -> Entity
        // ...
    }
    
    private function prepararPayload(string $xml): array
    {
        $gzipped = gzencode($xml);
        $base64 = base64_encode($gzipped);
        
        return ['dpsXmlGZipB64' => $base64];
    }
    
    private function processarResposta(array $response): NfseResponse
    {
        // Lógica de conversão resposta -> DTO
        // ...
    }
}
```

#### DTOs (Data Transfer Objects)

```php
<?php

namespace Hadder\NfseNacional\Application\DTO\Request;

final readonly class DpsRequest
{
    public function __construct(
        public int $tipoAmbiente,
        public string $dataEmissao,
        public string $versaoAplicacao,
        public int $serie,
        public int $numero,
        public string $dataCompetencia,
        public int $tipoEmissao,
        public string $codigoMunicipioEmissor,
        public PrestadorRequest $prestador,
        public TomadorRequest $tomador,
        public ServicoRequest $servico,
        public ?SubstituicaoRequest $substituicao = null,
        public ?IntermediarioRequest $intermediario = null,
    ) {}
    
    public function toArray(): array
    {
        return [
            'tipoAmbiente' => $this->tipoAmbiente,
            'dataEmissao' => $this->dataEmissao,
            // ...
        ];
    }
}
```

---

### 3. Infrastructure Layer (Camada de Infraestrutura)

**Responsabilidade:** Implementações técnicas e integrações externas.

#### HTTP Client

```php
<?php

namespace Hadder\NfseNacional\Infrastructure\Http\Client;

use Hadder\NfseNacional\Infrastructure\Http\Contract\HttpClientInterface;
use Hadder\NfseNacional\Infrastructure\Http\Exception\ConnectionException;
use Hadder\NfseNacional\Infrastructure\Http\Exception\TimeoutException;

class CurlHttpClient implements HttpClientInterface
{
    private const DEFAULT_TIMEOUT = 30;
    private const DEFAULT_CONNECT_TIMEOUT = 10;
    
    public function __construct(
        private int $timeout = self::DEFAULT_TIMEOUT,
        private int $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT,
    ) {}
    
    public function get(string $url, array $headers = []): array
    {
        return $this->request('GET', $url, null, $headers);
    }
    
    public function post(string $url, mixed $data, array $headers = []): array
    {
        return $this->request('POST', $url, $data, $headers);
    }
    
    private function request(
        string $method,
        string $url,
        mixed $data = null,
        array $headers = []
    ): array {
        $ch = curl_init();
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $this->prepareHeaders($headers),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];
        
        if ($data !== null) {
            $options[CURLOPT_POSTFIELDS] = is_array($data) 
                ? json_encode($data) 
                : $data;
        }
        
        curl_setopt_array($ch, $options);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        
        curl_close($ch);
        
        if ($errno === CURLE_OPERATION_TIMEDOUT) {
            throw new TimeoutException("Request timeout after {$this->timeout}s");
        }
        
        if ($errno !== 0) {
            throw new ConnectionException("CURL Error [{$errno}]: {$error}");
        }
        
        return [
            'status' => $httpCode,
            'body' => $response,
        ];
    }
    
    private function prepareHeaders(array $headers): array
    {
        $prepared = ['Content-Type: application/json'];
        
        foreach ($headers as $key => $value) {
            $prepared[] = "{$key}: {$value}";
        }
        
        return $prepared;
    }
}
```

#### XML Builder

```php
<?php

namespace Hadder\NfseNacional\Infrastructure\Xml\Builder;

use Hadder\NfseNacional\Domain\Entity\Dps;
use NFePHP\Common\DOMImproved as Dom;

class DpsXmlBuilder implements Contract\XmlBuilderInterface
{
    private Dom $dom;
    
    public function __construct()
    {
        $this->dom = new Dom('1.0', 'UTF-8');
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = false;
    }
    
    public function build(Dps $dps): string
    {
        $this->reset();
        
        // Elemento raiz
        $dpsNode = $this->dom->createElement('DPS');
        $dpsNode->setAttribute('versao', '1.01');
        $dpsNode->setAttribute('xmlns', 'http://www.sped.fazenda.gov.br/nfse');
        
        // infDPS
        $infDpsNode = $this->dom->createElement('infDPS');
        $infDpsNode->setAttribute('Id', $dps->getChaveAcesso()->getId());
        
        // Dados básicos
        $this->addElement($infDpsNode, 'tpAmb', $dps->getTipoAmbiente()->value);
        $this->addElement($infDpsNode, 'dhEmi', $dps->getDataEmissao()->format('Y-m-d\TH:i:sP'));
        $this->addElement($infDpsNode, 'verAplic', $dps->getVersaoAplicacao());
        $this->addElement($infDpsNode, 'serie', $dps->getSerie());
        $this->addElement($infDpsNode, 'nDPS', $dps->getNumero());
        $this->addElement($infDpsNode, 'dCompet', $dps->getDataCompetencia()->format('Y-m-d'));
        $this->addElement($infDpsNode, 'tpEmit', $dps->getTipoEmissao()->value);
        $this->addElement($infDpsNode, 'cLocEmi', $dps->getCodigoMunicipioEmissor());
        
        // Substituição (se houver)
        if ($dps->getSubstituicao() !== null) {
            $this->buildSubstituicao($infDpsNode, $dps->getSubstituicao());
        }
        
        // Prestador
        $this->buildPrestador($infDpsNode, $dps->getPrestador());
        
        // Tomador
        $this->buildTomador($infDpsNode, $dps->getTomador());
        
        // Intermediário (se houver)
        if ($dps->getIntermediario() !== null) {
            $this->buildIntermediario($infDpsNode, $dps->getIntermediario());
        }
        
        // Serviço
        $this->buildServico($infDpsNode, $dps->getServico());
        
        $dpsNode->appendChild($infDpsNode);
        $this->dom->appendChild($dpsNode);
        
        return $this->dom->saveXML();
    }
    
    private function reset(): void
    {
        $this->dom = new Dom('1.0', 'UTF-8');
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = false;
    }
    
    private function addElement(
        \DOMNode $parent,
        string $name,
        mixed $value,
        bool $required = true
    ): ?\DOMElement {
        if (!$required && ($value === null || $value === '')) {
            return null;
        }
        
        $element = $this->dom->createElement($name, htmlspecialchars((string) $value));
        $parent->appendChild($element);
        
        return $element;
    }
    
    private function buildPrestador(\DOMNode $parent, Prestador $prestador): void
    {
        // Implementação detalhada...
    }
    
    private function buildTomador(\DOMNode $parent, Tomador $tomador): void
    {
        // Implementação detalhada...
    }
    
    // Outros métodos...
}
```

#### API Connector

```php
<?php

namespace Hadder\NfseNacional\Infrastructure\Http;

use Hadder\NfseNacional\Infrastructure\Config\Configuration;
use Hadder\NfseNacional\Infrastructure\Http\Contract\HttpClientInterface;
use Hadder\NfseNacional\Infrastructure\Security\CertificateManager;

class ApiConnector
{
    private string $baseUrl;
    
    public function __construct(
        private Configuration $config,
        private HttpClientInterface $httpClient,
        private CertificateManager $certificateManager,
    ) {
        $this->baseUrl = $this->resolveBaseUrl();
    }
    
    public function get(string $endpoint, array $params = []): array
    {
        $url = $this->buildUrl($endpoint, $params);
        $headers = $this->buildHeaders();
        
        $response = $this->httpClient->get($url, $headers);
        
        return $this->parseResponse($response);
    }
    
    public function post(string $endpoint, array $data): array
    {
        $url = $this->buildUrl($endpoint);
        $headers = $this->buildHeaders();
        
        $response = $this->httpClient->post($url, $data, $headers);
        
        return $this->parseResponse($response);
    }
    
    private function resolveBaseUrl(): string
    {
        $ambiente = $this->config->getTipoAmbiente();
        $tipo = $this->config->getTipoApi(); // 'sefin', 'adn', 'nfse'
        
        $key = "{$tipo}_" . ($ambiente->isProducao() ? 'producao' : 'homologacao');
        
        return $this->config->getUrl($key);
    }
    
    private function buildUrl(string $endpoint, array $params = []): string
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }
    
    private function buildHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'NFSeNacional-PHP/' . $this->config->getVersion(),
        ];
    }
    
    private function parseResponse(array $response): array
    {
        $body = $response['body'];
        $status = $response['status'];
        
        // Se for gzip base64 (para consultas)
        if (is_string($body) && str_contains($body, 'nfseXmlGZipB64')) {
            $decoded = json_decode($body, true);
            if (isset($decoded['nfseXmlGZipB64'])) {
                $body = $this->decodeGzipBase64($decoded['nfseXmlGZipB64']);
            }
        }
        
        // Se for JSON
        if (is_string($body)) {
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $body = $decoded;
            }
        }
        
        return [
            'status' => $status,
            'data' => $body,
            'success' => $status >= 200 && $status < 300,
        ];
    }
    
    private function decodeGzipBase64(string $data): string
    {
        $decoded = base64_decode($data, true);
        if ($decoded === false) {
            throw new \RuntimeException('Falha ao decodificar base64');
        }
        
        $uncompressed = gzdecode($decoded);
        if ($uncompressed === false) {
            throw new \RuntimeException('Falha ao descomprimir gzip');
        }
        
        return $uncompressed;
    }
}
```

---

### 4. Presentation Layer (Camada de Apresentação)

**Responsabilidade:** Interface pública da biblioteca (API).

#### Facade Principal

```php
<?php

namespace Hadder\NfseNacional\Presentation\Facade;

use Hadder\NfseNacional\Application\DTO\Request\DpsRequest;
use Hadder\NfseNacional\Application\DTO\Request\EventoRequest;
use Hadder\NfseNacional\Application\DTO\Response\NfseResponse;
use Hadder\NfseNacional\Application\Service\EmitirDpsService;
use Hadder\NfseNacional\Application\Service\ConsultarNfseService;
use Hadder\NfseNacional\Application\Service\CancelarNfseService;
use Hadder\NfseNacional\Presentation\Factory\ServiceFactory;
use NFePHP\Common\Certificate;

class NfseNacionalFacade
{
    private EmitirDpsService $emitirDpsService;
    private ConsultarNfseService $consultarNfseService;
    private CancelarNfseService $cancelarNfseService;
    
    private function __construct(
        private array $config,
        private Certificate $certificado,
    ) {
        $this->inicializarServicos();
    }
    
    public static function create(array $config, Certificate $certificado): self
    {
        return new self($config, $certificado);
    }
    
    /**
     * Emite uma DPS (Declaração de Prestação de Serviço)
     *
     * @param DpsRequest $request Dados da DPS
     * @return NfseResponse Resposta com dados da NFSe emitida
     * @throws \Hadder\NfseNacional\Application\Exception\ValidationException
     * @throws \Hadder\NfseNacional\Infrastructure\Http\Exception\HttpException
     */
    public function emitirDps(DpsRequest $request): NfseResponse
    {
        return $this->emitirDpsService->executar($request);
    }
    
    /**
     * Consulta uma NFSe pela chave de acesso
     *
     * @param string $chave Chave de acesso (50 caracteres)
     * @return NfseResponse|null Dados da NFSe ou null se não encontrada
     */
    public function consultarPorChave(string $chave): ?NfseResponse
    {
        return $this->consultarNfseService->consultarPorChave($chave);
    }
    
    /**
     * Consulta uma DPS pela chave de acesso
     *
     * @param string $chave Chave de acesso (50 caracteres)
     * @return array Dados da DPS
     */
    public function consultarDpsPorChave(string $chave): array
    {
        return $this->consultarNfseService->consultarDpsPorChave($chave);
    }
    
    /**
     * Cancela uma NFSe
     *
     * @param EventoRequest $request Dados do cancelamento
     * @return array Resultado do cancelamento
     */
    public function cancelar(EventoRequest $request): array
    {
        return $this->cancelarNfseService->executar($request);
    }
    
    /**
     * Consulta eventos de uma NFSe
     *
     * @param string $chave Chave de acesso
     * @param string|null $tipoEvento Tipo do evento (opcional)
     * @param int|null $sequencial Número sequencial (opcional)
     * @return array Lista de eventos
     */
    public function consultarEventos(
        string $chave,
        ?string $tipoEvento = null,
        ?int $sequencial = null
    ): array {
        return $this->consultarNfseService->consultarEventos(
            $chave,
            $tipoEvento,
            $sequencial
        );
    }
    
    /**
     * Consulta DANFSE (Documento Auxiliar) de uma NFSe
     *
     * @param string $chave Chave de acesso
     * @return string|array PDF em base64 ou array com erro
     */
    public function consultarDanfse(string $chave): string|array
    {
        return $this->consultarNfseService->consultarDanfse($chave);
    }
    
    private function inicializarServicos(): void
    {
        $factory = new ServiceFactory($this->config, $this->certificado);
        
        $this->emitirDpsService = $factory->createEmitirDpsService();
        $this->consultarNfseService = $factory->createConsultarNfseService();
        $this->cancelarNfseService = $factory->createCancelarNfseService();
    }
}
```

---

## 🔒 Segurança e Compliance

### Gestão de Certificados

```php
<?php

namespace Hadder\NfseNacional\Infrastructure\Security;

use NFePHP\Common\Certificate;
use Hadder\NfseNacional\Infrastructure\Security\Exception\CertificateExpiredException;

class CertificateManager
{
    private Certificate $certificate;
    private string $tempDir;
    
    public function __construct(Certificate $certificate)
    {
        $this->certificate = $certificate;
        $this->validate();
        $this->setupTempDirectory();
    }
    
    private function validate(): void
    {
        if ($this->certificate->isExpired()) {
            $expiry = $this->certificate->getValidTo();
            throw new CertificateExpiredException(
                "Certificado expirado em {$expiry->format('d/m/Y')}"
            );
        }
        
        // Verificar se expira nos próximos 30 dias
        $daysToExpire = $this->certificate->getValidTo()->diff(new \DateTime())->days;
        if ($daysToExpire <= 30) {
            trigger_error(
                "Certificado expira em {$daysToExpire} dias",
                E_USER_WARNING
            );
        }
    }
    
    private function setupTempDirectory(): void
    {
        $cnpj = $this->certificate->getCnpj() ?? $this->certificate->getCpf();
        
        $this->tempDir = sys_get_temp_dir() 
            . '/nfse-nacional-' 
            . posix_getuid() 
            . '/' 
            . $cnpj 
            . '/certs/';
            
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0700, true);
        }
    }
    
    public function saveTemporaryFiles(): array
    {
        $files = [
            'private' => $this->tempDir . bin2hex(random_bytes(8)) . '.pem',
            'public' => $this->tempDir . bin2hex(random_bytes(8)) . '.pem',
            'cert' => $this->tempDir . bin2hex(random_bytes(8)) . '.pem',
        ];
        
        file_put_contents($files['private'], $this->certificate->privateKey);
        file_put_contents($files['public'], $this->certificate->publicKey);
        file_put_contents($files['cert'], $this->certificate);
        
        // Definir permissões restritas
        chmod($files['private'], 0600);
        chmod($files['public'], 0600);
        chmod($files['cert'], 0600);
        
        return $files;
    }
    
    public function cleanTemporaryFiles(array $files): void
    {
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
    
    public function getCertificate(): Certificate
    {
        return $this->certificate;
    }
}
```

### Validação XSD

```php
<?php

namespace Hadder\NfseNacional\Infrastructure\Xml\Validator;

use Hadder\NfseNacional\Infrastructure\Xml\Exception\XmlValidationException;

class XsdValidator
{
    private string $schemasDir;
    
    public function __construct(?string $schemasDir = null)
    {
        $this->schemasDir = $schemasDir ?? __DIR__ . '/../../../../storage/schemes/';
    }
    
    /**
     * Valida XML contra XSD
     *
     * @param string $xml XML a validar
     * @param string $xsdFile Nome do arquivo XSD
     * @throws XmlValidationException Se validação falhar
     */
    public function validate(string $xml, string $xsdFile): void
    {
        $xsdPath = $this->schemasDir . $xsdFile;
        
        if (!file_exists($xsdPath)) {
            throw new \InvalidArgumentException("Schema XSD não encontrado: {$xsdFile}");
        }
        
        libxml_use_internal_errors(true);
        libxml_clear_errors();
        
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        
        if (!$dom->loadXML($xml)) {
            $errors = $this->getLibxmlErrors();
            throw new XmlValidationException("XML malformado: " . implode('; ', $errors));
        }
        
        if (!$dom->schemaValidate($xsdPath)) {
            $errors = $this->getLibxmlErrors();
            throw new XmlValidationException(
                "XML não válido segundo XSD {$xsdFile}: " . implode('; ', $errors)
            );
        }
        
        libxml_clear_errors();
        libxml_use_internal_errors(false);
    }
    
    private function getLibxmlErrors(): array
    {
        $errors = [];
        
        foreach (libxml_get_errors() as $error) {
            $errors[] = sprintf(
                '[%s] Linha %d: %s',
                $error->level === LIBXML_ERR_WARNING ? 'AVISO' : 'ERRO',
                $error->line,
                trim($error->message)
            );
        }
        
        return $errors;
    }
}
```

### Tratamento de Erros da API

```php
<?php

namespace Hadder\NfseNacional\Infrastructure\Http;

use Hadder\NfseNacional\Application\DTO\Response\ErrorResponse;

class ResponseParser
{
    /**
     * Códigos de erro da NFSe Nacional
     */
    private const ERRO_NAO_CATALOGADO = 'E999';
    private const ERRO_CERTIFICADO_INVALIDO = 'E001';
    private const ERRO_CERTIFICADO_EXPIRADO = 'E002';
    
    public function parseError(array $response): ErrorResponse
    {
        $codigo = $response['codigo'] ?? self::ERRO_NAO_CATALOGADO;
        $mensagem = $response['mensagem'] ?? 'Erro não identificado';
        $detalhes = $response['detalhes'] ?? [];
        
        return new ErrorResponse(
            codigo: $codigo,
            mensagem: $this->traduzirMensagem($codigo, $mensagem),
            detalhes: $detalhes,
            recuperavel: $this->isRecuperavel($codigo),
        );
    }
    
    private function traduzirMensagem(string $codigo, string $mensagem): string
    {
        return match($codigo) {
            self::ERRO_NAO_CATALOGADO => 
                'Erro não catalogado. Se persistir em produção, contate o suporte.',
            self::ERRO_CERTIFICADO_INVALIDO => 
                'Certificado digital inválido ou não autorizado.',
            self::ERRO_CERTIFICADO_EXPIRADO => 
                'Certificado digital expirado.',
            default => $mensagem,
        };
    }
    
    private function isRecuperavel(string $codigo): bool
    {
        // Erros recuperáveis (podem tentar novamente)
        $recuperaveis = [
            self::ERRO_NAO_CATALOGADO,
            'E500', // Erro de servidor
            'E503', // Serviço indisponível
        ];
        
        return in_array($codigo, $recuperaveis);
    }
}
```

---

## 📋 Plano de Migração

### Fase 1: Preparação (Semanas 1-2)

#### 1.1 Setup do Ambiente
- [ ] Criar branch `refactor/clean-architecture`
- [ ] Configurar PHPStan (nível 8)
- [ ] Configurar PHP-CS-Fixer
- [ ] Setup PHPUnit
- [ ] Configurar CI/CD (GitHub Actions)

#### 1.2 Criação da Estrutura Base
- [ ] Criar estrutura de diretórios
- [ ] Implementar interfaces principais
- [ ] Criar exceções customizadas
- [ ] Implementar enums

### Fase 2: Domain Layer (Semanas 3-4)

#### 2.1 Value Objects
- [ ] Implementar `Cnpj`
- [ ] Implementar `Cpf`
- [ ] Implementar `ChaveAcesso`
- [ ] Implementar `CodigoMunicipio`
- [ ] Implementar `Money`
- [ ] Testes unitários (>90% cobertura)

#### 2.2 Entities
- [ ] Implementar `Dps`
- [ ] Implementar `Nfse`
- [ ] Implementar `Prestador`
- [ ] Implementar `Tomador`
- [ ] Implementar `Servico`
- [ ] Implementar `Evento`
- [ ] Testes unitários (>90% cobertura)

### Fase 3: Infrastructure Layer (Semanas 5-7)

#### 3.1 HTTP e Comunicação
- [ ] Implementar `HttpClientInterface`
- [ ] Implementar `CurlHttpClient`
- [ ] Implementar `ApiConnector`
- [ ] Testes de integração

#### 3.2 XML
- [ ] Implementar builders XML
- [ ] Implementar parsers XML
- [ ] Implementar `XsdValidator`
- [ ] Testes com XMLs reais

#### 3.3 Segurança
- [ ] Implementar `CertificateManager`
- [ ] Implementar `XmlSigner`
- [ ] Testes de assinatura

#### 3.4 Configuração
- [ ] Implementar `Configuration`
- [ ] Migrar `prefeituras.json`
- [ ] Implementar `MunicipioConfigLoader`

### Fase 4: Application Layer (Semanas 8-9)

#### 4.1 Services
- [ ] Implementar `EmitirDpsService`
- [ ] Implementar `ConsultarNfseService`
- [ ] Implementar `CancelarNfseService`
- [ ] Testes de integração

#### 4.2 DTOs e Validators
- [ ] Criar DTOs de request
- [ ] Criar DTOs de response
- [ ] Implementar validators
- [ ] Testes

### Fase 5: Presentation Layer (Semana 10)

#### 5.1 Facade e Factories
- [ ] Implementar `NfseNacionalFacade`
- [ ] Implementar factories
- [ ] Documentação da API pública

### Fase 6: Testes e Documentação (Semanas 11-12)

#### 6.1 Testes
- [ ] Testes end-to-end
- [ ] Testes com ambiente de homologação
- [ ] Validação com schemas v1.00 e v1.01
- [ ] Performance tests

#### 6.2 Documentação
- [ ] README atualizado
- [ ] Guia de migração
- [ ] Exemplos práticos
- [ ] API Reference
- [ ] Changelog

### Fase 7: Migração e Release (Semanas 13-14)

#### 7.1 Retrocompatibilidade
- [ ] Criar camada de compatibilidade
- [ ] Deprecar APIs antigas
- [ ] Guia de migração detalhado

#### 7.2 Release
- [ ] Code review completo
- [ ] Merge para main
- [ ] Tag v2.0.0
- [ ] Publicar no Packagist
- [ ] Anunciar breaking changes

---

## 🧪 Testes

### Estrutura de Testes

```
tests/
├── Unit/                    # Testes unitários isolados
│   ├── Domain/
│   │   ├── Entity/
│   │   │   ├── DpsTest.php
│   │   │   └── NfseTest.php
│   │   │
│   │   └── ValueObject/
│   │       ├── CnpjTest.php
│   │       ├── CpfTest.php
│   │       └── ChaveAcessoTest.php
│   │
│   └── Application/
│       └── Service/
│           └── EmitirDpsServiceTest.php
│
├── Integration/             # Testes de integração
│   ├── Http/
│   │   └── ApiConnectorTest.php
│   │
│   └── Xml/
│       ├── DpsXmlBuilderTest.php
│       └── XsdValidatorTest.php
│
├── Feature/                 # Testes end-to-end
│   ├── EmitirDpsTest.php
│   ├── ConsultarNfseTest.php
│   └── CancelarNfseTest.php
│
└── Fixtures/                # Dados para testes
    ├── certificado-test.pfx
    ├── xml/
    │   ├── dps-valid.xml
    │   ├── dps-invalid.xml
    │   └── nfse-response.xml
    │
    └── json/
        └── api-responses.json
```

### Exemplo de Teste Unitário

```php
<?php

namespace Tests\Unit\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Hadder\NfseNacional\Domain\ValueObject\Cnpj;
use Hadder\NfseNacional\Domain\Exception\InvalidCnpjException;

class CnpjTest extends TestCase
{
    public function test_deve_criar_cnpj_valido(): void
    {
        $cnpj = new Cnpj('11.222.333/0001-81');
        
        $this->assertEquals('11222333000181', $cnpj->getNumero());
        $this->assertEquals('11.222.333/0001-81', $cnpj->formatado());
    }
    
    public function test_deve_aceitar_cnpj_sem_formatacao(): void
    {
        $cnpj = new Cnpj('11222333000181');
        
        $this->assertEquals('11222333000181', $cnpj->getNumero());
    }
    
    public function test_deve_rejeitar_cnpj_invalido(): void
    {
        $this->expectException(InvalidCnpjException::class);
        
        new Cnpj('11.222.333/0001-99');
    }
    
    public function test_deve_rejeitar_cnpj_com_tamanho_incorreto(): void
    {
        $this->expectException(InvalidCnpjException::class);
        
        new Cnpj('123456789');
    }
    
    public function test_deve_ser_imutavel(): void
    {
        $cnpj1 = new Cnpj('11222333000181');
        $cnpj2 = new Cnpj('11222333000181');
        
        $this->assertEquals($cnpj1->getNumero(), $cnpj2->getNumero());
        $this->assertNotSame($cnpj1, $cnpj2);
    }
}
```

### Exemplo de Teste de Integração

```php
<?php

namespace Tests\Integration\Xml;

use PHPUnit\Framework\TestCase;
use Hadder\NfseNacional\Domain\Entity\Dps;
use Hadder\NfseNacional\Infrastructure\Xml\Builder\DpsXmlBuilder;
use Hadder\NfseNacional\Infrastructure\Xml\Validator\XsdValidator;

class DpsXmlBuilderTest extends TestCase
{
    private DpsXmlBuilder $builder;
    private XsdValidator $validator;
    
    protected function setUp(): void
    {
        $this->builder = new DpsXmlBuilder();
        $this->validator = new XsdValidator();
    }
    
    public function test_deve_gerar_xml_valido_dps(): void
    {
        $dps = $this->createDpsFixture();
        
        $xml = $this->builder->build($dps);
        
        // Verificar estrutura básica
        $this->assertStringContainsString('<DPS', $xml);
        $this->assertStringContainsString('versao="1.01"', $xml);
        $this->assertStringContainsString('<infDPS', $xml);
        
        // Validar contra XSD
        $this->validator->validate($xml, 'DPS_v1.01.xsd');
        
        // Se chegou aqui, está válido
        $this->assertTrue(true);
    }
    
    private function createDpsFixture(): Dps
    {
        // Criar DPS para teste
        // ...
    }
}
```

---

## 📖 Referências

### Especificações Técnicas

1. **Manual de Integração NFSe Nacional v1.01**
   - Localização: `storage/schemes/`
   - Schemas XSD: DPS_v1.01.xsd, NFSe_v1.01.xsd

2. **Tabelas de Códigos**
   - Código IBGE de Municípios (7 dígitos)
   - Códigos de Motivo de Evento
   - Códigos de Regime Tributário

### Padrões e Princípios

1. **PSR-1**: Basic Coding Standard
2. **PSR-4**: Autoloading Standard
3. **PSR-12**: Extended Coding Style
4. **SOLID Principles**
5. **Clean Architecture** (Robert C. Martin)
6. **Domain-Driven Design** (Eric Evans)

### Bibliotecas Utilizadas

1. **NFePHP/sped-common**: Funções comuns para SPED
2. **symfony/var-dumper**: Debug
3. **tecnickcom/tcpdf**: Geração de PDF

### URLs Oficiais

- **Produção**: https://www.nfse.gov.br/EmissorNacional
- **Homologação**: https://www.producaorestrita.nfse.gov.br/EmissorNacional
- **Portal NFSe**: https://www.nfse.gov.br/

---

## 🎯 Benefícios Esperados

### Técnicos

✅ **Manutenibilidade +80%**: Código organizado e fácil de entender  
✅ **Testabilidade 100%**: Todas as camadas testáveis isoladamente  
✅ **Cobertura de Testes >80%**: Qualidade garantida  
✅ **Performance +20%**: Otimizações de requests e processamento  
✅ **Extensibilidade**: Fácil adicionar novos municípios/serviços

### Negócio

✅ **Confiabilidade +95%**: Tratamento robusto de erros  
✅ **Conformidade 100%**: Aderente a especificações  
✅ **Segurança**: Gestão adequada de certificados  
✅ **Suporte**: Código documentado e padronizado  
✅ **Comunidade**: Contribuições facilitadas

---

## 📞 Suporte

Para dúvidas sobre a refatoração:

1. Consultar este documento
2. Ver exemplos em `exemples/`
3. Abrir issue no repositório
4. Consultar documentação oficial NFSe Nacional

---

**Documento mantido por:** Marcela Beatriz
**Última atualização:** 13/05/2026  
**Versão:** 1.0
