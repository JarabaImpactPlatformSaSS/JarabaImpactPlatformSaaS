<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the EntrepreneurLearning entity.
 *
 * Documenta los resultados de experimentos según Testing Business Ideas.
 * Vincula Test Cards completados con sus aprendizajes y decisiones.
 *
 * @ContentEntityType(
 *   id = "entrepreneur_learning",
 *   label = @Translation("Aprendizaje de Emprendedor"),
 *   label_collection = @Translation("Aprendizajes"),
 *   label_singular = @Translation("aprendizaje"),
 *   label_plural = @Translation("aprendizajes"),
 *   label_count = @PluralTranslation(
 *     singular = "@count aprendizaje",
 *     plural = "@count aprendizajes",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "entrepreneur_learning",
 *   admin_permission = "administer entrepreneur learnings",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "key_insight",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/learnings",
 *     "add-form" = "/admin/content/learnings/add",
 *     "canonical" = "/admin/content/learnings/{entrepreneur_learning}",
 *     "edit-form" = "/admin/content/learnings/{entrepreneur_learning}/edit",
 *     "delete-form" = "/admin/content/learnings/{entrepreneur_learning}/delete",
 *   },
 *   field_ui_base_route = "entity.entrepreneur_learning.settings",
 * )
 */
class EntrepreneurLearning extends ContentEntityBase implements EntrepreneurLearningInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     */
    public function label()
    {
        $insight = $this->get('key_insight')->value ?? '';
        return mb_strlen($insight) > 80 ? mb_substr($insight, 0, 77) . '...' : $insight;
    }

    /**
     * {@inheritdoc}
     */
    public function getHypothesis(): string
    {
        return $this->get('hypothesis')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getKeyInsight(): string
    {
        return $this->get('key_insight')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function isValidated(): ?bool
    {
        $value = $this->get('validated')->value;
        if ($value === NULL || $value === '') {
            return NULL;
        }
        return (bool) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getDecision(): string
    {
        return $this->get('decision')->value ?? 'iterate';
    }

    /**
     * {@inheritdoc}
     */
    public function getBmcBlock(): string
    {
        return $this->get('bmc_block')->value ?? 'VP';
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['entrepreneur_profile'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Perfil de Emprendedor'))
            ->setDescription(t('El emprendedor que documentó este aprendizaje.'))
            ->setSetting('target_type', 'entrepreneur_profile')
            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['test_card_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('ID Test Card'))
            ->setDescription(t('Identificador del Test Card original.'))
            ->setSetting('max_length', 50)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['hypothesis'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Hipótesis Probada'))
            ->setDescription(t('El enunciado de la hipótesis que se experimentó.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['validated'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Resultado'))
            ->setDescription(t('¿Se validó la hipótesis?'))
            ->setSetting('allowed_values', [
                '1' => t('Validada ✓'),
                '0' => t('Invalidada ✗'),
                '' => t('Inconcluso ?'),
            ])
            ->setDefaultValue('')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['key_insight'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Aprendizaje Clave'))
            ->setDescription(t('El insight principal obtenido del experimento.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['observations'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Observaciones'))
            ->setDescription(t('Datos y observaciones del experimento.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['customer_quotes'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Citas de Clientes'))
            ->setDescription(t('Frases textuales de clientes durante el experimento.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['decision'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Decisión'))
            ->setDescription(t('Acción a tomar basada en los resultados.'))
            ->setSetting('allowed_values', [
                'persevere' => t('Perseverar - Continuar en la misma dirección'),
                'pivot' => t('Pivotar - Cambiar de rumbo'),
                'iterate' => t('Iterar - Más experimentos necesarios'),
            ])
            ->setDefaultValue('iterate')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['pivot_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Pivot'))
            ->setDescription(t('Si la decisión es pivotar, qué tipo de pivot.'))
            ->setSetting('allowed_values', [
                'customer_segment' => t('Segmento de Cliente'),
                'customer_need' => t('Necesidad del Cliente'),
                'value_capture' => t('Captura de Valor (monetización)'),
                'channel' => t('Canal'),
                'platform' => t('Plataforma'),
                'technology' => t('Tecnología'),
                'zoom_in' => t('Zoom-in (feature → producto)'),
                'zoom_out' => t('Zoom-out (producto → feature)'),
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['bmc_block'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Bloque BMC Afectado'))
            ->setDescription(t('Bloque del Business Model Canvas que impacta este aprendizaje.'))
            ->setSetting('allowed_values', [
                'CS' => t('Segmentos de Clientes'),
                'VP' => t('Propuesta de Valor'),
                'CH' => t('Canales'),
                'CR' => t('Relaciones con Clientes'),
                'RS' => t('Flujos de Ingresos'),
                'KR' => t('Recursos Clave'),
                'KA' => t('Actividades Clave'),
                'KP' => t('Socios Clave'),
                'C$' => t('Estructura de Costes'),
            ])
            ->setDefaultValue('VP')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['sample_size'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Tamaño de Muestra'))
            ->setDescription(t('Número de clientes/experimentos realizados.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['confidence'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Confianza'))
            ->setDescription(t('Nivel de confianza en el aprendizaje.'))
            ->setSetting('allowed_values', [
                'high' => t('Alta'),
                'medium' => t('Media'),
                'low' => t('Baja'),
            ])
            ->setDefaultValue('medium')
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'))
            ->setDescription(t('Fecha de documentación del aprendizaje.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'))
            ->setDescription(t('Fecha de última modificación.'));

        return $fields;
    }

}
