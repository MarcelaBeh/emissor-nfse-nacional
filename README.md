# NFSe Padrão Nacional

**Biblioteca PHP para integração com NFSe Nacional** - Pacote Composer reutilizável para emissão, consulta e cancelamento de Notas Fiscais de Serviço Eletrônicas no padrão nacional (https://www.nfse.gov.br/).

Desenvolvido com componentes NFePHP (https://github.com/nfephp-org).

**Status:** Em desenvolvimento. Use por sua conta e risco.

---

## 📦 O que é este projeto?

Este é um **pacote Composer (biblioteca)** que:

- ✅ **Integra com APIs do Governo** (NFSe Nacional - SEFIN)
- ✅ **Funciona em qualquer projeto PHP** (Laravel, Symfony, CakePHP, vanilla PHP)
- ✅ **É instalado via Composer** (`composer require marcelabeh/emissor-nfse-nacional`)
- ✅ **Será usado por outros desenvolvedores** em seus próprios projetos
- ✅ **É independente de framework** - Zero acoplamento
- ✅ **Segue padrões PSR** (PSR-4, PSR-12) e Clean Architecture
- ✅ **Clean Architecture** (Domain, Application, Infrastructure, Presentation)

**Não é:** Uma aplicação standalone ou sistema completo.

---

## 📚 Documentação

- **[Arquitetura de Refatoração](docs/ARQUITETURA_REFATORACAO.md)** - Proposta completa de refatoração com Clean Architecture
- **[Guia de Implementação](docs/GUIA_IMPLEMENTACAO.md)** - Exemplos práticos e padrões de código
- **[Segurança e Compliance](docs/SEGURANCA_COMPLIANCE.md)** - Diretrizes de segurança e conformidade
- **[Princípios de Design](docs/LIBRARY_DESIGN.md)** - Como construir uma biblioteca reutilizável
- **[Roadmap](docs/ROADMAP.md)** - Progresso da refatoração
- **[Checklist Diário](docs/CHECKLIST_DIARIO.md)** - Checklist de qualidade para desenvolvimento

## ⚠️⚠️⚠️ AVISOS ⚠️⚠️⚠️

###  Configuração da Prefeitura

Na configuração do sistema, a variável `prefeitura` pode receber atualmente dois tipos de valores:

- Um identificador textual, por exemplo: `americana-sp`
- O código IBGE do município

⚠️ **Importante:** no momento, ambos os formatos são aceitos por compatibilidade.  
Porém, **futuramente o padrão adotado será exclusivamente o código IBGE**.  
Recomenda-se desde já utilizar o código IBGE para evitar ajustes em versões futuras.

### Método consultarNfseChave() e encoding

O arquivo XML após o gz_decode está vindo em ISO-8859-1. O método vai passar pelo mb_convert_encoding mantendo ISO, caso você tenha problemas utilize o segundo parâmetro como false como exemplo abaixo:

```
//Retorna ISO, padrão.
$tools->consultarNfseChave('CHAVE_NFSE');

//Retorna XML cru, sem passar por mb_convert_enconding
$tools->consultarNfseChave('CHAVE_NFSE', false);
```

## Install

**Este pacote é desenvolvido para uso do [Composer](https://getcomposer.org/), então não terá nenhuma explicação de instalação alternativa.**

```bash
composer require marcelabeh/emissor-nfse-nacional
```

## Quality Tools

Este projeto utiliza ferramentas automatizadas de qualidade:

| Ferramenta | Comando | Descrição |
|-----------|---------|-----------|
| PHP-CS-Fixer | `composer cs` / `composer cs:fix` | Padrão PSR-12 |
| PHPStan (nível 5) | `composer stan` | Análise estática |
| PHPUnit | `composer test` | Testes unitários e integração |

**Check completo:** `composer check` (executa CS → PHPStan → PHPUnit em sequência).

### Serviços implementados

| Serviço | Legacy (v1) | Nova Arquitetura (v2) |
|---------|-------------|----------------------|
| Emitir DPS | ✅ `enviaDps()` | ✅ `EmitirDpsService` |
| Consultar NFSe por Chave | ✅ `consultarNfseChave()` | ✅ `ConsultarNfseService` |
| Consultar DPS por Chave | ✅ `consultarDpsChave()` | ✅ via `ConsultarNfseService` |
| Consultar Eventos | ✅ `consultarNfseEventos()` | ✅ via `ConsultarNfseService` |
| Consultar DANFSe | ✅ `consultarDanfse()` | ✅ via `ConsultarNfseService` |
| Cancelar NFSe | ✅ `cancelaNfse()` | ✅ `CancelarNfseService` |

**API Unificada:** `NfseNacionalFacade` — ponto único de entrada para todos os serviços.

## Requerimentos

- PHP 8.3+
- ext-dom
- ext-curl
- ext-zlib
- ext-openssl
- ext-mbstring

## FAQ - E999 - Erro não catalogado

Podem existir diversos motivos para esse erro ocorrer, já que ele se refere a uma falha não catalogada pela própria Receita, incluindo erros de servidor (500) e outros problemas aleatórios.

Vale mencionar que, no ambiente de **homologação**, esses erros costumam aparecer sem motivo algum, enquanto no ambiente de **produção** a nota normalmente é emitida sem problemas.

Como a Receita só atualiza suas APIs quando está inspirada, listamos abaixo as causas mais comuns com base nos relatos que já recebemos:

- CPF/CNPJ do **prestador** não existente/cadastrado/habilitado na NFSe Nacional/Prefeitura;

## Status

![CI](https://github.com/marcelabeh/emissor-nfse-nacional/actions/workflows/ci.yml/badge.svg)
![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php)
![PHPStan](https://img.shields.io/badge/PHPStan-level%205-brightgreen)
![Tests](https://img.shields.io/badge/tests-25%20passing-brightgreen)
![PHP CS Fixer](https://img.shields.io/badge/code%20style-PSR--12-blue)

# Créditos

Este pacote é um **fork** do [hadder/nfse-nacional](https://github.com/Rainzart/nfse-nacional), originalmente desenvolvido por **Fernando Friedrich**.

O pacote original foi construído com base nos componentes do [NFePHP](https://github.com/nfephp-org), criado por **[Roberto L. Machado](https://github.com/robmachado)**.

Agradecimentos a todos os contribuidores do projeto original:  
https://github.com/Rainzart/nfse-nacional/graphs/contributors