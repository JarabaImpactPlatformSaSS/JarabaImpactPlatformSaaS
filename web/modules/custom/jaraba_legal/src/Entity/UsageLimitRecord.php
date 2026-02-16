<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * ENTIDAD USAGE LIMIT RECORD — Registro de límites de uso.
 *
 * ESTRUCTURA:
 * Content Entity que registra los límites de uso por tenant y recurso.
 * Cada registro representa un límite específico (API calls, storage, etc.)
 * con su valor actual de consumo y acciones tomadas al exceder.
 *
 * LÓGICA DE NEGOCIO:
 * - Los registros se actualizan automáticamente por AupEnforcerService.
 * - Al exceder un límite, se registra el timestamp y la acción tomada.
 * - Los límites se definen por plan de suscripción del tenant.
 * - Los registros son de solo lectura (auto-generados por el sistema).
 *
 * RELACIONES:
 * - tenant_id → Group (referencia al tenant propietario)
 *
 * Spec: Doc 184 §2.6. Plan: FASE 5, Stack Compliance Legal N1.
 *
 * @ContentEntityType(
 *   id = "usage_limit_record",
 *   label = @Translation("Usage Limit Record"),
 *   label_collection = @Translation("Usage Limit Records"),
 *   label_singular = @Translation("usage limit record"),
 *   label_plural = @Translation("usage limit records"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal\ListBuilder\UsageLimitRecordListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_legal\Form\UsageLimitRecordForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_legal\Access\UsageLimitRecordAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "usage_limit_record",
 *   admin_permission = "administer legal",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/usage-limit-record/{usage_limit_record}",
 *     "add-form" = "/admin/content/usage-limit-record/add",
 *     "edit-form" = "/admin/content/usage-limit-record/{usage_limit_record}/edit",
 *     "delete-form" = "/admin/content/usage-limit-record/{usage_limit_record}/delete",
 *     "collection" = "/admin/content/usage-limit-records",
 *   },
 *   field_ui_base_route = "jaraba_legal.usage_limit_record.settings",
 * )
 */
class UsageLimitRecord extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- TENANT (aislamiento multi-tenant) ---

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant al que pertenece este registro de límites.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => -10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- TIPO DE LÍMITE ---

    $fields['limit_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Tipo de límite'))
      ->setDescription(new TranslatableMarkup('Recurso al que aplica el límite de uso.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'api_calls' => new TranslatableMarkup('Llamadas API'),
        'storage_mb' => new TranslatableMarkup('Almacenamiento (MB)'),
        'bandwidth_mb' => new TranslatableMarkup('Ancho de banda (MB)'),
        'users' => new TranslatableMarkup('Usuarios'),
        'pages' => new TranslatableMarkup('Páginas'),
        'products' => new TranslatableMarkup('Productos'),
      ])
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- VALORES ---

    $fields['limit_value'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Valor del límite'))
      ->setDescription(new TranslatableMarkup('Valor máximo permitido para este recurso.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['current_usage'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Uso actual'))
      ->setDescription(new TranslatableMarkup('Valor de consumo actual del recurso.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- PERIODO ---

    $fields['period'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Periodo'))
      ->setDescription(new TranslatableMarkup('Periodo de medición del límite.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'daily' => new TranslatableMarkup('Diario'),
        'monthly' => new TranslatableMarkup('Mensual'),
        'yearly' => new TranslatableMarkup('Anual'),
      ])
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- EXCESO ---

    $fields['exceeded_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Fecha de exceso'))
      ->setDescription(new TranslatableMarkup('Timestamp UTC en que se excedió el límite.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['action_taken'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Acción tomada'))
      ->setDescription(new TranslatableMarkup('Acción aplicada al exceder el límite.'))
      ->setSetting('max_length', 50)
      ->setDisplayConfigurable('view', TRUE);

    // --- TIMESTAMPS ---

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'))
      ->setDescription(new TranslatableMarkup('Fecha de creación del registro.'));

    return $fields;
  }

  /**
   * Comprueba si el límite ha sido excedido.
   */
  public function isExceeded(): bool {
    return (int) $this->get('current_usage')->value > (int) $this->get('limit_value')->value;
  }

  /**
   * Obtiene el porcentaje de uso actual respecto al límite.
   */
  public function getUsagePercentage(): float {
    $limit = (int) $this->get('limit_value')->value;
    if ($limit === 0) {
      return 0.0;
    }
    return ((int) $this->get('current_usage')->value / $limit) * 100;
  }

  /**
   * Comprueba si el uso está cerca del límite (>=80%).
   */
  public function isNearLimit(): bool {
    return $this->getUsagePercentage() >= 80.0;
  }

  /**
   * Comprueba si se ha tomado alguna acción.
   */
  public function hasActionTaken(): bool {
    return !empty($this->get('action_taken')->value);
  }

}
