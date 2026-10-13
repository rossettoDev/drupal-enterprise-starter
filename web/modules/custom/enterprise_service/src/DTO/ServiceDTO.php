<?php

namespace Drupal\enterprise_service\DTO;

/**
 * DTO para representação de um serviço na API.
 */
class ServiceDTO {

  /**
   * UUID do serviço.
   *
   * @var string
   */
  public string $uuid;

  /**
   * Título do serviço.
   *
   * @var string
   */
  public string $title;

  /**
   * Resumo do serviço.
   *
   * @var string|null
   */
  public ?string $summary;

  /**
   * Descrição completa do serviço.
   *
   * @var string|null
   */
  public ?string $description;

  /**
   * URL do serviço.
   *
   * @var string|null
   */
  public ?string $url;

  /**
   * Canal de atendimento.
   *
   * @var string|null
   */
  public ?string $channel;

  /**
   * Categoria do serviço (UUID).
   *
   * @var string|null
   */
  public ?string $category;

  /**
   * Converte o DTO para array.
   *
   * @return array
   *   Array associativo com os dados do serviço.
   */
  public function toArray(): array {
    return [
      'uuid' => $this->uuid,
      'title' => $this->title,
      'summary' => $this->summary,
      'description' => $this->description,
      'url' => $this->url,
      'channel' => $this->channel,
      'category' => $this->category,
    ];
  }

}
