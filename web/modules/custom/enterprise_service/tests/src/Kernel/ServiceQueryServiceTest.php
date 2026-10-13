<?php

namespace Drupal\Tests\enterprise_service\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Testa o serviço de consulta de serviços.
 *
 * @group enterprise_service
 */
class ServiceQueryServiceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'filter',
    'taxonomy',
    'link',
    'enterprise_service',
  ];

  /**
   * Serviço de consulta.
   *
   * @var \Drupal\enterprise_service\Service\ServiceQueryService
   */
  protected $queryService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['filter', 'node', 'system']);

    // Cria tipo de conteúdo Service.
    NodeType::create([
      'type' => 'service',
      'name' => 'Serviço',
    ])->save();

    // Cria vocabulário service_category.
    Vocabulary::create([
      'vid' => 'service_category',
      'name' => 'Categoria de Serviço',
    ])->save();

    // Cria campos.
    $this->createField('field_summary', 'text');
    $this->createField('field_description', 'text_long');
    $this->createField('field_url', 'link');
    $this->createField('field_channel', 'text');
    $this->createFieldReference('field_category', 'taxonomy_term', 'service_category');

    $this->queryService = $this->container->get('enterprise_service.query_service');
  }

  /**
   * Testa consulta sem filtros.
   */
  public function testQueryWithoutFilters(): void {
    // Cria serviços publicados.
    $this->createService('Serviço A', TRUE);
    $this->createService('Serviço B', TRUE);
    $this->createService('Rascunho', FALSE);

    $result = $this->queryService->queryServices([], 1, 10);

    $this->assertCount(2, $result['items']);
    $this->assertEquals(2, $result['total']);
  }

  /**
   * Testa filtro por título.
   */
  public function testQueryWithTitleFilter(): void {
    $this->createService('Emissão de Certidão', TRUE);
    $this->createService('Consulta de Processo', TRUE);
    $this->createService('Certidão Negativa', TRUE);

    $result = $this->queryService->queryServices(['title' => 'Certidão'], 1, 10);

    $this->assertCount(2, $result['items']);
    $this->assertEquals(2, $result['total']);
  }

  /**
   * Testa filtro por categoria.
   */
  public function testQueryWithCategoryFilter(): void {
    $term = Term::create([
      'vid' => 'service_category',
      'name' => 'Documentos',
    ]);
    $term->save();

    $this->createService('Serviço com Categoria', TRUE, $term->id());
    $this->createService('Serviço sem Categoria', TRUE);

    $result = $this->queryService->queryServices(['category' => $term->uuid()], 1, 10);

    $this->assertCount(1, $result['items']);
    $this->assertEquals(1, $result['total']);
    $this->assertEquals($term->uuid(), $result['items'][0]['category']);
  }

  /**
   * Testa categoria inexistente.
   */
  public function testQueryWithNonExistentCategory(): void {
    $this->createService('Serviço A', TRUE);

    $fake_uuid = '12345678-1234-1234-1234-123456789012';
    $result = $this->queryService->queryServices(['category' => $fake_uuid], 1, 10);

    $this->assertCount(0, $result['items']);
    $this->assertEquals(0, $result['total']);
  }

  /**
   * Testa paginação.
   */
  public function testPagination(): void {
    for ($i = 1; $i <= 15; $i++) {
      $this->createService("Serviço $i", TRUE);
    }

    // Primeira página.
    $result = $this->queryService->queryServices([], 1, 10);
    $this->assertCount(10, $result['items']);
    $this->assertEquals(15, $result['total']);

    // Segunda página.
    $result = $this->queryService->queryServices([], 2, 10);
    $this->assertCount(5, $result['items']);
    $this->assertEquals(15, $result['total']);

    // Página vazia.
    $result = $this->queryService->queryServices([], 3, 10);
    $this->assertCount(0, $result['items']);
    $this->assertEquals(15, $result['total']);
  }

  /**
   * Testa ordenação determinística.
   */
  public function testDeterministicOrdering(): void {
    $node1 = $this->createService('Serviço 1', TRUE);
    $node2 = $this->createService('Serviço 2', TRUE);

    $result1 = $this->queryService->queryServices([], 1, 10);
    $result2 = $this->queryService->queryServices([], 1, 10);

    $this->assertEquals($result1['items'][0]['uuid'], $result2['items'][0]['uuid']);
    $this->assertEquals($result1['items'][1]['uuid'], $result2['items'][1]['uuid']);
  }

  /**
   * Cria um serviço para teste.
   */
  protected function createService(string $title, bool $published, ?int $category = NULL): Node {
    $values = [
      'type' => 'service',
      'title' => $title,
      'status' => $published ? 1 : 0,
    ];

    if ($category) {
      $values['field_category'] = $category;
    }

    $node = Node::create($values);
    $node->save();
    return $node;
  }

  /**
   * Cria um campo de texto.
   */
  protected function createField(string $field_name, string $type): void {
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => $type,
    ])->save();

    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => 'service',
    ])->save();
  }

  /**
   * Cria um campo de referência.
   */
  protected function createFieldReference(string $field_name, string $target_type, string $target_bundle): void {
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => $target_type,
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => 'service',
      'settings' => [
        'handler' => 'default',
        'handler_settings' => [
          'target_bundles' => [$target_bundle => $target_bundle],
        ],
      ],
    ])->save();
  }

}
