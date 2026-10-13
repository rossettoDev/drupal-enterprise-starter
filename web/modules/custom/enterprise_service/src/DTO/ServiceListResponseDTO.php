<?php

namespace Drupal\enterprise_service\DTO;

/**
 * DTO para resposta da listagem de serviços.
 */
class ServiceListResponseDTO {

  /**
   * Lista de serviços.
   *
   * @var array
   */
  public array $data;

  /**
   * Metadados de paginação.
   *
   * @var array
   */
  public array $meta;

  /**
   * Construtor.
   *
   * @param array $data
   *   Lista de serviços.
   * @param int $total
   *   Total de resultados.
   * @param int $page
   *   Página atual.
   * @param int $perPage
   *   Itens por página.
   */
  public function __construct(array $data, int $total, int $page, int $perPage) {
    $this->data = $data;
    $this->meta = [
      'total' => $total,
      'page' => $page,
      'per_page' => $perPage,
      'total_pages' => $perPage > 0 ? (int) ceil($total / $perPage) : 0,
    ];
  }

  /**
   * Converte o DTO para array.
   *
   * @return array
   *   Array associativo com dados e metadados.
   */
  public function toArray(): array {
    return [
      'data' => $this->data,
      'meta' => $this->meta,
    ];
  }

}
