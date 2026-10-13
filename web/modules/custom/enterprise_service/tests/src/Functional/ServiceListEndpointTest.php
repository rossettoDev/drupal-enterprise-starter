<?php

namespace Drupal\Tests\enterprise_service\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Testa o endpoint GET /api/v1/services.
 *
 * @group enterprise_service
 */
class ServiceListEndpointTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'taxonomy',
    'field',
    'text',
    'link',
    'filter',
    'enterprise_service',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Instala configurações do tipo service.
    $this->container->get('module_handler')->loadInclude('node', 'install');
  }

  /**
   * Testa endpoint sem parâmetros.
   */
  public function testEndpointWithoutParameters(): void {
    $node = $this->createService('Serviço Teste', TRUE);

    $response = $this->drupalGet('/api/v1/services', ['query' => []]);
    $this->assertSession()->statusCodeEquals(200);

    $data = json_decode($response, TRUE);
    $this->assertArrayHasKey('data', $data);
    $this->assertArrayHasKey('meta', $data);
    $this->assertArrayHasKey('total', $data['meta']);
    $this->assertArrayHasKey('page', $data['meta']);
    $this->assertArrayHasKey('per_page', $data['meta']);
    $this->assertArrayHasKey('total_pages', $data['meta']);

    $this->assertEquals(1, $data['meta']['page']);
    $this->assertEquals(10, $data['meta']['per_page']);
  }

  /**
   * Testa paginação.
   */
  public function testPagination(): void {
    for ($i = 1; $i <= 25; $i++) {
      $this->createService("Serviço $i", TRUE);
    }

    // Página 1 com 10 itens.
    $response = $this->drupalGet('/api/v1/services', ['query' => ['page' => 1, 'per_page' => 10]]);
    $data = json_decode($response, TRUE);
    $this->assertCount(10, $data['data']);
    $this->assertEquals(25, $data['meta']['total']);
    $this->assertEquals(3, $data['meta']['total_pages']);

    // Página 2 com 10 itens.
    $response = $this->drupalGet('/api/v1/services', ['query' => ['page' => 2, 'per_page' => 10]]);
    $data = json_decode($response, TRUE);
    $this->assertCount(10, $data['data']);

    // Página 3 com 5 itens.
    $response = $this->drupalGet('/api/v1/services', ['query' => ['page' => 3, 'per_page' => 10]]);
    $data = json_decode($response, TRUE);
    $this->assertCount(5, $data['data']);
  }

  /**
   * Testa filtro por título.
   */
  public function testTitleFilter(): void {
    $this->createService('Emissão de Certidão', TRUE);
    $this->createService('Consulta de Processo', TRUE);
    $this->createService('Certidão Negativa', TRUE);

    $response = $this->drupalGet('/api/v1/services', ['query' => ['title' => 'Certidão']]);
    $data = json_decode($response, TRUE);

    $this->assertCount(2, $data['data']);
    $this->assertEquals(2, $data['meta']['total']);
  }

  /**
   * Testa filtro por categoria.
   */
  public function testCategoryFilter(): void {
    $term = Term::create([
      'vid' => 'service_category',
      'name' => 'Documentos',
    ]);
    $term->save();

    $this->createService('Serviço com Categoria', TRUE, $term->id());
    $this->createService('Serviço sem Categoria', TRUE);

    $response = $this->drupalGet('/api/v1/services', ['query' => ['category' => $term->uuid()]]);
    $data = json_decode($response, TRUE);

    $this->assertCount(1, $data['data']);
    $this->assertEquals($term->uuid(), $data['data'][0]['category']);
  }

  /**
   * Testa filtros combinados.
   */
  public function testCombinedFilters(): void {
    $term = Term::create([
      'vid' => 'service_category',
      'name' => 'Certidões',
    ]);
    $term->save();

    $this->createService('Certidão de Registro', TRUE, $term->id());
    $this->createService('Certidão Negativa', TRUE, $term->id());
    $this->createService('Consulta de Certidão', TRUE);

    $response = $this->drupalGet('/api/v1/services', [
      'query' => [
        'title' => 'Certidão',
        'category' => $term->uuid(),
      ],
    ]);
    $data = json_decode($response, TRUE);

    $this->assertCount(2, $data['data']);
  }

  /**
   * Testa parâmetro page inválido.
   */
  public function testInvalidPageParameter(): void {
    $this->drupalGet('/api/v1/services', ['query' => ['page' => 0]]);
    $this->assertSession()->statusCodeEquals(400);

    $this->drupalGet('/api/v1/services', ['query' => ['page' => -1]]);
    $this->assertSession()->statusCodeEquals(400);

    $this->drupalGet('/api/v1/services', ['query' => ['page' => 'abc']]);
    $this->assertSession()->statusCodeEquals(400);
  }

  /**
   * Testa parâmetro per_page inválido.
   */
  public function testInvalidPerPageParameter(): void {
    $this->drupalGet('/api/v1/services', ['query' => ['per_page' => 0]]);
    $this->assertSession()->statusCodeEquals(400);

    $this->drupalGet('/api/v1/services', ['query' => ['per_page' => 51]]);
    $this->assertSession()->statusCodeEquals(400);

    $this->drupalGet('/api/v1/services', ['query' => ['per_page' => 'xyz']]);
    $this->assertSession()->statusCodeEquals(400);
  }

  /**
   * Testa categoria inexistente.
   */
  public function testNonExistentCategory(): void {
    $this->createService('Serviço A', TRUE);

    $fake_uuid = '12345678-1234-1234-1234-123456789012';
    $response = $this->drupalGet('/api/v1/services', ['query' => ['category' => $fake_uuid]]);
    $data = json_decode($response, TRUE);

    $this->assertSession()->statusCodeEquals(200);
    $this->assertCount(0, $data['data']);
    $this->assertEquals(0, $data['meta']['total']);
  }

  /**
   * Testa que rascunhos não aparecem.
   */
  public function testDraftsNotIncluded(): void {
    $this->createService('Serviço Publicado', TRUE);
    $this->createService('Serviço Rascunho', FALSE);

    $response = $this->drupalGet('/api/v1/services');
    $data = json_decode($response, TRUE);

    $this->assertCount(1, $data['data']);
    $this->assertEquals('Serviço Publicado', $data['data'][0]['title']);
  }

  /**
   * Testa página vazia.
   */
  public function testEmptyPage(): void {
    $this->createService('Único Serviço', TRUE);

    $response = $this->drupalGet('/api/v1/services', ['query' => ['page' => 2]]);
    $data = json_decode($response, TRUE);

    $this->assertSession()->statusCodeEquals(200);
    $this->assertCount(0, $data['data']);
    $this->assertEquals(1, $data['meta']['total']);
  }

  /**
   * Testa estrutura de erro.
   */
  public function testErrorStructure(): void {
    $response = $this->drupalGet('/api/v1/services', ['query' => ['page' => 'invalid']]);
    $data = json_decode($response, TRUE);

    $this->assertSession()->statusCodeEquals(400);
    $this->assertArrayHasKey('error', $data);
    $this->assertArrayHasKey('code', $data['error']);
    $this->assertArrayHasKey('message', $data['error']);
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

}
