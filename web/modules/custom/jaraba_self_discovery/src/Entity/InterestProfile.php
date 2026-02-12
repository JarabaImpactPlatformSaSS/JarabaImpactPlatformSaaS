<?php

declare(strict_types=1);

namespace Drupal\jaraba_self_discovery\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad InterestProfile para perfiles RIASEC.
 *
 * Almacena los resultados del test de intereses vocacionales RIASEC
 * (Holland) como Content Entity, permitiendo Field UI, Views y
 * consultas estructuradas.
 *
 * @ContentEntityType(
 *   id = "interest_profile",
 *   label = @Translation("Perfil de Intereses RIASEC"),
 *   label_collection = @Translation("Perfiles de Intereses RIASEC"),
 *   label_singular = @Translation("perfil de intereses"),
 *   label_plural = @Translation("perfiles de intereses"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_self_discovery\ListBuilder\InterestProfileListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_self_discovery\Access\InterestProfileAccessControlHandler",
 *   },
 *   base_table = "interest_profile",
 *   admin_permission = "administer self discovery",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.interest_profile.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "user_id",
 *     "label" = "riasec_code",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/interest-profile/{interest_profile}",
 *     "add-form" = "/admin/content/interest-profiles/add",
 *     "edit-form" = "/admin/content/interest-profile/{interest_profile}/edit",
 *     "delete-form" = "/admin/content/interest-profile/{interest_profile}/delete",
 *     "collection" = "/admin/content/interest-profiles",
 *   },
 * )
 */
class InterestProfile extends ContentEntityBase implements EntityOwnerInterface
{

    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Campo owner (usuario propietario).
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Usuario'))
            ->setDescription(t('Usuario al que pertenece este perfil.'))
            ->setSetting('target_type', 'user')
            ->setRequired(TRUE)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'entity_reference_label',
            ])
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Codigo RIASEC (3 letras, ej: "RIA").
        $fields['riasec_code'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Codigo RIASEC'))
            ->setDescription(t('Codigo de 3 letras del perfil RIASEC (ej: RIA).'))
            ->setSettings([
                'max_length' => 3,
            ])
            ->setRequired(TRUE)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'string',
                'weight' => 0,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Puntuaciones por tipo RIASEC (0-100).
        $types = [
            'realistic' => t('Realista'),
            'investigative' => t('Investigador'),
            'artistic' => t('Artistico'),
            'social' => t('Social'),
            'enterprising' => t('Emprendedor'),
            'conventional' => t('Convencional'),
        ];

        $weight = 1;
        foreach ($types as $key => $label) {
            $fields["score_$key"] = BaseFieldDefinition::create('integer')
                ->setLabel($label)
                ->setDescription(t('Puntuacion de 0 a 100 para @type.', ['@type' => $label]))
                ->setSettings([
                    'min' => 0,
                    'max' => 100,
                ])
                ->setDefaultValue(0)
                ->setDisplayOptions('view', [
                    'label' => 'inline',
                    'type' => 'number_integer',
                    'weight' => $weight,
                ])
                ->setDisplayOptions('form', [
                    'type' => 'number',
                    'weight' => $weight,
                ])
                ->setDisplayConfigurable('form', TRUE)
                ->setDisplayConfigurable('view', TRUE);

            $weight++;
        }

        // Tipos dominantes (JSON array).
        $fields['dominant_types'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Tipos dominantes'))
            ->setDescription(t('JSON array con los tipos dominantes.'))
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'basic_string',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('view', TRUE);

        // Respuestas (JSON de las respuestas del test).
        $fields['answers'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Respuestas'))
            ->setDescription(t('JSON con las respuestas individuales del test.'))
            ->setDisplayConfigurable('view', TRUE);

        // Carreras sugeridas (JSON array).
        $fields['suggested_careers'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Carreras sugeridas'))
            ->setDescription(t('JSON array con las carreras sugeridas para este perfil.'))
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'basic_string',
                'weight' => 12,
            ])
            ->setDisplayConfigurable('view', TRUE);

        // Timestamp de creacion.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha de creacion'))
            ->setDescription(t('Fecha en que se completo el test.'))
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'timestamp',
                'weight' => 20,
            ])
            ->setDisplayConfigurable('view', TRUE);

        // Timestamp de actualizacion.
        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Ultima actualizacion'))
            ->setDescription(t('Fecha de ultima modificacion.'))
            ->setDisplayConfigurable('view', TRUE);

        return $fields;
    }

    /**
     * Obtiene el codigo RIASEC.
     */
    public function getRiasecCode(): string
    {
        return (string) $this->get('riasec_code')->value;
    }

    /**
     * Obtiene todas las puntuaciones RIASEC como array.
     */
    public function getAllScores(): array
    {
        return [
            'R' => (int) $this->get('score_realistic')->value,
            'I' => (int) $this->get('score_investigative')->value,
            'A' => (int) $this->get('score_artistic')->value,
            'S' => (int) $this->get('score_social')->value,
            'E' => (int) $this->get('score_enterprising')->value,
            'C' => (int) $this->get('score_conventional')->value,
        ];
    }

    /**
     * Obtiene los tipos dominantes.
     */
    public function getDominantTypes(): array
    {
        $raw = $this->get('dominant_types')->value;
        return $raw ? (json_decode($raw, TRUE) ?? []) : [];
    }

    /**
     * Obtiene las carreras sugeridas.
     */
    public function getSuggestedCareers(): array
    {
        $raw = $this->get('suggested_careers')->value;
        return $raw ? (json_decode($raw, TRUE) ?? []) : [];
    }

}
