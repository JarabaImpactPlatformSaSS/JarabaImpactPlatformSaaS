<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Informe Personalizado (CustomReport).
 *
 * PROPÓSITO:
 * Almacena definiciones de informes personalizados de analytics que los
 * administradores pueden crear, configurar con métricas/filtros, programar
 * para envío automático y ejecutar bajo demanda.
 *
 * LÓGICA:
 * - report_type: determina la plantilla de consulta (metrics_summary,
 *   event_breakdown, conversion, retention, custom).
 * - metrics: JSON array de métricas a incluir en el informe.
 * - filters: JSON con criterios de filtrado adicionales.
 * - schedule: permite programar envío automático (diario, semanal, mensual).
 * - recipients: lista de emails separados por coma para el envío.
 * - tenant_id: aislamiento multi-tenant vía referencia a grupo.
 *
 * RELACIONES:
 * - tenant_id referencia la entidad Group del ecosistema.
 * - Consumido por ReportExecutionService para ejecutar y enviar.
 * - Consumido por ReportApiController para endpoints REST.
 *
 * @ContentEntityType(
 *   id = "custom_report",
 *   label = @Translation("Informe Personalizado"),
 *   label_collection = @Translation("Informes Personalizados"),
 *   label_singular = @Translation("informe personalizado"),
 *   label_plural = @Translation("informes personalizados"),
 *   label_count = @PluralTranslation(
 *     singular = "@count informe personalizado",
 *     plural = "@count informes personalizados",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_analytics\CustomReportListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_analytics\Form\CustomReportForm",
 *       "add" = "Drupal\jaraba_analytics\Form\CustomReportForm",
 *       "edit" = "Drupal\jaraba_analytics\Form\CustomReportForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_analytics\Access\CustomReportAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "custom_report",
 *   admin_permission = "administer jaraba analytics",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "collection" = "/admin/analytics/reports",
 *     "canonical" = "/admin/analytics/reports/{custom_report}",
 *     "add-form" = "/admin/analytics/reports/add",
 *     "edit-form" = "/admin/analytics/reports/{custom_report}/edit",
 *     "delete-form" = "/admin/analytics/reports/{custom_report}/delete",
 *   },
 * )
 */
class CustomReport extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre del Informe'))
      ->setDescription(t('Nombre descriptivo del informe personalizado.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('El tenant (grupo) al que pertenece este informe.'))
      ->setSetting('target_type', 'group')
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['report_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Informe'))
      ->setDescription(t('Tipo de informe que determina la plantilla de consulta.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'metrics_summary' => t('Resumen de Métricas'),
        'event_breakdown' => t('Desglose de Eventos'),
        'conversion' => t('Conversión'),
        'retention' => t('Retención'),
        'custom' => t('Personalizado'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['metrics'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Métricas'))
      ->setDescription(t('JSON array de métricas a incluir en el informe.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['filters'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Filtros'))
      ->setDescription(t('JSON con filtros del informe.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['date_range'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Rango de Fechas'))
      ->setDescription(t('Período de tiempo que cubre el informe.'))
      ->setSetting('allowed_values', [
        'today' => t('Hoy'),
        'yesterday' => t('Ayer'),
        'last_7_days' => t('Últimos 7 días'),
        'last_30_days' => t('Últimos 30 días'),
        'last_90_days' => t('Últimos 90 días'),
        'custom' => t('Personalizado'),
      ])
      ->setDefaultValue('last_30_days')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 25,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['schedule'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Programación'))
      ->setDescription(t('Frecuencia de envío automático del informe.'))
      ->setSetting('allowed_values', [
        'none' => t('Sin programación'),
        'daily' => t('Diario'),
        'weekly' => t('Semanal'),
        'monthly' => t('Mensual'),
      ])
      ->setDefaultValue('none')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['recipients'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Destinatarios'))
      ->setDescription(t('Emails separados por coma para el envío programado.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 35,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['last_executed'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Última Ejecución'))
      ->setDescription(t('Fecha y hora de la última ejecución del informe.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'weight' => 40,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

  /**
   * Obtiene las métricas decodificadas del informe.
   *
   * @return array
   *   Array de métricas configuradas.
   */
  public function getMetrics(): array {
    $value = $this->get('metrics')->value;
    if (empty($value)) {
      return [];
    }
    $decoded = json_decode($value, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * Obtiene los filtros decodificados del informe.
   *
   * @return array
   *   Array asociativo de filtros.
   */
  public function getFilters(): array {
    $value = $this->get('filters')->value;
    if (empty($value)) {
      return [];
    }
    $decoded = json_decode($value, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * Obtiene la lista de emails destinatarios.
   *
   * @return array
   *   Array de direcciones de email.
   */
  public function getRecipients(): array {
    $value = $this->get('recipients')->value;
    if (empty($value)) {
      return [];
    }
    return array_map('trim', explode(',', $value));
  }

}
