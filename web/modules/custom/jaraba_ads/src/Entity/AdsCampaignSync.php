<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Campaña Sincronizada.
 *
 * ESTRUCTURA:
 * Entidad que representa una campaña publicitaria sincronizada desde
 * una plataforma externa. Almacena la configuración de la campaña
 * (nombre, tipo, presupuesto, objetivo, targeting) y su estado de
 * sincronización.
 *
 * LÓGICA:
 * Una AdsCampaignSync pertenece a una AdsAccount y a un tenant.
 * El external_campaign_id vincula con el ID nativo de la plataforma.
 * La sincronización periódica actualiza estado, presupuestos y targeting.
 *
 * RELACIONES:
 * - AdsCampaignSync -> AdsAccount (account_id): cuenta vinculada
 * - AdsCampaignSync -> Tenant (tenant_id): tenant propietario
 * - AdsCampaignSync <- AdsMetricsDaily (campaign_id): métricas diarias
 * - AdsCampaignSync <- AdsSyncService: sincronizado por
 *
 * @ContentEntityType(
 *   id = "ads_campaign_sync",
 *   label = @Translation("Campana Sincronizada"),
 *   label_collection = @Translation("Campanas Sincronizadas"),
 *   label_singular = @Translation("campana sincronizada"),
 *   label_plural = @Translation("campanas sincronizadas"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_ads\ListBuilder\AdsCampaignSyncListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_ads\Form\AdsCampaignSyncForm",
 *       "add" = "Drupal\jaraba_ads\Form\AdsCampaignSyncForm",
 *       "edit" = "Drupal\jaraba_ads\Form\AdsCampaignSyncForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_ads\Access\AdsCampaignSyncAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ads_campaign_sync",
 *   fieldable = TRUE,
 *   admin_permission = "administer ads settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "campaign_name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/ads-campaigns-sync/{ads_campaign_sync}",
 *     "add-form" = "/admin/content/ads-campaigns-sync/add",
 *     "edit-form" = "/admin/content/ads-campaigns-sync/{ads_campaign_sync}/edit",
 *     "delete-form" = "/admin/content/ads-campaigns-sync/{ads_campaign_sync}/delete",
 *     "collection" = "/admin/content/ads-campaigns-sync",
 *   },
 *   field_ui_base_route = "entity.ads_campaign_sync.settings",
 * )
 */
class AdsCampaignSync extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- Tenant ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de esta campaña sincronizada.'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Cuenta de ads ---
    $fields['account_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Cuenta de Ads'))
      ->setDescription(t('Cuenta de ads asociada a esta campaña.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'ads_account')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- ID externo de la campaña ---
    $fields['external_campaign_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID Externo de Campaña'))
      ->setDescription(t('Identificador de la campaña en la plataforma de ads.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Nombre de campaña ---
    $fields['campaign_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre de Campaña'))
      ->setDescription(t('Nombre de la campaña sincronizada desde la plataforma.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Tipo de campaña ---
    $fields['campaign_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Campaña'))
      ->setDescription(t('Tipo de campaña publicitaria.'))
      ->setSetting('allowed_values', [
        'search' => t('Búsqueda'),
        'display' => t('Display'),
        'video' => t('Vídeo'),
        'shopping' => t('Shopping'),
        'social' => t('Social'),
        'app' => t('Aplicación'),
      ])
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Estado ---
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual de la campaña en la plataforma.'))
      ->setSetting('allowed_values', [
        'active' => t('Activa'),
        'paused' => t('Pausada'),
        'ended' => t('Finalizada'),
        'draft' => t('Borrador'),
      ])
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Presupuesto diario ---
    $fields['daily_budget'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Presupuesto Diario'))
      ->setDescription(t('Presupuesto diario de la campaña.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Presupuesto total ---
    $fields['lifetime_budget'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Presupuesto Total'))
      ->setDescription(t('Presupuesto total de la campaña durante todo su ciclo de vida.'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Moneda ---
    $fields['currency'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Moneda'))
      ->setDescription(t('Código ISO de la moneda del presupuesto.'))
      ->setSetting('max_length', 3)
      ->setDefaultValue('EUR')
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Fecha de inicio ---
    $fields['start_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Inicio'))
      ->setDescription(t('Fecha de inicio programada de la campaña.'))
      ->setDisplayOptions('form', ['weight' => 9])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Fecha de fin ---
    $fields['end_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Fin'))
      ->setDescription(t('Fecha de fin programada de la campaña.'))
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Objetivo ---
    $fields['objective'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Objetivo'))
      ->setDescription(t('Objetivo de la campaña (conversiones, tráfico, awareness, etc.).'))
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Resumen de targeting ---
    $fields['targeting_summary'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Resumen de Targeting'))
      ->setDescription(t('Resumen de la configuración de targeting en formato JSON.'))
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Última sincronización ---
    $fields['last_synced_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Última Sincronización'))
      ->setDescription(t('Timestamp de la última sincronización exitosa.'))
      ->setDisplayOptions('form', ['weight' => 13])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Metadatos ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creación'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificación'));

    return $fields;
  }

}
