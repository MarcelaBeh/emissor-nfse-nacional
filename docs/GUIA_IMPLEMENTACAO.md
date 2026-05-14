# Guia de Implementação - Refatoração NFSe Nacional

**Complemento ao documento:** [ARQUITETURA_REFATORACAO.md](ARQUITETURA_REFATORACAO.md)

---

## 📋 Índice

1. [Ordem de Implementação](#ordem-de-implementação)
2. [Exemplos de Código](#exemplos-de-código)
3. [Checklist de Desenvolvimento](#checklist-de-desenvolvimento)
4. [Padrões e Convenções](#padrões-e-convenções)
5. [Tratamento de Erros](#tratamento-de-erros)
6. [Performance e Otimização](#performance-e-otimização)

---

## 🚀 Ordem de Implementação

### Princípio: "De dentro para fora"

Implementar da camada mais interna (Domain) para a mais externa (Presentation).

```
1. Domain (Núcleo) → 2. Infrastructure → 3. Application → 4. Presentation
```

Essa ordem garante que:
- Dependências sempre apontam para dentro
- Testes podem ser feitos incrementalmente
- Cada camada tem o que precisa da camada anterior

---

## 💻 Exemplos de Código

### 1. Value Objects Completos

#### Cnpj.php

```php
<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Domain\ValueObject;

use emissorNfseNacional\NfseNacional\Domain\Exception\InvalidCnpjException;

/**
 * Value Object que representa um CNPJ válido
 * 
 * Imutável e auto-validável
 */
final readonly class Cnpj
{
    private string $numero;
    
    public function __construct(string $cnpj)
    {
        $this->numero = $this->validate($cnpj);
    }
    
    private function validate(string $cnpj): string
    {
        // Remove caracteres não numéricos
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        
        // Verifica tamanho
        if (strlen($cnpj) !== 14) {
            throw new InvalidCnpjException(
                "CNPJ deve ter 14 dígitos. Fornecido: " . strlen($cnpj)
            );
        }
        
        // Verifica se não é sequência repetida (00000000000000, 11111111111111, etc)
        if (preg_match('/^(\d)\1+$/', $cnpj)) {
            throw new InvalidCnpjException("CNPJ inválido: sequência repetida");
        }
        
        // Valida dígitos verificadores
        if (!$this->validarDigitoVerificador($cnpj)) {
            throw new InvalidCnpjException("CNPJ com dígito verificador inválido");
        }
        
        return $cnpj;
    }
    
    private function validarDigitoVerificador(string $cnpj): bool
    {
        // Primeiro dígito
        $soma = 0;
        $multiplicador = 5;
        
        for ($i = 0; $i < 12; $i++) {
            $soma += (int)$cnpj[$i] * $multiplicador;
            $multiplicador = ($multiplicador === 2) ? 9 : $multiplicador - 1;
        }
        
        $resto = $soma % 11;
        $digito1 = ($resto < 2) ? 0 : 11 - $resto;
        
        if ((int)$cnpj[12] !== $digito1) {
            return false;
        }
        
        // Segundo dígito
        $soma = 0;
        $multiplicador = 6;
        
        for ($i = 0; $i < 13; $i++) {
            $soma += (int)$cnpj[$i] * $multiplicador;
            $multiplicador = ($multiplicador === 2) ? 9 : $multiplicador - 1;
        }
        
        $resto = $soma % 11;
        $digito2 = ($resto < 2) ? 0 : 11 - $resto;
        
        return (int)$cnpj[13] === $digito2;
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
    
    public function equals(self $other): bool
    {
        return $this->numero === $other->numero;
    }
    
    public function __toString(): string
    {
        return $this->numero;
    }
}
```

#### ChaveAcesso.php

```php
<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Domain\ValueObject;

use emissorNfseNacional\NfseNacional\Domain\Exception\InvalidChaveAcessoException;

/**
 * Chave de Acesso da NFSe Nacional (50 caracteres)
 */
final readonly class ChaveAcesso
{
    private string $chave;
    
    public function __construct(string $chave)
    {
        $this->chave = $this->validate($chave);
    }
    
    private function validate(string $chave): string
    {
        $chave = preg_replace('/[^0-9]/', '', $chave);
        
        if (strlen($chave) !== 50) {
            throw new InvalidChaveAcessoException(
                "Chave de acesso deve ter 50 dígitos. Fornecido: " . strlen($chave)
            );
        }
        
        // TODO: Validar dígito verificador se necessário
        
        return $chave;
    }
    
    public function getChave(): string
    {
        return $this->chave;
    }
    
    public function getId(): string
    {
        return 'DPS' . $this->chave;
    }
    
    public function formatada(): string
    {
        // Formata em blocos de 4 dígitos separados por espaço
        return implode(' ', str_split($this->chave, 4));
    }
    
    public function __toString(): string
    {
        return $this->chave;
    }
}
```

#### Money.php

```php
<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Domain\ValueObject;

/**
 * Value Object para valores monetários
 * 
 * Evita problemas de precisão com floats
 */
final readonly class Money
{
    private int $cents; // Armazena em centavos
    
    public function __construct(float|int|string $value)
    {
        if (is_string($value)) {
            $value = (float) str_replace(',', '.', $value);
        }
        
        $this->cents = (int) round($value * 100);
    }
    
    public static function fromCents(int $cents): self
    {
        $instance = new self(0);
        $instance->cents = $cents;
        return $instance;
    }
    
    public function getValue(): float
    {
        return $this->cents / 100;
    }
    
    public function getCents(): int
    {
        return $this->cents;
    }
    
    public function add(self $other): self
    {
        return self::fromCents($this->cents + $other->cents);
    }
    
    public function subtract(self $other): self
    {
        return self::fromCents($this->cents - $other->cents);
    }
    
    public function multiply(float $factor): self
    {
        return self::fromCents((int) round($this->cents * $factor));
    }
    
    public function percentage(float $percent): self
    {
        return $this->multiply($percent / 100);
    }
    
    public function isPositive(): bool
    {
        return $this->cents > 0;
    }
    
    public function isZero(): bool
    {
        return $this->cents === 0;
    }
    
    public function formatted(bool $withSymbol = true): string
    {
        $value = number_format($this->getValue(), 2, ',', '.');
        return $withSymbol ? 'R$ ' . $value : $value;
    }
    
    public function __toString(): string
    {
        return $this->formatted(false);
    }
}
```

---

### 2. Entities Completas

#### Prestador.php

```php
<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Domain\Entity;

use emissorNfseNacional\NfseNacional\Domain\ValueObject\Cnpj;
use emissorNfseNacional\NfseNacional\Domain\ValueObject\Cpf;
use emissorNfseNacional\NfseNacional\Domain\ValueObject\Email;
use emissorNfseNacional\NfseNacional\Domain\ValueObject\Telefone;
use emissorNfseNacional\NfseNacional\Domain\Enum\RegimeTributario;

class Prestador
{
    public function __construct(
        private Cnpj|Cpf $documento,
        private ?string $inscricaoMunicipal,
        private string $razaoSocial,
        private ?Telefone $telefone,
        private ?Email $email,
        private Endereco $endereco,
        private RegimeTributario $regimeTributario,
        private ?string $nif = null,
        private ?string $caepf = null,
    ) {
        $this->validate();
    }
    
    private function validate(): void
    {
        if (empty($this->razaoSocial)) {
            throw new \InvalidArgumentException('Razão social é obrigatória');
        }
        
        if (strlen($this->razaoSocial) > 150) {
            throw new \InvalidArgumentException('Razão social deve ter no máximo 150 caracteres');
        }
    }
    
    public function getDocumento(): Cnpj|Cpf
    {
        return $this->documento;
    }
    
    public function isCnpj(): bool
    {
        return $this->documento instanceof Cnpj;
    }
    
    public function getCnpj(): ?Cnpj
    {
        return $this->isCnpj() ? $this->documento : null;
    }
    
    public function getCpf(): ?Cpf
    {
        return !$this->isCnpj() ? $this->documento : null;
    }
    
    // Getters...
    
    public function getRazaoSocial(): string
    {
        return $this->razaoSocial;
    }
    
    public function getRegimeTributario(): RegimeTributario
    {
        return $this->regimeTributario;
    }
}
```

#### Servico.php

```php
<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Domain\Entity;

use emissorNfseNacional\NfseNacional\Domain\ValueObject\Money;
use emissorNfseNacional\NfseNacional\Domain\ValueObject\CodigoMunicipio;

class Servico
{
    private Money $valorTotal;
    private Money $baseCalculo;
    private Money $valorIss;
    
    public function __construct(
        private string $discriminacao,
        private string $codigoTributacao,
        private CodigoMunicipio $localPrestacao,
        Money $valorServicos,
        private float $aliquotaIss,
        private Money $valorDeducoes,
        private Money $descontoIncondicionado,
        private Money $descontoCondicionado,
    ) {
        $this->calcularValores($valorServicos);
        $this->validate();
    }
    
    private function calcularValores(Money $valorServicos): void
    {
        // Base de cálculo = Valor Serviços - Deduções
        $this->baseCalculo = $valorServicos->subtract($this->valorDeducoes);
        
        // ISS = Base de Cálculo * Alíquota
        $this->valorIss = $this->baseCalculo->percentage($this->aliquotaIss);
        
        // Valor Total = Valor Serviços - Descontos
        $this->valorTotal = $valorServicos
            ->subtract($this->descontoIncondicionado)
            ->subtract($this->descontoCondicionado);
    }
    
    private function validate(): void
    {
        if (empty($this->discriminacao)) {
            throw new \InvalidArgumentException('Discriminação do serviço é obrigatória');
        }
        
        if (strlen($this->discriminacao) > 2000) {
            throw new \InvalidArgumentException('Discriminação deve ter no máximo 2000 caracteres');
        }
        
        if ($this->aliquotaIss < 0 || $this->aliquotaIss > 100) {
            throw new \InvalidArgumentException('Alíquota ISS deve estar entre 0 e 100');
        }
        
        if (!$this->valorTotal->isPositive()) {
            throw new \InvalidArgumentException('Valor total deve ser positivo');
        }
    }
    
    public function getValorTotal(): Money
    {
        return $this->valorTotal;
    }
    
    public function getValorIss(): Money
    {
        return $this->valorIss;
    }
    
    public function getBaseCalculo(): Money
    {
        return $this->baseCalculo;
    }
    
    // Outros getters...
}
```

---

### 3. Configuração com Suporte a Múltiplos Ambientes

#### Configuration.php

```php
<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Infrastructure\Config;

use emissorNfseNacional\NfseNacional\Domain\Enum\TipoAmbiente;
use emissorNfseNacional\NfseNacional\Infrastructure\Config\Exception\ConfigException;

class Configuration implements Contract\ConfigInterface
{
    private array $config;
    private array $urls;
    private array $operations;
    
    public function __construct(array $config)
    {
        $this->config = $this->validateConfig($config);
        $this->loadUrls();
        $this->loadOperations();
    }
    
    private function validateConfig(array $config): array
    {
        $required = ['tpAmb', 'prefeitura'];
        
        foreach ($required as $field) {
            if (!isset($config[$field])) {
                throw new ConfigException("Campo obrigatório não informado: {$field}");
            }
        }
        
        if (!in_array($config['tpAmb'], [1, 2])) {
            throw new ConfigException('tpAmb deve ser 1 (Produção) ou 2 (Homologação)');
        }
        
        return $config;
    }
    
    private function loadUrls(): void
    {
        $configFile = __DIR__ . '/../../../storage/prefeituras.json';
        $json = json_decode(file_get_contents($configFile), true);
        
        $prefeitura = $this->config['prefeitura'];
        
        // URLs padrão
        $this->urls = [
            'sefin_homologacao' => 'https://sefin.producaorestrita.nfse.gov.br/SefinNacional',
            'sefin_producao' => 'https://sefin.nfse.gov.br/sefinnacional',
            'adn_homologacao' => 'https://adn.producaorestrita.nfse.gov.br',
            'adn_producao' => 'https://adn.nfse.gov.br',
            'nfse_homologacao' => 'https://www.producaorestrita.nfse.gov.br/EmissorNacional',
            'nfse_producao' => 'https://www.nfse.gov.br/EmissorNacional',
        ];
        
        // Override com configurações específicas do município
        if (isset($json[$prefeitura]['urls'])) {
            $this->urls = array_merge($this->urls, $json[$prefeitura]['urls']);
        }
    }
    
    private function loadOperations(): void
    {
        $configFile = __DIR__ . '/../../../storage/prefeituras.json';
        $json = json_decode(file_get_contents($configFile), true);
        
        $prefeitura = $this->config['prefeitura'];
        
        // Operations padrão
        $this->operations = [
            'consultar_nfse' => 'nfse/{chave}',
            'consultar_dps' => 'dps/{chave}',
            'consultar_eventos' => 'nfse/{chave}/eventos/{tipoEvento}/{nSequencial}',
            'consultar_danfse' => 'danfse/{chave}',
            'consultar_danfse_nfse_certificado' => 'Certificado',
            'consultar_danfse_nfse_download' => 'Notas/Download/DANFSe/{chave}',
            'emitir_nfse' => 'nfse',
            'cancelar_nfse' => 'nfse/{chave}/eventos',
        ];
        
        // Override com operações específicas do município
        if (isset($json[$prefeitura]['operations'])) {
            $this->operations = array_merge($this->operations, $json[$prefeitura]['operations']);
        }
    }
    
    public function getTipoAmbiente(): TipoAmbiente
    {
        return TipoAmbiente::from($this->config['tpAmb']);
    }
    
    public function getUrl(string $key): string
    {
        if (!isset($this->urls[$key])) {
            throw new ConfigException("URL não configurada: {$key}");
        }
        
        return $this->urls[$key];
    }
    
    public function getOperation(string $key): string
    {
        if (!isset($this->operations[$key])) {
            throw new ConfigException("Operação não configurada: {$key}");
        }
        
        return $this->operations[$key];
    }
    
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }
    
    public function getVersion(): string
    {
        return '2.0.0';
    }
}
```

---

### 4. Factory Pattern para Criação de Serviços

#### ServiceFactory.php

```php
<?php

declare(strict_types=1);

namespace emissorNfseNacional\NfseNacional\Presentation\Factory;

use emissorNfseNacional\NfseNacional\Application\Service\EmitirDpsService;
use emissorNfseNacional\NfseNacional\Application\Service\ConsultarNfseService;
use emissorNfseNacional\NfseNacional\Application\Service\CancelarNfseService;
use emissorNfseNacional\NfseNacional\Application\Validator\DpsValidator;
use emissorNfseNacional\NfseNacional\Infrastructure\Config\Configuration;
use emissorNfseNacional\NfseNacional\Infrastructure\Http\ApiConnector;
use emissorNfseNacional\NfseNacional\Infrastructure\Http\Client\CurlHttpClient;
use emissorNfseNacional\NfseNacional\Infrastructure\Security\CertificateManager;
use emissorNfseNacional\NfseNacional\Infrastructure\Security\XmlSigner;
use emissorNfseNacional\NfseNacional\Infrastructure\Xml\Builder\DpsXmlBuilder;
use emissorNfseNacional\NfseNacional\Infrastructure\Xml\Validator\XsdValidator;
use NFePHP\Common\Certificate;

class ServiceFactory
{
    private Configuration $configuration;
    private CertificateManager $certificateManager;
    private ApiConnector $apiConnector;
    
    public function __construct(array $config, Certificate $certificate)
    {
        $this->configuration = new Configuration($config);
        $this->certificateManager = new CertificateManager($certificate);
        $this->apiConnector = $this->createApiConnector();
    }
    
    private function createApiConnector(): ApiConnector
    {
        $httpClient = new CurlHttpClient(
            timeout: 30,
            connectTimeout: 10
        );
        
        return new ApiConnector(
            $this->configuration,
            $httpClient,
            $this->certificateManager
        );
    }
    
    public function createEmitirDpsService(): EmitirDpsService
    {
        return new EmitirDpsService(
            apiConnector: $this->apiConnector,
            xmlBuilder: new DpsXmlBuilder(),
            xmlSigner: new XmlSigner($this->certificateManager->getCertificate()),
            xsdValidator: new XsdValidator(),
            validator: new DpsValidator(),
        );
    }
    
    public function createConsultarNfseService(): ConsultarNfseService
    {
        return new ConsultarNfseService(
            apiConnector: $this->apiConnector,
        );
    }
    
    public function createCancelarNfseService(): CancelarNfseService
    {
        return new CancelarNfseService(
            apiConnector: $this->apiConnector,
            xmlBuilder: new \emissorNfseNacional\NfseNacional\Infrastructure\Xml\Builder\EventoXmlBuilder(),
            xmlSigner: new XmlSigner($this->certificateManager->getCertificate()),
            xsdValidator: new XsdValidator(),
        );
    }
}
```

---

## ✅ Checklist de Desenvolvimento

### Para Cada Classe

- [ ] **Responsabilidade Única**: A classe tem apenas uma razão para mudar?
- [ ] **Nomeação Clara**: O nome da classe indica claramente sua responsabilidade?
- [ ] **Dependências Injetadas**: Todas as dependências são recebidas no construtor?
- [ ] **Imutabilidade**: Value Objects são readonly/imutáveis?
- [ ] **Validação**: Dados são validados no momento da criação?
- [ ] **Exceções Específicas**: Usa exceções específicas do domínio?
- [ ] **Type Hints**: Todos os parâmetros e retornos têm type hints?
- [ ] **DocBlocks**: Métodos públicos têm documentação clara?
- [ ] **Testes**: Cobertura de testes >= 80%?

### Para Value Objects

```php
// ✅ BOM
final readonly class Cpf
{
    private string $numero;
    
    public function __construct(string $cpf)
    {
        $this->numero = $this->validate($cpf);
    }
    
    private function validate(string $cpf): string
    {
        // Validação completa
        return $cpf;
    }
}

// ❌ RUIM
class Cpf
{
    public string $numero; // Mutável e público
    
    public function __construct(string $cpf)
    {
        $this->numero = $cpf; // Sem validação
    }
}
```

### Para Entities

```php
// ✅ BOM
class Dps
{
    public function __construct(
        private TipoAmbiente $tipoAmbiente,
        private \DateTimeImmutable $dataEmissao,
        private Prestador $prestador,
        private Tomador $tomador,
        private Servico $servico,
    ) {
        $this->validate();
    }
    
    private function validate(): void
    {
        // Regras de negócio
    }
}

// ❌ RUIM
class Dps
{
    public int $tpAmb;
    public string $dhEmi;
    public $prestador; // Sem type hint
    
    // Sem validação no construtor
}
```

---

## 📐 Padrões e Convenções

### Nomenclatura

```php
// Classes: PascalCase
class EmitirDpsService {}

// Métodos: camelCase
public function executarEmissao() {}

// Constantes: UPPER_SNAKE_CASE
private const DEFAULT_TIMEOUT = 30;

// Propriedades privadas: camelCase
private string $apiUrl;

// Interfaces: sufixo "Interface"
interface HttpClientInterface {}

// Exceptions: sufixo "Exception"
class InvalidCnpjException extends DomainException {}

// Traits: sufixo "Trait"
trait ValidatesTrait {}

// Enums: PascalCase, valores UPPER_CASE
enum TipoAmbiente: int
{
    case PRODUCAO = 1;
    case HOMOLOGACAO = 2;
}
```

### Estrutura de Métodos

```php
class ExemploService
{
    // 1. Constantes
    private const MAX_RETRIES = 3;
    
    // 2. Propriedades
    private ApiConnector $connector;
    
    // 3. Construtor
    public function __construct(ApiConnector $connector)
    {
        $this->connector = $connector;
    }
    
    // 4. Métodos públicos
    public function executar(Request $request): Response
    {
        // Implementação
    }
    
    // 5. Métodos privados
    private function validate(Request $request): void
    {
        // Implementação
    }
    
    // 6. Getters (se necessário)
    public function getConnector(): ApiConnector
    {
        return $this->connector;
    }
}
```

### Tratamento de Exceções

```php
// ✅ BOM - Exceções específicas
try {
    $cnpj = new Cnpj($input);
} catch (InvalidCnpjException $e) {
    // Tratamento específico para CNPJ inválido
    log_error('CNPJ inválido', ['input' => $input, 'error' => $e->getMessage()]);
    throw $e;
}

// ❌ RUIM - Exceção genérica
try {
    $cnpj = new Cnpj($input);
} catch (\Exception $e) {
    // Muito genérico
}
```

---

## 🔥 Tratamento de Erros

### Hierarquia de Exceções

```php
// Base exception
abstract class NfseNacionalException extends \Exception {}

// Domain exceptions
abstract class DomainException extends NfseNacionalException {}
class InvalidCnpjException extends DomainException {}
class InvalidCpfException extends DomainException {}

// Application exceptions
abstract class ApplicationException extends NfseNacionalException {}
class ValidationException extends ApplicationException {}
class ServiceException extends ApplicationException {}

// Infrastructure exceptions
abstract class InfrastructureException extends NfseNacionalException {}
class HttpException extends InfrastructureException {}
class XmlException extends InfrastructureException {}
```

### Exemplo de Uso

```php
public function emitirDps(DpsRequest $request): NfseResponse
{
    try {
        // Validação de domínio
        $dps = $this->criarDps($request);
        
        // Processamento
        $xml = $this->builder->build($dps);
        
        // Comunicação externa
        $response = $this->connector->post('nfse', $payload);
        
        return $this->parseResponse($response);
        
    } catch (DomainException $e) {
        // Erro nos dados de entrada
        throw new ValidationException(
            "Dados inválidos: {$e->getMessage()}",
            previous: $e
        );
        
    } catch (HttpException $e) {
        // Erro de comunicação
        throw new ServiceException(
            "Falha ao comunicar com API: {$e->getMessage()}",
            previous: $e
        );
        
    } catch (\Throwable $e) {
        // Erro inesperado
        throw new ServiceException(
            "Erro inesperado ao emitir DPS",
            previous: $e
        );
    }
}
```

---

## ⚡ Performance e Otimização

### 1. Cache de Configurações

```php
class CachedConfiguration implements ConfigInterface
{
    private ?array $cache = null;
    
    public function __construct(
        private ConfigInterface $inner,
        private CacheInterface $cache
    ) {}
    
    public function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = "config.{$key}";
        
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }
        
        $value = $this->inner->get($key, $default);
        $this->cache->set($cacheKey, $value, ttl: 3600);
        
        return $value;
    }
}
```

### 2. Pool de Conexões HTTP

```php
class HttpConnectionPool
{
    private array $connections = [];
    private int $maxConnections = 5;
    
    public function getConnection(string $host): CurlHandle
    {
        if (!isset($this->connections[$host])) {
            $this->connections[$host] = curl_init();
            // Configurar conexão...
        }
        
        return $this->connections[$host];
    }
    
    public function __destruct()
    {
        foreach ($this->connections as $conn) {
            curl_close($conn);
        }
    }
}
```

### 3. Lazy Loading de Schemas XSD

```php
class XsdValidator
{
    private static array $loadedSchemas = [];
    
    public function validate(string $xml, string $xsdFile): void
    {
        if (!isset(self::$loadedSchemas[$xsdFile])) {
            self::$loadedSchemas[$xsdFile] = file_get_contents(
                $this->schemasDir . $xsdFile
            );
        }
        
        // Usar schema carregado...
    }
}
```

---

## 🧪 Exemplo Completo de Teste

```php
<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use emissorNfseNacional\NfseNacional\Presentation\Facade\NfseNacionalFacade;
use emissorNfseNacional\NfseNacional\Application\DTO\Request\DpsRequest;
use NFePHP\Common\Certificate;

class EmitirDpsTest extends TestCase
{
    private NfseNacionalFacade $facade;
    
    protected function setUp(): void
    {
        $config = [
            'tpAmb' => 2, // Homologação
            'prefeitura' => '3550308', // São Paulo
        ];
        
        $certContent = file_get_contents(__DIR__ . '/../Fixtures/cert-test.pfx');
        $cert = Certificate::readPfx($certContent, 'senha123');
        
        $this->facade = NfseNacionalFacade::create($config, $cert);
    }
    
    public function test_deve_emitir_dps_com_sucesso(): void
    {
        $request = new DpsRequest(
            tipoAmbiente: 2,
            dataEmissao: (new \DateTime())->format('c'),
            versaoAplicacao: 'TestSystem_v1.0',
            serie: 1,
            numero: 1,
            dataCompetencia: (new \DateTime())->format('Y-m-d'),
            tipoEmissao: 1,
            codigoMunicipioEmissor: '3550308',
            prestador: $this->createPrestadorRequest(),
            tomador: $this->createTomadorRequest(),
            servico: $this->createServicoRequest(),
        );
        
        $response = $this->facade->emitirDps($request);
        
        $this->assertTrue($response->success);
        $this->assertNotEmpty($response->chaveAcesso);
        $this->assertNotEmpty($response->numero);
    }
    
    private function createPrestadorRequest(): PrestadorRequest
    {
        // Criar dados do prestador...
    }
}
```

---

## 📚 Recursos Adicionais

### Comandos Úteis

```bash
# Análise estática
vendor/bin/phpstan analyse src --level=8

# Code style
vendor/bin/php-cs-fixer fix src

# Testes
vendor/bin/phpunit --coverage-html coverage

# Testes com filtro
vendor/bin/phpunit --filter=EmitirDpsTest
```

### Links Importantes

- [PSR-4 Autoloading](https://www.php-fig.org/psr/psr-4/)
- [PSR-12 Coding Style](https://www.php-fig.org/psr/psr-12/)
- [NFePHP Documentation](https://github.com/nfephp-org)
- [Clean Architecture](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)

---

**Próximos Passos:**

1. Revisar este guia
2. Implementar Value Objects
3. Implementar Entities
4. Testes unitários
5. Implementar Infrastructure
6. Testes de integração
7. Implementar Application
8. Implementar Presentation
9. Testes end-to-end
10. Documentação final

---

**Mantido por:** Marcela Beatriz
**Última atualização:** 13/05/2026
