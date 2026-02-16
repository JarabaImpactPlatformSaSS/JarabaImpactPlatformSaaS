<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * ENTIDAD AUP VIOLATION — Violación de la Política de Uso Aceptable.
 *
 * ESTRUCTURA:
 * Content Entity que registra las violaciones de AUP detectadas por tenant.
 * Cada registro representa un incidente con tipo, severidad, acción tomada
 * y timestamps de detección/resolución.
 *
 * LÓGICA DE NEGOCIO:
 * - Las violaciones se generan automáticamente por AupEnforcerService.
 * - Según la severidad, se aplican acciones graduales (warning→throttle→suspend→terminate).
 * - Las violaciones resueltas mantienen el registro para auditoría.
 * - Los registros son de solo lectura una vez creados (auto-generados).
 *
 * RELACIONES:
 * - tenant_id → Group (referencia al tenant propietario)
 *
 * Spec: Doc 184 §2.3. Plan: FASE 5, Stack Compliance Legal N1.
 *
 * @ContentEntityType(
 *   id = "aup_violation",
 *   label = @Translation("AUP Violation"),
 *   label_collection = @Translation("AUP Violations"),
 *   label_singular = @Translation("AUP violation"),
 *   label_plural = @Translation("AUP violations"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal\ListBuilder\AupViolationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_legal\Form\AupViolationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_legal\Access\AupViolationAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "aup_violation",
 *   admin_permission = "administer legal",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/aup-violation/{aup_violation}",
 *     "add-form" = "/admin/content/aup-violation/add",
 *     "edit-form" = "/admin/content/aup-violation/{aup_violation}/edit",
 *     "delete-form" = "/admin/content/aup-violation/{aup_violation}/delete",
 *     "collection" = "/admin/content/aup-violations",
 *   },
 *   field_ui_base_route = "jaraba_legal.aup_violation.settings",
 * )
 */
class AupViolation extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- TENANT (aislamiento multi-tenant) ---

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant al que pertenece esta violación.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => -10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- CLASIFICACIÓN ---

    $fields['violation_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Tipo de violación'))
      ->setDescription(new TranslatableMarkup('Categoría de la violación de AUP detectada.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'rate_limit' => new TranslatableMarkup('Límite de peticiones'),
        'storage' => new TranslatableMarkup('Almacenamiento'),
        'bandwidth' => new TranslatableMarkup('Ancho de banda'),
        'api_abuse' => new TranslatableMarkup('Abuso de API'),
        'content' => new TranslatableMarkup('Contenido'),
        'other' => new TranslatableMarkup('Otro'),
      ])
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['severity'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Severidad'))
      ->setDescription(new TranslatableMarkup('Nivel de severidad de la violación.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'low' => new TranslatableMarkup('Baja'),
        'medium' => new TranslatableMarkup('Media'),
        'high' => new TranslatableMarkup('Alta'),
        'critical' => new TranslatableMarkup('Crítica'),
      ])
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- DETALLE ---

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Descripción'))
      ->setDescription(new TranslatableMarkup('Descripción detallada de la violación detectada.'))
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- ACCIÓN ---

    $fields['action_taken'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Acción tomada'))
      ->setDescription(new TranslatableMarkup('Acción aplicada como consecuencia de la violación.'))
      ->setSetting('allowed_values', [
        'warning' => new TranslatableMarkup('Aviso'),
        'throttle' => new TranslatableMarkup('Limitación'),
        'suspend' => new TranslatableMarkup('Suspensión'),
        'terminate' => new TranslatableMarkup('Terminación'),
      ])
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- TIMESTAMPS ---

    $fields['detected_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Fecha de detección'))
      ->setDescription(new TranslatableMarkup('Timestamp UTC de detección de la violación.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['resolved_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Fecha de resolución'))
      ->setDescription(new TranslatableMarkup('Timestamp UTC de resolución de la violación.'))
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'))
      ->setDescription(new TranslatableMarkup('Fecha de creación del registro.'));

    return $fields;
  }

  /**
   * Comprueba si la violación ha sido resuelta.
   */
  public function isResolved(): bool {
    return !empty($this->get('resolved_at')->value);
  }

  /**
   * Comprueba si la violación es de severidad crítica.
   */
  public function isCritical(): bool {
    return $this->get('severity')->value === 'critical';
  }

  /**
   * Comprueba si se ha tomado acción contra la violación.
   */
  public function hasActionTaken(): bool {
    return !empty($this->get('action_taken')->value);
  }

}
