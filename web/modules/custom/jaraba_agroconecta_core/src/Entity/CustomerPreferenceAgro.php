<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad CustomerPreferenceAgro.
 *
 * Preferencias zero-party del consumidor, capturadas de forma declarada
 * (formularios), inferida (comportamiento) o por historial de compras.
 * Alimenta las recomendaciones personalizadas del Sales Agent.
 * Referencia: Doc 68 — Sales Agent v1.
 *
 * @ContentEntityType(
 *   id = "customer_preference_agro",
 *   label = @Translation("Preferencia de Cliente"),
 *   label_collection = @Translation("Preferencias de Clientes"),
 *   label_singular = @Translation("preferencia de cliente"),
 *   label_plural = @Translation("preferencias de clientes"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "customer_preference_agro",
 *   admin_permission = "administer agroconecta",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-customer-preferences/{customer_preference_agro}",
 *     "collection" = "/admin/content/agro-customer-preferences",
 *   },
 *   field_ui_base_route = "entity.customer_preference_agro.settings",
 * )
 */
class CustomerPreferenceAgro extends ContentEntityBase implements EntityChangedInterface
{

    use EntityChangedTrait;

    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['customer_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Cliente'))
            ->setSetting('target_type', 'user')
            ->setRequired(TRUE);

        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setSetting('target_type', 'tenant')
            ->setRequired(TRUE);

        $fields['preference_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de preferencia'))
            ->setSetting('allowed_values', [
                'dietary' => t('Dietética'),
                'taste' => t('Gusto/sabor'),
                'budget' => t('Presupuesto'),
                'occasion' => t('Ocasión'),
                'origin' => t('Origen preferido'),
                'certification' => t('Certificación'),
                'allergen' => t('Alérgeno a evitar'),
                'packaging' => t('Preferencia de formato'),
            ])
            ->setRequired(TRUE);

        $fields['preference_key'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Clave'))
            ->setDescription(t('Clave específica: ej. "gluten_free", "organic", "budget_max".'))
            ->setSetting('max_length', 128)
            ->setRequired(TRUE);

        $fields['preference_value'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Valor'))
            ->setDescription(t('Valor de la preferencia: ej. "true", "50", "Córdoba".'))
            ->setSetting('max_length', 255)
            ->setRequired(TRUE);

        $fields['source'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Fuente'))
            ->setSetting('allowed_values', [
                'declared' => t('Declarada por el usuario'),
                'inferred' => t('Inferida por comportamiento'),
                'purchase' => t('Historial de compras'),
                'conversation' => t('Conversación con agente'),
            ])
            ->setRequired(TRUE)
            ->setDefaultValue('declared');

        $fields['confidence'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Confianza'))
            ->setDescription(t('Nivel de confianza 0.0-1.0 (1.0 = declarada).'))
            ->setSetting('precision', 3)
            ->setSetting('scale', 2)
            ->setDefaultValue('1.00');

        $fields['is_active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activa'))
            ->setDefaultValue(TRUE);

        $fields['last_verified'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Última verificación'))
            ->setDescription(t('Cuándo se confirmó por última vez esta preferencia.'));

        $fields['created'] = BaseFieldDefinition::create('created')->setLabel(t('Creado'));
        $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Modificado'));

        return $fields;
    }

    public function getPreferenceType(): string
    {
        return $this->get('preference_type')->value ?? '';
    }

    public function getPreferenceKey(): string
    {
        return $this->get('preference_key')->value ?? '';
    }

    public function getPreferenceValue(): string
    {
        return $this->get('preference_value')->value ?? '';
    }

    public function getSource(): string
    {
        return $this->get('source')->value ?? 'declared';
    }

    public function getConfidence(): float
    {
        return (float) ($this->get('confidence')->value ?? 1.0);
    }

    public function isActive(): bool
    {
        return (bool) ($this->get('is_active')->value ?? TRUE);
    }
}
