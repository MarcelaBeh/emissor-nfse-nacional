# Segurança e Compliance - NFSe Nacional

**Complemento aos documentos:**
- [ARQUITETURA_REFATORACAO.md](ARQUITETURA_REFATORACAO.md)
- [GUIA_IMPLEMENTACAO.md](GUIA_IMPLEMENTACAO.md)

---

## 📋 Índice

1. [Segurança de Certificados Digitais](#segurança-de-certificados-digitais)
2. [Validações Obrigatórias](#validações-obrigatórias)
3. [Compliance com NFSe Nacional](#compliance-com-nfse-nacional)
4. [Tratamento de Dados Sensíveis](#tratamento-de-dados-sensíveis)
5. [Logs e Auditoria](#logs-e-auditoria)
6. [Testes de Segurança](#testes-de-segurança)

---

## 🔐 Segurança de Certificados Digitais

### 1. Armazenamento Temporário Seguro

O certificado digital **NUNCA** deve ser armazenado permanentemente. Apenas arquivos temporários com permissões restritas.

```php
<?php

namespace emissorNfseNacional\NfseNacional\Infrastructure\Security;

use NFePHP\Common\Certificate;

class SecureCertificateStorage
{
    private string $tempDir;
    private array $temporaryFiles = [];
    
    public function __construct(Certificate $certificate)
    {
        $this->setupSecureTempDirectory();
        $this->storeTemporaryFiles($certificate);
        
        // Registrar destruição automática
        register_shutdown_function([$this, 'cleanup']);
    }
    
    private function setupSecureTempDirectory(): void
    {
        $uid = function_exists('posix_getuid') ? posix_getuid() : getmyuid();
        
        $this->tempDir = sys_get_temp_dir() 
            . DIRECTORY_SEPARATOR 
            . 'nfse-secure-' 
            . $uid 
            . DIRECTORY_SEPARATOR 
            . bin2hex(random_bytes(16)) 
            . DIRECTORY_SEPARATOR;
        
        // Criar diretório com permissões restritas
        if (!mkdir($this->tempDir, 0700, true)) {
            throw new \RuntimeException('Falha ao criar diretório temporário seguro');
        }
        
        // Verificar permissões
        $perms = fileperms($this->tempDir) & 0777;
        if ($perms !== 0700) {
            throw new \RuntimeException('Permissões do diretório temporário não são seguras');
        }
    }
    
    private function storeTemporaryFiles(Certificate $certificate): void
    {
        // Chave privada
        $this->temporaryFiles['private'] = $this->createSecureFile(
            $certificate->privateKey,
            'private'
        );
        
        // Chave pública
        $this->temporaryFiles['public'] = $this->createSecureFile(
            $certificate->publicKey,
            'public'
        );
        
        // Certificado completo
        $this->temporaryFiles['cert'] = $this->createSecureFile(
            (string) $certificate,
            'cert'
        );
    }
    
    private function createSecureFile(string $content, string $type): string
    {
        // Nome aleatório
        $filename = $this->tempDir . bin2hex(random_bytes(16)) . "_{$type}.pem";
        
        // Criar arquivo
        if (file_put_contents($filename, $content) === false) {
            throw new \RuntimeException("Falha ao criar arquivo temporário: {$type}");
        }
        
        // Permissões restritas (apenas proprietário pode ler)
        chmod($filename, 0600);
        
        // Verificar permissões
        $perms = fileperms($filename) & 0777;
        if ($perms !== 0600) {
            unlink($filename);
            throw new \RuntimeException('Falha ao definir permissões seguras');
        }
        
        return $filename;
    }
    
    public function getPrivateKeyPath(): string
    {
        return $this->temporaryFiles['private'];
    }
    
    public function getPublicKeyPath(): string
    {
        return $this->temporaryFiles['public'];
    }
    
    public function getCertificatePath(): string
    {
        return $this->temporaryFiles['cert'];
    }
    
    public function cleanup(): void
    {
        // Sobrescrever arquivos com dados aleatórios antes de deletar
        foreach ($this->temporaryFiles as $file) {
            if (file_exists($file)) {
                $size = filesize($file);
                file_put_contents($file, random_bytes($size));
                unlink($file);
            }
        }
        
        // Remover diretório
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
        
        $this->temporaryFiles = [];
    }
    
    public function __destruct()
    {
        $this->cleanup();
    }
}
```

### 2. Validação de Certificado

```php
<?php

namespace emissorNfseNacional\NfseNacional\Infrastructure\Security;

use NFePHP\Common\Certificate;
use emissorNfseNacional\NfseNacional\Infrastructure\Security\Exception\CertificateException;

class CertificateValidator
{
    private const MIN_KEY_LENGTH = 2048; // RSA mínimo 2048 bits
    private const WARNING_DAYS_BEFORE_EXPIRY = 30;
    
    public function validate(Certificate $certificate): void
    {
        $this->checkExpiration($certificate);
        $this->checkKeyStrength($certificate);
        $this->checkCertificateChain($certificate);
        $this->checkRevocation($certificate);
    }
    
    private function checkExpiration(Certificate $certificate): void
    {
        if ($certificate->isExpired()) {
            throw new CertificateException(
                sprintf(
                    'Certificado expirado em %s',
                    $certificate->getValidTo()->format('d/m/Y H:i:s')
                )
            );
        }
        
        // Avisar se expira em breve
        $now = new \DateTime();
        $validTo = $certificate->getValidTo();
        $daysToExpire = $now->diff($validTo)->days;
        
        if ($daysToExpire <= self::WARNING_DAYS_BEFORE_EXPIRY) {
            trigger_error(
                sprintf(
                    'ATENÇÃO: Certificado expira em %d dias (%s)',
                    $daysToExpire,
                    $validTo->format('d/m/Y')
                ),
                E_USER_WARNING
            );
        }
    }
    
    private function checkKeyStrength(Certificate $certificate): void
    {
        $publicKey = openssl_pkey_get_public($certificate->publicKey);
        
        if ($publicKey === false) {
            throw new CertificateException('Falha ao ler chave pública do certificado');
        }
        
        $details = openssl_pkey_get_details($publicKey);
        
        if ($details === false) {
            throw new CertificateException('Falha ao obter detalhes da chave pública');
        }
        
        // Verificar tipo e tamanho da chave
        if ($details['type'] === OPENSSL_KEYTYPE_RSA) {
            $bits = $details['bits'];
            
            if ($bits < self::MIN_KEY_LENGTH) {
                throw new CertificateException(
                    sprintf(
                        'Chave RSA muito fraca: %d bits (mínimo: %d bits)',
                        $bits,
                        self::MIN_KEY_LENGTH
                    )
                );
            }
        }
    }
    
    private function checkCertificateChain(Certificate $certificate): void
    {
        // Verificar cadeia de certificação
        $cert = openssl_x509_read($certificate);
        
        if ($cert === false) {
            throw new CertificateException('Certificado inválido');
        }
        
        // Verificar se é um certificado ICP-Brasil
        $parsed = openssl_x509_parse($cert);
        
        if (!isset($parsed['subject'])) {
            throw new CertificateException('Certificado sem informações de subject');
        }
        
        // Validar OID do CNPJ/CPF (obrigatório para ICP-Brasil)
        if (!isset($parsed['subject']['UID'])) {
            throw new CertificateException(
                'Certificado não possui UID (CNPJ/CPF) - não é ICP-Brasil válido'
            );
        }
    }
    
    private function checkRevocation(Certificate $certificate): void
    {
        // TODO: Implementar verificação de revogação via OCSP/CRL
        // Por enquanto, apenas avisar
        trigger_error(
            'Verificação de revogação de certificado não implementada',
            E_USER_NOTICE
        );
    }
}
```

---

## ✅ Validações Obrigatórias

### 1. Validação de CNPJ/CPF

```php
<?php

namespace emissorNfseNacional\NfseNacional\Domain\ValueObject;

final readonly class DocumentoValidator
{
    /**
     * Validação completa de CNPJ conforme algoritmo da Receita Federal
     */
    public static function validarCnpj(string $cnpj): bool
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        
        // Verifica tamanho
        if (strlen($cnpj) !== 14) {
            return false;
        }
        
        // Verifica sequências repetidas
        if (preg_match('/^(\d)\1+$/', $cnpj)) {
            return false;
        }
        
        // Calcula primeiro dígito verificador
        $soma = 0;
        $pesos = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        
        for ($i = 0; $i < 12; $i++) {
            $soma += (int)$cnpj[$i] * $pesos[$i];
        }
        
        $resto = $soma % 11;
        $digito1 = ($resto < 2) ? 0 : 11 - $resto;
        
        if ((int)$cnpj[12] !== $digito1) {
            return false;
        }
        
        // Calcula segundo dígito verificador
        $soma = 0;
        $pesos = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        
        for ($i = 0; $i < 13; $i++) {
            $soma += (int)$cnpj[$i] * $pesos[$i];
        }
        
        $resto = $soma % 11;
        $digito2 = ($resto < 2) ? 0 : 11 - $resto;
        
        return (int)$cnpj[13] === $digito2;
    }
    
    /**
     * Validação completa de CPF conforme algoritmo da Receita Federal
     */
    public static function validarCpf(string $cpf): bool
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        // Verifica tamanho
        if (strlen($cpf) !== 11) {
            return false;
        }
        
        // Verifica sequências repetidas
        if (preg_match('/^(\d)\1+$/', $cpf)) {
            return false;
        }
        
        // Calcula primeiro dígito verificador
        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += (int)$cpf[$i] * (10 - $i);
        }
        
        $resto = $soma % 11;
        $digito1 = ($resto < 2) ? 0 : 11 - $resto;
        
        if ((int)$cpf[9] !== $digito1) {
            return false;
        }
        
        // Calcula segundo dígito verificador
        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += (int)$cpf[$i] * (11 - $i);
        }
        
        $resto = $soma % 11;
        $digito2 = ($resto < 2) ? 0 : 11 - $resto;
        
        return (int)$cpf[10] === $digito2;
    }
}
```

### 2. Validação de Código IBGE

```php
<?php

namespace emissorNfseNacional\NfseNacional\Domain\ValueObject;

use emissorNfseNacional\NfseNacional\Domain\Exception\InvalidCodigoMunicipioException;

final readonly class CodigoMunicipio
{
    private string $codigo;
    
    public function __construct(string $codigo)
    {
        $this->codigo = $this->validate($codigo);
    }
    
    private function validate(string $codigo): string
    {
        // Remove caracteres não numéricos
        $codigo = preg_replace('/[^0-9]/', '', $codigo);
        
        // Código IBGE tem 7 dígitos
        if (strlen($codigo) !== 7) {
            throw new InvalidCodigoMunicipioException(
                "Código IBGE deve ter 7 dígitos. Fornecido: " . strlen($codigo)
            );
        }
        
        // Primeiro dígito é o código da UF (1-5)
        $codigoUf = (int)substr($codigo, 0, 2);
        if ($codigoUf < 11 || $codigoUf > 53) {
            throw new InvalidCodigoMunicipioException(
                "Código UF inválido: {$codigoUf}"
            );
        }
        
        // Validar dígito verificador
        if (!$this->validarDigitoVerificador($codigo)) {
            throw new InvalidCodigoMunicipioException(
                "Dígito verificador inválido para código: {$codigo}"
            );
        }
        
        return $codigo;
    }
    
    private function validarDigitoVerificador(string $codigo): bool
    {
        // Implementação da validação do dígito verificador
        // conforme especificação IBGE
        $soma = 0;
        $peso = 2;
        
        for ($i = 5; $i >= 0; $i--) {
            $soma += (int)$codigo[$i] * $peso;
            $peso = ($peso === 9) ? 2 : $peso + 1;
        }
        
        $resto = $soma % 11;
        $dv = ($resto === 0 || $resto === 1) ? 0 : 11 - $resto;
        
        return (int)$codigo[6] === $dv;
    }
    
    public function getCodigo(): string
    {
        return $this->codigo;
    }
    
    public function getUf(): string
    {
        // Retorna sigla da UF baseado nos 2 primeiros dígitos
        $codigoUf = (int)substr($this->codigo, 0, 2);
        
        return match($codigoUf) {
            11 => 'RO', 12 => 'AC', 13 => 'AM', 14 => 'RR', 15 => 'PA',
            16 => 'AP', 17 => 'TO', 21 => 'MA', 22 => 'PI', 23 => 'CE',
            24 => 'RN', 25 => 'PB', 26 => 'PE', 27 => 'AL', 28 => 'SE',
            29 => 'BA', 31 => 'MG', 32 => 'ES', 33 => 'RJ', 35 => 'SP',
            41 => 'PR', 42 => 'SC', 43 => 'RS', 50 => 'MS', 51 => 'MT',
            52 => 'GO', 53 => 'DF',
            default => throw new \RuntimeException("Código UF desconhecido: {$codigoUf}"),
        };
    }
}
```

### 3. Validação de Motivo de Evento (TSCodJustSubst)

```php
<?php

namespace emissorNfseNacional\NfseNacional\Domain\Enum;

/**
 * Códigos de Justificativa de Substituição
 * 
 * IMPORTANTE: No XSD, esses códigos são definidos como strings
 * com zero à esquerda (pattern="\d{2}"), não como inteiros.
 */
enum MotivoSubstituicao: string
{
    case DESENQUADRAMENTO_SIMPLES = '01';
    case ENQUADRAMENTO_SIMPLES = '02';
    case INCLUSAO_IMUNIDADE = '03';
    case EXCLUSAO_IMUNIDADE = '04';
    case REJEICAO_TOMADOR = '05';
    case OUTROS = '99';
    
    public function descricao(): string
    {
        return match($this) {
            self::DESENQUADRAMENTO_SIMPLES => 'Desenquadramento de NFS-e do Simples Nacional',
            self::ENQUADRAMENTO_SIMPLES => 'Enquadramento de NFS-e no Simples Nacional',
            self::INCLUSAO_IMUNIDADE => 'Inclusão Retroativa de Imunidade/Isenção para NFS-e',
            self::EXCLUSAO_IMUNIDADE => 'Exclusão Retroativa de Imunidade/Isenção para NFS-e',
            self::REJEICAO_TOMADOR => 'Rejeição de NFS-e pelo tomador ou pelo intermediário',
            self::OUTROS => 'Outros',
        };
    }
    
    /**
     * Retorna o valor com zero à esquerda (para XML)
     */
    public function paraXml(): string
    {
        return $this->value;
    }
    
    /**
     * Verifica se requer descrição adicional
     */
    public function requerDescricao(): bool
    {
        return $this === self::OUTROS;
    }
}
```

---

## 📜 Compliance com NFSe Nacional

### 1. Validação Contra XSD

```php
<?php

namespace emissorNfseNacional\NfseNacional\Infrastructure\Xml\Validator;

use emissorNfseNacional\NfseNacional\Infrastructure\Xml\Exception\XmlValidationException;

class StrictXsdValidator
{
    private string $schemasDir;
    private array $validationErrors = [];
    
    public function __construct(?string $schemasDir = null)
    {
        $this->schemasDir = $schemasDir ?? __DIR__ . '/../../../../storage/schemes/';
    }
    
    /**
     * Valida XML estritamente contra XSD
     * 
     * @throws XmlValidationException Se houver qualquer erro
     */
    public function validateStrict(string $xml, string $xsdFile): void
    {
        $xsdPath = $this->schemasDir . $xsdFile;
        
        if (!file_exists($xsdPath)) {
            throw new \InvalidArgumentException("Schema XSD não encontrado: {$xsdPath}");
        }
        
        // Habilitar erros internos
        libxml_use_internal_errors(true);
        libxml_clear_errors();
        
        // Criar DOM
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        
        // Carregar XML
        if (!@$dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NONET)) {
            $this->collectErrors();
            throw new XmlValidationException(
                "XML malformado:\n" . $this->formatErrors()
            );
        }
        
        // Validar contra XSD
        if (!@$dom->schemaValidate($xsdPath)) {
            $this->collectErrors();
            throw new XmlValidationException(
                "XML não conforme com XSD '{$xsdFile}':\n" . $this->formatErrors()
            );
        }
        
        // Limpar
        libxml_clear_errors();
        libxml_use_internal_errors(false);
        $this->validationErrors = [];
    }
    
    private function collectErrors(): void
    {
        $this->validationErrors = [];
        
        foreach (libxml_get_errors() as $error) {
            $this->validationErrors[] = [
                'level' => $this->getErrorLevel($error->level),
                'code' => $error->code,
                'line' => $error->line,
                'column' => $error->column,
                'message' => trim($error->message),
                'file' => $error->file,
            ];
        }
    }
    
    private function formatErrors(): string
    {
        $formatted = [];
        
        foreach ($this->validationErrors as $error) {
            $formatted[] = sprintf(
                "[%s] Linha %d, Coluna %d: %s",
                $error['level'],
                $error['line'],
                $error['column'],
                $error['message']
            );
        }
        
        return implode("\n", $formatted);
    }
    
    private function getErrorLevel(int $level): string
    {
        return match($level) {
            LIBXML_ERR_WARNING => 'AVISO',
            LIBXML_ERR_ERROR => 'ERRO',
            LIBXML_ERR_FATAL => 'ERRO FATAL',
            default => 'DESCONHECIDO',
        };
    }
    
    public function getErrors(): array
    {
        return $this->validationErrors;
    }
}
```

### 2. Verificação de Versão de Schema

```php
<?php

namespace emissorNfseNacional\NfseNacional\Infrastructure\Xml\Validator;

use emissorNfseNacional\NfseNacional\Domain\Enum\VersaoSchema;

class SchemaVersionManager
{
    /**
     * Determina a versão correta do schema baseado na data
     */
    public function determinarVersao(\DateTimeInterface $data): VersaoSchema
    {
        // v1.01 passou a ser obrigatória em 01/01/2024
        $dataObrigatoriaV101 = new \DateTime('2024-01-01');
        
        if ($data >= $dataObrigatoriaV101) {
            return VersaoSchema::V1_01;
        }
        
        return VersaoSchema::V1_00;
    }
    
    /**
     * Obtém o arquivo XSD correto para validação
     */
    public function getSchemaFile(string $tipo, VersaoSchema $versao): string
    {
        return match($tipo) {
            'DPS' => "DPS_v{$versao->value}.xsd",
            'NFSe' => "NFSe_v{$versao->value}.xsd",
            'evento' => "evento_v{$versao->value}.xsd",
            'pedRegEvento' => "pedRegEvento_v{$versao->value}.xsd",
            default => throw new \InvalidArgumentException("Tipo de schema desconhecido: {$tipo}"),
        };
    }
    
    /**
     * Valida se a versão informada no XML está correta
     */
    public function validarVersaoInformada(string $versaoInformada, VersaoSchema $versaoEsperada): void
    {
        if ($versaoInformada !== $versaoEsperada->value) {
            throw new \RuntimeException(
                sprintf(
                    'Versão informada no XML (%s) não corresponde à versão esperada (%s)',
                    $versaoInformada,
                    $versaoEsperada->value
                )
            );
        }
    }
}
```

---

## 🔒 Tratamento de Dados Sensíveis

### 1. Sanitização de Logs

```php
<?php

namespace emissorNfseNacional\NfseNacional\Infrastructure\Logger;

class SensitiveDataSanitizer
{
    /**
     * Campos que devem ser ofuscados nos logs
     */
    private const SENSITIVE_FIELDS = [
        'privateKey',
        'senha',
        'password',
        'certificado',
        'pfx',
        'token',
        'authorization',
    ];
    
    /**
     * Padrões regex para dados sensíveis
     */
    private const SENSITIVE_PATTERNS = [
        // CPF: 000.000.000-00
        '/\b\d{3}\.\d{3}\.\d{3}-\d{2}\b/' => '***.***.***-**',
        
        // CNPJ: 00.000.000/0000-00
        '/\b\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}\b/' => '**.***.***/****-**',
        
        // Chave de acesso (50 dígitos)
        '/\b\d{50}\b/' => str_repeat('*', 50),
        
        // Email
        '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/' => '***@***.***',
    ];
    
    /**
     * Sanitiza dados antes de logar
     */
    public function sanitize(mixed $data): mixed
    {
        if (is_string($data)) {
            return $this->sanitizeString($data);
        }
        
        if (is_array($data)) {
            return $this->sanitizeArray($data);
        }
        
        if (is_object($data)) {
            return $this->sanitizeObject($data);
        }
        
        return $data;
    }
    
    private function sanitizeString(string $data): string
    {
        foreach (self::SENSITIVE_PATTERNS as $pattern => $replacement) {
            $data = preg_replace($pattern, $replacement, $data);
        }
        
        return $data;
    }
    
    private function sanitizeArray(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            // Ofuscar campo completamente se estiver na lista de sensíveis
            if ($this->isSensitiveField($key)) {
                $sanitized[$key] = '*** REDACTED ***';
                continue;
            }
            
            // Sanitizar recursivamente
            $sanitized[$key] = $this->sanitize($value);
        }
        
        return $sanitized;
    }
    
    private function sanitizeObject(object $data): array
    {
        return $this->sanitizeArray((array) $data);
    }
    
    private function isSensitiveField(string $field): bool
    {
        $field = strtolower($field);
        
        foreach (self::SENSITIVE_FIELDS as $sensitive) {
            if (str_contains($field, strtolower($sensitive))) {
                return true;
            }
        }
        
        return false;
    }
}
```

### 2. Logger Seguro

```php
<?php

namespace emissorNfseNacional\NfseNacional\Infrastructure\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class SecureLogger implements LoggerInterface
{
    private SensitiveDataSanitizer $sanitizer;
    private string $logFile;
    
    public function __construct(string $logFile)
    {
        $this->logFile = $logFile;
        $this->sanitizer = new SensitiveDataSanitizer();
        
        $this->ensureLogDirectoryExists();
        $this->setSecurePermissions();
    }
    
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        // Sanitizar mensagem e contexto
        $message = $this->sanitizer->sanitize($message);
        $context = $this->sanitizer->sanitize($context);
        
        // Formatar log
        $logEntry = sprintf(
            "[%s] [%s] %s %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );
        
        // Escrever no arquivo
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    private function ensureLogDirectoryExists(): void
    {
        $dir = dirname($this->logFile);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
    }
    
    private function setSecurePermissions(): void
    {
        if (file_exists($this->logFile)) {
            chmod($this->logFile, 0640); // Apenas dono e grupo podem ler
        }
    }
    
    // Implementar outros métodos da interface PSR-3...
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }
    
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }
    
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }
    
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }
    
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }
    
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }
    
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }
    
    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }
}
```

---

## 📊 Logs e Auditoria

### 1. Estrutura de Auditoria

```php
<?php

namespace emissorNfseNacional\NfseNacional\Infrastructure\Audit;

class AuditLog
{
    private SecureLogger $logger;
    
    public function __construct(SecureLogger $logger)
    {
        $this->logger = $logger;
    }
    
    public function logEmissao(string $chaveAcesso, array $dados): void
    {
        $this->logger->info('NFSe emitida', [
            'operacao' => 'emissao_nfse',
            'chave_acesso' => $chaveAcesso,
            'numero' => $dados['numero'] ?? null,
            'serie' => $dados['serie'] ?? null,
            'prestador_cnpj' => $this->mask($dados['prestador_cnpj'] ?? ''),
            'valor_total' => $dados['valor_total'] ?? null,
            'timestamp' => time(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'cli',
        ]);
    }
    
    public function logCancelamento(string $chaveAcesso, string $motivo): void
    {
        $this->logger->warning('NFSe cancelada', [
            'operacao' => 'cancelamento_nfse',
            'chave_acesso' => $chaveAcesso,
            'motivo' => $motivo,
            'timestamp' => time(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'cli',
        ]);
    }
    
    public function logErro(string $operacao, \Throwable $exception): void
    {
        $this->logger->error("Erro em {$operacao}", [
            'operacao' => $operacao,
            'erro' => $exception->getMessage(),
            'arquivo' => $exception->getFile(),
            'linha' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'timestamp' => time(),
        ]);
    }
    
    private function mask(string $value): string
    {
        if (strlen($value) <= 4) {
            return str_repeat('*', strlen($value));
        }
        
        return str_repeat('*', strlen($value) - 4) . substr($value, -4);
    }
}
```

---

## 🧪 Testes de Segurança

### 1. Teste de Validação de Certificado

```php
<?php

namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use emissorNfseNacional\NfseNacional\Infrastructure\Security\CertificateValidator;
use NFePHP\Common\Certificate;

class CertificateValidatorTest extends TestCase
{
    private CertificateValidator $validator;
    
    protected function setUp(): void
    {
        $this->validator = new CertificateValidator();
    }
    
    public function test_deve_rejeitar_certificado_expirado(): void
    {
        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage('Certificado expirado');
        
        $cert = $this->createExpiredCertificate();
        $this->validator->validate($cert);
    }
    
    public function test_deve_rejeitar_chave_rsa_fraca(): void
    {
        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage('Chave RSA muito fraca');
        
        $cert = $this->createCertificateWithWeakKey(1024); // 1024 bits
        $this->validator->validate($cert);
    }
    
    public function test_deve_aceitar_certificado_valido(): void
    {
        $cert = $this->createValidCertificate();
        
        // Não deve lançar exceção
        $this->validator->validate($cert);
        
        $this->assertTrue(true);
    }
    
    private function createExpiredCertificate(): Certificate
    {
        // Criar certificado de teste expirado
        // ...
    }
}
```

### 2. Teste de Sanitização

```php
<?php

namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use emissorNfseNacional\NfseNacional\Infrastructure\Logger\SensitiveDataSanitizer;

class SensitiveDataSanitizerTest extends TestCase
{
    private SensitiveDataSanitizer $sanitizer;
    
    protected function setUp(): void
    {
        $this->sanitizer = new SensitiveDataSanitizer();
    }
    
    public function test_deve_ofuscar_cpf(): void
    {
        $data = 'CPF do cliente: 123.456.789-00';
        $result = $this->sanitizer->sanitize($data);
        
        $this->assertStringNotContainsString('123.456.789-00', $result);
        $this->assertStringContainsString('***.***.***-**', $result);
    }
    
    public function test_deve_ofuscar_cnpj(): void
    {
        $data = 'CNPJ: 12.345.678/0001-90';
        $result = $this->sanitizer->sanitize($data);
        
        $this->assertStringNotContainsString('12.345.678/0001-90', $result);
        $this->assertStringContainsString('**.***.***/****-**', $result);
    }
    
    public function test_deve_ofuscar_campo_senha_em_array(): void
    {
        $data = [
            'usuario' => 'joao',
            'senha' => 'senhaSecreta123',
            'email' => 'joao@example.com',
        ];
        
        $result = $this->sanitizer->sanitize($data);
        
        $this->assertEquals('joao', $result['usuario']);
        $this->assertEquals('*** REDACTED ***', $result['senha']);
        $this->assertStringContainsString('***@***.***', $result['email']);
    }
}
```

---

## 📋 Checklist de Segurança

### Antes do Deploy

- [ ] Certificados armazenados apenas em memória/temp seguro
- [ ] Permissões de arquivo configuradas corretamente (0600/0700)
- [ ] Logs sanitizados (sem dados sensíveis)
- [ ] Validação XSD habilitada
- [ ] HTTPS obrigatório para comunicação
- [ ] Timeout configurado para requests
- [ ] Tratamento de exceções implementado
- [ ] Auditoria de operações implementada
- [ ] Testes de segurança executados
- [ ] Documentação de segurança atualizada

### Checklist de Código

- [ ] Sem hardcoded credentials
- [ ] Sem var_dump/print_r em produção
- [ ] SQL preparados (se aplicável)
- [ ] Input validation em todas as entradas
- [ ] Output encoding em todas as saídas
- [ ] Error messages não revelam detalhes internos
- [ ] Dependências atualizadas (composer update)
- [ ] PHPStan nível 8 sem erros
- [ ] Code coverage >= 80%

---

**Documento mantido por:** Equipe de Segurança  
**Última atualização:** 14/05/2026  
**Versão:** 1.0

---

**⚠️ IMPORTANTE:** Este documento contém diretrizes críticas de segurança. Todas as recomendações devem ser seguidas rigorosamente ao trabalhar com certificados digitais e comunicação com sistemas governamentais.
