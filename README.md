# NFSe Padrão Nacional

<p align="center">
  <img src="images/logo-nfs-e-horizontal.png" alt="NFSe Nacional" width="400">
</p>

[![Packagist](https://img.shields.io/packagist/v/marcelabeh/emissor-nfse-nacional.svg)](https://packagist.org/packages/marcelabeh/emissor-nfse-nacional)
[![PHP Version](https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php)](https://www.php.net/)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen)](https://phpstan.org/)
[![Tests](https://img.shields.io/badge/tests-passing-brightgreen)](https://phpunit.de/)
[![License](https://img.shields.io/badge/license-LGPL--3.0-blue.svg)](LICENSE)

**Biblioteca PHP para integração com NFSe Nacional** - Pacote Composer reutilizável para emissão, consulta e cancelamento de Notas Fiscais de Serviço Eletrônicas no padrão nacional.

**Clean Architecture** • **SOLID** • **PSR-12** • **PSR-3 (logging)** • **PHP 8.3+** • **PHPStan Level 8**

---

## 📦 Instalação

```bash
composer require marcelabeh/emissor-nfse-nacional
```

## 🚀 Uso Rápido

```php
use MarcelaBeh\EmissorNfseNacional\Presentation\Facade\NfseNacionalFacade;
use MarcelaBeh\EmissorNfseNacional\Presentation\Factory\ConfigFactory;
use NFePHP\Common\Certificate;

// 1. Carregar certificado
$certificado = Certificate::readPfx(file_get_contents($caminhoCertificado), $senha);

// 2. Criar configuração
$config = ConfigFactory::createHomologacao('codigo-ibge-municipio');

// 3. Criar facade (aceita Configuration|array — sem cast)
$nfse = NfseNacionalFacade::create($config, $certificado);

// 4. Emitir DPS
$response = $nfse->emitirDps($dpsRequest);
```

## 📚 Documentação

| Documento | Descrição |
|-----------|-----------|
| [GUIA_IMPLEMENTACAO.md](docs/GUIA_IMPLEMENTACAO.md) | Guia completo de uso com exemplos |
| [ARQUITETURA.md](docs/ARQUITETURA.md) | Arquitetura do sistema e decisões de design |
| [SEGURANCA_COMPLIANCE.md](docs/SEGURANCA_COMPLIANCE.md) | Diretrizes de segurança |
| [CONTRIBUTING.md](CONTRIBUTING.md) | Como contribuir |
| [CHANGELOG.md](CHANGELOG.md) | Histórico de alterações |
| [examples/](examples/) | Exemplos práticos de uso |

## ✅ Requisitos

- PHP 8.3+
- ext-dom, ext-curl, ext-zlib, ext-openssl, ext-mbstring

## 🔧 Qualidade de Código

```bash
composer test    # PHPUnit
composer cs      # PHP-CS-Fixer (dry-run)
composer cs:fix  # PHP-CS-Fixer (aplicar)
composer stan    # PHPStan nível 8
composer check   # Tudo junto
```

**Métricas:**
- ✅ Suíte completa de testes unitários e de integração
- ✅ **PHPStan nível 8** (máximo rigor) - 0 erros
- ✅ **Clean Architecture** com SOLID
- ✅ **PSR-12** (estilo) e **PSR-3** (logging)
- ✅ Conformidade com os schemas NFSe Nacional v1.00 e v1.01

## 📁 Estrutura

```
src/
├── Domain/           # Entidades e Value Objects
├── Application/     # Services e DTOs
├── Infrastructure/  # HTTP, XML, Segurança
└── Presentation/    # Facade e Factories
```

## 🏛️ Padrões Aplicados

| Padrão | Onde |
|--------|------|
| Clean Architecture | Separação em camadas (Domain → Application → Infrastructure → Presentation) |
| Facade | `NfseNacionalFacade` - ponto único de entrada |
| Factory | `ServiceFactory`, `ConfigFactory` - criação de dependências |
| DTO | `DpsRequest`, `NfseResponse`, `EventoRequest` - transporte de dados |
| Value Object | `Cnpj`, `Cpf`, `Money`, `ChaveAcesso` - imutáveis e auto-validáveis |
| Validator | `DpsValidator`, `XsdValidator` - validação de entrada e XML |

## 🔄 Fluxo de Uso

```
1. Carregar certificado → Certificate::readPfx()
2. Criar configuração → ConfigFactory::createHomologacao() ou createProducao()
3. Instanciar facade → NfseNacionalFacade::create()
4. Montar request → DpsRequest / EventoRequest
5. Executar operação → facade->emitirDps() / consultarPorChave() / cancelar()
```

## 📄 API Principal

**NfseNacionalFacade** - Ponto único de entrada:

- `emitirDps(DpsRequest)` → `NfseResponse`
- `consultarPorChave(string)` → `NfseResponse|null`
- `consultarDpsPorChave(string)` → `array`
- `cancelar(EventoRequest)` → `EventoResponse`
- `consultarEventos(string)` → `array`

## ⚠️ Avisos Importantes

### Configuração do Município

A variável `prefeitura` deve receber o **código IBGE do município** (7 dígitos), conforme especificação dos XSDs da NFSe (ABRASF).

### Encoding XML

O XML pode vir em ISO-8859-1. Use o segundo parâmetro se necessário:
```php
$nfse->consultarPorChave('CHAVE', false);
```

## 🐛 Erros da SEFIN

Quando a emissão/evento é rejeitado, a SEFIN retorna os erros estruturados. A resposta os expõe:

```php
$response->mensagem;  // primeiro erro, ex.: "E0617 - Não é permitido informar alíquota..."
$response->erros;     // lista completa: [['codigo' => 'E0617', 'descricao' => '...'], ...]
$response->dados;     // payload cru da SEFIN
```

Os códigos (`E0617`, `E1860`, ...) e suas descrições vêm da própria SEFIN. Causas comuns de rejeição:
- CNPJ/CPF do prestador não habilitado na NFS-e Nacional
- Dados fiscais inconsistentes com o município/regime
- Indisponibilidade do ambiente de homologação

## 🤝 Créditos e Agradecimentos

Este projeto é uma **reestruturação completa** com Clean Architecture do projeto original.

### 📦 Projeto Original
- **Repositório:** [hadder/nfse-nacional](https://github.com/Rainzart/nfse-nacional)
- **Autor:** Fernando Friedrich ([@fernando-friedrich](https://github.com/Rainzart))
- **Contribuição:** Base inicial e compreensão das regras de negócio NFSe Nacional

### 🔐 Dependências
- **NFePHP/sped-common** - Manipulação de certificados digitais A1/A3 e assinatura XML
  - Projeto mantido pela comunidade brasileira desde 2008
  - [github.com/nfephp-org/sped-common](https://github.com/nfephp-org/sped-common)
  - Criado por Roberto L. Machado ([@robmachado](https://github.com/robmachado))

### 👩‍💻 Manutenção Atual
- **Marcela Beatriz** ([@marcelabeh](https://github.com/marcelabeh))
- Arquitetura: Clean Architecture + SOLID + DDD
- Análise estática: PHPStan level 8

## 📜 Licença

**LGPL-3.0-or-later** (GNU Lesser General Public License v3.0 ou posterior)

Veja o arquivo [LICENSE](LICENSE) para detalhes completos.

### 💼 O que isso significa?

✅ **Você PODE:**
- Usar comercialmente (grátis)
- Modificar o código
- Distribuir em projetos proprietários
- Vender produtos que usam esta biblioteca

⚠️ **Você DEVE:**
- Manter o copyright original
- Se modificar A BIBLIOTECA, compartilhar as mudanças (LGPL)
- Incluir uma cópia da licença LGPL
