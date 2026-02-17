<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Solicitud de Fondos (FundingApplication).
 *
 * ESTRUCTURA:
 * Entidad que representa una solicitud formal de fondos vinculada a una
 * convocatoria (FundingOpportunity). Incluye importes solicitados/aprobados,
 * desglose presupuestario, indicadores de impacto y plazos.
 *
 * LOGICA:
 * Al crear una solicitud, se auto-genera un numero de referencia con formato
 * SOL-YYYY-NNNN donde YYYY es el ano actual y NNNN es un correlativo
 * secuencial por tenant (ENTITY-AUTONUMBER-001).
 * El ciclo de vida es: draft -> submitted -> approved|rejected -> justifying -> closed.
 * Cada cambio de estado se registra automaticamente via hook_entity_update().
 *
 * RELACIONES:
 * - FundingApplication -> Group (tenant_id): tenant propietario.
 * - FundingApplication -> User (uid): usuario responsable.
 * - FundingApplication -> FundingOpportunity (opportunity_id): convocatoria asociada.
 * - FundingApplication <- TechnicalReport (application_id): memorias tecnicas.
 *
 * @ContentEntityType(
 *   id = "funding_application",
 *   label = @Translation("Solicitud de Fondos"),
 *   label_collection = @Translation("Solicitudes de Fondos"),
 *   label_singular = @Translation("solicitud de fondos"),
 *   label_plural = @Translation("solicitudes de fondos"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_funding\ListBuilder\FundingApplicationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_funding\Form\FundingApplicationForm",
 *       "add" = "Drupal\jaraba_funding\Form\FundingApplicationForm",
 *       "edit" = "Drupal\jaraba_funding\Form\FundingApplicationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_funding\Access\FundingApplicationAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "funding_application",
 *   admin_permission = "administer funding",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "application_number",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/funding-applications",
 *     "add-form" = "/admin/content/funding-applications/add",
 *     "canonical" = "/admin/content/funding-applications/{funding_application}",
 *     "edit-form" = "/admin/content/funding-applications/{funding_application}/edit",
 *     "delete-form" = "/admin/content/funding-applications/{funding_application}/delete",
 *   },
 *   field_ui_base_route = "jaraba_funding.funding_application.settings",
 * )
 */
class FundingApplication extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   *
   * Logica: Auto-genera el numero de solicitud SOL-YYYY-NNNN.
   *   El correlativo es unico por tenant y ano (ENTITY-AUTONUMBER-001).
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);

    if ($this->isNew() && empty($this->get('application_number')->value)) {
      $year = date('Y');
      $tenant_id = $this->get('tenant_id')->target_id;
      $query = $storage->getQuery()
        ->condition('tenant_id', $tenant_id)
        ->condition('application_number', "SOL-{$year}-", 'STARTS_WITH')
        ->accessCheck(FALSE)
        ->count();
      $count = (int) $query->execute();
      $this->set('application_number', sprintf('SOL-%s-%04d', $year, $count + 1));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // --- BLOQUE 1: Identificacion ---

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant propietario de esta solicitud.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['opportunity_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Convocatoria'))
      ->setDescription(new TranslatableMarkup('Convocatoria de fondos asociada a esta solicitud.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'funding_opportunity')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['application_number'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Numero de solicitud'))
      ->setDescription(new TranslatableMarkup('Referencia auto-generada SOL-YYYY-NNNN.'))
      ->setSetting('max_length', 32)
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- BLOQUE 2: Estado ---

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado'))
      ->setDescription(new TranslatableMarkup('Estado actual de la solicitud.'))
      ->setRequired(TRUE)
      ->setDefaultValue('draft')
      ->setSetting('allowed_values', [
        'draft' => 'Borrador',
        'submitted' => 'Presentada',
        'approved' => 'Aprobada',
        'rejected' => 'Rechazada',
        'justifying' => 'En justificacion',
        'closed' => 'Cerrada',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- BLOQUE 3: Importes ---

    $fields['amount_requested'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Importe solicitado'))
      ->setDescription(new TranslatableMarkup('Importe solicitado en euros.'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['amount_approved'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Importe aprobado'))
      ->setDescription(new TranslatableMarkup('Importe aprobado tras resolucion.'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- BLOQUE 4: Fechas ---

    $fields['submission_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fecha de envio'))
      ->setDescription(new TranslatableMarkup('Fecha de presentacion de la solicitud.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['resolution_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fecha de resolucion'))
      ->setDescription(new TranslatableMarkup('Fecha de resolucion de la convocatoria.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['next_deadline'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Proximo plazo'))
      ->setDescription(new TranslatableMarkup('Proximo plazo relevante para esta solicitud.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- BLOQUE 5: Datos complementarios ---

    $fields['budget_breakdown'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Desglose presupuestario'))
      ->setDescription(new TranslatableMarkup('Desglose detallado del presupuesto (JSON serializado).'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['impact_indicators'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Indicadores de impacto'))
      ->setDescription(new TranslatableMarkup('Indicadores de impacto del proyecto (JSON serializado).'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['justification_notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Notas de justificacion'))
      ->setDescription(new TranslatableMarkup('Notas y documentacion de justificacion del proyecto.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- BLOQUE 6: Metadatos ---

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Fecha de creacion'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Fecha de modificacion'));

    return $fields;
  }

}
