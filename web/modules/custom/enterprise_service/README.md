# Enterprise Service API

Módulo customizado que implementa API REST para consulta de serviços publicados no catálogo.

## Funcionalidades

### Endpoints

#### `GET /api/v1/services`

Lista serviços publicados com suporte a filtros e paginação.

**Parâmetros de Query:**

- `page` (opcional, padrão: 1): Número da página. Deve ser >= 1.
- `per_page` (opcional, padrão: 10): Itens por página. Deve ser entre 1 e 50.
- `title` (opcional): Filtro parcial por título (LIKE).
- `category` (opcional): UUID da categoria de serviço.

**Resposta de Sucesso (200):**

```json
{
  "data": [
    {
      "uuid": "abc-123",
      "title": "Emissão de Certidão",
      "summary": "Resumo do serviço",
      "description": "Descrição completa",
      "url": "https://exemplo.com",
      "channel": "Online",
      "category": "def-456"
    }
  ],
  "meta": {
    "total": 25,
    "page": 1,
    "per_page": 10,
    "total_pages": 3
  }
}
```

**Resposta de Erro (400):**

```json
{
  "error": {
    "code": "invalid_page",
    "message": "O parâmetro \"page\" deve ser um número inteiro maior ou igual a 1"
  }
}
```

**Códigos de Erro:**

- `invalid_page`: Parâmetro `page` inválido.
- `invalid_per_page`: Parâmetro `per_page` fora do intervalo permitido.

### Regras de Negócio

1. **Paginação**: `page` começa em 1; `per_page` tem padrão 10 e máximo 50.
2. **Filtros**: Aceita `title` (parcial) e `category` (UUID).
3. **Acesso**: Retorna apenas serviços publicados; respeita permissões de visualização dos campos.
4. **Ordenação**: Resultados ordenados por data de criação (DESC) e ID (DESC) para garantir determinismo.
5. **Total**: O total de itens (`meta.total`) corresponde aos mesmos critérios de acesso e filtros aplicados aos itens retornados.
6. **Categoria inexistente**: UUID de categoria válido mas não existente retorna lista vazia (não erro).

## Estrutura do Módulo

### Services

- **`enterprise_service.query_service`**: Serviço de consulta, paginação e serialização de serviços.

### DTOs

- **`ServiceDTO`**: Representação de um serviço individual.
- **`ServiceListResponseDTO`**: Resposta da listagem com dados e metadados.
- **`ErrorResponseDTO`**: Resposta padronizada de erro.

### Logger Channel

- **`enterprise_service`**: Canal de log para operações do módulo.

## Testes

### Kernel Tests

- Consulta sem filtros
- Filtro por título
- Filtro por categoria
- Categoria inexistente
- Paginação
- Ordenação determinística

### Functional Tests

- Endpoint sem parâmetros
- Paginação completa
- Filtros combinados
- Validação de parâmetros inválidos
- Páginas vazias
- Ausência de rascunhos
- Estrutura de erros

Execute os testes com:

```bash
vendor/bin/phpunit web/modules/custom/enterprise_service/tests
```

Ou via Lando:

```bash
lando phpunit web/modules/custom/enterprise_service/tests
```

## Instalação

O módulo é habilitado via configuração em `config/sync/core.extension.yml`.

## Dependências

- US04: Tipo de conteúdo Service com campos
- US05: Controle de autoria e publicação

## Compatibilidade

- Drupal 11
- Estrutura reutilizável para histórias futuras (US10, US11, US12)
