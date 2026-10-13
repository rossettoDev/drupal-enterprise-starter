<?php

namespace Drupal\enterprise_service\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\enterprise_service\DTO\ErrorResponseDTO;
use Drupal\enterprise_service\DTO\ServiceListResponseDTO;
use Drupal\enterprise_service\Service\ServiceQueryService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller para listagem de serviços via API.
 */
class ServiceListController extends ControllerBase {

  /**
   * Serviço de consulta.
   *
   * @var \Drupal\enterprise_service\Service\ServiceQueryService
   */
  protected ServiceQueryService $queryService;

  /**
   * Construtor.
   *
   * @param \Drupal\enterprise_service\Service\ServiceQueryService $query_service
   *   Serviço de consulta.
   */
  public function __construct(ServiceQueryService $query_service) {
    $this->queryService = $query_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('enterprise_service.query_service')
    );
  }

  /**
   * Endpoint GET /api/v1/services.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Resposta JSON.
   */
  public function list(Request $request): JsonResponse {
    // Valida e extrai parâmetros.
    $validation = $this->validateParameters($request);
    if ($validation['error']) {
      $error = new ErrorResponseDTO($validation['code'], $validation['message']);
      return new JsonResponse($error->toArray(), 400);
    }

    $page = $validation['page'];
    $perPage = $validation['per_page'];
    $filters = $validation['filters'];

    try {
      // Consulta serviços.
      $result = $this->queryService->queryServices($filters, $page, $perPage);

      // Monta resposta.
      $response_dto = new ServiceListResponseDTO(
        $result['items'],
        $result['total'],
        $page,
        $perPage
      );

      // Retorna com cache público (histórias futuras vão configurar).
      $response = new JsonResponse($response_dto->toArray());
      $response->setPublic();
      $response->setMaxAge(0);

      return $response;
    }
    catch (\Exception $e) {
      $this->getLogger('enterprise_service')->error(
        'Erro ao consultar serviços: @message',
        ['@message' => $e->getMessage()]
      );

      $error = new ErrorResponseDTO(
        'internal_error',
        'Erro interno ao processar a requisição'
      );
      return new JsonResponse($error->toArray(), 500);
    }
  }

  /**
   * Valida parâmetros da requisição.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return array
   *   Array com 'error' (bool), 'code', 'message', 'page', 'per_page', 'filters'.
   */
  protected function validateParameters(Request $request): array {
    $page = $request->query->get('page', '1');
    $perPage = $request->query->get('per_page', '10');
    $title = $request->query->get('title');
    $category = $request->query->get('category');

    // Valida page.
    if (!ctype_digit($page) || (int) $page < 1) {
      return [
        'error' => TRUE,
        'code' => 'invalid_page',
        'message' => 'O parâmetro "page" deve ser um número inteiro maior ou igual a 1',
      ];
    }
    $page = (int) $page;

    // Valida per_page.
    if (!ctype_digit($perPage) || (int) $perPage < 1 || (int) $perPage > 50) {
      return [
        'error' => TRUE,
        'code' => 'invalid_per_page',
        'message' => 'O parâmetro "per_page" deve ser um número inteiro entre 1 e 50',
      ];
    }
    $perPage = (int) $perPage;

    // Monta filtros.
    $filters = [];
    if ($title !== NULL && $title !== '') {
      $filters['title'] = $title;
    }
    if ($category !== NULL && $category !== '') {
      $filters['category'] = $category;
    }

    return [
      'error' => FALSE,
      'page' => $page,
      'per_page' => $perPage,
      'filters' => $filters,
    ];
  }

}
