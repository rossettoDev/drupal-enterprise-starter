<?php

namespace Drupal\enterprise_service\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\enterprise_service\DTO\ServiceDTO;
use Psr\Log\LoggerInterface;

/**
 * Serviço para consulta e serialização de serviços.
 */
class ServiceQueryService {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Construtor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger channel.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Current user.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerInterface $logger,
    AccountProxyInterface $current_user
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->currentUser = $current_user;
  }

  /**
   * Consulta serviços com filtros e paginação.
   *
   * @param array $filters
   *   Filtros a aplicar (title, category).
   * @param int $page
   *   Número da página (começa em 1).
   * @param int $perPage
   *   Itens por página.
   *
   * @return array
   *   Array com 'items' (array de ServiceDTO) e 'total' (int).
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function queryServices(array $filters, int $page, int $perPage): array {
    $storage = $this->entityTypeManager->getStorage('node');

    // Query base: tipo service, publicado, ordenação determinística.
    $query = $storage->getQuery()
      ->condition('type', 'service')
      ->condition('status', 1)
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->sort('nid', 'DESC');

    // Aplica filtros.
    if (!empty($filters['title'])) {
      $query->condition('title', '%' . $filters['title'] . '%', 'LIKE');
    }

    if (!empty($filters['category'])) {
      // Valida se é um UUID válido.
      if ($this->isValidUuid($filters['category'])) {
        // Busca termo pela UUID.
        $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
        $terms = $term_storage->loadByProperties([
          'uuid' => $filters['category'],
          'vid' => 'service_category',
        ]);

        if (!empty($terms)) {
          $term = reset($terms);
          $query->condition('field_category', $term->id());
        }
        else {
          // Categoria não existe: retorna vazio.
          return ['items' => [], 'total' => 0];
        }
      }
      else {
        // UUID inválido: retorna vazio.
        return ['items' => [], 'total' => 0];
      }
    }

    // Conta total respeitando filtros e acesso.
    $count_query = clone $query;
    $total = (int) $count_query->count()->execute();

    // Aplica paginação.
    $offset = ($page - 1) * $perPage;
    $query->range($offset, $perPage);

    // Executa query.
    $nids = $query->execute();

    // Carrega e serializa nós.
    $items = [];
    if (!empty($nids)) {
      $nodes = $storage->loadMultiple($nids);
      foreach ($nodes as $node) {
        // Verifica acesso explícito ao nó.
        if ($node->access('view', $this->currentUser)) {
          $items[] = $this->serializeNode($node);
        }
      }
    }

    return [
      'items' => $items,
      'total' => $total,
    ];
  }

  /**
   * Serializa um nó em ServiceDTO.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Nó a serializar.
   *
   * @return array
   *   Array associativo com dados do serviço.
   */
  protected function serializeNode($node): array {
    $dto = new ServiceDTO();
    $dto->uuid = $node->uuid();
    $dto->title = $node->getTitle();

    // Campos opcionais com verificação de acesso.
    $dto->summary = $this->getFieldValue($node, 'field_summary');
    $dto->description = $this->getFieldValue($node, 'field_description');
    $dto->url = $this->getFieldValue($node, 'field_url');
    $dto->channel = $this->getFieldValue($node, 'field_channel');

    // Categoria: retorna UUID se existir.
    if ($node->hasField('field_category') && !$node->get('field_category')->isEmpty()) {
      $category = $node->get('field_category')->entity;
      if ($category && $category->access('view', $this->currentUser)) {
        $dto->category = $category->uuid();
      }
      else {
        $dto->category = NULL;
      }
    }
    else {
      $dto->category = NULL;
    }

    return $dto->toArray();
  }

  /**
   * Obtém valor de um campo respeitando acesso.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Nó.
   * @param string $field_name
   *   Nome do campo.
   *
   * @return string|null
   *   Valor do campo ou NULL.
   */
  protected function getFieldValue($node, string $field_name): ?string {
    if ($node->hasField($field_name) && !$node->get($field_name)->isEmpty()) {
      $field = $node->get($field_name);
      if ($field->access('view', $this->currentUser)) {
        return $field->value;
      }
    }
    return NULL;
  }

  /**
   * Valida se uma string é um UUID válido.
   *
   * @param string $uuid
   *   String a validar.
   *
   * @return bool
   *   TRUE se for UUID válido.
   */
  protected function isValidUuid(string $uuid): bool {
    $pattern = '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i';
    return (bool) preg_match($pattern, $uuid);
  }

}
