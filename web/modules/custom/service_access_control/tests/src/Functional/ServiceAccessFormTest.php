<?php

namespace Drupal\Tests\service_access_control\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * Testa formulários e interface de serviços.
 *
 * @group service_access_control
 */
class ServiceAccessFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field',
    'text',
    'filter',
    'user',
    'system',
    'service_access_control',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Cria o tipo de conteúdo 'service'.
    $this->drupalCreateContentType([
      'type' => 'service',
      'name' => 'Serviço',
    ]);

    // Cria os papéis com as permissões corretas.
    $editor_role = Role::create([
      'id' => 'service_editor',
      'label' => 'Editor de Serviços',
    ]);
    $editor_role->grantPermission('access content');
    $editor_role->grantPermission('create service content');
    $editor_role->grantPermission('edit own service content');
    $editor_role->grantPermission('delete own service content');
    $editor_role->grantPermission('view own unpublished content');
    $editor_role->save();

    $manager_role = Role::create([
      'id' => 'service_manager',
      'label' => 'Gestor de Serviços',
    ]);
    $manager_role->grantPermission('access content');
    $manager_role->grantPermission('create service content');
    $manager_role->grantPermission('edit any service content');
    $manager_role->grantPermission('edit own service content');
    $manager_role->grantPermission('delete any service content');
    $manager_role->grantPermission('delete own service content');
    $manager_role->grantPermission('view own unpublished content');
    $manager_role->save();
  }

  /**
   * Testa que editor não vê campo de publicação no formulário.
   */
  public function testEditorDoesNotSeePublishField() {
    $editor = $this->drupalCreateUser();
    $editor->addRole('service_editor');
    $editor->save();

    $this->drupalLogin($editor);
    $this->drupalGet('node/add/service');

    // Verifica que o campo de status não está visível.
    $this->assertSession()->fieldNotExists('status[value]');
  }

  /**
   * Testa que gestor vê campo de publicação no formulário.
   */
  public function testManagerSeesPublishField() {
    $manager = $this->drupalCreateUser();
    $manager->addRole('service_manager');
    $manager->save();

    $this->drupalLogin($manager);
    $this->drupalGet('node/add/service');

    // Verifica que o campo de status está visível.
    $this->assertSession()->fieldExists('status[value]');
  }

  /**
   * Testa que editor cria serviço como rascunho pelo formulário.
   */
  public function testEditorCreatesUnpublishedViaForm() {
    $editor = $this->drupalCreateUser();
    $editor->addRole('service_editor');
    $editor->save();

    $this->drupalLogin($editor);

    // Cria um serviço.
    $edit = [
      'title[0][value]' => 'Serviço do Editor via Form',
    ];
    $this->drupalGet('node/add/service');
    $this->submitForm($edit, 'Save');

    // Carrega o nó criado.
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['title' => 'Serviço do Editor via Form']);
    $node = reset($nodes);

    $this->assertFalse($node->isPublished(), 'Serviço criado por editor está como rascunho.');
  }

  /**
   * Testa que gestor pode publicar pelo formulário.
   */
  public function testManagerCanPublishViaForm() {
    $manager = $this->drupalCreateUser();
    $manager->addRole('service_manager');
    $manager->save();

    $this->drupalLogin($manager);

    // Cria um serviço publicado.
    $edit = [
      'title[0][value]' => 'Serviço Publicado via Form',
      'status[value]' => 1,
    ];
    $this->drupalGet('node/add/service');
    $this->submitForm($edit, 'Save');

    // Carrega o nó criado.
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['title' => 'Serviço Publicado via Form']);
    $node = reset($nodes);

    $this->assertTrue($node->isPublished(), 'Gestor consegue publicar serviço pelo formulário.');
  }

  /**
   * Testa que editor não vê serviços de outros na listagem.
   */
  public function testEditorCannotAccessOthersService() {
    $editor1 = $this->drupalCreateUser();
    $editor1->addRole('service_editor');
    $editor1->save();

    $editor2 = $this->drupalCreateUser();
    $editor2->addRole('service_editor');
    $editor2->save();

    // Editor 1 cria um serviço.
    $node = $this->drupalCreateNode([
      'type' => 'service',
      'title' => 'Serviço de Editor 1',
      'uid' => $editor1->id(),
      'status' => 0,
    ]);

    // Editor 2 tenta acessar.
    $this->drupalLogin($editor2);
    $this->drupalGet('node/' . $node->id() . '/edit');

    // Deve ser negado.
    $this->assertSession()->statusCodeEquals(403);
  }

}
