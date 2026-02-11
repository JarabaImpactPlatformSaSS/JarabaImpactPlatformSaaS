<?php

namespace Drupal\jaraba_ads\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Campaña Publicitaria.
 *
 * ESTRUCTURA:
 * Entidad central de jaraba_ads que representa una campaña de publicidad
 * pagada en plataformas externas (Google Ads, Meta Ads, LinkedIn Ads,
 * TikTok Ads). Almacena la configuración de la campaña (nombre, plataforma,
 * ID externo), presupuestos (diario, total, gastado), métricas de
 * rendimiento (impresiones, clics, conversiones, CTR, CPC, ROAS) y
 * periodo de ejecución (start_date, end_date).
 *
 * LÓGICA:
 * Una AdCampaign pertenece a un usuario (uid) y a un tenant (tenant_id).
 * El campo campaign_id_external vincula con el ID nativo de la plataforma
 * de ads para sincronización. El status controla el ciclo de vida:
 * draft -> active -> paused/completed. Los campos de métricas
 * (impressions, clicks, conversions, ctr, cpc, roas) se actualizan
 * mediante sincronización periódica con las APIs de ads.
 * El ROAS se calcula como revenue/spend, el CTR como clicks/impressions*100,
 * y el CPC como spend/clicks.
 *
 * RELACIONES:
 * - AdCampaign -> User (uid): usuario propietario
 * - AdCampaign -> Tenant (tenant_id): tenant propietario
 * - AdCampaign <- CampaignManagerService: gestionado por
 * - AdCampaign <- AdsAnalyticsService: analizado por
 * - AdCampaign <- AdCampaignListBuilder: listado en admin
 *
 * @ContentEntityType(
 *   id = "ad_campaign",
 *   label = @Translation("Campaña Publicitaria"),
 *   label_collection = @Translation("Campañas Publicitarias"),
 *   label_singular = @Translation("campaña publicitaria"),
 *   label_plural = @Translation("campañas publicitarias"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_ads\ListBuilder\AdCampaignListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_ads\Form\AdCampaignForm",
 *       "add" = "Drupal\jaraba_ads\Form\AdCampaignForm",
 *       "edit" = "Drupal\jaraba_ads\Form\AdCampaignForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_ads\Access\AdCampaignAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ad_campaign",
 *   admin_permission = "administer ads settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/ad-campaign/{ad_campaign}",
 *     "add-form" = "/admin/content/ad-campaign/add",
 *     "edit-form" = "/admin/content/ad-campaign/{ad_campaign}/edit",
 *     "delete-form" = "/admin/content/ad-campaign/{ad_campaign}/delete",
 *     "collection" = "/admin/content/ad-campaigns",
 *   },
 *   field_ui_base_route = "jaraba_ads.ad_campaign.settings",
 * )
 */
class AdCampaign extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // --- Identificación ---
    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre de Campaña'))
      ->setDescription(t('Nombre descriptivo de la campaña publicitaria.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Plataforma ---
    $fields['platform'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Plataforma'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'google_ads' => t('Google Ads'),
        'meta_ads' => t('Meta Ads'),
        'linkedin_ads' => t('LinkedIn Ads'),
        'tiktok_ads' => t('TikTok Ads'),
      ])
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- ID Externo de la Campaña ---
    $fields['campaign_id_external'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID Externo'))
      ->setDescription(t('Identificador nativo de la campaña en la plataforma de ads.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Estado ---
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setDefaultValue('draft')
      ->setSetting('allowed_values', [
        'draft' => t('Borrador'),
        'active' => t('Activa'),
        'paused' => t('Pausada'),
        'completed' => t('Completada'),
      ])
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Presupuestos ---
    $fields['budget_daily'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Presupuesto Diario (EUR)'))
      ->setDescription(t('Presupuesto máximo diario para la campaña.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['budget_total'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Presupuesto Total (EUR)'))
      ->setDescription(t('Presupuesto total acumulado para la campaña.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['spend_to_date'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Gasto Acumulado (EUR)'))
      ->setDescription(t('Gasto total acumulado de la campaña hasta la fecha.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Métricas de Rendimiento ---
    $fields['impressions'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Impresiones'))
      ->setDescription(t('Número total de impresiones de la campaña.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 15])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['clicks'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Clics'))
      ->setDescription(t('Número total de clics en anuncios de la campaña.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 16])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['conversions'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Conversiones'))
      ->setDescription(t('Número total de conversiones atribuidas a la campaña.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 17])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ctr'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('CTR (%)'))
      ->setDescription(t('Click-Through Rate: porcentaje de clics sobre impresiones.'))
      ->setSetting('precision', 8)
      ->setSetting('scale', 4)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 18])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['cpc'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('CPC (EUR)'))
      ->setDescription(t('Cost Per Click: coste medio por clic.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 4)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 19])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['roas'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('ROAS'))
      ->setDescription(t('Return On Ad Spend: ratio de retorno sobre inversión publicitaria.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 4)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 20])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Periodo ---
    $fields['start_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Inicio'))
      ->setDisplayOptions('form', ['weight' => 25])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['end_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Fin'))
      ->setDisplayOptions('form', ['weight' => 26])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Tenant ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'tenant')
      ->setDisplayOptions('form', ['weight' => 30])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Metadatos ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creación'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificación'));

    return $fields;
  }

  /**
   * Comprueba si la campaña está activa.
   *
   * ESTRUCTURA: Método helper que evalúa el campo status.
   * LÓGICA: Devuelve TRUE solo cuando el estado es 'active'.
   * RELACIONES: Consumido por CampaignManagerService.
   *
   * @return bool
   *   TRUE si la campaña tiene estado 'active'.
   */
  public function isActive(): bool {
    return $this->get('status')->value === 'active';
  }

  /**
   * Calcula el porcentaje de presupuesto consumido.
   *
   * ESTRUCTURA: Método helper que combina budget_total y spend_to_date.
   * LÓGICA: Si budget_total es 0, devuelve 0. En caso contrario,
   *   devuelve el porcentaje de gasto acumulado sobre el presupuesto total.
   * RELACIONES: Consumido por AdCampaignListBuilder y AdsAnalyticsService.
   *
   * @return float
   *   Porcentaje de presupuesto consumido (0-100+).
   */
  public function getBudgetUtilization(): float {
    $total = (float) $this->get('budget_total')->value;
    if ($total <= 0) {
      return 0.0;
    }
    $spend = (float) $this->get('spend_to_date')->value;
    return round(($spend / $total) * 100, 1);
  }

  /**
   * Recalcula las métricas derivadas (CTR, CPC) a partir de datos base.
   *
   * ESTRUCTURA: Método mutador sobre campos calculados.
   * LÓGICA: CTR = (clicks / impressions) * 100; CPC = spend / clicks.
   *   Si no hay impresiones o clics, los campos quedan en 0.
   * RELACIONES: Invocado antes de save() por CampaignManagerService.
   *
   * @return $this
   */
  public function recalculateMetrics(): self {
    $impressions = (int) $this->get('impressions')->value;
    $clicks = (int) $this->get('clicks')->value;
    $spend = (float) $this->get('spend_to_date')->value;

    // CTR = (clicks / impressions) * 100
    if ($impressions > 0) {
      $this->set('ctr', round(($clicks / $impressions) * 100, 4));
    }
    else {
      $this->set('ctr', 0);
    }

    // CPC = spend / clicks
    if ($clicks > 0) {
      $this->set('cpc', round($spend / $clicks, 4));
    }
    else {
      $this->set('cpc', 0);
    }

    return $this;
  }

}
