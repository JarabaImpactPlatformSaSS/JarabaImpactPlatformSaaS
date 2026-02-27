<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\ecosistema_jaraba_core\Entity\ReviewableEntityTrait;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad ReviewAgro.
 *
 * Representa una reseña de producto, productor o pedido en el marketplace
 * AgroConecta. Incluye verificación de compra, moderación y respuesta
 * del productor. Alimenta el social proof y el SEO del marketplace.
 *
 * @ContentEntityType(
 *   id = "review_agro",
 *   label = @Translation("Reseña Agro"),
 *   label_collection = @Translation("Reseñas Agro"),
 *   label_singular = @Translation("reseña agro"),
 *   label_plural = @Translation("reseñas agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\Entity\ReviewAgroListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agroconecta_core\Form\ReviewAgroForm",
 *       "add" = "Drupal\jaraba_agroconecta_core\Form\ReviewAgroForm",
 *       "edit" = "Drupal\jaraba_agroconecta_core\Form\ReviewAgroForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agroconecta_core\Access\ReviewAgroAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "review_agro",
 *   admin_permission = "administer agroconecta",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.review_agro.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-reviews/{review_agro}",
 *     "add-form" = "/admin/content/agro-reviews/add",
 *     "edit-form" = "/admin/content/agro-reviews/{review_agro}/edit",
 *     "delete-form" = "/admin/content/agro-reviews/{review_agro}/delete",
 *     "collection" = "/admin/content/agro-reviews",
 *   },
 * )
 */
class ReviewAgro extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;
    use ReviewableEntityTrait;

    /**
     * Tipos de reseña.
     */
    const TYPE_PRODUCT = 'product';
    const TYPE_PRODUCER = 'producer';
    const TYPE_ORDER = 'order';

    /**
     * Estados de moderación.
     */
    const STATE_PENDING = 'pending';
    const STATE_APPROVED = 'approved';
    const STATE_REJECTED = 'rejected';
    const STATE_FLAGGED = 'flagged';

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        // TENANT-BRIDGE-001: tenant_id como entity_reference a group.
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('Marketplace donde se publico la resena.'))
            ->setSetting('target_type', 'group')
            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        // Tipo de reseña: product, producer, order.
        $fields['type'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Tipo'))
            ->setDescription(t('Tipo de reseña: product, producer, order.'))
            ->setRequired(TRUE)
            ->setDefaultValue(self::TYPE_PRODUCT)
            ->setSetting('max_length', 16)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Tipo de entidad objetivo (product_agro, producer_profile, order_agro).
        $fields['target_entity_type'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Tipo de entidad objetivo'))
            ->setDescription(t('Tipo de entidad sobre la que se hace la reseña.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 32)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // ID de la entidad objetivo.
        $fields['target_entity_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('ID entidad objetivo'))
            ->setDescription(t('ID de la entidad sobre la que se hace la reseña.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Valoración numérica (1-5 estrellas).
        $fields['rating'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Valoración'))
            ->setDescription(t('Puntuación de 1 a 5 estrellas.'))
            ->setRequired(TRUE)
            ->setSetting('min', 1)
            ->setSetting('max', 5)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Título de la reseña (opcional).
        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setDescription(t('Título breve de la reseña.'))
            ->setSetting('max_length', 100)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Cuerpo de la reseña.
        $fields['body'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Texto de la reseña'))
            ->setDescription(t('Contenido completo de la reseña.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -5,
                'settings' => [
                    'rows' => 5,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // ¿Compra verificada?
        $fields['verified_purchase'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Compra verificada'))
            ->setDescription(t('Indica si el autor compró realmente el producto.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => -4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Estado de moderación.
        $fields['state'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Estado'))
            ->setDescription(t('Estado de moderación: pending, approved, rejected, flagged.'))
            ->setDefaultValue(self::STATE_PENDING)
            ->setSetting('max_length', 16)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Respuesta del productor.
        $fields['response'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Respuesta del productor'))
            ->setDescription(t('Respuesta oficial del productor a esta reseña.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -2,
                'settings' => [
                    'rows' => 3,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Quién respondió (productor).
        $fields['response_by'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Respondido por'))
            ->setDescription(t('Productor que respondió a la reseña.'))
            ->setSetting('target_type', 'user')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Campos del trait: helpful_count, ai_summary, ai_summary_generated_at.
        // photos y review_status no se anaden porque ya existen como state y la entidad no tiene photos.
        $traitFields = static::reviewableBaseFieldDefinitions();
        $fields['helpful_count'] = $traitFields['helpful_count'];
        $fields['photos'] = $traitFields['photos'];
        $fields['ai_summary'] = $traitFields['ai_summary'];
        $fields['ai_summary_generated_at'] = $traitFields['ai_summary_generated_at'];

        // Campos de sistema.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

    /**
     * Obtiene la etiqueta legible del estado.
     *
     * @return string
     *   Etiqueta traducida del estado de moderación.
     */
    public function getStateLabel(): string
    {
        $labels = [
            self::STATE_PENDING => t('Pendiente de moderación'),
            self::STATE_APPROVED => t('Aprobada'),
            self::STATE_REJECTED => t('Rechazada'),
            self::STATE_FLAGGED => t('Marcada para revisión'),
        ];
        return (string) ($labels[$this->get('state')->value] ?? $this->get('state')->value);
    }

    /**
     * Obtiene la etiqueta legible del tipo.
     *
     * @return string
     *   Etiqueta traducida del tipo de reseña.
     */
    public function getTypeLabel(): string
    {
        $labels = [
            self::TYPE_PRODUCT => t('Producto'),
            self::TYPE_PRODUCER => t('Productor'),
            self::TYPE_ORDER => t('Pedido'),
        ];
        return (string) ($labels[$this->get('type')->value] ?? $this->get('type')->value);
    }

    /**
     * Indica si la reseña tiene respuesta del productor.
     *
     * @return bool
     *   TRUE si existe respuesta.
     */
    public function hasResponse(): bool
    {
        return !empty($this->get('response')->value);
    }

    /**
     * Indica si la reseña está aprobada.
     *
     * @return bool
     *   TRUE si la reseña está en estado aprobado.
     */
    public function isApproved(): bool
    {
        return $this->get('state')->value === self::STATE_APPROVED;
    }

    /**
     * Obtiene la representación visual del rating.
     *
     * @return string
     *   Rating con estrellas (ej: "★★★★☆").
     */
    public function getRatingStars(): string
    {
        $rating = (int) ($this->get('rating')->value ?? 0);
        return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
    }

}
