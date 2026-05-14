# NFSe Padrão Nacional

<p align="center">
  <img src="images/logo-nfs-e-horizontal.png" alt="NFSe Nacional" width="400">
</p>

**Biblioteca PHP para integração com NFSe Nacional** - Pacote Composer reutilizável para emissão, consulta e cancelamento de Notas Fiscais de Serviço Eletrônicas no padrão nacional.

**Status:** Em desenvolvimento. Use por sua conta e risco.

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
$certificado = Certificate::loadPfx($caminhoCertificado, $senha);

// 2. Criar configuração
$config = ConfigFactory::createHomologacao('codigo-ibge-municipio');

// 3. Criar facade
$nfse = NfseNacionalFacade::create((array)$config, $certificado);

// 4. Emitir DPS
$response = $nfse->emitirDps($dpsRequest);
```

## 📚 Documentação

| Documento | Descrição |
|-----------|-----------|
| [GUIA_IMPLEMENTACAO.md](docs/GUIA_IMPLEMENTACAO.md) | Guia completo de uso com exemplos |
| [ARQUITETURA.md](docs/ARQUITETURA.md) | Arquitetura do sistema e decisões de design |
| [SEGURANCA.md](docs/SEGURANCA.md) | Diretrizes de segurança |
| [CONTRIBUTING.md](CONTRIBUTING.md) | Como contribuir |
| [CHANGELOG.md](CHANGELOG.md) | Histórico de alterações |
| [examples/](examples/) | Exemplos práticos de uso |

## ✅ Requisitos

- PHP 8.3+
- ext-dom, ext-curl, ext-zlib, ext-openssl, ext-mbstring

## 🔧 Qualidade de Código

```bash
composer test    # PHPUnit (481 testes)
composer cs     # PHP-CS-Fixer (dry-run)
composer cs:fix # PHP-CS-Fixer (aplicar)
composer stan    # PHPStan nível 8
composer check   # Tudo junto
```

| Badge | Info |
|-------|------|
| ![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php) | PHP 8.3+ |
| ![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen) | Análise estática |
| ![Tests](https://img.shields.io/badge/tests-481%20passing-brightgreen) | 100% passando |
| ![PSR-12](https://img.shields.io/badge/code%20style-PSR--12-blue) | Código limpo |

## 📁 Estrutura

```
src/
├── Domain/           # Entidades e Value Objects
├── Application/     # Services e DTOs
├── Infrastructure/  # HTTP, XML, Segurança
└── Presentation/    # Facade e Factories
```

## 📄 API Principal

**NfseNacionalFacade** - Ponto único de entrada:

- `emitirDps(DpsRequest)` → `NfseResponse`
- `consultarPorChave(string)` → `NfseResponse|null`
- `consultarDpsPorChave(string)` → `array`
- `cancelar(EventoRequest)` → `EventoResponse`
- `consultarEventos(string)` → `array`
- `consultarDanfse(string)` → `string|array`
- `consultarDanfseNfse(string)` → `string|array`

## ⚠️ Avisos Importantes

### Configuração do Município

A variável `prefeitura` aceita:
- Código IBGE do município (recomendado)
- Identificador textual (ex: `americana-sp`) - temporário

### Encoding XML

O XML pode vir em ISO-8859-1. Use o segundo parâmetro se necessário:
```php
$nfse->consultarNfseChave('CHAVE', false);
```

## 🐛 FAQ - Erro E999

O erro E999 indica falha não catalogada pela Receita. Causas comuns:
- CNPJ/CPF do prestador não cadastrado/habilitado na NFSe Nacional
- Erros de servidor (500)
- Problemas no ambiente de homologação (comum)

## 📦 API Legado (v1)

| Serviço | Método |
|---------|--------|
| Emitir DPS | `enviaDps()` |
| Consultar NFSe | `consultarNfseChave()` |
| Cancelar | `cancelaNfse()` |

Consulte `examples/` para detalhes.

## 🤝 Créditos

- **Original:** [hadder/nfse-nacional](https://github.com/Rainzart/nfse-nacional) por Fernando Friedrich
- **Baseado em:** [NFePHP](https://github.com/nfephp-org) por Roberto L. Machado
- **Mantido por:** Marcela Beatriz