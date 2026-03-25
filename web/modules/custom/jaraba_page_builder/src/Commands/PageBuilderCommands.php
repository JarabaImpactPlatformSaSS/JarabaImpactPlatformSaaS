<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Commands;

use Drush\Commands\DrushCommands;
use Drupal\jaraba_page_builder\Service\MultiBlockMigrationService;

/**
 * Comandos Drush para el Page Builder.
 *
 * Proporciona comandos para gestión de páginas y migraciones.
 *
 * @package Drupal\jaraba_page_builder\Commands
 */
class PageBuilderCommands extends DrushCommands {

  /**
   * Servicio de migración multi-block.
   *
   * @var \Drupal\jaraba_page_builder\Service\MultiBlockMigrationService
   */
  protected MultiBlockMigrationService $migrationService;

  /**
   * Constructor.
   */
  public function __construct(MultiBlockMigrationService $migration_service) {
    parent::__construct();
    $this->migrationService = $migration_service;
  }

  /**
   * Migra páginas legacy a modo multi-block.
   *
   * @param array $options
   *   Opciones del comando.
   *
   * @command page-builder:migrate-multiblock
   * @aliases pb:migrate, multiblock:migrate
   * @option dry-run Simula la migración sin guardar cambios.
   * @option limit Número máximo de páginas a migrar.
   * @usage drush page-builder:migrate-multiblock
   *   Migra todas las páginas legacy.
   * @usage drush pb:migrate --dry-run
   *   Simula la migración sin cambios.
   * @usage drush pb:migrate --limit=10
   *   Migra solo 10 páginas.
   */
  public function migrateMultiblock(
    array $options = [
      'dry-run' => FALSE,
      'limit' => NULL,
    ],
  ): void {
    $dry_run = (bool) $options['dry-run'];
    $limit = $options['limit'] ? (int) $options['limit'] : NULL;

    // Contar pendientes.
    $pending = $this->migrationService->countPending();
    $this->io()->title('Migración Multi-Block');

    if ($pending === 0) {
      $this->io()->success('No hay páginas pendientes de migración.');
      return;
    }

    $this->io()->text([
      sprintf('Páginas pendientes: %d', $pending),
      $dry_run ? '🔍 Modo DRY RUN - No se guardarán cambios' : '⚡ Modo REAL - Se guardarán cambios',
    ]);

    if (!$dry_run && !$this->io()->confirm('¿Proceder con la migración?', TRUE)) {
      $this->io()->warning('Migración cancelada.');
      return;
    }

    // Ejecutar migración.
    $results = $this->migrationService->migrateAll($limit, $dry_run);

    // Mostrar resultados.
    $this->io()->section('Resultados');
    $this->io()->table(
          ['Métrica', 'Valor'],
          [
              ['Total procesadas', $results['total']],
              ['Migradas', $results['migrated']],
              ['Omitidas (ya migradas)', $results['skipped']],
              ['Fallidas', $results['failed']],
          ]
      );

    if ($results['failed'] > 0) {
      $this->io()->warning('Algunas páginas fallaron. Revisa los logs para más detalles.');
    }
    elseif ($dry_run) {
      $this->io()->note('Ejecuta sin --dry-run para aplicar los cambios.');
    }
    else {
      $this->io()->success('Migración completada exitosamente.');
    }
  }

  /**
   * Muestra el estado de migración multi-block.
   *
   * @command page-builder:multiblock-status
   * @aliases pb:status
   * @usage drush pb:status
   *   Muestra cuántas páginas están en modo legacy vs multiblock.
   */
  public function multiblockStatus(): void {
    $pending = $this->migrationService->countPending();

    $this->io()->title('Estado Multi-Block');
    $this->io()->table(
          ['Estado', 'Cantidad'],
          [
              ['Páginas Legacy (pendientes)', $pending],
          ]
      );

    if ($pending > 0) {
      $this->io()->note('Ejecuta "drush pb:migrate" para migrar las páginas legacy.');
    }
    else {
      $this->io()->success('Todas las páginas están en modo multi-block.');
    }
  }

  /**
   * Revierte una página de multiblock a legacy.
   *
   * @param int $page_id
   *   ID de la página a revertir.
   *
   * @command page-builder:revert-multiblock
   * @aliases pb:revert
   * @usage drush pb:revert 123
   *   Revierte la página 123 a modo legacy.
   */
  public function revertMultiblock(int $page_id): void {
    $result = $this->migrationService->revertPage($page_id);

    if ($result['success']) {
      if (!empty($result['skipped'])) {
        $this->io()->note($result['message']);
      }
      else {
        $this->io()->success($result['message']);
      }
    }
    else {
      $this->io()->error($result['message']);
    }
  }

}
