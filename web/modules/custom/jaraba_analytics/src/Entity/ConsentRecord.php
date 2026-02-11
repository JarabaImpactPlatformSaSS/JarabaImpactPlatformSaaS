<?php

namespace Drupal\jaraba_analytics\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad ConsentRecord.
 *
 * Almacena registros de consentimiento GDPR/RGPD para el
 * Consent Management Platform (CMP) nativo.
 *
 * @ContentEntityType(
 *   id = "consent_record",
 *   label = @Translation("Consent Record"),
 *   label_collection = @Translation("Consent Records"),
 *   label_singular = @Translation("consent record"),
 *   label_plural = @Translation("consent records"),
 *   label_count = @PluralTranslation(
 *     singular = "@count consent record",
 *     plural = "@count consent records",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "consent_record",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class ConsentRecord extends ContentEntityBase implements ContentEntityInterface
{

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Tenant ID (multi-tenant isolation).
        $fields['tenant_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Tenant ID'))
            ->setDescription(t('ID del tenant al que pertenece este consentimiento.'))
            ->setRequired(FALSE);

        // Visitor ID (cookie persistente).
        $fields['visitor_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Visitor ID'))
            ->setDescription(t('ID único del visitante (cookie persistente).'))
            ->setSettings([
                'max_length' => 64,
            ])
            ->setRequired(TRUE);

        // Consentimiento para Analytics.
        $fields['consent_analytics'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Analytics'))
            ->setDescription(t('Permite tracking de analytics.'))
            ->setDefaultValue(FALSE);

        // Consentimiento para Marketing.
        $fields['consent_marketing'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Marketing'))
            ->setDescription(t('Permite tracking de marketing y retargeting.'))
            ->setDefaultValue(FALSE);

        // Consentimiento para Funcionales.
        $fields['consent_functional'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Functional'))
            ->setDescription(t('Permite cookies funcionales.'))
            ->setDefaultValue(FALSE);

        // Necesarias (siempre TRUE).
        $fields['consent_necessary'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Necessary'))
            ->setDescription(t('Cookies necesarias (siempre habilitadas).'))
            ->setDefaultValue(TRUE);

        // Versión de la política de privacidad.
        $fields['policy_version'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Policy Version'))
            ->setDescription(t('Versión de la política de privacidad aceptada.'))
            ->setSettings([
                'max_length' => 20,
            ])
            ->setDefaultValue('1.0');

        // Hash de IP (anonimizado GDPR).
        $fields['ip_hash'] = BaseFieldDefinition::create('string')
            ->setLabel(t('IP Hash'))
            ->setDescription(t('Hash SHA-256 de la IP del visitante.'))
            ->setSettings([
                'max_length' => 64,
            ]);

        // User-Agent (truncado por privacidad).
        $fields['user_agent'] = BaseFieldDefinition::create('string')
            ->setLabel(t('User Agent'))
            ->setDescription(t('User-Agent truncado del navegador.'))
            ->setSettings([
                'max_length' => 100,
            ]);

        // Timestamp de cuando se otorgó el consentimiento.
        $fields['granted_at'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Granted At'))
            ->setDescription(t('Fecha y hora cuando se otorgó el consentimiento.'));

        // Timestamp de última actualización.
        $fields['updated_at'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Updated At'))
            ->setDescription(t('Fecha y hora de última modificación.'));

        return $fields;
    }

    /**
     * Verificar si tiene consentimiento para una categoría.
     *
     * @param string $category
     *   Categoría: 'analytics', 'marketing', 'functional', 'necessary'.
     *
     * @return bool
     *   TRUE si tiene consentimiento.
     */
    public function hasConsent(string $category): bool
    {
        $field_name = 'consent_' . $category;
        if ($this->hasField($field_name)) {
            return (bool) $this->get($field_name)->value;
        }
        return FALSE;
    }

    /**
     * Obtener todas las categorías de consentimiento.
     *
     * @return array
     *   Array asociativo con estado de cada categoría.
     */
    public function getAllConsents(): array
    {
        return [
            'necessary' => TRUE,
            'functional' => (bool) $this->get('consent_functional')->value,
            'analytics' => (bool) $this->get('consent_analytics')->value,
            'marketing' => (bool) $this->get('consent_marketing')->value,
        ];
    }

}
