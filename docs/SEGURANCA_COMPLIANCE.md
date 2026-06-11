# Segurança e Compliance - NFSe Nacional

**Complemento aos documentos:**
- [ARQUITETURA.md](ARQUITETURA.md)
- [GUIA_IMPLEMENTACAO.md](GUIA_IMPLEMENTACAO.md)
- [CONTRIBUTING.md](../CONTRIBUTING.md)

> Este documento descreve **apenas o que a biblioteca realmente implementa**.
> Namespace do pacote: `MarcelaBeh\EmissorNfseNacional`.

---

## 📋 Índice

1. [Certificado Digital](#certificado-digital)
2. [Assinatura XML](#assinatura-xml)
3. [Validação contra XSD](#validação-contra-xsd)
4. [Validações de Domínio](#validações-de-domínio)
5. [Tratamento de Dados Sensíveis em Log](#tratamento-de-dados-sensíveis-em-log)

---

## 🔐 Certificado Digital

O `CertificateManager` (`src/Infrastructure/Security/CertificateManager.php`) é o
único responsável pelo certificado. Ele:

- **Valida na construção**: lança `CertificateExpiredException` se o certificado
  já expirou e `CertificateExpiringException` se vence em **30 dias ou menos**.
- **Materializa os PEMs sob demanda**: o diretório temporário é criado com
  `mkdir(..., 0o700)` e cada arquivo é criado por `tempnam()` (permissão `0600`,
  owner-only). Os PEMs só são escritos quando uma request realmente ocorre.
- **Limpa no destrutor**: ao ser destruído, sobrescreve cada arquivo temporário
  com bytes nulos (`str_repeat("\0", ...)`) e remove (`unlink`).

```php
// Trecho real de CertificateManager::validate()
private function validate(): void
{
    if ($this->certificate->isExpired()) {
        throw new CertificateExpiredException(
            "Certificado expirado em {$this->certificate->getValidTo()->format('d/m/Y')}"
        );
    }

    $daysToExpire = $this->certificate->getValidTo()->diff(new \DateTime())->days;
    if ($daysToExpire <= 30) {
        throw new CertificateExpiringException("Certificado expira em {$daysToExpire} dias");
    }
}
```

> **Escopo:** a validação cobre **expiração** e **proximidade de vencimento**.
> Força de chave RSA, cadeia ICP-Brasil e revogação (OCSP/CRL) **não** são
> verificadas pela lib — quando exigidas, cabem ao integrador.

---

## ✍️ Assinatura XML

O `XmlSigner` (`src/Infrastructure/Security/XmlSigner.php`) assina o XML com
**SHA-256** (`OPENSSL_ALGO_SHA256`), via `NFePHP\Common\Signer`. A DPS é assinada
sobre a tag `infDPS`; o evento, sobre `infPedReg`.

---

## ✅ Validação contra XSD

O `XsdValidator` (`src/Infrastructure/Xml/Validator/XsdValidator.php`) valida o
XML gerado contra os schemas oficiais antes do envio:

```php
public function validate(string $xml, string $tipo, VersaoSchema $versao = VersaoSchema::V1_01): void
```

- Tipos suportados: `DPS`, `NFSe`, `pedRegEvento` (mapeados em `SCHEMAS`, por versão).
- **Mitigação de XXE/SSRF**: o parse usa `LIBXML_NONET`, que bloqueia requisições
  de rede e impede a resolução de DTDs/entidades externas. **Não** é usado
  `LIBXML_NOENT` (que reativaria a expansão de entidades).
- Em caso de violação, lança `XmlValidationException` com os erros do libxml.

---

## 🧮 Validações de Domínio

Os Value Objects validam o dado na construção e lançam exceção tipada quando inválido:

- **`Cnpj` / `Cpf`** (`src/Domain/ValueObject/`): validam o **dígito verificador**
  conforme o algoritmo da Receita Federal (`validarDigitoVerificador`).
- **`CodigoMunicipio`**: exige **7 dígitos** (código IBGE); lança
  `ValidationException` caso contrário.
- **`ChaveAcesso`**: exige **50 dígitos**.
- **`Nif`**: limite de 40 caracteres (`TSNIF`).

> As regras de formato (tamanho, dígito verificador) são da lib. A **existência**
> de um código na tabela do IBGE/serviços é validada pela SEFIN, não pela lib.

---

## 🔒 Tratamento de Dados Sensíveis em Log

Por padrão os serviços usam `NullLogger` (sem saída). Para registrar logs em
produção sem vazar dados sensíveis, injete um **`SanitizedLogger`**
(`src/Infrastructure/Security/SanitizedLogger.php`) — aceito por
`NfseNacionalFacade::create()` e por `ServiceFactory`.

O `SanitizedLogger` é um logger **PSR-3** (`Psr\Log\LoggerInterface`, via
`AbstractLogger`) e escreve através de uma `Closure` injetada. Como o contrato é
PSR-3, qualquer logger compatível (Monolog, Symfony, etc.) também pode ser
injetado no lugar dele. Antes de escrever, o `SanitizedLogger` passa a mensagem e
o contexto pelo `SensitiveDataSanitizer`, que mascara:

- **CPF** (com e sem máscara) → `***********` / `***.***.***-**`
- **CNPJ** (com e sem máscara) → `**************` / `**.***.***/****-**`
- **Chave de acesso** (50 dígitos) → mascarada
- **E-mail** → `***@***`
- **Chaves sensíveis** em arrays (`senha`, `token`, `password`, `secret`, ...) → `[REDACTED]`

```php
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Security\SanitizedLogger;
use MarcelaBeh\EmissorNfseNacional\Presentation\Facade\NfseNacionalFacade;

$logger = new SanitizedLogger(fn (string $linha) => error_log($linha));

$facade = NfseNacionalFacade::create($config, $certificado, $logger);
```

---

## Checklist de Segurança

Antes do deploy, confirme:

- [ ] Certificado válido e fora da janela de 30 dias do vencimento
- [ ] Certificado e senha fora do controle de versão (não commitar `.pfx`)
- [ ] Logs em produção usando `SanitizedLogger` (não um logger cru)
- [ ] Ambiente correto (`tpAmb`: 1 = produção, 2 = homologação)
