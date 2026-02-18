<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad InteractiveResult.
 *
 * Almacena los resultados de un usuario al completar contenido interactivo.
 * Incluye respuestas, puntuación, tiempo empleado y estado de completado.
 *
 * @ContentEntityType(
 *   id = "interactive_result",
 *   label = @Translation("Resultado Interactivo"),
 *   label_collection = @Translation("Resultados Interactivos"),
 *   label_singular = @Translation("resultado interactivo"),
 *   label_plural = @Translation("resultados interactivos"),
 *   label_count = @PluralTranslation(
 *     singular = "@count resultado interactivo",
 *     plural = "@count resultados interactivos",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_interactive\InteractiveResultListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_interactive\InteractiveResultAccessControlHandler",
 *   },
 *   base_table = "interactive_result",
 *   admin_permission = "administer interactive content",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/interactive-result/{interactive_result}",
 *     "collection" = "/admin/content/interactive-results",
 *   },
 *   field_ui_base_route = "entity.interactive_result.settings",
 * )
 */
class InteractiveResult extends ContentEntityBase implements EntityOwnerInterface, InteractiveResultInterface
{

    use EntityOwnerTrait;

    /**
     * Obtiene el contenido interactivo asociado.
     *
     * @return \Drupal\jaraba_interactive\Entity\InteractiveContentInterface|null
     *   La entidad de contenido interactivo o NULL.
     */
    public function getInteractiveContent(): ?InteractiveContentInterface
    {
        return $this->get('content_id')->entity;
    }

    /**
     * Obtiene la puntuación del usuario.
     *
     * @return float
     *   La puntuación obtenida.
     */
    public function getScore(): float
    {
        return (float) ($this->get('score')->value ?? 0);
    }

    /**
     * Verifica si el usuario aprobó.
     *
     * @return bool
     *   TRUE si aprobó, FALSE en caso contrario.
     */
    public function hasPassed(): bool
    {
        return (bool) ($this->get('passed')->value ?? FALSE);
    }

    /**
     * Obtiene los datos de respuesta como array.
     *
     * @return array
     *   Los datos JSON decodificados.
     */
    public function getResponseData(): array
    {
        $value = $this->get('response_data')->value;
        return $value ? json_decode($value, TRUE) : [];
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Añadir trait fields (uid como user_id).
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Usuario'))
            ->setDescription(t('El usuario que realizó esta actividad.'))
            ->setSetting('target_type', 'user')
            ->setRequired(TRUE)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'entity_reference_label',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['content_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Contenido'))
            ->setDescription(t('El contenido interactivo completado.'))
            ->setSetting('target_type', 'interactive_content')
            ->setRequired(TRUE)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'entity_reference_label',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['enrollment_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('ID de Inscripción'))
            ->setDescription(t('Referencia a la inscripción LMS asociada.'))
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['response_data'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Datos de Respuesta'))
            ->setDescription(t('Respuestas del usuario en formato JSON.'))
            ->setDisplayConfigurable('view', TRUE);

        $fields['score'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Puntuación'))
            ->setDescription(t('Puntuación obtenida (0-100).'))
            ->setSetting('precision', 5)
            ->setSetting('scale', 2)
            ->setDefaultValue(0)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_decimal',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['max_score'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Puntuación Máxima'))
            ->setDescription(t('La puntuación máxima posible.'))
            ->setSetting('precision', 5)
            ->setSetting('scale', 2)
            ->setDefaultValue(100)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['passed'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Aprobado'))
            ->setDescription(t('Indica si el usuario superó el contenido.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'boolean',
                'weight' => 6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['attempts'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Intentos'))
            ->setDescription(t('Número de intentos realizados.'))
            ->setDefaultValue(1)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_integer',
                'weight' => 7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['time_spent'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Tiempo Empleado'))
            ->setDescription(t('Tiempo total en segundos.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_integer',
                'weight' => 8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['completed'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Completado'))
            ->setDescription(t('Indica si el contenido fue completado.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'boolean',
                'weight' => 9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['completed_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Fecha de Completado'))
            ->setDescription(t('Cuando se completó el contenido.'))
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'timestamp',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'))
            ->setDescription(t('La marca temporal de creación.'));

        return $fields;
    }

}
