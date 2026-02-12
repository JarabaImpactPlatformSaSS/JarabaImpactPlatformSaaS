<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de versionado de artículos de conocimiento del tenant (G114-2).
 *
 * PROPÓSITO:
 * Proporciona funcionalidad completa de versionado para entidades de
 * conocimiento: TenantFaq, TenantPolicy y TenantDocument. Incluye
 * listado de revisiones, comparación diff, carga y reversión.
 *
 * LÓGICA:
 * - Genérico para cualquier entidad revisionable del módulo
 * - Comparación campo a campo con detección de tipo de cambio
 * - Metadatos de revisión (autor, fecha, mensaje)
 * - Reversión crea una nueva revisión con los valores antiguos
 *
 * DIRECTRICES:
 * - Patrón idéntico a jaraba_page_builder.revision_diff
 * - Los campos a comparar se configuran por tipo de entidad
 * - Traducciones con t() en etiquetas
 *
 * @see \Drupal\jaraba_page_builder\Service\RevisionDiffService
 */
class KnowledgeRevisionService {

  /**
   * Campos a comparar por tipo de entidad.
   *
   * @var array<string, string[]>
   */
  protected const COMPARE_FIELDS = [
    'tenant_faq' => [
      'question',
      'answer',
      'category',
      'priority',
      'is_published',
    ],
    'tenant_policy' => [
      'title',
      'content',
      'summary',
      'policy_type',
      'version_number',
      'version_notes',
      'is_published',
    ],
    'tenant_document' => [
      'title',
      'description',
      'category',
      'extracted_text',
      'processing_status',
    ],
  ];

  /**
   * Etiquetas legibles por campo.
   *
   * @var array<string, string>
   */
  protected const FIELD_LABELS = [
    'question' => 'Pregunta',
    'answer' => 'Respuesta',
    'category' => 'Categoría',
    'priority' => 'Prioridad',
    'is_published' => 'Publicada',
    'title' => 'Título',
    'content' => 'Contenido',
    'summary' => 'Resumen',
    'policy_type' => 'Tipo de Política',
    'version_number' => 'Versión',
    'version_notes' => 'Notas de Versión',
    'description' => 'Descripción',
    'extracted_text' => 'Texto Extraído',
    'processing_status' => 'Estado de Procesamiento',
  ];

  /**
   * Constructor del servicio de revisiones.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Usuario actual (para mensajes de reversión).
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * Lista todas las revisiones de una entidad.
   *
   * @param string $entityType
   *   Tipo de entidad (tenant_faq o tenant_policy).
   * @param int $entityId
   *   ID de la entidad.
   *
   * @return array
   *   Array de revisiones con metadatos.
   */
  public function listRevisions(string $entityType, int $entityId): array {
    $storage = $this->entityTypeManager->getStorage($entityType);

    $revisionIds = $storage->getQuery()
      ->accessCheck(FALSE)
      ->allRevisions()
      ->condition('id', $entityId)
      ->sort('revision_id', 'DESC')
      ->execute();

    $revisions = [];
    foreach (array_keys($revisionIds) as $revisionId) {
      $revision = $storage->loadRevision($revisionId);
      if (!$revision) {
        continue;
      }

      $userName = 'Sistema';
      if ($revision instanceof RevisionLogInterface) {
        $user = $revision->getRevisionUser();
        $userName = $user?->getDisplayName() ?? 'Sistema';
      }

      $revisions[] = [
        'id' => $revisionId,
        'created' => $revision->get('changed')->value ?? $revision->get('created')->value,
        'user' => $userName,
        'log' => ($revision instanceof RevisionLogInterface)
          ? ($revision->getRevisionLogMessage() ?? '')
          : '',
        'is_current' => $revision->isDefaultRevision(),
      ];
    }

    return $revisions;
  }

  /**
   * Carga una revisión específica de una entidad.
   *
   * @param string $entityType
   *   Tipo de entidad.
   * @param int $entityId
   *   ID de la entidad.
   * @param int $revisionId
   *   ID de la revisión.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   La revisión o NULL si no existe.
   */
  public function loadRevision(string $entityType, int $entityId, int $revisionId): ?ContentEntityInterface {
    try {
      $storage = $this->entityTypeManager->getStorage($entityType);
      $revision = $storage->loadRevision($revisionId);

      if ($revision && (int) $revision->id() === $entityId) {
        return $revision;
      }

      return NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('Error cargando revisión @rid de @type @eid: @msg', [
        '@rid' => $revisionId,
        '@type' => $entityType,
        '@eid' => $entityId,
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Compara dos revisiones y devuelve las diferencias.
   *
   * @param string $entityType
   *   Tipo de entidad.
   * @param \Drupal\Core\Entity\ContentEntityInterface $older
   *   Revisión más antigua.
   * @param \Drupal\Core\Entity\ContentEntityInterface $newer
   *   Revisión más reciente.
   *
   * @return array
   *   Array de diferencias por campo.
   */
  public function compareRevisions(string $entityType, ContentEntityInterface $older, ContentEntityInterface $newer): array {
    $fields = self::COMPARE_FIELDS[$entityType] ?? [];
    $diff = [];

    foreach ($fields as $fieldName) {
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
          'label' => self::FIELD_LABELS[$fieldName] ?? ucfirst(str_replace('_', ' ', $fieldName)),
        ];
      }
    }

    return $diff;
  }

  /**
   * Revierte una entidad a una revisión anterior.
   *
   * Crea una nueva revisión con los valores de la revisión indicada,
   * registrando un mensaje de log descriptivo.
   *
   * @param string $entityType
   *   Tipo de entidad (tenant_faq, tenant_policy o tenant_document).
   * @param int $entityId
   *   ID de la entidad.
   * @param int $revisionId
   *   ID de la revisión a la que revertir.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   La nueva revisión creada, o NULL si falla.
   */
  public function revertToRevision(string $entityType, int $entityId, int $revisionId): ?ContentEntityInterface {
    try {
      $storage = $this->entityTypeManager->getStorage($entityType);

      // Cargar la revisión objetivo.
      $targetRevision = $storage->loadRevision($revisionId);
      if (!$targetRevision || (int) $targetRevision->id() !== $entityId) {
        $this->logger->warning('Revisión @rid no encontrada para @type @eid.', [
          '@rid' => $revisionId,
          '@type' => $entityType,
          '@eid' => $entityId,
        ]);
        return NULL;
      }

      // Crear nueva revisión basada en la revisión objetivo.
      $newRevision = $storage->createRevision($targetRevision);

      if ($newRevision instanceof RevisionLogInterface) {
        $newRevision->setRevisionLogMessage(
          sprintf('Revertida a revisión #%d por %s.', $revisionId, $this->currentUser->getDisplayName())
        );
        $newRevision->setRevisionCreationTime(\Drupal::time()->getRequestTime());
        $newRevision->setRevisionUserId((int) $this->currentUser->id());
      }

      $newRevision->setNewRevision(TRUE);
      $newRevision->isDefaultRevision(TRUE);
      $newRevision->save();

      $this->logger->info('Entidad @type @eid revertida a revisión @rid. Nueva revisión: @new_rid.', [
        '@type' => $entityType,
        '@eid' => $entityId,
        '@rid' => $revisionId,
        '@new_rid' => $newRevision->getRevisionId(),
      ]);

      return $newRevision;
    }
    catch (\Exception $e) {
      $this->logger->error('Error revirtiendo @type @eid a revisión @rid: @msg', [
        '@type' => $entityType,
        '@eid' => $entityId,
        '@rid' => $revisionId,
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Obtiene un resumen compacto de una revisión específica.
   *
   * Devuelve los valores actuales de los campos comparables junto
   * con los metadatos de la revisión (autor, fecha, mensaje).
   *
   * @param string $entityType
   *   Tipo de entidad.
   * @param int $entityId
   *   ID de la entidad.
   * @param int $revisionId
   *   ID de la revisión.
   *
   * @return array|null
   *   Array con metadatos y valores de campos, o NULL si no existe.
   */
  public function getRevisionSummary(string $entityType, int $entityId, int $revisionId): ?array {
    $revision = $this->loadRevision($entityType, $entityId, $revisionId);
    if (!$revision) {
      return NULL;
    }

    $fields = self::COMPARE_FIELDS[$entityType] ?? [];
    $fieldValues = [];
    foreach ($fields as $fieldName) {
      if ($revision->hasField($fieldName)) {
        $fieldValues[$fieldName] = [
          'label' => self::FIELD_LABELS[$fieldName] ?? ucfirst(str_replace('_', ' ', $fieldName)),
          'value' => $this->getFieldValue($revision, $fieldName),
        ];
      }
    }

    $userName = 'Sistema';
    if ($revision instanceof RevisionLogInterface) {
      $user = $revision->getRevisionUser();
      $userName = $user?->getDisplayName() ?? 'Sistema';
    }

    return [
      'revision_id' => $revisionId,
      'entity_id' => $entityId,
      'entity_type' => $entityType,
      'is_current' => $revision->isDefaultRevision(),
      'created' => $revision->get('changed')->value ?? $revision->get('created')->value,
      'user' => $userName,
      'log' => ($revision instanceof RevisionLogInterface)
        ? ($revision->getRevisionLogMessage() ?? '')
        : '',
      'fields' => $fieldValues,
    ];
  }

  /**
   * Cuenta el número total de revisiones de una entidad.
   *
   * @param string $entityType
   *   Tipo de entidad.
   * @param int $entityId
   *   ID de la entidad.
   *
   * @return int
   *   Número de revisiones.
   */
  public function countRevisions(string $entityType, int $entityId): int {
    try {
      $storage = $this->entityTypeManager->getStorage($entityType);

      return (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->allRevisions()
        ->condition('id', $entityId)
        ->count()
        ->execute();
    }
    catch (\Exception $e) {
      $this->logger->error('Error contando revisiones de @type @eid: @msg', [
        '@type' => $entityType,
        '@eid' => $entityId,
        '@msg' => $e->getMessage(),
      ]);
      return 0;
    }
  }

  /**
   * Obtiene el valor legible de un campo.
   */
  protected function getFieldValue(ContentEntityInterface $entity, string $fieldName): string {
    if (!$entity->hasField($fieldName)) {
      return '';
    }

    $fieldItem = $entity->get($fieldName);

    // Campos booleanos.
    if ($fieldItem->getFieldDefinition()->getType() === 'boolean') {
      return $fieldItem->value ? 'Sí' : 'No';
    }

    // Campos list_string: mostrar etiqueta legible.
    if ($fieldItem->getFieldDefinition()->getType() === 'list_string') {
      $allowed = $fieldItem->getFieldDefinition()->getSetting('allowed_values');
      $value = $fieldItem->value ?? '';
      return $allowed[$value] ?? $value;
    }

    // Campos text_long: extraer texto sin HTML.
    $value = $fieldItem->value;
    if (is_string($value)) {
      return $value;
    }

    return (string) ($value ?? '');
  }

  /**
   * Detecta el tipo de cambio entre dos valores.
   */
  protected function detectChangeType(string $oldValue, string $newValue): string {
    if ($oldValue === '' && $newValue !== '') {
      return 'added';
    }
    if ($oldValue !== '' && $newValue === '') {
      return 'removed';
    }
    return 'modified';
  }

}
