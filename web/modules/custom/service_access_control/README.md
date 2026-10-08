# Service Access Control

Módulo customizado que implementa controle de autoria e publicação para o tipo de conteúdo "Serviço".

## Funcionalidades

### Papéis

- **service_editor (Editor de Serviços)**
  - Pode criar novos serviços
  - Pode editar apenas seus próprios serviços
  - **Não pode** publicar ou despublicar serviços
  - **Não pode** editar serviços de outros autores
  - Vê apenas seus próprios rascunhos

- **service_manager (Gestor de Serviços)**
  - Pode criar novos serviços
  - Pode editar qualquer serviço (próprio ou de outros)
  - **Pode** publicar e despublicar serviços
  - Pode excluir qualquer serviço
  - Pode gerenciar revisões

### Controles Implementados

#### 1. hook_node_access()
Controla o acesso de edição aos nós do tipo 'service':
- Editores só podem editar seus próprios serviços
- Gestores podem editar qualquer serviço

#### 2. hook_node_presave()
**Garante no servidor** que o status de publicação não pode ser alterado por editores:
- Novos serviços criados por editores são forçados a rascunho
- Se um editor tentar alterar o status (publicar ou despublicar), a mudança é revertida
- Aplica-se mesmo para requisições manipuladas (ex: via API ou formulário modificado)

#### 3. hook_form_alter()
Oculta o campo de status de publicação do formulário para editores:
- Melhora a experiência do usuário
- Complementa (mas não substitui) a validação no servidor

## Segurança

- **Validação no servidor**: O controle de publicação é feito no `hook_node_presave()`, garantindo que mesmo requisições manipuladas não conseguem burlar a restrição
- **Separação de responsabilidades**: Editores e gestores têm permissões claramente separadas
- **Sem permissões administrativas**: Nenhum dos papéis recebe `administer nodes` ou outras permissões administrativas amplas

## Testes

Os testes cobrem:
- ✅ Editor cria serviço como rascunho
- ✅ Editor edita seu próprio serviço
- ✅ Editor não edita serviço de outro autor
- ✅ Editor não consegue publicar
- ✅ Editor não consegue despublicar
- ✅ Gestor edita qualquer serviço
- ✅ Gestor publica e despublica
- ✅ Visitantes veem apenas serviços publicados
- ✅ Requisição manipulada não consegue alterar status

Execute os testes com:
```bash
vendor/bin/phpunit web/modules/custom/service_access_control/tests
```

## Instalação

O módulo é habilitado automaticamente via configuração em `config/sync/core.extension.yml`.

Os papéis são importados via:
- `config/sync/user.role.service_editor.yml`
- `config/sync/user.role.service_manager.yml`
