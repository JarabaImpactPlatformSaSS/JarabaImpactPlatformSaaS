<?php

declare(strict_types=1);

namespace Drupal\jaraba_dr\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * ENTIDAD DR TEST RESULT -- Resultado de prueba de Disaster Recovery.
 *
 * ESTRUCTURA:
 * Content Entity que almacena los resultados de las pruebas de DR.
 * Cada prueba registra tipo, estado, duracion y metricas RTO/RPO
 * para verificar que la plataforma cumple los SLA de recuperacion.
 *
 * LOGICA DE NEGOCIO:
 * - Las pruebas pueden ser de tipo: backup_restore, failover, network,
 *   database o full_dr (prueba completa end-to-end).
 * - Se registra RTO (Recovery Time Objective) y RPO (Recovery Point Objective)
 *   alcanzados para comparar con los SLA comprometidos.
 * - results_data almacena JSON con detalles tecnicos de la prueba.
 * - DR es a nivel de plataforma, no multi-tenant.
 *
 * RELACIONES:
 * - executed_by -> User (usuario que ejecuto la prueba)
 *
 * Spec: Doc 185 s4.3. Plan: FASE 9, Stack Compliance Legal N1.
 *
 * @ContentEntityType(
 *   id = "dr_test_result",
 *   label = @Translation("DR Test Result"),
 *   label_collection = @Translation("DR Test Results"),
 *   label_singular = @Translation("DR test result"),
 *   label_plural = @Translation("DR test results"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_dr\ListBuilder\DrTestResultListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_dr\Form\DrTestResultForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_dr\Access\DrTestResultAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "dr_test_result",
 *   admin_permission = "administer dr",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "test_name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/dr-test-result/{dr_test_result}",
 *     "add-form" = "/admin/content/dr-test-result/add",
 *     "edit-form" = "/admin/content/dr-test-result/{dr_test_result}/edit",
 *     "delete-form" = "/admin/content/dr-test-result/{dr_test_result}/delete",
 *     "collection" = "/admin/content/dr-test-results",
 *   },
 *   field_ui_base_route = "jaraba_dr.dr_test_result.settings",
 * )
 */
class DrTestResult extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- IDENTIFICACION ---

    $fields['test_name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Nombre del test'))
      ->setDescription(new TranslatableMarkup('Nombre descriptivo de la prueba DR ejecutada.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['test_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Tipo de test'))
      ->setDescription(new TranslatableMarkup('Categoria de la prueba DR.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'backup_restore' => new TranslatableMarkup('Restauracion de backup'),
        'failover' => new TranslatableMarkup('Failover'),
        'network' => new TranslatableMarkup('Red'),
        'database' => new TranslatableMarkup('Base de datos'),
        'full_dr' => new TranslatableMarkup('DR completo'),
      ])
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Descripcion'))
      ->setDescription(new TranslatableMarkup('Descripcion detallada de la prueba y su alcance.'))
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- ESTADO Y EJECUCION ---

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado'))
      ->setDescription(new TranslatableMarkup('Estado actual de la prueba DR.'))
      ->setRequired(TRUE)
      ->setDefaultValue('scheduled')
      ->setSetting('allowed_values', [
        'scheduled' => new TranslatableMarkup('Programado'),
        'running' => new TranslatableMarkup('En ejecucion'),
        'passed' => new TranslatableMarkup('Superado'),
        'failed' => new TranslatableMarkup('Fallido'),
        'cancelled' => new TranslatableMarkup('Cancelado'),
      ])
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['started_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Inicio'))
      ->setDescription(new TranslatableMarkup('Timestamp UTC de inicio de la prueba.'))
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['completed_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Fin'))
      ->setDescription(new TranslatableMarkup('Timestamp UTC de finalizacion de la prueba.'))
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['duration_seconds'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Duracion (segundos)'))
      ->setDescription(new TranslatableMarkup('Duracion total de la prueba en segundos.'))
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- METRICAS RTO / RPO ---

    $fields['rto_achieved'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('RTO alcanzado (segundos)'))
      ->setDescription(new TranslatableMarkup('Recovery Time Objective alcanzado en la prueba, en segundos.'))
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['rpo_achieved'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('RPO alcanzado (segundos)'))
      ->setDescription(new TranslatableMarkup('Recovery Point Objective alcanzado en la prueba, en segundos.'))
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- DATOS TECNICOS ---

    $fields['results_data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Datos de resultados'))
      ->setDescription(new TranslatableMarkup('JSON con los resultados tecnicos detallados de la prueba.'))
      ->setDisplayOptions('form', ['weight' => 9])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- RESPONSABLE ---

    $fields['executed_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Ejecutado por'))
      ->setDescription(new TranslatableMarkup('Usuario que ejecuto la prueba DR.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- TIMESTAMPS ---

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'))
      ->setDescription(new TranslatableMarkup('Fecha de creacion del registro.'));

    return $fields;
  }

  /**
   * Comprueba si la prueba ha sido superada.
   */
  public function isPassed(): bool {
    return $this->get('status')->value === 'passed';
  }

  /**
   * Comprueba si la prueba ha fallado.
   */
  public function isFailed(): bool {
    return $this->get('status')->value === 'failed';
  }

  /**
   * Comprueba si la prueba esta en ejecucion.
   */
  public function isRunning(): bool {
    return $this->get('status')->value === 'running';
  }

  /**
   * Comprueba si la prueba esta programada.
   */
  public function isScheduled(): bool {
    return $this->get('status')->value === 'scheduled';
  }

  /**
   * Devuelve la duracion de la prueba en formato legible.
   */
  public function getFormattedDuration(): string {
    $seconds = (int) $this->get('duration_seconds')->value;
    if ($seconds <= 0) {
      return '-';
    }
    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $secs = $seconds % 60;
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
  }

  /**
   * Decodifica el JSON de results_data.
   *
   * @return array<string, mixed>
   *   Array con los datos de resultados o vacio si no hay datos.
   */
  public function getResultsDataDecoded(): array {
    $json = $this->get('results_data')->value;
    if (empty($json)) {
      return [];
    }
    $data = json_decode($json, TRUE);
    return is_array($data) ? $data : [];
  }

}
