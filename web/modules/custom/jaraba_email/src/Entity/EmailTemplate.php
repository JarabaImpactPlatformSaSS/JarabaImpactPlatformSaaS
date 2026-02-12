<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Plantilla de Email.
 *
 * PROPÓSITO:
 * Almacena plantillas MJML con compilación automática a HTML.
 * Permite reutilizar diseños en múltiples campañas.
 *
 * CATEGORÍAS:
 * - onboarding: Emails de bienvenida
 * - nurture: Serie de nutrición de leads
 * - transactional: Confirmaciones, recibos, etc.
 * - newsletter: Boletines informativos
 * - promotional: Ofertas y promociones
 * - reengagement: Recuperación de usuarios inactivos
 *
 * VERTICALES:
 * - all: Plantilla universal
 * - empleabilidad, emprendimiento, agroconecta, etc.
 *
 * MJML:
 * El campo mjml_content almacena el código MJML que se
 * compila automáticamente a HTML en html_compiled.
 *
 * VARIABLES:
 * Campo JSON que documenta las variables disponibles
 * para personalización (ej: {{subscriber.first_name}}).
 *
 * ESPECIFICACIÓN: Doc 139 - Email_Marketing_Technical_Guide
 *
 * @ContentEntityType(
 *   id = "email_template",
 *   label = @Translation("Plantilla de Email"),
 *   label_collection = @Translation("Plantillas de Email"),
 *   label_singular = @Translation("plantilla"),
 *   label_plural = @Translation("plantillas"),
 *   label_count = @PluralTranslation(
 *     singular = "@count plantilla",
 *     plural = "@count plantillas",
 *   ),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\jaraba_email\EmailTemplateListBuilder",
 *     "form" = {
 *       "add" = "Drupal\jaraba_email\Form\EmailTemplateForm",
 *       "edit" = "Drupal\jaraba_email\Form\EmailTemplateForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_email\EmailAccessControlHandler",
 *   },
 *   base_table = "email_template",
 *   admin_permission = "administer email templates",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/jaraba/email/templates/{email_template}",
 *     "add-form" = "/admin/jaraba/email/templates/add",
 *     "edit-form" = "/admin/jaraba/email/templates/{email_template}/edit",
 *     "delete-form" = "/admin/jaraba/email/templates/{email_template}/delete",
 *     "collection" = "/admin/jaraba/email/templates",
 *   },
 * )
 */
class EmailTemplate extends ContentEntityBase implements EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     *
     * Define los campos base para la entidad EmailTemplate.
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        // Nombre de la plantilla.
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre de la Plantilla'))
            ->setRequired(TRUE)
            ->setSettings(['max_length' => 255])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ]);

        // Categoría de la plantilla.
        $fields['category'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Categoría'))
            ->setSettings([
                'allowed_values' => [
                    'onboarding' => 'Onboarding',
                    'nurture' => 'Nutrición',
                    'transactional' => 'Transaccional',
                    'newsletter' => 'Newsletter',
                    'promotional' => 'Promocional',
                    'reengagement' => 'Reactivación',
                ],
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 1,
            ]);

        // Vertical de negocio para la plantilla.
        $fields['vertical'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Vertical'))
            ->setDefaultValue('all')
            ->setSettings([
                'allowed_values' => [
                    'all' => 'Todas las Verticales',
                    'empleabilidad' => 'Empleabilidad',
                    'emprendimiento' => 'Emprendimiento',
                    'agroconecta' => 'AgroConecta',
                    'comercioconecta' => 'ComercioConecta',
                    'serviciosconecta' => 'ServiciosConecta',
                ],
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 2,
            ]);

        // Asunto por defecto del email.
        $fields['subject_line'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Asunto por Defecto'))
            ->setRequired(TRUE)
            ->setSettings(['max_length' => 255])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 3,
            ]);

        // Texto de vista previa (preheader).
        $fields['preview_text'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Texto de Vista Previa'))
            ->setSettings(['max_length' => 255])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 4,
            ]);

        // Contenido MJML de la plantilla.
        $fields['mjml_content'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Contenido MJML'))
            ->setDescription(t('Código MJML de la plantilla.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 5,
                'settings' => ['rows' => 20],
            ]);

        // HTML compilado automáticamente desde MJML.
        $fields['html_compiled'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('HTML Compilado'))
            ->setDescription(t('HTML auto-generado desde MJML.'));

        // Versión de texto plano.
        $fields['text_version'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Versión Texto Plano'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 6,
                'settings' => ['rows' => 10],
            ]);

        // Variables disponibles en formato JSON.
        $fields['variables'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Variables'))
            ->setDescription(t('Array JSON de variables disponibles.'));

        // URL de la miniatura de previsualización.
        $fields['thumbnail_url'] = BaseFieldDefinition::create('string')
            ->setLabel(t('URL de Miniatura'))
            ->setSettings(['max_length' => 500]);

        // Si es una plantilla de sistema (no editable por usuarios).
        $fields['is_system'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Plantilla de Sistema'))
            ->setDefaultValue(FALSE);

        // Estado activo/inactivo.
        $fields['is_active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activa'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 7,
            ]);

        // Versión de la plantilla (para versionado).
        $fields['version'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Versión'))
            ->setDefaultValue(1);

        // Referencia al tenant (NULL = plantilla global).
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('NULL = plantilla global.'))
            ->setSetting('target_type', 'group');

        // Timestamps automáticos.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creada'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificada'));

        return $fields;
    }

    /**
     * Obtiene el nombre de la plantilla.
     *
     * @return string
     *   El nombre de la plantilla.
     */
    public function getName(): string
    {
        return $this->get('name')->value ?? '';
    }

    /**
     * Obtiene el contenido MJML.
     *
     * @return string
     *   El código MJML de la plantilla.
     */
    public function getMjmlContent(): string
    {
        return $this->get('mjml_content')->value ?? '';
    }

    /**
     * Obtiene el HTML compilado.
     *
     * @return string
     *   El HTML generado desde MJML.
     */
    public function getHtmlCompiled(): string
    {
        return $this->get('html_compiled')->value ?? '';
    }

    /**
     * Establece el HTML compilado.
     *
     * @param string $html
     *   El HTML compilado a almacenar.
     *
     * @return static
     *   La instancia actual para encadenamiento.
     */
    public function setHtmlCompiled(string $html): static
    {
        $this->set('html_compiled', $html);
        return $this;
    }

    /**
     * Obtiene las variables disponibles.
     *
     * @return array
     *   Array de variables de la plantilla.
     */
    public function getVariables(): array
    {
        $json = $this->get('variables')->value;
        if (empty($json)) {
            return [];
        }
        return json_decode($json, TRUE) ?? [];
    }

}
