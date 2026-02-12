<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad CouponAgro.
 *
 * Representa un código promocional canjeable por el usuario. Cada cupón
 * está vinculado a una PromotionAgro y tiene su propio código único,
 * límites de uso y rango de validez independiente.
 *
 * @ContentEntityType(
 *   id = "coupon_agro",
 *   label = @Translation("Cupón Agro"),
 *   label_collection = @Translation("Cupones Agro"),
 *   label_singular = @Translation("cupón agro"),
 *   label_plural = @Translation("cupones agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\Entity\CouponAgroListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agroconecta_core\Form\CouponAgroForm",
 *       "add" = "Drupal\jaraba_agroconecta_core\Form\CouponAgroForm",
 *       "edit" = "Drupal\jaraba_agroconecta_core\Form\CouponAgroForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\CouponAgroAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "coupon_agro",
 *   admin_permission = "administer agroconecta",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.coupon_agro.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "code",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-coupons/{coupon_agro}",
 *     "add-form" = "/admin/content/agro-coupons/add",
 *     "edit-form" = "/admin/content/agro-coupons/{coupon_agro}/edit",
 *     "delete-form" = "/admin/content/agro-coupons/{coupon_agro}/delete",
 *     "collection" = "/admin/content/agro-coupons",
 *   },
 * )
 */
class CouponAgro extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        // Tenant ID.
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('Marketplace propietario de este cupón.'))
            ->setSetting('target_type', 'taxonomy_term')
            ->setSetting('handler_settings', ['target_bundles' => ['tenants' => 'tenants']])
            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        // Código del cupón (VERANO2026, BIENVENIDA10, etc.)
        $fields['code'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Código'))
            ->setDescription(t('Código único del cupón (ej: VERANO2026). El usuario introduce este código.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 64)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Referencia a la promoción que aplica.
        $fields['promotion_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Promoción asociada'))
            ->setDescription(t('Promoción que se aplica al canjear este cupón.'))
            ->setSetting('target_type', 'promotion_agro')
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Usos máximos totales de este cupón.
        $fields['max_uses'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Usos máximos'))
            ->setDescription(t('Número máximo de canjes (0 = ilimitado).'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Contador de canjes.
        $fields['current_uses'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Canjes actuales'))
            ->setDescription(t('Número de veces canjeado.'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('form', FALSE)
            ->setDisplayConfigurable('view', TRUE);

        // Usos por usuario.
        $fields['max_uses_per_user'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Usos por usuario'))
            ->setDescription(t('Máximo de canjes por usuario (0 = ilimitado).'))
            ->setDefaultValue(1)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Fecha de inicio.
        $fields['start_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Válido desde'))
            ->setDescription(t('Inicio de la validez del cupón.'))
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => -5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Fecha de fin.
        $fields['end_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Válido hasta'))
            ->setDescription(t('Fin de la validez. Si vacía, no caduca.'))
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => -4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Pedido mínimo (override del de la promoción).
        $fields['minimum_order'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Pedido mínimo (override)'))
            ->setDescription(t('Si > 0, overridea el mínimo de la promoción.'))
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDefaultValue('0.00')
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // ¿Activo?
        $fields['is_active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activo'))
            ->setDescription(t('Solo los cupones activos son canjeables.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => -2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Campos de sistema.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

    // ===================================================
    // Helpers
    // ===================================================

    /**
     * Obtiene el código del cupón.
     */
    public function getCode(): string
    {
        return strtoupper(trim($this->get('code')->value ?? ''));
    }

    /**
     * Obtiene la promoción asociada.
     *
     * @return \Drupal\jaraba_agroconecta_core\Entity\PromotionAgro|null
     */
    public function getPromotion(): ?PromotionAgro
    {
        $id = $this->get('promotion_id')->target_id;
        if ($id) {
            /** @var \Drupal\jaraba_agroconecta_core\Entity\PromotionAgro|null $promo */
            $promo = $this->entityTypeManager()->getStorage('promotion_agro')->load($id);
            return $promo;
        }
        return NULL;
    }

    /**
     * Indica si el cupón está activo.
     */
    public function isActive(): bool
    {
        return (bool) $this->get('is_active')->value;
    }

    /**
     * Indica si el cupón está vigente por fechas.
     */
    public function isWithinDateRange(): bool
    {
        $now = new \DateTime();
        $start = $this->get('start_date')->value;
        $end = $this->get('end_date')->value;

        if ($start && new \DateTime($start) > $now) {
            return FALSE;
        }
        if ($end && new \DateTime($end) < $now) {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Indica si quedan canjes disponibles.
     */
    public function hasUsesRemaining(): bool
    {
        $max = (int) $this->get('max_uses')->value;
        if ($max === 0) {
            return TRUE;
        }
        return (int) $this->get('current_uses')->value < $max;
    }

    /**
     * Incrementa el contador de canjes.
     */
    public function incrementUses(): self
    {
        $current = (int) $this->get('current_uses')->value;
        $this->set('current_uses', $current + 1);
        return $this;
    }

    /**
     * Obtiene el pedido mínimo efectivo (override o el de la promoción).
     */
    public function getEffectiveMinimumOrder(): float
    {
        $couponMin = (float) ($this->get('minimum_order')->value ?? 0);
        if ($couponMin > 0) {
            return $couponMin;
        }
        $promo = $this->getPromotion();
        return $promo ? $promo->getMinimumOrder() : 0.0;
    }

}
