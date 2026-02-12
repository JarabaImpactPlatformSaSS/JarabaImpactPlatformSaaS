<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\jaraba_page_builder\PageContentInterface;

/**
 * Servicio de migración de páginas legacy a multi-block.
 *
 * PROPÓSITO:
 * Convierte páginas en modo 'legacy' (con template_id + content_data único)
 * a modo 'multiblock' (con array de secciones).
 *
 * ESTRATEGIA DE MIGRACIÓN:
 * 1. Carga páginas con layout_mode = 'legacy' (o NULL)
 * 2. Crea una sección inicial usando template_id y content_data existentes
 * 3. Actualiza layout_mode a 'multiblock'
 * 4. Preserva los campos originales por compatibilidad
 *
 * @package Drupal\jaraba_page_builder\Service
 */
class MultiBlockMigrationService
{

    /**
     * Entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * UUID service.
     *
     * @var \Drupal\Component\Uuid\UuidInterface
     */
    protected UuidInterface $uuid;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        LoggerChannelFactoryInterface $logger_factory,
        UuidInterface $uuid
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->logger = $logger_factory->get('jaraba_page_builder');
        $this->uuid = $uuid;
    }

    /**
     * Obtiene páginas pendientes de migración.
     *
     * @param int|null $limit
     *   Límite de páginas a retornar.
     *
     * @return array
     *   Array de IDs de páginas legacy.
     */
    public function getPendingMigration(?int $limit = NULL): array
    {
        $storage = $this->entityTypeManager->getStorage('page_content');

        $query = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('layout_mode', ['legacy', ''], 'IN');

        if ($limit) {
            $query->range(0, $limit);
        }

        return $query->execute();
    }

    /**
     * Cuenta páginas pendientes de migración.
     *
     * @return int
     *   Número de páginas legacy.
     */
    public function countPending(): int
    {
        return count($this->getPendingMigration());
    }

    /**
     * Migra una página específica a multi-block.
     *
     * @param int $page_id
     *   ID de la página a migrar.
     * @param bool $dry_run
     *   Si es TRUE, no guarda cambios.
     *
     * @return array
     *   Resultado de la migración con claves: success, message, page_id.
     */
    public function migratePage(int $page_id, bool $dry_run = FALSE): array
    {
        $storage = $this->entityTypeManager->getStorage('page_content');
        /** @var \Drupal\jaraba_page_builder\PageContentInterface|null $page */
        $page = $storage->load($page_id);

        if (!$page instanceof PageContentInterface) {
            return [
                'success' => FALSE,
                'message' => "Página no encontrada: $page_id",
                'page_id' => $page_id,
            ];
        }

        // Verificar si ya está migrada
        $layout_mode = $page->get('layout_mode')->value ?? 'legacy';
        if ($layout_mode === 'multiblock') {
            return [
                'success' => TRUE,
                'message' => "Ya migrada",
                'page_id' => $page_id,
                'skipped' => TRUE,
            ];
        }

        // Obtener datos actuales
        $template_id = $page->get('template_id')->value ?? '';
        $content_data_raw = $page->get('content_data')->value ?? '{}';
        $content_data = json_decode($content_data_raw, TRUE) ?: [];

        // Si no hay template, crear sección vacía
        if (empty($template_id)) {
            $template_id = 'blank_section';
        }

        // Crear la sección inicial
        $section = [
            'uuid' => $this->uuid->generate(),
            'template_id' => $template_id,
            'content' => $content_data,
            'weight' => 0,
            'visible' => TRUE,
            'migrated_at' => date('c'),
        ];

        $sections = [$section];

        // Log de la migración
        $this->logger->info('Migrando página @id: template=@template, content_keys=@keys', [
            '@id' => $page_id,
            '@template' => $template_id,
            '@keys' => implode(', ', array_keys($content_data)),
        ]);

        if (!$dry_run) {
            // Actualizar la página
            $page->set('layout_mode', 'multiblock');
            $page->set('sections', json_encode($sections, JSON_UNESCAPED_UNICODE));
            $page->save();
        }

        return [
            'success' => TRUE,
            'message' => $dry_run ? "Simulación exitosa" : "Migrada correctamente",
            'page_id' => $page_id,
            'template_id' => $template_id,
            'sections_count' => 1,
        ];
    }

    /**
     * Migra múltiples páginas.
     *
     * @param int|null $limit
     *   Límite de páginas a migrar.
     * @param bool $dry_run
     *   Si es TRUE, no guarda cambios.
     *
     * @return array
     *   Resultados agregados de la migración.
     */
    public function migrateAll(?int $limit = NULL, bool $dry_run = FALSE): array
    {
        $pending_ids = $this->getPendingMigration($limit);
        $results = [
            'total' => count($pending_ids),
            'migrated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'dry_run' => $dry_run,
            'details' => [],
        ];

        foreach ($pending_ids as $page_id) {
            $result = $this->migratePage((int) $page_id, $dry_run);
            $results['details'][] = $result;

            if ($result['success']) {
                if (!empty($result['skipped'])) {
                    $results['skipped']++;
                } else {
                    $results['migrated']++;
                }
            } else {
                $results['failed']++;
            }
        }

        $this->logger->notice('Migración multi-block completada: @migrated migradas, @skipped omitidas, @failed fallidas de @total', [
            '@migrated' => $results['migrated'],
            '@skipped' => $results['skipped'],
            '@failed' => $results['failed'],
            '@total' => $results['total'],
        ]);

        return $results;
    }

    /**
     * Revierte una página de multiblock a legacy.
     *
     * @param int $page_id
     *   ID de la página.
     *
     * @return array
     *   Resultado de la reversión.
     */
    public function revertPage(int $page_id): array
    {
        $storage = $this->entityTypeManager->getStorage('page_content');
        /** @var \Drupal\jaraba_page_builder\PageContentInterface|null $page */
        $page = $storage->load($page_id);

        if (!$page instanceof PageContentInterface) {
            return [
                'success' => FALSE,
                'message' => "Página no encontrada: $page_id",
            ];
        }

        $layout_mode = $page->get('layout_mode')->value ?? 'legacy';
        if ($layout_mode !== 'multiblock') {
            return [
                'success' => TRUE,
                'message' => "Ya está en modo legacy",
                'skipped' => TRUE,
            ];
        }

        // Simplemente cambiar el modo - los datos originales siguen en template_id y content_data
        $page->set('layout_mode', 'legacy');
        $page->save();

        $this->logger->info('Revertida página @id a modo legacy', ['@id' => $page_id]);

        return [
            'success' => TRUE,
            'message' => "Revertida a modo legacy",
            'page_id' => $page_id,
        ];
    }

}
