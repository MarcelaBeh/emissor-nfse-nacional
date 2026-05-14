# Como Contribuir

Obrigado por contribuir com o emissor-nfse-nacional!

---

## 📋 Checklist Antes de PR

```bash
# 1. Clone o repositório
git clone https://github.com/marcelabeh/emissor-nfse-nacional.git
cd emissor-nfse-nacional

# 2. Instale dependências
composer install

# 3. Execute todos os checks
composer check
```

### ✅ Código

- [ ] Código segue PSR-12
- [ ] Todas as classes têm type hints completos
- [ ] Nenhum `var_dump`, `dd`, `print_r` no código
- [ ] Sem código comentado (exceto documentação)
- [ ] Variáveis e métodos com nomes significativos

### ✅ Testes

- [ ] Testes unitários escritos
- [ ] Testes passando (`composer test`)
- [ ] Cobertura não diminuiu
- [ ] Edge cases cobertos

### ✅ Qualidade

- [ ] PHPStan nível 8 sem erros (`composer stan`)
- [ ] PHP-CS-Fixer aplicado (`composer cs:fix`)
- [ ] Sem duplicação de código

### ✅ Segurança

- [ ] Inputs validados
- [ ] Outputs sanitizados
- [ ] Sem dados sensíveis em logs
- [ ] Sem credenciais hardcoded

---

## 🔀 Fluxo de Trabalho

### 1. Fork do Repositório

Faça fork do repositório principal.

### 2. Crie uma Branch

```bash
git checkout -b feature/minha-nova-feature
# ou
git checkout -b fix/correcao-bug
```

### 3. Faça suas Alterações

```bash
# Edite arquivos...
git add .
git commit -m "tipo: descrição clara"
```

#### Convenções de Commit

```bash
# Features
git commit -m "feat: adiciona novo Value Object Xpto"

# Bug Fixes
git commit -m "fix: corrige validação de CNPJ em caso edge"

# Documentação
git commit -m "docs: atualiza guia de uso"

# Testes
git commit -m "test: adiciona testes para Parser"

# Refatoração
git commit -m "refactor: simplifica lógica de cálculo"

# Performance
git commit -m "perf: otimiza parsing de XML"

# Estilo
git commit -m "style: formata código com CS-Fixer"

# Build/CI
git commit -m "chore: atualiza dependências"
```

### 4. Push e Abra PR

```bash
git push origin feature/minha-nova-feature
```

Abra um Pull Request no GitHub.

---

## 📐 Padrões de Código

### Clean Architecture

```
src/
├── Domain/           # Regras de negócio puras
│   ├── Entity/       # Entidades principais
│   ├── ValueObject/ # Value Objects imutáveis
│   ├── Enum/        # Enums
│   └── Contract/    # Interfaces
├── Application/     # Casos de uso
│   ├── Service/     # Services
│   ├── DTO/         # Data Transfer Objects
│   └── Validator/   # Validadores
├── Infrastructure/  # Implementações externas
│   ├── Http/        # Cliente HTTP
│   ├── Xml/         # Builders e Parsers
│   ├── Security/    # Certificados e assinatura
│   └── Repository/  # Repositórios
└── Presentation/    # API pública
    ├── Facade/      # Facade principal
    └── Factory/     # Factories
```

### Regras para Cada Camada

**Domain:**
- ✅ Não depende de outras camadas
- ✅ `readonly` e `final` quando possível
- ✅ Validação no construtor
- ✅ Sem efeitos colaterais

**Application:**
- ✅ Depende apenas de interfaces (Domain)
- ✅ Retorna DTOs ou Entities
- ✅ Sem acesso direto a recursos externos

**Infrastructure:**
- ✅ Implementa interfaces do Domain
- ✅ Trata erros externos
- ✅ Adapta bibliotecas externas

**Presentation:**
- ✅ Depende de Application e Infrastructure
- ✅ API pública simples e clara
- ✅ Documentação completa

---

## ✅ Checklist por Tipo de Arquivo

### Value Object

- [ ] É `readonly`?
- [ ] É `final`?
- [ ] Tem validação no construtor?
- [ ] É imutável?
- [ ] Tem método `equals()`?
- [ ] Lança exceção específica se inválido?
- [ ] Testes > 90%?

### Entity

- [ ] Tem construtor com validação?
- [ ] Propriedades são `private`?
- [ ] Usa Value Objects quando apropriado?
- [ ] Getters retornam cópias de objetos mutáveis?
- [ ] Testes cobrem regras de negócio?

### Service (Application)

- [ ] Dependências injetadas no construtor?
- [ ] Retorna DTO ou Entity?
- [ ] Não acessa recursos externos diretamente?
- [ ] Tratamento de exceções?
- [ ] Testes unitários e de integração?

### Builder/Parser XML

- [ ] Valida contra XSD?
- [ ] Trata encoding corretamente?
- [ ] Sanitiza inputs?
- [ ] Testes com XMLs reais?

### Facade

- [ ] API pública clara?
- [ ] Documentação PHPDoc completa?
- [ ] Exemplos de uso?
- [ ] Testes de integração?

---

## 🔒 Checklist de Segurança

### Certificados

- [ ] Nunca commitar certificados
- [ ] Arquivos temporários com permissões 0600
- [ ] Cleanup de arquivos temporários
- [ ] Validação de expiração

### Validações

- [ ] CNPJ validado com dígito verificador
- [ ] CPF validado com dígito verificador
- [ ] Código IBGE com 7 dígitos
- [ ] Chave de acesso com 50 caracteres

### Logs

- [ ] CPF/CNPJ sanitizados
- [ ] Emails ofuscados
- [ ] Senhas/tokens nunca logados
- [ ] Chaves privadas nunca logadas

---

## 📞 Em Caso de Dúvida

1. Consulte a [documentação](docs/)
2. Veja os [exemplos](examples/)
3. Abra uma issue no GitHub
4. Pergunte nas Discussions

---

**Lembre-se:** Qualidade > Velocidade. Segurança é obrigatória. Código é lido mais vezes do que escrito.