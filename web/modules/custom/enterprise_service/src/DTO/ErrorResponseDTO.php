<?php

namespace Drupal\enterprise_service\DTO;

/**
 * DTO para resposta de erro da API.
 */
class ErrorResponseDTO {

  /**
   * Código do erro.
   *
   * @var string
   */
  public string $code;

  /**
   * Mensagem do erro.
   *
   * @var string
   */
  public string $message;

  /**
   * Construtor.
   *
   * @param string $code
   *   Código do erro.
   * @param string $message
   *   Mensagem do erro.
   */
  public function __construct(string $code, string $message) {
    $this->code = $code;
    $this->message = $message;
  }

  /**
   * Converte o DTO para array.
   *
   * @return array
   *   Array associativo com código e mensagem.
   */
  public function toArray(): array {
    return [
      'error' => [
        'code' => $this->code,
        'message' => $this->message,
      ],
    ];
  }

}
