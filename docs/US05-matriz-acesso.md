# US05 - Matriz de Acesso por Papel

Documentação das permissões e controles de acesso para o tipo de conteúdo "Serviço".

## Legenda

- ✅ Permitido
- ❌ Negado
- 🔒 Controlado (depende de condições)

## Matriz de Acesso

| Operação | Visitante (anonymous) | Editor de Serviços | Gestor de Serviços | Administrador |
|----------|----------------------|-------------------|-------------------|---------------|
| **Visualizar serviço publicado** | ✅ | ✅ | ✅ | ✅ |
| **Visualizar rascunho próprio** | ❌ | ✅ | ✅ | ✅ |
| **Visualizar rascunho de outro** | ❌ | ❌ | ✅ | ✅ |
| **Criar novo serviço** | ❌ | ✅ (como rascunho) | ✅ | ✅ |
| **Editar próprio serviço** | ❌ | ✅ | ✅ | ✅ |
| **Editar serviço de outro** | ❌ | ❌ | ✅ | ✅ |
| **Publicar serviço** | ❌ | ❌ | ✅ | ✅ |
| **Despublicar serviço** | ❌ | ❌ | ✅ | ✅ |
| **Excluir próprio serviço** | ❌ | ✅ | ✅ | ✅ |
| **Excluir serviço de outro** | ❌ | ❌ | ✅ | ✅ |
| **Gerenciar revisões** | ❌ | ❌ | ✅ | ✅ |

## Controles Implementados

### 1. Permissões Nativas do Drupal

#### service_editor
```yaml
permissions:
  - 'access content'
  - 'access content overview'
  - 'create service content'
  - 'delete own service content'
  - 'edit own service content'
  - 'view own unpublished content'
  - 'view the administration theme'
```

#### service_manager
```yaml
permissions:
  - 'access content'
  - 'access content overview'
  - 'create service content'
  - 'delete any service content'
  - 'delete own service content'
  - 'delete service revisions'
  - 'edit any service content'
  - 'edit own service content'
  - 'revert service revisions'
  - 'view all revisions'
  - 'view own unpublished content'
  - 'view service revisions'
  - 'view the administration theme'
```

### 2. Controles Programáticos

#### hook_node_access()
- **Finalidade**: Controlar acesso de edição baseado em autoria
- **Comportamento**:
  - `service_editor` só pode editar (`update`) serviços onde `uid` = seu próprio ID
  - `service_manager` pode editar qualquer serviço
  - Outros papéis seguem as permissões nativas

#### hook_node_presave()
- **Finalidade**: **Validação no servidor** do status de publicação
- **Comportamento**:
  - Quando `service_editor` cria um serviço: força `status = 0` (rascunho)
  - Quando `service_editor` edita: se detectar mudança no campo `status`, reverte para o valor original
  - Aplica-se **mesmo para requisições manipuladas** (ex: POST direto, API, etc.)
  - `service_manager` e administradores não sofrem essa restrição

#### hook_form_alter()
- **Finalidade**: Melhorar UX ocultando campo de publicação
- **Comportamento**:
  - Oculta o campo `status` do formulário para `service_editor`
  - `service_manager` e administradores veem o campo normalmente
  - **Nota**: Este é apenas um controle de UI; a segurança real está no `hook_node_presave()`

## Fluxo de Trabalho

### Cenário 1: Editor cria novo serviço
1. Editor acessa `/node/add/service`
2. Preenche título e campos
3. **Não vê** a opção "Publicado" (campo oculto)
4. Salva o formulário
5. `hook_node_presave()` força `status = 0`
6. Serviço criado como **rascunho**

### Cenário 2: Editor tenta publicar (manipulação)
1. Editor cria serviço rascunho (nid = 123)
2. Editor inspeciona HTML e adiciona `<input name="status[value]" value="1">`
3. Submete o formulário com `status = 1`
4. `hook_node_presave()` detecta a tentativa e reverte para `status = 0`
5. Mensagem de aviso é exibida
6. Serviço continua como **rascunho**

### Cenário 3: Gestor publica serviço
1. Gestor acessa serviço rascunho (de qualquer autor)
2. Edita e marca "Publicado"
3. `hook_node_presave()` permite (gestor não tem restrição)
4. Serviço passa para **publicado**

### Cenário 4: Visitante acessa catálogo
1. Visitante acessa a listagem de serviços
2. Drupal filtra por `status = 1` (comportamento nativo)
3. Apenas serviços **publicados** aparecem

## Status de Publicação

| Status | Valor | Descrição | Visível para |
|--------|-------|-----------|--------------|
| Rascunho | `0` | Conteúdo não publicado | Autor e gestores |
| Publicado | `1` | Conteúdo visível publicamente | Todos |

## Segurança

### Validação em Camadas

1. **Camada de UI** (`hook_form_alter`):
   - Oculta opções não permitidas
   - Melhora experiência do usuário
   - **Não é** a linha de defesa principal

2. **Camada de Negócio** (`hook_node_presave`):
   - **Validação obrigatória no servidor**
   - Independe do formulário usado
   - Aplica-se a todas as rotas (UI, API, REST, JSON:API)
   - **Linha de defesa definitiva**

3. **Camada de Acesso** (`hook_node_access`):
   - Controla quem pode editar com base em autoria
   - Integrado com sistema de permissões nativas

### Requisitos Atendidos

- ✅ `service_editor` cria e edita seus próprios serviços
- ✅ `service_editor` não publica nem edita serviços de outros
- ✅ `service_manager` gerencia e publica serviços
- ✅ Visitantes acessam apenas serviços publicados
- ✅ **Restrição verificada no servidor** (requisição manipulada não funciona)
- ✅ Papéis sem permissões administrativas amplas (`is_admin: false`)

## Testes

Ver `web/modules/custom/service_access_control/tests/`:
- `Kernel/ServiceAccessControlTest.php` - 11 testes cobrindo lógica de negócio
- `Functional/ServiceAccessFormTest.php` - 5 testes cobrindo UI e formulários
