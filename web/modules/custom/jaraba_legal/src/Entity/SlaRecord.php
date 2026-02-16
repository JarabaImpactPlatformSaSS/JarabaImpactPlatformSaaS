<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * ENTIDAD SLA RECORD — Registro de cumplimiento SLA.
 *
 * ESTRUCTURA:
 * Content Entity que registra las métricas de SLA por periodo y tenant.
 * Cada registro representa un periodo (mensual típicamente) con el uptime
 * medido, el target, y los créditos aplicados si procede.
 *
 * LÓGICA DE NEGOCIO:
 * - Los registros se generan automáticamente por SlaCalculatorService via cron.
 * - Si uptime < target, se calcula el crédito según el porcentaje configurado.
 * - Los créditos se aplican al siguiente ciclo de facturación (integración con billing).
 * - El historial de SLA es inmutable (solo lectura una vez generado).
 *
 * RELACIONES:
 * - tenant_id → Group (referencia al tenant propietario)
 *
 * Spec: Doc 184 §2.2. Plan: FASE 5, Stack Compliance Legal N1.
 *
 * @ContentEntityType(
 *   id = "sla_record",
 *   label = @Translation("SLA Record"),
 *   label_collection = @Translation("SLA Records"),
 *   label_singular = @Translation("SLA record"),
 *   label_plural = @Translation("SLA records"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal\ListBuilder\SlaRecordListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_legal\Form\SlaRecordForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_legal\Access\SlaRecordAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "sla_record",
 *   admin_permission = "administer legal",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/sla-record/{sla_record}",
 *     "add-form" = "/admin/content/sla-record/add",
 *     "edit-form" = "/admin/content/sla-record/{sla_record}/edit",
 *     "delete-form" = "/admin/content/sla-record/{sla_record}/delete",
 *     "collection" = "/admin/content/sla-records",
 *   },
 *   field_ui_base_route = "jaraba_legal.sla_record.settings",
 * )
 */
class SlaRecord extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- TENANT (aislamiento multi-tenant) ---

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant al que pertenece este registro SLA.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => -10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- PERIODO ---

    $fields['period_start'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Inicio del periodo'))
      ->setDescription(new TranslatableMarkup('Timestamp UTC del inicio del periodo de medición.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['period_end'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Fin del periodo'))
      ->setDescription(new TranslatableMarkup('Timestamp UTC del fin del periodo de medición.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- MÉTRICAS ---

    $fields['uptime_percentage'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Uptime (%)'))
      ->setDescription(new TranslatableMarkup('Porcentaje de disponibilidad medido en el periodo.'))
      ->setRequired(TRUE)
      ->setSetting('precision', 5)
      ->setSetting('scale', 3)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['target_percentage'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Target (%)'))
      ->setDescription(new TranslatableMarkup('Porcentaje de disponibilidad comprometido en el SLA.'))
      ->setRequired(TRUE)
      ->setSetting('precision', 5)
      ->setSetting('scale', 3)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['downtime_minutes'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Downtime (minutos)'))
      ->setDescription(new TranslatableMarkup('Minutos de indisponibilidad durante el periodo.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- CRÉDITOS ---

    $fields['credit_percentage'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Crédito (%)'))
      ->setDescription(new TranslatableMarkup('Porcentaje de crédito aplicado por incumplimiento.'))
      ->setDefaultValue('0.00')
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['credit_applied'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Crédito aplicado'))
      ->setDescription(new TranslatableMarkup('Indica si el crédito ya se aplicó al ciclo de facturación.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- INCIDENTES ---

    $fields['incident_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Número de incidentes'))
      ->setDescription(new TranslatableMarkup('Número de incidentes de disponibilidad durante el periodo.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- TIMESTAMPS ---

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'))
      ->setDescription(new TranslatableMarkup('Fecha de creación del registro.'));

    return $fields;
  }

  /**
   * Comprueba si el SLA se cumplió en este periodo.
   */
  public function isMet(): bool {
    return (float) $this->get('uptime_percentage')->value >= (float) $this->get('target_percentage')->value;
  }

  /**
   * Comprueba si se ha aplicado crédito por incumplimiento.
   */
  public function isCreditApplied(): bool {
    return (bool) $this->get('credit_applied')->value;
  }

  /**
   * Comprueba si hubo incidentes en el periodo.
   */
  public function hasIncidents(): bool {
    return (int) $this->get('incident_count')->value > 0;
  }

}
