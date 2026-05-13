# Checklist Rápido - Desenvolvimento Diário

Use este checklist para garantir qualidade antes de cada commit/PR.

---

## ✅ Antes de Commitar

### Código
- [ ] Código segue PSR-12
- [ ] Todas as classes têm type hints completos
- [ ] Nenhum `var_dump`, `dd`, `print_r` no código
- [ ] Sem código comentado (exceto documentação)
- [ ] Sem `TODO` ou `FIXME` sem issue associada
- [ ] Variáveis e métodos com nomes significativos

### Testes
- [ ] Testes unitários escritos
- [ ] Testes passando (`vendor/bin/phpunit`)
- [ ] Cobertura não diminuiu
- [ ] Edge cases cobertos

### Qualidade
- [ ] PHPStan nível 8 sem erros (`vendor/bin/phpstan analyse src`)
- [ ] PHP-CS-Fixer aplicado (`vendor/bin/php-cs-fixer fix src`)
- [ ] Sem duplicação de código

### Segurança
- [ ] Inputs validados
- [ ] Outputs sanitizados (se aplicável)
- [ ] Sem dados sensíveis em logs
- [ ] Sem credenciais hardcoded

### Documentação
- [ ] DocBlocks em métodos públicos
- [ ] README atualizado (se necessário)
- [ ] CHANGELOG atualizado (se feature/fix)

---

## ✅ Antes de Pull Request

### Revisão Geral
- [ ] Branch atualizada com main
- [ ] Todos os testes passando
- [ ] Build do CI/CD verde
- [ ] Commits com mensagens claras

### Code Review
- [ ] Self-review feito
- [ ] Código segue arquitetura proposta
- [ ] Sem breaking changes não documentados
- [ ] Performance aceitável

### Documentação
- [ ] PR description clara
- [ ] Screenshots/exemplos (se UI)
- [ ] Breaking changes documentados
- [ ] Migration guide (se necessário)

---

## ✅ Checklist por Tipo de Arquivo

### Value Object
- [ ] É `readonly`?
- [ ] É `final`?
- [ ] Tem validação no construtor?
- [ ] É imutável?
- [ ] Tem método `__toString()`?
- [ ] Tem método `equals()`?
- [ ] Lança exceção específica se inválido?
- [ ] Testes > 90%?

### Entity
- [ ] Tem construtor com validação?
- [ ] Propriedades são `private`?
- [ ] Usa Value Objects quando apropriado?
- [ ] Regras de negócio na entity?
- [ ] Getters retornam cópias de objetos mutáveis?
- [ ] Testes cobrem regras de negócio?

### Service
- [ ] Dependências injetadas no construtor?
- [ ] Retorna DTO ou Entity?
- [ ] Não acessa recursos externos diretamente?
- [ ] Tratamento de exceções?
- [ ] Testes de integração?

### Builder/Parser XML
- [ ] Valida contra XSD?
- [ ] Trata encoding corretamente?
- [ ] Sanitiza inputs?
- [ ] Testes com XMLs reais?

### Controller/Facade
- [ ] API pública clara?
- [ ] Documentação completa?
- [ ] Exemplos de uso?
- [ ] Testes E2E?

---

## 🔒 Checklist de Segurança Específico

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
- [ ] Email validado
- [ ] Valores monetários sem float direto

### Logs
- [ ] CPF/CNPJ sanitizados
- [ ] Emails ofuscados
- [ ] Senhas/tokens nunca logados
- [ ] Chaves privadas nunca logadas

---

## 📊 Checklist de Performance

- [ ] Queries otimizadas (se aplicável)
- [ ] Caching implementado quando faz sentido
- [ ] Lazy loading de schemas XSD
- [ ] Conexões HTTP reutilizadas
- [ ] XML parsing otimizado
- [ ] Memory leaks verificados

---

## 🧪 Checklist de Testes

### Testes Unitários
- [ ] Happy path
- [ ] Edge cases
- [ ] Exceções esperadas
- [ ] Valores limite
- [ ] Null/vazios

### Testes de Integração
- [ ] Comunicação HTTP mockada
- [ ] XML válido gerado
- [ ] Parsing correto
- [ ] Erros tratados

### Testes E2E
- [ ] Fluxo completo de emissão
- [ ] Fluxo completo de consulta
- [ ] Fluxo completo de cancelamento
- [ ] Ambiente de homologação

---

## 📝 Convenções de Commit

```bash
# Features
git commit -m "feat: adiciona Value Object Cnpj com validação"

# Fixes
git commit -m "fix: corrige validação de dígito verificador do CPF"

# Docs
git commit -m "docs: atualiza guia de implementação com exemplos"

# Tests
git commit -m "test: adiciona testes para DpsXmlBuilder"

# Refactor
git commit -m "refactor: simplifica lógica de cálculo em Money"

# Performance
git commit -m "perf: otimiza parsing de XML com lazy loading"

# Style
git commit -m "style: formata código com PHP-CS-Fixer"

# Chore
git commit -m "chore: atualiza dependências do composer"
```

---

## 🚀 Comandos Rápidos

```bash
# Testes
composer test                    # Rodar todos os testes
composer test:unit              # Apenas unitários
composer test:integration       # Apenas integração
composer test:coverage          # Com cobertura

# Qualidade
composer analyse                # PHPStan
composer format                 # PHP-CS-Fixer
composer check                  # Tudo junto

# CI Local (antes do push)
composer ci                     # Simula CI/CD local
```

---

## 📞 Em Caso de Dúvida

1. Consultar [GUIA_IMPLEMENTACAO.md](GUIA_IMPLEMENTACAO.md)
2. Consultar [SEGURANCA_COMPLIANCE.md](SEGURANCA_COMPLIANCE.md)
3. Ver exemplos em `/exemples`
4. Perguntar no GitHub Discussions
5. Abrir issue

---

**Lembre-se:** 
- ✅ Qualidade > Velocidade
- 🔒 Segurança é obrigatória, não opcional
- 📝 Código é lido mais vezes do que escrito
- 🧪 Se não tem teste, está quebrado

---

**Última atualização:** 13/05/2026
