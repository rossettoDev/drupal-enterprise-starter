<?php

namespace Drupal\Tests\service_access_control\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Testa controle de autoria e publicação de serviços.
 *
 * @group service_access_control
 */
class ServiceAccessControlTest extends EntityKernelTestBase {

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
   * Editor de serviços.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editor;

  /**
   * Outro editor de serviços.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $otherEditor;

  /**
   * Gestor de serviços.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['node', 'filter', 'system']);

    // Cria o tipo de conteúdo 'service'.
    $service_type = NodeType::create([
      'type' => 'service',
      'name' => 'Serviço',
    ]);
    $service_type->save();

    // Cria os papéis.
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

    // Cria os usuários.
    $this->editor = User::create([
      'name' => 'editor1',
      'mail' => 'editor1@example.com',
      'status' => 1,
    ]);
    $this->editor->addRole('service_editor');
    $this->editor->save();

    $this->otherEditor = User::create([
      'name' => 'editor2',
      'mail' => 'editor2@example.com',
      'status' => 1,
    ]);
    $this->otherEditor->addRole('service_editor');
    $this->otherEditor->save();

    $this->manager = User::create([
      'name' => 'manager1',
      'mail' => 'manager1@example.com',
      'status' => 1,
    ]);
    $this->manager->addRole('service_manager');
    $this->manager->save();
  }

  /**
   * Testa que editor pode criar serviço como rascunho.
   */
  public function testEditorCanCreateDraft() {
    $this->container->get('current_user')->setAccount($this->editor);

    $node = Node::create([
      'type' => 'service',
      'title' => 'Teste Editor Cria',
      'uid' => $this->editor->id(),
      'status' => 1,
    ]);
    $node->save();

    // Deve ser forçado a rascunho.
    $this->assertFalse($node->isPublished(), 'Editor não pode criar serviço publicado.');
  }

  /**
   * Testa que editor pode editar seu próprio serviço.
   */
  public function testEditorCanEditOwn() {
    $node = Node::create([
      'type' => 'service',
      'title' => 'Serviço do Editor',
      'uid' => $this->editor->id(),
      'status' => 0,
    ]);
    $node->save();

    $access = $node->access('update', $this->editor, TRUE);
    $this->assertTrue($access->isAllowed(), 'Editor pode editar seu próprio serviço.');
  }

  /**
   * Testa que editor não pode editar serviço de outro editor.
   */
  public function testEditorCannotEditOthers() {
    $node = Node::create([
      'type' => 'service',
      'title' => 'Serviço de Outro Editor',
      'uid' => $this->otherEditor->id(),
      'status' => 0,
    ]);
    $node->save();

    $access = $node->access('update', $this->editor, TRUE);
    $this->assertFalse($access->isAllowed(), 'Editor não pode editar serviço de outro autor.');
  }

  /**
   * Testa que editor não pode publicar serviço.
   */
  public function testEditorCannotPublish() {
    $this->container->get('current_user')->setAccount($this->editor);

    // Cria rascunho.
    $node = Node::create([
      'type' => 'service',
      'title' => 'Teste Publicação',
      'uid' => $this->editor->id(),
      'status' => 0,
    ]);
    $node->save();

    // Tenta publicar.
    $node->setPublished();
    $node->save();

    // Recarrega do banco.
    $node = Node::load($node->id());

    $this->assertFalse($node->isPublished(), 'Editor não consegue publicar seu próprio serviço.');
  }

  /**
   * Testa que editor não pode despublicar serviço publicado.
   */
  public function testEditorCannotUnpublish() {
    // Gestor cria e publica um serviço.
    $this->container->get('current_user')->setAccount($this->manager);

    $node = Node::create([
      'type' => 'service',
      'title' => 'Serviço Publicado',
      'uid' => $this->editor->id(),
      'status' => 1,
    ]);
    $node->save();

    $this->assertTrue($node->isPublished(), 'Gestor publicou o serviço.');

    // Editor tenta despublicar.
    $this->container->get('current_user')->setAccount($this->editor);
    $node->setUnpublished();
    $node->save();

    // Recarrega do banco.
    $node = Node::load($node->id());

    $this->assertTrue($node->isPublished(), 'Editor não consegue despublicar serviço.');
  }

  /**
   * Testa que gestor pode editar qualquer serviço.
   */
  public function testManagerCanEditAny() {
    $node = Node::create([
      'type' => 'service',
      'title' => 'Serviço do Editor',
      'uid' => $this->editor->id(),
      'status' => 0,
    ]);
    $node->save();

    $access = $node->access('update', $this->manager, TRUE);
    $this->assertTrue($access->isAllowed(), 'Gestor pode editar serviço de qualquer autor.');
  }

  /**
   * Testa que gestor pode publicar serviço.
   */
  public function testManagerCanPublish() {
    $this->container->get('current_user')->setAccount($this->manager);

    $node = Node::create([
      'type' => 'service',
      'title' => 'Serviço para Publicar',
      'uid' => $this->manager->id(),
      'status' => 0,
    ]);
    $node->save();

    $this->assertFalse($node->isPublished(), 'Serviço criado como rascunho.');

    // Publica.
    $node->setPublished();
    $node->save();

    // Recarrega.
    $node = Node::load($node->id());

    $this->assertTrue($node->isPublished(), 'Gestor consegue publicar serviço.');
  }

  /**
   * Testa que gestor pode despublicar serviço.
   */
  public function testManagerCanUnpublish() {
    $this->container->get('current_user')->setAccount($this->manager);

    $node = Node::create([
      'type' => 'service',
      'title' => 'Serviço Publicado para Despublicar',
      'uid' => $this->manager->id(),
      'status' => 1,
    ]);
    $node->save();

    $this->assertTrue($node->isPublished(), 'Serviço criado publicado.');

    // Despublica.
    $node->setUnpublished();
    $node->save();

    // Recarrega.
    $node = Node::load($node->id());

    $this->assertFalse($node->isPublished(), 'Gestor consegue despublicar serviço.');
  }

  /**
   * Testa que usuário anônimo só vê serviços publicados.
   */
  public function testAnonymousSeesOnlyPublished() {
    // Cria um serviço publicado.
    $published = Node::create([
      'type' => 'service',
      'title' => 'Serviço Público',
      'uid' => $this->manager->id(),
      'status' => 1,
    ]);
    $published->save();

    // Cria um rascunho.
    $draft = Node::create([
      'type' => 'service',
      'title' => 'Serviço Rascunho',
      'uid' => $this->editor->id(),
      'status' => 0,
    ]);
    $draft->save();

    // Usuário anônimo.
    $anonymous = User::getAnonymousUser();

    // Deve ver o publicado.
    $access_published = $published->access('view', $anonymous, TRUE);
    $this->assertTrue($access_published->isAllowed(), 'Anônimo pode ver serviço publicado.');

    // Não deve ver o rascunho.
    $access_draft = $draft->access('view', $anonymous, TRUE);
    $this->assertFalse($access_draft->isAllowed(), 'Anônimo não pode ver rascunho.');
  }

  /**
   * Testa manipulação de requisição não publica serviço.
   */
  public function testManipulatedRequestCannotPublish() {
    $this->container->get('current_user')->setAccount($this->editor);

    // Cria rascunho.
    $node = Node::create([
      'type' => 'service',
      'title' => 'Teste Manipulação',
      'uid' => $this->editor->id(),
      'status' => 0,
    ]);
    $node->save();

    // Simula manipulação: altera diretamente o campo status.
    $node->get('status')->setValue(1);
    $node->save();

    // Recarrega do banco.
    $node = Node::load($node->id());

    // Deve continuar como rascunho devido ao hook_node_presave.
    $this->assertFalse($node->isPublished(), 'Manipulação de requisição não consegue publicar serviço.');
  }

}
