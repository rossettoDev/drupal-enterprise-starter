# US03 — Instalação a partir de Configuração Versionada

## Objetivo

Permitir que avaliadores técnicos instalem o site Drupal Enterprise Starter em um banco de dados vazio, reproduzindo toda a estrutura editorial, permissões, Views e tema final a partir da configuração versionada em `config/sync`.

## Solução Implementada

### Fluxo de Instalação

O projeto utiliza o comando `drush site:install --existing-config`, disponível desde o Drupal 8.6, que:

1. Instala o perfil `standard` em modo minimal
2. Importa automaticamente toda a configuração de `config/sync`
3. Resolve conflitos de UUID entre instalação e configuração
4. Habilita módulos, temas e configurações definidas nos arquivos YML

### Diretório de Sincronização

- **Caminho:** `config/sync` (raiz do projeto)
- **Configurado em:** `web/sites/default/settings.php`
- **Quantidade de arquivos:** ~160 arquivos de configuração exportados

## Como Usar

### Instalação Automatizada (Recomendado)

Execute o script de instalação a partir da raiz do projeto:

```bash
./tools/install.sh
```

O script realiza automaticamente:
- ✅ Validações de ambiente e configuração
- ✅ Inicialização do Lando (se necessário)
- ✅ Verificação do banco de dados MariaDB
- ✅ Limpeza de instalações anteriores
- ✅ Instalação de dependências via Composer
- ✅ Instalação do Drupal com importação de configuração
- ✅ Mensagens claras de progresso e erros

### Instalação Manual

Se preferir executar manualmente os comandos:

```bash
# 1. Iniciar o ambiente Lando
lando start

# 2. Recriar o banco de dados
lando mysql -e "DROP DATABASE IF EXISTS drupal11; CREATE DATABASE drupal11;"

# 3. Instalar dependências
lando composer install

# 4. Instalar o Drupal a partir da configuração
lando drush site:install standard \
  --existing-config \
  --account-name=admin \
  --account-pass=admin \
  --site-name="Drupal Enterprise Starter" \
  -y
```

## Acesso ao Site

Após a instalação bem-sucedida:

- **URL:** https://drupal-enterprise-starter.lndo.site
- **Usuário admin:** `admin`
- **Senha admin:** `admin`

## Validação da Instalação

### Checklist de Validação

Após instalar, verifique se os seguintes elementos foram importados corretamente:

#### 1. Tipo de Conteúdo "Serviço" (US04)
```bash
lando drush config:get node.type.servico
```

Deve conter:
- Nome: "Serviço"
- Campos customizados: categoria_servico, descricao_curta, informacoes_adicionais, status_servico

#### 2. Papéis e Permissões (US05)
```bash
lando drush config:get user.role.editor
lando drush config:get user.role.gerente_conteudo
```

Deve listar os papéis:
- Editor
- Gerente de Conteúdo

#### 3. Views de Listagem (US07)
```bash
lando drush config:get views.view.listagem_servicos
```

Deve conter:
- Display "page_1"
- Filtros expostos (categoria, status)
- Configuração de paginação

#### 4. Tema Enterprise Theme (US08)
```bash
lando drush config:get system.theme
```

Deve mostrar:
- `default: enterprise_theme`

Ou verifique visualmente:
- Acesse https://drupal-enterprise-starter.lndo.site
- Layout responsivo com breakpoints mobile/tablet/desktop
- Menu de navegação principal funcional

#### 5. API REST (US09)
```bash
lando drush config:get rest.resource.entity.node
lando drush config:get views.view.api_servicos
```

Endpoints ativos:
- `/api/services` (listagem JSON via Views REST Export)

## Exportar Configuração Atualizada

Sempre que fizer alterações na estrutura do site via interface administrativa:

```bash
# Exportar toda a configuração
lando drush config:export -y

# Verificar mudanças
git status config/sync/

# Commitar as alterações
git add config/sync/
git commit -m "config: exportar alterações de [descrição]"
```

## Resolução de Problemas

### Erro: "Configuration validation failed"

**Causa:** Módulo ou dependência referenciada na configuração não está disponível.

**Solução:**
1. Verifique se todas as dependências estão instaladas:
   ```bash
   lando composer install
   ```
2. Verifique se há módulos customizados faltando em `web/modules/custom/`

### Erro: "The database is not empty"

**Causa:** Banco de dados já contém tabelas de instalação anterior.

**Solução:** O script `tools/install.sh` já trata isso automaticamente. Se estiver instalando manualmente:
```bash
lando mysql -e "DROP DATABASE drupal11; CREATE DATABASE drupal11;"
```

### Erro: "Cannot connect to database"

**Causa:** Container MariaDB não está pronto.

**Solução:** Aguarde alguns segundos e tente novamente. O script automatizado já implementa retry com timeout.

### Aviso: "UUID mismatch"

**Informação:** Este aviso é normal e esperado durante `--existing-config`. O Drupal resolve automaticamente substituindo os UUIDs da instalação pelos da configuração.

## Informações Técnicas

### Perfil de Instalação
- **Perfil usado:** `standard`
- **Motivo:** Base completa do Drupal com módulos essenciais, sobre a qual a configuração customizada é aplicada.

### Banco de Dados
- **Engine:** MariaDB 10.11
- **Charset:** utf8mb4
- **Collation:** utf8mb4_unicode_ci
- **Database:** `drupal11`
- **Credenciais:** drupal11 / drupal11

### Dependências da Configuração
A configuração exportada depende de:
- Módulos core habilitados pelo perfil standard
- Módulos customizados:
  - `enterprise_service` (gestão de serviços)
  - `service_access_control` (permissões)
- Tema customizado:
  - `enterprise_theme` (interface responsiva e acessível)

## Referências

- [Drupal Configuration Management](https://www.drupal.org/docs/configuration-management)
- [Drush site:install --existing-config](https://www.drush.org/latest/commands/site_install/)
- [Lando Drupal 11 Recipe](https://docs.lando.dev/drupal/)

## Histórico de Alterações

| Data | Descrição |
|------|-----------|
| 2026-10-14 | Implementação inicial US03: script install.sh e documentação |
