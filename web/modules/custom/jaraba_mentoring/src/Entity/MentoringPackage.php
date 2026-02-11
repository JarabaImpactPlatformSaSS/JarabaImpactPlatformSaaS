<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Mentoring Package.
 *
 * Representa un paquete de mentoría ofrecido por un mentor.
 * Productos vendibles: sesiones individuales, packs, suscripciones.
 *
 * SPEC: 31_Emprendimiento_Mentoring_Core_v1
 *
 * @ContentEntityType(
 *   id = "mentoring_package",
 *   label = @Translation("Paquete de Mentoría"),
 *   label_collection = @Translation("Paquetes de Mentoría"),
 *   label_singular = @Translation("paquete de mentoría"),
 *   label_plural = @Translation("paquetes de mentoría"),
 *   label_count = @PluralTranslation(
 *     singular = "@count paquete",
 *     plural = "@count paquetes",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_mentoring\MentoringPackageListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_mentoring\Form\MentoringPackageForm",
 *       "add" = "Drupal\jaraba_mentoring\Form\MentoringPackageForm",
 *       "edit" = "Drupal\jaraba_mentoring\Form\MentoringPackageForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_mentoring\MentoringPackageAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "mentoring_package",
 *   admin_permission = "administer mentoring packages",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   links = {
 *     "collection" = "/admin/content/mentoring-packages",
 *     "add-form" = "/admin/content/mentoring-packages/add",
 *     "canonical" = "/admin/content/mentoring-package/{mentoring_package}",
 *     "edit-form" = "/admin/content/mentoring-package/{mentoring_package}/edit",
 *     "delete-form" = "/admin/content/mentoring-package/{mentoring_package}/delete",
 *   },
 *   field_ui_base_route = "entity.mentoring_package.settings",
 * )
 */
class MentoringPackage extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // === Referencia al Mentor ===
        $fields['mentor_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Mentor'))
            ->setDescription(t('Mentor que ofrece este paquete.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'mentor_profile')
            ->setDisplayOptions('view', ['weight' => -5])
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === Información del Paquete ===
        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setDescription(t('Nombre del paquete de mentoría.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', ['weight' => 0])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Descripción detallada de lo que incluye el paquete.'))
            ->setDisplayOptions('view', ['weight' => 1])
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['package_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Paquete'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'single_session' => 'Sesión Individual',
                'session_pack' => 'Pack de Sesiones',
                'monthly_subscription' => 'Suscripción Mensual',
                'intensive_program' => 'Programa Intensivo',
            ])
            ->setDefaultValue('single_session')
            ->setDisplayOptions('view', ['weight' => 2])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === Configuración de Sesiones ===
        $fields['sessions_included'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Sesiones Incluidas'))
            ->setDescription(t('Número de sesiones incluidas en el paquete.'))
            ->setRequired(TRUE)
            ->setDefaultValue(1)
            ->setSetting('min', 1)
            ->setDisplayOptions('view', ['weight' => 5])
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['session_duration_minutes'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Duración por Sesión (minutos)'))
            ->setRequired(TRUE)
            ->setDefaultValue(60)
            ->setSetting('min', 30)
            ->setDisplayOptions('view', ['weight' => 6])
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === Precios ===
        $fields['price'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Precio (€)'))
            ->setRequired(TRUE)
            ->setSetting('precision', 8)
            ->setSetting('scale', 2)
            ->setDisplayOptions('view', ['weight' => 10])
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['discount_percent'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Descuento (%)'))
            ->setDefaultValue(0)
            ->setSetting('min', 0)
            ->setSetting('max', 50)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 11,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // === Soporte Asíncrono ===
        $fields['includes_async_support'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Incluye Soporte Asíncrono'))
            ->setDescription(t('Acceso a mensajería fuera de sesiones.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 15,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['async_response_hours'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Tiempo de Respuesta (horas)'))
            ->setDescription(t('Tiempo máximo de respuesta para mensajes.'))
            ->setDefaultValue(48)
            ->setDisplayConfigurable('form', TRUE);

        // === Estado ===
        $fields['is_published'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Publicado'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 20,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['is_featured'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Destacado'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 21,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // === Estadísticas ===
        $fields['total_sold'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Total Vendidos'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        // === Timestamps ===
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

    /**
     * Gets the mentor profile.
     */
    public function getMentor(): ?MentorProfile
    {
        $mentor = $this->get('mentor_id')->entity;
        return $mentor instanceof MentorProfile ? $mentor : NULL;
    }

    /**
     * Gets the package price.
     */
    public function getPrice(): float
    {
        return (float) ($this->get('price')->value ?? 0);
    }

    /**
     * Gets the number of sessions included.
     */
    public function getSessionsIncluded(): int
    {
        return (int) ($this->get('sessions_included')->value ?? 1);
    }

}
