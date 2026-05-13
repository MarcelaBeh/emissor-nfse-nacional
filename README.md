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

**Não é:** Uma aplicação standalone ou sistema completo.

---

## 📚 Documentação

- **[Arquitetura de Refatoração](docs/ARQUITETURA_REFATORACAO.md)** - Proposta completa de refatoração com Clean Architecture
- **[Guia de Implementação](docs/GUIA_IMPLEMENTACAO.md)** - Exemplos práticos e padrões de código
- **[Segurança e Compliance](docs/SEGURANCA_COMPLIANCE.md)** - Diretrizes de segurança e conformidade

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

### Serviços implementados

- consultarNfseChave
- consultarDpsChave
- consultarNfseEventos
- consultarDanfse
- enviaDps
- cancelaNfse

## Requerimentos

- PHP 8.2+
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

# Créditos

Este pacote é um **fork** do [hadder/nfse-nacional](https://github.com/Rainzart/nfse-nacional), originalmente desenvolvido por **Fernando Friedrich**.

O pacote original foi construído com base nos componentes do [NFePHP](https://github.com/nfephp-org), criado por **[Roberto L. Machado](https://github.com/robmachado)**.

Agradecimentos a todos os contribuidores do projeto original:  
https://github.com/Rainzart/nfse-nacional/graphs/contributors