<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\jaraba_andalucia_ei\Entity\ExpedienteDocumento;
use Drupal\user\Entity\User;

/**
 * Kernel tests for ExpedienteDocumento CRUD operations.
 *
 * Tests basic CRUD, allowed values for categoria, and
 * estado_revision transitions.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Entity\ExpedienteDocumento
 * @group jaraba_andalucia_ei
 */
class ExpedienteDocumentoCrudTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'datetime',
    'file',
    'jaraba_andalucia_ei',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');

    // Skip if dependencies unavailable.
    $definitions = \Drupal::entityTypeManager()->getDefinitions();
    if (!isset($definitions['expediente_documento'])) {
      $this->markTestSkipped('ExpedienteDocumento entity type not available (missing dependencies).');
    }

    try {
      if (isset($definitions['group'])) {
        $this->installEntitySchema('group');
      }
      if (isset($definitions['programa_participante_ei'])) {
        $this->installEntitySchema('programa_participante_ei');
      }
      $this->installEntitySchema('expediente_documento');
    }
    catch (\Exception $e) {
      $this->markTestSkipped('Could not install entity schemas: ' . $e->getMessage());
    }

    // Create a user to serve as owner.
    User::create([
      'uid' => 1,
      'name' => 'admin',
      'status' => 1,
    ])->save();
  }

  /**
   * Tests creating an ExpedienteDocumento entity.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function createExpedienteDocumento(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('expediente_documento');

    /** @var \Drupal\jaraba_andalucia_ei\Entity\ExpedienteDocumentoInterface $doc */
    $doc = $storage->create([
      'titulo' => 'DNI Juan Garcia',
      'categoria' => 'sto_dni',
      'estado_revision' => 'pendiente',
      'uid' => 1,
    ]);

    $doc->save();

    $this->assertNotEmpty($doc->id());
    $this->assertEquals('DNI Juan Garcia', $doc->getTitulo());
    $this->assertEquals('sto_dni', $doc->getCategoria());
    $this->assertEquals('pendiente', $doc->getEstadoRevision());
  }

  /**
   * Tests updating titulo field.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function updateTitulo(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('expediente_documento');

    $doc = $storage->create([
      'titulo' => 'Original Title',
      'categoria' => 'tarea_cv',
      'estado_revision' => 'pendiente',
      'uid' => 1,
    ]);
    $doc->save();

    $doc->setTitulo('Updated Title');
    $doc->save();

    $loaded = $storage->load($doc->id());
    $this->assertEquals('Updated Title', $loaded->getTitulo());
  }

  /**
   * Tests that allowed values for categoria include all expected keys.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function categoriaAllowedValuesContainExpectedKeys(): void {
    $categorias = ExpedienteDocumento::CATEGORIAS;

    // STO categories.
    $this->assertArrayHasKey('sto_dni', $categorias);
    $this->assertArrayHasKey('sto_empadronamiento', $categorias);
    $this->assertArrayHasKey('sto_vida_laboral', $categorias);
    $this->assertArrayHasKey('sto_demanda_empleo', $categorias);
    $this->assertArrayHasKey('sto_prestaciones', $categorias);
    $this->assertArrayHasKey('sto_titulo_academico', $categorias);

    // Program categories.
    $this->assertArrayHasKey('programa_contrato', $categorias);
    $this->assertArrayHasKey('programa_consentimiento', $categorias);

    // Task categories.
    $this->assertArrayHasKey('tarea_cv', $categorias);
    $this->assertArrayHasKey('tarea_proyecto', $categorias);

    // Certification categories.
    $this->assertArrayHasKey('cert_formacion', $categorias);
    $this->assertArrayHasKey('cert_participacion', $categorias);
  }

  /**
   * Tests estado_revision transitions.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function estadoRevisionTransitions(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('expediente_documento');

    $doc = $storage->create([
      'titulo' => 'Test Doc',
      'categoria' => 'tarea_cv',
      'estado_revision' => 'pendiente',
      'uid' => 1,
    ]);
    $doc->save();

    // Transition: pendiente -> en_revision.
    $doc->setEstadoRevision('en_revision');
    $doc->save();
    $this->assertEquals('en_revision', $storage->load($doc->id())->getEstadoRevision());

    // Transition: en_revision -> aprobado.
    $doc->setEstadoRevision('aprobado');
    $doc->save();
    $this->assertEquals('aprobado', $storage->load($doc->id())->getEstadoRevision());
  }

  /**
   * Tests estado_revision to rechazado.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function estadoRevisionRechazado(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('expediente_documento');

    $doc = $storage->create([
      'titulo' => 'Rejected Doc',
      'categoria' => 'tarea_carta',
      'estado_revision' => 'pendiente',
      'uid' => 1,
    ]);
    $doc->save();

    $doc->setEstadoRevision('rechazado');
    $doc->save();
    $this->assertEquals('rechazado', $storage->load($doc->id())->getEstadoRevision());
  }

  /**
   * Tests estado_revision to requiere_cambios.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function estadoRevisionRequiereCambios(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('expediente_documento');

    $doc = $storage->create([
      'titulo' => 'Needs Changes',
      'categoria' => 'tarea_plan_empleo',
      'estado_revision' => 'pendiente',
      'uid' => 1,
    ]);
    $doc->save();

    $doc->setEstadoRevision('requiere_cambios');
    $doc->save();
    $this->assertEquals('requiere_cambios', $storage->load($doc->id())->getEstadoRevision());
  }

  /**
   * Tests default value of estado_revision is pendiente.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function defaultEstadoRevisionIsPendiente(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('expediente_documento');

    $doc = $storage->create([
      'titulo' => 'Default State',
      'categoria' => 'sto_dni',
      'uid' => 1,
    ]);
    $doc->save();

    $this->assertEquals('pendiente', $doc->getEstadoRevision());
  }

  /**
   * Tests deleting an ExpedienteDocumento entity.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function deleteExpedienteDocumento(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('expediente_documento');

    $doc = $storage->create([
      'titulo' => 'To Delete',
      'categoria' => 'sto_otros',
      'estado_revision' => 'pendiente',
      'uid' => 1,
    ]);
    $doc->save();
    $id = $doc->id();

    $doc->delete();

    $this->assertNull($storage->load($id));
  }

}
