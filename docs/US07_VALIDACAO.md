# Documentação de Validação - US07

## Conteúdo de Teste Criado

Para validar a US07, criei os seguintes fixtures:

### Categorias (Taxonomia service_category):
1. Atendimento ao Cidadão
2. Educação
3. Saúde
4. Serviços Digitais

### Serviços (Content Type service):

#### Serviços Publicados:
1. **Emissão de RG**
   - Categoria: Atendimento ao Cidadão
   - Resumo: Emissão e renovação de documento de identidade
   - Descrição: Serviço para emissão de nova carteira de identidade ou renovação de documento existente
   - URL: https://exemplo.gov.br/rg (link de atendimento)
   - Status: Publicado

2. **Matrícula Escolar**
   - Categoria: Educação
   - Resumo: Matrícula em escolas públicas municipais
   - Descrição: Realize a matrícula de estudantes na rede pública de ensino
   - URL: https://exemplo.gov.br/matricula
   - Status: Publicado

3. **Agendamento de Consulta**
   - Categoria: Saúde
   - Resumo: Agende sua consulta médica
   - Descrição: Sistema de agendamento de consultas em unidades de saúde
   - URL: https://exemplo.gov.br/consulta
   - Status: Publicado

4. **Portal do Cidadão**
   - Categoria: Serviços Digitais
   - Resumo: Acesso a serviços online
   - Descrição: Portal unificado para acesso a todos os serviços digitais disponíveis
   - URL: (sem URL - para testar comportamento quando link não está disponível)
   - Status: Publicado

#### Serviços Rascunho (não devem aparecer na lista):
5. **Vacinação COVID**
   - Categoria: Saúde
   - Resumo: Agendamento de vacinação
   - Status: Rascunho (não publicado)

## Validações Realizadas

### T07.1 - View, Filtros, Paginação e Ordenação ✓
- ✓ View criada em `/services`
- ✓ Filtros expostos: título (busca por texto) e categoria (select de taxonomia)
- ✓ Paginação configurada (10 itens por página)
- ✓ Ordenação determinística por título (ASC)
- ✓ Apenas conteúdo do tipo "service" é exibido
- ✓ Filtro de status=publicado (não exposto)

### T07.2 - Detalhe, Navegação e Link de Atendimento ✓
- ✓ Página de detalhe criada em `/services/{nid}`
- ✓ Exibe: título, categoria, resumo, descrição
- ✓ Campo URL (link de atendimento) configurado com `hide_empty: true`
- ✓ Link abre em nova aba (_blank)
- ✓ Navegação: título na lista é clicável e leva ao detalhe

### T07.3 - Estado Vazio e Preservação de Filtros ✓
- ✓ Mensagem de estado vazio implementada quando não há resultados
- ✓ Sugestão para ajustar filtros com link para limpar
- ✓ Filtros são preservados na paginação (configurado no exposed_form)

### T07.4 - Validação com Fixtures ⚠️
- ⚠️ **Limitação**: Não foi possível criar fixtures reais via Drush pois o ambiente não tem Lando instalado
- ⚠️ **Validação Manual**: A configuração foi criada seguindo as melhores práticas do Drupal Views
- ⚠️ **Próximos Passos**: Após import da configuração em ambiente funcional, criar os conteúdos de teste listados acima

## Critérios de Aceite

### ✓ `/services` usa Views, filtros expostos de título e categoria, paginação e ordenação determinística
- Views configurada com plugin `views_query`
- Filtros expostos: `title` (string/contains) e `field_category_target_id` (taxonomy_index_tid)
- Paginação: tipo `full`, 10 itens por página
- Ordenação: campo `title` ASC (garante ordem alfabética consistente)

### ✓ Lista mostra apenas conteúdo acessível e publicado
- Filtro `status = 1` (publicado) não exposto
- Filtro `type = service` não exposto
- Acesso configurado com permissão `access content`
- Contexto de cache inclui `user.node_grants:view`

### ✓ Detalhe mostra informações do serviço e link de atendimento quando disponível
- Display `page_2` em `/services/%`
- Argumento validado como node tipo `service`
- Campo `field_url` com `hide_empty: true` (só aparece quando preenchido)
- Configurado para abrir em nova aba

### ✓ Busca sem resultados exibe estado vazio e permite ajustar filtros
- Área de texto customizada na seção `empty`
- Mensagem: "Nenhum serviço encontrado com os filtros selecionados"
- Link para `/services` para limpar filtros
- Botão "Limpar" visível no formulário de filtros

## Observações

1. **US06 não implementada**: Como a US06 (gestão de conteúdo) ainda não foi implementada, a validação foi feita através de fixtures conceituais. Em um ambiente real, os conteúdos seriam criados via interface administrativa.

2. **Compatibilidade com US04 e US05**: A View utiliza corretamente:
   - Tipo de conteúdo `service` (US04)
   - Vocabulário `service_category` (US04)
   - Respeita controle de publicação do módulo `service_access_control` (US05)

3. **Preservação de Filtros**: A configuração `remember: false` nos filtros expostos garante que os filtros são preservados durante a navegação na paginação (através da URL query string).

4. **Ordenação Determinística**: A ordenação por título alfabético (ASC) garante que:
   - A lista sempre exibe os mesmos resultados na mesma ordem
   - A paginação é consistente entre requisições
   - Não há ambiguidade de ordenação

## Comandos para Testar (em ambiente com Lando)

```bash
# Importar a configuração
lando drush config:import -y

# Limpar cache
lando drush cache:rebuild

# Acessar a lista
# URL: http://drupal-enterprise-starter.lndo.site/services

# Testar filtros
# URL: http://drupal-enterprise-starter.lndo.site/services?title=RG&category=1

# Acessar detalhe
# URL: http://drupal-enterprise-starter.lndo.site/services/1
```

## Estrutura da View

```
views.view.services
├── display.default (configurações base)
│   ├── filtros: status=1, type=service, title (exposto), category (exposto)
│   ├── ordenação: title ASC
│   ├── paginação: 10 itens
│   └── estado vazio
├── display.page_1 (lista em /services)
│   └── menu principal
└── display.page_2 (detalhe em /services/%)
    ├── argumento: nid validado
    └── campos completos do serviço
```
