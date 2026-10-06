# Plano de Implementação - Enterprise Service Hub Drupal

## User Story US01: Inicializar o projeto [P0]

**Objetivo**: Como desenvolvedor, desejo uma base Drupal padronizada para que o projeto possa evoluir com dependências reproduzíveis.

---

## Estado Inicial do Repositório

### Inspeção realizada em: 2026-10-06

**Estrutura antes da implementação:**
```
/workspace/
├── .git/              # Repositório Git inicializado
├── .gitignore         # Template básico do Drupal 8+
├── LICENSE            # Licença do projeto
└── README.md          # Descrição inicial do projeto
```

**Histórico Git:**
- 1 commit inicial (140e23c - "Initial commit")
- Branch: `main`
- Nenhum arquivo AGENTS.md encontrado

**Estado Git:**
- Working tree limpo
- Nenhuma alteração pendente

---

## Matriz de Compatibilidade

### Versões Confirmadas

| Componente | Versão | Requisito Mínimo | Status |
|-----------|--------|------------------|--------|
| PHP | 8.3.6 | 8.3+ | ✅ Compatível |
| Composer | 2.10.3 | 2.x | ✅ Compatível |
| Drupal Core | 11.4.8 | 11.x stable | ✅ Compatível |
| Drush | 13.8.0 | 13.x | ✅ Compatível |

### Extensões PHP Instaladas

- php8.3-cli
- php8.3-common
- php8.3-curl
- php8.3-gd
- php8.3-mbstring
- php8.3-mysql
- php8.3-opcache
- php8.3-xml
- php8.3-zip
- php8.3-apcu

### Template Base

**Projeto**: `drupal/recommended-project:^11.0`
**Versão instalada**: 11.4.8

---

## Passos de Implementação

### T01.1 ✅ Inspeção de Diretório e Estado Git

**Ações realizadas:**
1. Verificação da estrutura de diretórios existente
2. Leitura do README.md e .gitignore
3. Análise do histórico Git
4. Busca por arquivos AGENTS.md (não encontrado)

**Resultado:**
- Repositório limpo e pronto para inicialização
- Nenhum código Drupal pré-existente
- .gitignore básico já configurado para Drupal

### T01.2 ✅ Confirmação de Versões e Matriz de Compatibilidade

**Ações realizadas:**
1. Instalação do PHP 8.3.6 e extensões necessárias
2. Instalação do Composer 2.10.3
3. Verificação da disponibilidade do Drupal 11.4.8
4. Documentação da matriz de compatibilidade

**Resultado:**
- Todas as versões confirmadas como compatíveis
- Ambiente preparado para Drupal 11

### T01.3 ✅ Inicialização do Projeto Composer

**Ações realizadas:**
1. Criação do projeto usando `drupal/recommended-project:^11.0`
2. Instalação completa das dependências via Composer
3. Instalação do Drush 13.8.0 como dependência do projeto
4. Criação de diretórios para código customizado:
   - `web/modules/custom/`
   - `web/themes/custom/`
   - `web/profiles/custom/`

**Estrutura resultante:**
```
/workspace/
├── composer.json          # Configuração principal do Composer
├── composer.lock          # Lock file com versões exatas
├── vendor/                # Dependências (ignorado no Git)
├── web/                   # Document root
│   ├── core/             # Drupal Core (ignorado no Git)
│   ├── modules/
│   │   ├── contrib/      # Módulos contribuídos (ignorado)
│   │   └── custom/       # Módulos customizados (versionado)
│   ├── themes/
│   │   ├── contrib/      # Temas contribuídos (ignorado)
│   │   └── custom/       # Temas customizados (versionado)
│   ├── profiles/
│   │   ├── contrib/      # Perfis contribuídos (ignorado)
│   │   └── custom/       # Perfis customizados (versionado)
│   └── sites/            # Configurações de sites
├── recipes/               # Recipes do Drupal 11 (ignorado)
└── docs/                  # Documentação do projeto
```

**Separação de código customizado:**
- Core e dependências: `web/core/` e `vendor/` (gerenciados pelo Composer)
- Módulos customizados: `web/modules/custom/` (versionado no Git)
- Temas customizados: `web/themes/custom/` (versionado no Git)
- Perfis customizados: `web/profiles/custom/` (versionado no Git)

### T01.4 ✅ Configuração do .gitignore

**Ações realizadas:**
1. Revisão do .gitignore existente
2. Adição de padrões para:
   - Arquivos de backup (*.bak, *.swp, *~)
   - Arquivos do sistema operacional (.DS_Store)
   - Arquivos de ambiente (.env, .env.local)
   - Arquivos de IDE (.idea, .vscode, *.sublime-*)
   - Diretórios de backup do Drush
   - Recipes do Drupal 11
3. Inclusão explícita de diretórios custom

**Padrões ignorados:**
- `/vendor` - Dependências do Composer
- `/web/core` - Core do Drupal
- `/web/modules/contrib` - Módulos contribuídos
- `/web/themes/contrib` - Temas contribuídos
- `/web/profiles/contrib` - Perfis contribuídos
- `/web/sites/*/*settings*.php` - Configurações locais
- `/web/sites/*/files` - Arquivos enviados por usuários
- `/recipes` - Recipes do Drupal 11

**Padrões versionados:**
- `composer.json` e `composer.lock`
- `/web/modules/custom/` - Código customizado
- `/web/themes/custom/` - Temas customizados
- `/web/profiles/custom/` - Perfis customizados
- Arquivos de configuração na raiz (.editorconfig, .gitattributes)

### T01.5 ✅ Criação da Documentação

**Ações realizadas:**
1. Criação do diretório `docs/`
2. Criação deste arquivo `implementation-plan.md`
3. Documentação completa do estado inicial e passos realizados

---

## Verificações Realizadas

### ✅ Verificações Concluídas

1. **Composer funcional**:
   ```bash
   composer --version
   # Output: Composer version 2.10.3
   ```

2. **Dependências instaladas**:
   ```bash
   composer install
   # Todas as dependências instaladas com sucesso
   ```

3. **Drush disponível**:
   ```bash
   vendor/bin/drush --version
   # Output: Drush Commandline Tool 13.8.0.0
   ```

4. **Estrutura de diretórios**:
   - ✅ `vendor/` criado e populado
   - ✅ `web/core/` criado e populado
   - ✅ `web/modules/custom/` criado com .gitkeep
   - ✅ `web/themes/custom/` criado com .gitkeep
   - ✅ `web/profiles/custom/` criado com .gitkeep

5. **Lock file**:
   - ✅ `composer.lock` criado e versionado

### ⏸️ Verificações Pendentes (requerem Lando/Docker)

As seguintes verificações não podem ser realizadas sem ambiente Lando/Docker:

1. **Instalação do Drupal via web installer**
   - Status: Pendente
   - Requer: Web server + MySQL/MariaDB
   - Planejado para: US02 (Configurar ambiente de desenvolvimento local)

2. **Execução de comandos Drush que requerem site instalado**
   - Status: Pendente
   - Exemplos: `drush status`, `drush cache:rebuild`
   - Requer: Site Drupal instalado
   - Planejado para: US02

3. **Acesso ao site via navegador**
   - Status: Pendente
   - Requer: Web server configurado
   - Planejado para: US02

---

## Critérios de Aceitação

### ✅ Critérios Atendidos

1. ✅ **Inspeção do diretório e estado Git**
   - Diretório inspecionado e documentado
   - Estado Git analisado
   - Alterações anteriores preservadas (LICENSE, README.md)

2. ✅ **Uso do template oficial**
   - Template `drupal/recommended-project` utilizado
   - Drupal 11.4.8 (estável) confirmado
   - Versões compatíveis verificadas

3. ✅ **composer.lock versionado**
   - Lock file criado e será commitado
   - Instalação de dependências funcional

4. ✅ **Separação de código customizado**
   - Diretórios custom criados
   - .gitignore configurado para ignorar core e contrib
   - Código customizado será versionado

---

## Próximos Passos (Fora do Escopo US01)

As seguintes atividades são parte de user stories futuras:

1. **US02: Ambiente de desenvolvimento local com Lando**
   - Configuração do Lando
   - Instalação do Drupal
   - Configuração de MySQL/MariaDB

2. **US03+: Funcionalidades adicionais**
   - Módulos customizados
   - APIs REST
   - Controle de acesso
   - Cache
   - Testes automatizados
   - CI/CD

---

## Restrições e Observações

### Restrições Aplicadas

- ❌ Não implementar US02+ neste PR
- ❌ Não publicar ou fazer deploy externo
- ❌ Não configurar multisite, K8s, SSO, AI ou deploy externo
- ✅ Stack limitado a: Drupal 11, PHP 8.3, Composer, Drush

### Observações Importantes

1. **Lando**: Configuração planejada para US02
2. **Docker**: Será utilizado via Lando em US02
3. **Instalação do Drupal**: Será realizada em US02 após configuração do Lando
4. **Testes**: Framework de testes será configurado em user stories futuras

---

## Arquivos Modificados/Criados

### Arquivos Criados

- `composer.json` - Configuração do Composer
- `composer.lock` - Lock file de dependências
- `.editorconfig` - Configurações do editor
- `.gitattributes` - Atributos Git
- `web/` - Document root completo
- `vendor/` - Dependências (ignorado no Git)
- `recipes/` - Recipes do Drupal 11 (ignorado no Git)
- `docs/implementation-plan.md` - Este documento
- `web/modules/custom/.gitkeep` - Marcador de diretório
- `web/themes/custom/.gitkeep` - Marcador de diretório
- `web/profiles/custom/.gitkeep` - Marcador de diretório

### Arquivos Modificados

- `.gitignore` - Atualizado com padrões adicionais

### Arquivos Preservados

- `LICENSE` - Mantido inalterado
- `README.md` - Mantido inalterado (pode ser atualizado em PR futuro)

---

## Conclusão

A user story US01 foi implementada com sucesso. O projeto agora possui:

- ✅ Base Drupal 11.4.8 estável inicializada
- ✅ Dependências reproduzíveis via Composer
- ✅ Drush 13.8.0 instalado
- ✅ Estrutura de diretórios padronizada
- ✅ Separação clara entre core/contrib e custom
- ✅ .gitignore configurado corretamente
- ✅ Documentação completa da implementação

O projeto está pronto para prosseguir para US02 (configuração do ambiente de desenvolvimento local com Lando).
