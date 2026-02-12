<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Funding Match (Match de Convocatoria).
 *
 * ESTRUCTURA:
 * Entidad que almacena los resultados del matching entre suscripciones
 * de usuarios y convocatorias de subvenciones. Incluye scores parciales
 * por cada dimension de matching (region, beneficiario, sector, tamano,
 * semantico) y un score global.
 *
 * LOGICA:
 * Los matches se generan automaticamente cuando se sincronizan nuevas
 * convocatorias. El overall_score combina los scores parciales segun
 * pesos configurables. El campo user_interest permite al usuario
 * marcar el match como interesado, aplicado o descartado.
 *
 * RELACIONES:
 * - FundingMatch -> FundingSubscription (subscription_id): suscripcion origen
 * - FundingMatch -> FundingCall (call_id): convocatoria matcheada
 * - FundingMatch -> Tenant (tenant_id): tenant propietario
 * - FundingMatch <- FundingAlert (match_id): alertas generadas
 *
 * @ContentEntityType(
 *   id = "funding_match",
 *   label = @Translation("Funding Match"),
 *   label_collection = @Translation("Funding Matches"),
 *   label_singular = @Translation("funding match"),
 *   label_plural = @Translation("funding matches"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "storage_schema" = "Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema",
 *     "access" = "Drupal\jaraba_funding\Access\FundingCallAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "funding_match",
 *   admin_permission = "administer jaraba funding",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/funding-matches/{funding_match}",
 *     "collection" = "/admin/content/funding-matches",
 *     "edit-form" = "/admin/content/funding-matches/{funding_match}/edit",
 *     "delete-form" = "/admin/content/funding-matches/{funding_match}/delete",
 *   },
 * )
 */
class FundingMatch extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // --- Subscription ---
    $fields['subscription_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Suscripcion'))
      ->setDescription(t('Suscripcion que genero este match.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'funding_subscription')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Call ---
    $fields['call_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Convocatoria'))
      ->setDescription(t('Convocatoria matcheada.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'funding_call')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Tenant ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de este registro.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Overall Score ---
    $fields['overall_score'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Score Global'))
      ->setDescription(t('Score global de matching (0-100).'))
      ->setRequired(TRUE)
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('view', TRUE);

    // --- Region Score ---
    $fields['region_score'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Score Region'))
      ->setDescription(t('Score de coincidencia por region.'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('view', TRUE);

    // --- Beneficiary Score ---
    $fields['beneficiary_score'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Score Beneficiario'))
      ->setDescription(t('Score de coincidencia por tipo de beneficiario.'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('view', TRUE);

    // --- Sector Score ---
    $fields['sector_score'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Score Sector'))
      ->setDescription(t('Score de coincidencia por sector.'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('view', TRUE);

    // --- Size Score ---
    $fields['size_score'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Score Tamano'))
      ->setDescription(t('Score de coincidencia por tamano de empresa.'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('view', TRUE);

    // --- Semantic Score ---
    $fields['semantic_score'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Score Semantico'))
      ->setDescription(t('Score de similitud semantica.'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('view', TRUE);

    // --- Score Breakdown (JSON) ---
    $fields['score_breakdown'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Desglose de Score'))
      ->setDescription(t('JSON con detalles del calculo de score.'));

    // --- User Interest ---
    $fields['user_interest'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Interes del Usuario'))
      ->setDescription(t('Estado de interes del usuario en este match.'))
      ->setRequired(TRUE)
      ->setDefaultValue('new')
      ->setSetting('allowed_values', [
        'new' => 'Nuevo',
        'interested' => 'Interesado',
        'applied' => 'Solicitado',
        'dismissed' => 'Descartado',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Notes ---
    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notas'))
      ->setDescription(t('Notas del usuario sobre este match.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Metadatos ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creacion'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificacion'));

    return $fields;
  }

}
