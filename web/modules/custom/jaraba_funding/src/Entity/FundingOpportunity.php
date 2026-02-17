<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Convocatoria de Fondos (FundingOpportunity).
 *
 * ESTRUCTURA:
 * Entidad pivote del modulo de fondos. Representa una convocatoria de
 * subvencion o fondo europeo (Kit Digital, PRTR, FSE+, Erasmus+, etc.).
 * Almacena organismo convocante, programa, importe maximo, plazos,
 * requisitos y documentacion requerida.
 *
 * LOGICA:
 * El campo status controla el ciclo de vida: upcoming -> open -> closed -> resolved.
 * El campo alert_days_before determina cuantos dias antes del deadline se
 * genera una alerta automatica al tenant. Las convocatorias se gestionan
 * manualmente o se importan via API.
 *
 * RELACIONES:
 * - FundingOpportunity -> Group (tenant_id): tenant propietario.
 * - FundingOpportunity -> User (uid): usuario creador.
 * - FundingOpportunity <- FundingApplication (opportunity_id): solicitudes asociadas.
 *
 * @ContentEntityType(
 *   id = "funding_opportunity",
 *   label = @Translation("Convocatoria de Fondos"),
 *   label_collection = @Translation("Convocatorias de Fondos"),
 *   label_singular = @Translation("convocatoria de fondos"),
 *   label_plural = @Translation("convocatorias de fondos"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_funding\ListBuilder\FundingOpportunityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_funding\Form\FundingOpportunityForm",
 *       "add" = "Drupal\jaraba_funding\Form\FundingOpportunityForm",
 *       "edit" = "Drupal\jaraba_funding\Form\FundingOpportunityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_funding\Access\FundingOpportunityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "funding_opportunity",
 *   admin_permission = "administer funding",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/funding-opportunities",
 *     "add-form" = "/admin/content/funding-opportunities/add",
 *     "canonical" = "/admin/content/funding-opportunities/{funding_opportunity}",
 *     "edit-form" = "/admin/content/funding-opportunities/{funding_opportunity}/edit",
 *     "delete-form" = "/admin/content/funding-opportunities/{funding_opportunity}/delete",
 *   },
 *   field_ui_base_route = "jaraba_funding.funding_opportunity.settings",
 * )
 */
class FundingOpportunity extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // --- BLOQUE 1: Identificacion ---

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant propietario de esta convocatoria.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Nombre'))
      ->setDescription(new TranslatableMarkup('Nombre de la convocatoria de fondos.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- BLOQUE 2: Datos del organismo ---

    $fields['funding_body'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Organismo convocante'))
      ->setDescription(new TranslatableMarkup('Entidad que convoca (Red.es, SEPE, Junta de Andalucia, UE).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['program'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Programa'))
      ->setDescription(new TranslatableMarkup('Programa marco (Kit Digital, FSE+, PRTR, Erasmus+).'))
      ->setSetting('max_length', 100)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- BLOQUE 3: Importes ---

    $fields['max_amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Importe maximo'))
      ->setDescription(new TranslatableMarkup('Importe maximo de la convocatoria en euros.'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- BLOQUE 4: Plazos ---

    $fields['deadline'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fecha limite'))
      ->setDescription(new TranslatableMarkup('Plazo limite de solicitud.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['alert_days_before'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Dias de alerta'))
      ->setDescription(new TranslatableMarkup('Dias antes del deadline para generar alerta.'))
      ->setDefaultValue(15)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- BLOQUE 5: Requisitos ---

    $fields['requirements'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Requisitos'))
      ->setDescription(new TranslatableMarkup('Requisitos de elegibilidad en formato estructurado.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['documentation_required'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Documentacion requerida'))
      ->setDescription(new TranslatableMarkup('Lista de documentos necesarios para la solicitud.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- BLOQUE 6: Estado y clasificacion ---

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado'))
      ->setDescription(new TranslatableMarkup('Estado actual de la convocatoria.'))
      ->setRequired(TRUE)
      ->setDefaultValue('upcoming')
      ->setSetting('allowed_values', [
        'upcoming' => 'Proxima',
        'open' => 'Abierta',
        'closed' => 'Cerrada',
        'resolved' => 'Resuelta',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['url'] = BaseFieldDefinition::create('link')
      ->setLabel(new TranslatableMarkup('URL oficial'))
      ->setDescription(new TranslatableMarkup('Enlace a la convocatoria oficial.'))
      ->setSetting('link_type', 16)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- BLOQUE 7: Notas ---

    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Notas internas'))
      ->setDescription(new TranslatableMarkup('Notas internas del equipo sobre esta convocatoria.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- BLOQUE 8: Metadatos ---

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Fecha de creacion'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Fecha de modificacion'));

    return $fields;
  }

}
