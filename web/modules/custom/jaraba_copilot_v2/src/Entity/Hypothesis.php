<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Hypothesis entity.
 *
 * Hipótesis de negocio que el emprendedor debe validar mediante experimentos.
 * Sigue el formato: "Creo que [segmento] [hará acción] porque [razón]".
 *
 * @ContentEntityType(
 *   id = "hypothesis",
 *   label = @Translation("Hipótesis"),
 *   label_collection = @Translation("Hipótesis"),
 *   label_singular = @Translation("hipótesis"),
 *   label_plural = @Translation("hipótesis"),
 *   label_count = @PluralTranslation(
 *     singular = "@count hipótesis",
 *     plural = "@count hipótesis",
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
 *   base_table = "hypothesis",
 *   admin_permission = "administer hypotheses",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "statement",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/hypotheses",
 *     "add-form" = "/admin/content/hypotheses/add",
 *     "canonical" = "/admin/content/hypotheses/{hypothesis}",
 *     "edit-form" = "/admin/content/hypotheses/{hypothesis}/edit",
 *     "delete-form" = "/admin/content/hypotheses/{hypothesis}/delete",
 *   },
 *   field_ui_base_route = "entity.hypothesis.settings",
 * )
 */
class Hypothesis extends ContentEntityBase implements HypothesisInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     */
    public function label()
    {
        $statement = $this->get('statement')->value ?? '';
        return mb_strlen($statement) > 80 ? mb_substr($statement, 0, 77) . '...' : $statement;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatement(): string
    {
        return $this->get('statement')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return $this->get('hypothesis_type')->value ?? 'DESIRABILITY';
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
    public function getValidationStatus(): string
    {
        return $this->get('validation_status')->value ?? 'PENDING';
    }

    /**
     * {@inheritdoc}
     */
    public function getImportanceScore(): int
    {
        return (int) $this->get('importance_score')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getEvidenceScore(): int
    {
        return (int) $this->get('evidence_score')->value;
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
            ->setDescription(t('El emprendedor que creó esta hipótesis.'))
            ->setSetting('target_type', 'entrepreneur_profile')
            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['statement'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Enunciado'))
            ->setDescription(t('La hipótesis en formato: "Creo que [segmento] [hará acción] porque [razón]".'))
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['hypothesis_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Hipótesis'))
            ->setDescription(t('Clasificación según lo que valida.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'DESIRABILITY' => t('Deseabilidad - ¿Lo quieren?'),
                'FEASIBILITY' => t('Factibilidad - ¿Podemos hacerlo?'),
                'VIABILITY' => t('Viabilidad - ¿Es rentable?'),
            ])
            ->setDefaultValue('DESIRABILITY')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['bmc_block'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Bloque BMC'))
            ->setDescription(t('Bloque del Business Model Canvas que impacta.'))
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
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['importance_score'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Importancia'))
            ->setDescription(t('¿Qué tan crítica es esta hipótesis? (1-5)'))
            ->setDefaultValue(3)
            ->setSetting('min', 1)
            ->setSetting('max', 5)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['evidence_score'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Evidencia Actual'))
            ->setDescription(t('¿Cuánta evidencia tenemos? (1-5)'))
            ->setDefaultValue(1)
            ->setSetting('min', 1)
            ->setSetting('max', 5)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['validation_status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado de Validación'))
            ->setDescription(t('Estado actual de la hipótesis.'))
            ->setSetting('allowed_values', [
                'PENDING' => t('Pendiente'),
                'IN_PROGRESS' => t('En Progreso'),
                'VALIDATED' => t('Validada'),
                'INVALIDATED' => t('Invalidada'),
                'INCONCLUSIVE' => t('Inconcluso'),
            ])
            ->setDefaultValue('PENDING')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['suggested_experiment'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Experimento Sugerido'))
            ->setDescription(t('ID del experimento sugerido para validar.'))
            ->setSetting('max_length', 50)
            ->setDisplayConfigurable('form', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'))
            ->setDescription(t('Fecha de creación de la hipótesis.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'))
            ->setDescription(t('Fecha de última modificación.'));

        return $fields;
    }

}
