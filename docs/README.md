# Documentação - NFSe Nacional

Índice completo da documentação do projeto de refatoração.

---

## 📖 Documentos Disponíveis

### 1. [Arquitetura de Refatoração](ARQUITETURA_REFATORACAO.md) ⭐
**Proposta completa de arquitetura limpa e moderna**

Conteúdo:
- Visão geral da arquitetura proposta
- Estrutura de diretórios detalhada
- Camadas e responsabilidades
- Implementação detalhada de cada camada
- Plano de migração (14 semanas)
- Estrutura de testes
- Benefícios esperados

📊 **Ideal para:** Arquitetos, Tech Leads, Desenvolvedores Seniores

---

### 2. [Princípios de Design de Biblioteca](LIBRARY_DESIGN.md) 📦 **RECOMENDADO**
**Como construir uma biblioteca reutilizável**

Conteúdo:
- 10 Mandamentos de Bibliotecas PHP
- Framework-agnostic design
- Minimal dependencies
- Semantic versioning
- Zero global state
- Interface-based extensibility
- Exemplos de uso (Laravel, Symfony, Vanilla PHP)
- Checklist de biblioteca

📦 **Ideal para:** Todos os desenvolvedores (entender contexto de biblioteca)

---

### 3. [Guia de Implementação](GUIA_IMPLEMENTACAO.md) ⭐
**Guia prático com exemplos de código**

Conteúdo:
- Ordem de implementação (de dentro para fora)
- Exemplos completos de Value Objects
- Exemplos completos de Entities
- Configuração com múltiplos ambientes
- Factory Pattern para serviços
- Checklist de desenvolvimento
- Padrões e convenções
- Tratamento de erros
- Performance e otimização
- Exemplos de testes

💻 **Ideal para:** Desenvolvedores implementando a refatoração

---

### 4. [Segurança e Compliance](SEGURANCA_COMPLIANCE.md) 🔒 **OBRIGATÓRIO**
**Diretrizes críticas de segurança**

Conteúdo:
- Segurança de certificados digitais
- Armazenamento temporário seguro
- Validações obrigatórias (CNPJ, CPF, Código IBGE)
- Compliance com NFSe Nacional
- Validação estrita contra XSD
- Tratamento de dados sensíveis
- Sanitização de logs
- Auditoria de operações
- Testes de segurança
- Checklist de segurança

🔒 **Ideal para:** Todos os desenvolvedores (LEITURA OBRIGATÓRIA)

---

### 5. [Roadmap de Refatoração](ROADMAP.md) 📅 ⭐
**Acompanhamento visual do progresso**

Conteúdo:
- Timeline detalhado (14 semanas)
- Progresso por fase
- Marcos importantes
- Métricas de qualidade
- Riscos e mitigações
- Checklist de release
- Responsabilidades e papéis

📈 **Ideal para:** Gestores de projeto, Tech Leads, stakeholders

**Status Atual:** ✅ Fases 1-5 Implementadas (Domain, Infrastructure, Application, Presentation)

---

### 6. [Atualizações](doc.atualizacao.txt)
**Histórico de atualizações e códigos de erro**

Conteúdo:
- Códigos de erro da API NFSe Nacional
- Mensagens de validação
- Atualizações do sistema

---

### 7. [Checklist Diário](CHECKLIST_DIARIO.md) ✅
**Checklist rápido para desenvolvimento**

Conteúdo:
- Checklist antes de commitar
- Checklist antes de PR
- Checklist por tipo de arquivo
- Checklist de segurança
- Checklist de performance
- Checklist de testes
- Comandos rápidos

⚡ **Ideal para:** Todos os desenvolvedores (uso diário)

---

## 🎯 Fluxo de Leitura Recomendado

### Para novos desenvolvedores:
1. ✅ Ler [LIBRARY_DESIGN.md](LIBRARY_DESIGN.md) (entender contexto de biblioteca) 📦
2. ✅ Ler [ARQUITETURA_REFATORACAO.md](ARQUITETURA_REFATORACAO.md) (visão geral)
3. ✅ Ler [SEGURANCA_COMPLIANCE.md](SEGURANCA_COMPLIANCE.md) (crítico!)
4. ✅ Ler [GUIA_IMPLEMENTACAO.md](GUIA_IMPLEMENTACAO.md) (implementação)
5. ✅ Consultar [ROADMAP.md](ROADMAP.md) (progresso e timeline)
6. ✅ Consultar exemplos na pasta `/exemples`

### Para arquitetos/tech leads:
1. ✅ Ler [LIBRARY_DESIGN.md](LIBRARY_DESIGN.md) (princípios de biblioteca) 📦
2. ✅ Ler [ARQUITETURA_REFATORACAO.md](ARQUITETURA_REFATORACAO.md) completo
3. ✅ Revisar [ROADMAP.md](ROADMAP.md) e plano de migração (Fase 1-7)
4. ✅ Validar estrutura proposta
5. ✅ Ler [SEGURANCA_COMPLIANCE.md](SEGURANCA_COMPLIANCE.md)

### Para gestores de projeto:
1. ✅ Ler [ROADMAP.md](ROADMAP.md) (timeline, marcos, riscos)
2. ✅ Revisar métricas de qualidade
3. ✅ Acompanhar progresso semanal
4. ✅ Ler [ARQUITETURA_REFATORACAO.md](ARQUITETURA_REFATORACAO.md) (visão geral)

### Para usuários da biblioteca (outros projetos):
1. ✅ Ler [LIBRARY_DESIGN.md](LIBRARY_DESIGN.md) (como usar como biblioteca) 📦
2. ✅ Consultar exemplos de integração (Laravel, Symfony, Vanilla PHP)
3. ✅ Ver README.md principal para quick start
4. ✅ Consultar pasta `/exemples` para casos de uso

### Para revisão de código:
1. ✅ Consultar [GUIA_IMPLEMENTACAO.md](GUIA_IMPLEMENTACAO.md) - Checklist de Desenvolvimento
2. ✅ Verificar [SEGURANCA_COMPLIANCE.md](SEGURANCA_COMPLIANCE.md) - Checklist de Segurança
3. ✅ Verificar [LIBRARY_DESIGN.md](LIBRARY_DESIGN.md) - Checklist de Biblioteca
4. ✅ Validar convenções e padrões

---

## 🔑 Conceitos-Chave

### Clean Architecture
Arquitetura em camadas com separação clara de responsabilidades:
- **Domain**: Regras de negócio puras
- **Application**: Casos de uso e orquestração
- **Infrastructure**: Implementações técnicas
- **Presentation**: Interface pública (API)

### SOLID Principles
- **S**ingle Responsibility
- **O**pen/Closed
- **L**iskov Substitution
- **I**nterface Segregation
- **D**ependency Inversion

### Value Objects
Objetos imutáveis que representam conceitos sem identidade (Cnpj, Cpf, Money, etc.)

### Entities
Objetos com identidade única que representam conceitos de negócio (Dps, Nfse, Prestador, etc.)

### DTOs (Data Transfer Objects)
Objetos para transferência de dados entre camadas

---

## 📦 Estrutura de Pastas

```
docs/
├── README.md                          # Este arquivo (índice)
├── ARQUITETURA_REFATORACAO.md         # Arquitetura completa ⭐
├── LIBRARY_DESIGN.md                  # Princípios de biblioteca 📦
├── GUIA_IMPLEMENTACAO.md              # Guia prático ⭐
├── SEGURANCA_COMPLIANCE.md            # Segurança e compliance 🔒
├── ROADMAP.md                         # Roadmap e progresso 📅 (Fases 1-5 ✅)
├── CHECKLIST_DIARIO.md                # Checklist de desenvolvimento ✅
└── doc.atualizacao.txt                # Histórico de atualizações / códigos de erro
```

---

## 🚀 Início Rápido

### 1. Entender a Proposta
Leia a [Arquitetura de Refatoração](ARQUITETURA_REFATORACAO.md) para compreender a visão geral.

### 2. Preparar Ambiente
```bash
# Instalar dependências
composer install

# Configurar ferramentas de qualidade
composer require --dev phpstan/phpstan
composer require --dev friendsofphp/php-cs-fixer
```

### 3. Começar Implementação
Siga a ordem do [Guia de Implementação](GUIA_IMPLEMENTACAO.md):
1. Domain Layer (Value Objects → Entities)
2. Infrastructure Layer
3. Application Layer
4. Presentation Layer

### 4. Garantir Segurança
Revise constantemente o [Guia de Segurança](SEGURANCA_COMPLIANCE.md) durante o desenvolvimento.

---

## ⚠️ Avisos Importantes

### 🔒 Segurança
**NUNCA:**
- Commitar certificados digitais
- Logar dados sensíveis sem sanitização
- Usar certificados de produção em testes
- Armazenar certificados permanentemente

**SEMPRE:**
- Validar todos os inputs
- Usar arquivos temporários com permissões restritas
- Sanitizar logs
- Validar contra XSD

### 📜 Compliance
**OBRIGATÓRIO:**
- Validação contra schemas XSD oficiais
- Uso correto de códigos (com zeros à esquerda quando necessário)
- Código IBGE de 7 dígitos
- Validação de CNPJ/CPF
- Assinatura digital válida

---

## 🤝 Contribuindo

Ao contribuir com código:

1. ✅ Leia todos os documentos
2. ✅ Siga os padrões estabelecidos
3. ✅ Escreva testes (cobertura >= 80%)
4. ✅ Valide com PHPStan nível 8
5. ✅ Revise o checklist de segurança
6. ✅ Documente mudanças

---

## 📞 Suporte

Para dúvidas sobre a refatoração:

1. Consultar documentação nesta pasta
2. Ver exemplos em `/exemples`
3. Abrir issue no repositório
4. Consultar documentação oficial NFSe Nacional

---

## 🔗 Links Úteis

- [Portal NFSe Nacional](https://www.nfse.gov.br/)
- [Ambiente de Homologação](https://www.producaorestrita.nfse.gov.br/)
- [NFePHP](https://github.com/nfephp-org)
- [PSR Standards](https://www.php-fig.org/psr/)

---

**Documento mantido por:** Marcela Beatriz
**Última atualização:** 13/05/2026  
**Versão da documentação:** 1.0
