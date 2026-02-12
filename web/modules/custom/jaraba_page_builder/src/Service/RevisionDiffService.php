<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jaraba_page_builder\Entity\PageContentInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de comparación de revisiones de PageContent.
 *
 * PROPÓSITO:
 * Proporciona funcionalidad de diff visual para comparar dos revisiones
 * de una página, identificando cambios en campos de texto y en la
 * estructura JSON de secciones.
 *
 * PATRÓN:
 * - Comparación campo a campo con detección de tipo de cambio
 * - JSON diff para secciones (añadido/eliminado/modificado)
 * - Metadatos de revisión (autor, fecha, mensaje)
 *
 * @see docs/planificacion/20260202-Auditoria_Plan_Elevacion_Clase_Mundial_v1.md (Gap G)
 */
class RevisionDiffService
{

    /**
     * Campos de texto a comparar.
     *
     * @var string[]
     */
    protected const TEXT_FIELDS = [
        'title',
        'slug',
        'meta_title',
        'meta_description',
    ];

    /**
     * Canal de log.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Constructor.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   Gestor de tipos de entidad.
     * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
     *   Factory de canales de log.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        LoggerChannelFactoryInterface $loggerFactory,
    ) {
        $this->logger = $loggerFactory->get('jaraba_page_builder');
    }

    /**
     * Carga una revisión específica de PageContent.
     *
     * @param int $entityId
     *   ID de la entidad.
     * @param int $revisionId
     *   ID de la revisión.
     *
     * @return \Drupal\jaraba_page_builder\Entity\PageContentInterface|null
     *   La revisión o NULL si no existe.
     */
    public function loadRevision(int $entityId, int $revisionId): ?PageContentInterface
    {
        try {
            $storage = $this->entityTypeManager->getStorage('page_content');
            /** @var \Drupal\jaraba_page_builder\Entity\PageContentInterface|null $revision */
            $revision = $storage->loadRevision($revisionId);

            // Verificar que la revisión pertenece a la entidad correcta.
            if ($revision && (int) $revision->id() === $entityId) {
                return $revision;
            }

            return NULL;
        } catch (\Exception $e) {
            $this->logger->error('Error cargando revisión @rid de entidad @eid: @message', [
                '@rid' => $revisionId,
                '@eid' => $entityId,
                '@message' => $e->getMessage(),
            ]);
            return NULL;
        }
    }

    /**
     * Lista todas las revisiones de una entidad.
     *
     * @param int $entityId
     *   ID de la entidad.
     *
     * @return array
     *   Array de revisiones con metadatos.
     */
    public function listRevisions(int $entityId): array
    {
        $storage = $this->entityTypeManager->getStorage('page_content');

        // Obtener IDs de revisiones usando entity query.
        $revisionIds = $this->entityTypeManager->getStorage('page_content')
            ->getQuery()
            ->accessCheck(FALSE)
            ->allRevisions()
            ->condition('id', $entityId)
            ->sort('revision_id', 'DESC')
            ->execute();

        $revisions = [];
        foreach (array_keys($revisionIds) as $revisionId) {
            /** @var \Drupal\jaraba_page_builder\Entity\PageContent $revision */
            $revision = $storage->loadRevision($revisionId);
            if ($revision) {
                // Usar datos de la revisión: owner como autor, changed como fecha.
                $owner = $revision->getOwner();

                $revisions[] = [
                    'id' => $revisionId,
                    'created' => $revision->get('changed')->value ?? $revision->get('created')->value,
                    'user' => $owner?->getDisplayName() ?? 'Sistema',
                    'log' => '', // Sin log de revisión personalizado por ahora.
                    'is_current' => $revision->isDefaultRevision(),
                ];
            }
        }

        return $revisions;
    }

    /**
     * Compara dos revisiones y devuelve las diferencias.
     *
     * @param \Drupal\jaraba_page_builder\Entity\PageContentInterface $older
     *   Revisión más antigua.
     * @param \Drupal\jaraba_page_builder\Entity\PageContentInterface $newer
     *   Revisión más reciente.
     *
     * @return array
     *   Array de diferencias por campo.
     */
    public function compareRevisions(PageContentInterface $older, PageContentInterface $newer): array
    {
        $diff = [];

        // Comparar campos de texto.
        foreach (self::TEXT_FIELDS as $fieldName) {
            if (!$older->hasField($fieldName) || !$newer->hasField($fieldName)) {
                continue;
            }

            $oldValue = $this->getFieldValue($older, $fieldName);
            $newValue = $this->getFieldValue($newer, $fieldName);

            if ($oldValue !== $newValue) {
                $diff[$fieldName] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                    'type' => $this->detectChangeType($oldValue, $newValue),
                    'label' => $this->getFieldLabel($fieldName),
                ];
            }
        }

        // Comparar secciones (JSON).
        $sectionsDiff = $this->compareSections($older, $newer);
        if (!empty($sectionsDiff)) {
            $diff['sections'] = [
                'changes' => $sectionsDiff,
                'type' => 'sections',
                'label' => 'Secciones',
            ];
        }

        return $diff;
    }

    /**
     * Compara las secciones JSON de dos revisiones.
     *
     * @param \Drupal\jaraba_page_builder\Entity\PageContentInterface $older
     *   Revisión más antigua.
     * @param \Drupal\jaraba_page_builder\Entity\PageContentInterface $newer
     *   Revisión más reciente.
     *
     * @return array
     *   Array de cambios en secciones.
     */
    protected function compareSections(PageContentInterface $older, PageContentInterface $newer): array
    {
        if (!$older->hasField('sections') || !$newer->hasField('sections')) {
            return [];
        }

        $oldSections = $this->parseSections($older->get('sections')->value ?? '[]');
        $newSections = $this->parseSections($newer->get('sections')->value ?? '[]');

        $changes = [];

        // Indexar secciones por UUID.
        $oldByUuid = $this->indexSectionsByUuid($oldSections);
        $newByUuid = $this->indexSectionsByUuid($newSections);

        // Detectar secciones eliminadas.
        foreach ($oldByUuid as $uuid => $section) {
            if (!isset($newByUuid[$uuid])) {
                $changes[] = [
                    'type' => 'removed',
                    'uuid' => $uuid,
                    'template' => $section['template_id'] ?? 'unknown',
                    'label' => $this->humanizeTemplateName($section['template_id'] ?? ''),
                ];
            }
        }

        // Detectar secciones añadidas.
        foreach ($newByUuid as $uuid => $section) {
            if (!isset($oldByUuid[$uuid])) {
                $changes[] = [
                    'type' => 'added',
                    'uuid' => $uuid,
                    'template' => $section['template_id'] ?? 'unknown',
                    'label' => $this->humanizeTemplateName($section['template_id'] ?? ''),
                ];
            }
        }

        // Detectar secciones modificadas.
        foreach ($newByUuid as $uuid => $section) {
            if (isset($oldByUuid[$uuid])) {
                $oldSection = $oldByUuid[$uuid];
                if ($this->sectionsAreDifferent($oldSection, $section)) {
                    $changes[] = [
                        'type' => 'modified',
                        'uuid' => $uuid,
                        'template' => $section['template_id'] ?? 'unknown',
                        'label' => $this->humanizeTemplateName($section['template_id'] ?? ''),
                        'fields_changed' => $this->getChangedFields($oldSection, $section),
                    ];
                }
            }
        }

        // Detectar cambios de orden.
        $oldOrder = array_keys($oldByUuid);
        $newOrder = array_keys($newByUuid);
        if ($oldOrder !== $newOrder && !empty($oldOrder) && !empty($newOrder)) {
            $changes[] = [
                'type' => 'reordered',
                'message' => 'El orden de las secciones ha cambiado',
            ];
        }

        return $changes;
    }

    /**
     * Obtiene el valor de un campo.
     */
    protected function getFieldValue(PageContentInterface $entity, string $fieldName): string
    {
        if (!$entity->hasField($fieldName)) {
            return '';
        }

        $value = $entity->get($fieldName)->value;
        return is_string($value) ? $value : '';
    }

    /**
     * Detecta el tipo de cambio entre dos valores.
     */
    protected function detectChangeType(string $oldValue, string $newValue): string
    {
        if (empty($oldValue) && !empty($newValue)) {
            return 'added';
        }
        if (!empty($oldValue) && empty($newValue)) {
            return 'removed';
        }
        return 'modified';
    }

    /**
     * Obtiene la etiqueta legible de un campo.
     */
    protected function getFieldLabel(string $fieldName): string
    {
        $labels = [
            'title' => 'Título',
            'slug' => 'URL amigable',
            'meta_title' => 'Meta título',
            'meta_description' => 'Meta descripción',
            'sections' => 'Secciones',
        ];

        return $labels[$fieldName] ?? ucfirst(str_replace('_', ' ', $fieldName));
    }

    /**
     * Parsea el JSON de secciones.
     */
    protected function parseSections(string $json): array
    {
        try {
            $decoded = json_decode($json, TRUE, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : [];
        } catch (\JsonException $e) {
            return [];
        }
    }

    /**
     * Indexa secciones por su UUID.
     */
    protected function indexSectionsByUuid(array $sections): array
    {
        $indexed = [];
        foreach ($sections as $section) {
            if (isset($section['uuid'])) {
                $indexed[$section['uuid']] = $section;
            }
        }
        return $indexed;
    }

    /**
     * Humaniza el nombre de un template.
     */
    protected function humanizeTemplateName(string $templateId): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $templateId));
    }

    /**
     * Comprueba si dos secciones son diferentes.
     */
    protected function sectionsAreDifferent(array $old, array $new): bool
    {
        // Comparar contenido excluyendo uuid y weight.
        $oldContent = $old['content'] ?? [];
        $newContent = $new['content'] ?? [];

        return json_encode($oldContent) !== json_encode($newContent);
    }

    /**
     * Obtiene los campos que han cambiado en una sección.
     */
    protected function getChangedFields(array $old, array $new): array
    {
        $oldContent = $old['content'] ?? [];
        $newContent = $new['content'] ?? [];

        $changed = [];
        $allKeys = array_unique(array_merge(array_keys($oldContent), array_keys($newContent)));

        foreach ($allKeys as $key) {
            $oldVal = $oldContent[$key] ?? NULL;
            $newVal = $newContent[$key] ?? NULL;

            if ($oldVal !== $newVal) {
                $changed[] = $key;
            }
        }

        return $changed;
    }

}
