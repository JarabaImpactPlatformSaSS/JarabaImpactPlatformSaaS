<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Publicacion programada de paginas del Page Builder.
 *
 * P1-05: Permite programar la publicacion o despublicacion de paginas
 * en una fecha y hora especifica. El cron procesa las programaciones
 * pendientes y ejecuta la accion correspondiente.
 *
 * FLUJO:
 * 1. Usuario crea ScheduledPublish con fecha + accion (publish/unpublish).
 * 2. Cron verifica si hay programaciones cuya fecha ha pasado.
 * 3. ScheduledPublishService ejecuta la accion sobre PageContent.
 * 4. Se marca como 'completed' con timestamp de ejecucion.
 *
 * @ContentEntityType(
 *   id = "scheduled_publish",
 *   label = @Translation("Publicacion Programada"),
 *   label_collection = @Translation("Publicaciones Programadas"),
 *   label_singular = @Translation("publicacion programada"),
 *   label_plural = @Translation("publicaciones programadas"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_page_builder\ScheduledPublishListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_page_builder\Form\ScheduledPublishForm",
 *       "add" = "Drupal\jaraba_page_builder\Form\ScheduledPublishForm",
 *       "edit" = "Drupal\jaraba_page_builder\Form\ScheduledPublishForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_page_builder\ScheduledPublishAccessControlHandler",
 *   },
 *   base_table = "scheduled_publish",
 *   data_table = "scheduled_publish_field_data",
 *   translatable = FALSE,
 *   admin_permission = "administer page builder",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/scheduled-publishes",
 *     "add-form" = "/admin/content/scheduled-publishes/add",
 *     "canonical" = "/admin/content/scheduled-publishes/{scheduled_publish}",
 *     "edit-form" = "/admin/content/scheduled-publishes/{scheduled_publish}/edit",
 *     "delete-form" = "/admin/content/scheduled-publishes/{scheduled_publish}/delete",
 *   },
 *   field_ui_base_route = "entity.scheduled_publish.settings",
 * )
 */
class ScheduledPublish extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * Accion: publicar la pagina.
   */
  const ACTION_PUBLISH = 'publish';

  /**
   * Accion: despublicar la pagina.
   */
  const ACTION_UNPUBLISH = 'unpublish';

  /**
   * Estado: pendiente de ejecucion.
   */
  const STATUS_PENDING = 'pending';

  /**
   * Estado: en proceso de ejecucion.
   */
  const STATUS_PROCESSING = 'processing';

  /**
   * Estado: ejecutada correctamente.
   */
  const STATUS_COMPLETED = 'completed';

  /**
   * Estado: error en la ejecucion.
   */
  const STATUS_FAILED = 'failed';

  /**
   * Estado: cancelada manualmente.
   */
  const STATUS_CANCELLED = 'cancelled';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Titulo'))
      ->setDescription(t('Nombre descriptivo de esta programacion.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 255,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['page_content_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Pagina'))
      ->setDescription(t('La pagina del Page Builder a la que se aplica esta programacion.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'page_content')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -8,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['action'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Accion'))
      ->setDescription(t('Accion a ejecutar cuando llegue la fecha programada.'))
      ->setRequired(TRUE)
      ->setSettings([
        'allowed_values' => [
          self::ACTION_PUBLISH => t('Publicar'),
          self::ACTION_UNPUBLISH => t('Despublicar'),
        ],
      ])
      ->setDefaultValue(self::ACTION_PUBLISH)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -6,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['scheduled_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha programada'))
      ->setDescription(t('Fecha y hora en que se ejecutara la accion.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -4,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['schedule_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual de la programacion.'))
      ->setRequired(TRUE)
      ->setSettings([
        'allowed_values' => [
          self::STATUS_PENDING => t('Pendiente'),
          self::STATUS_PROCESSING => t('Procesando'),
          self::STATUS_COMPLETED => t('Completada'),
          self::STATUS_FAILED => t('Error'),
          self::STATUS_CANCELLED => t('Cancelada'),
        ],
      ])
      ->setDefaultValue(self::STATUS_PENDING)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -2,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['executed_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Ejecutada el'))
      ->setDescription(t('Fecha y hora en que se ejecuto la programacion.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['error_message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Mensaje de error'))
      ->setDescription(t('Mensaje de error si la ejecucion fallo.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'basic_string',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Notas'))
      ->setDescription(t('Notas internas sobre esta programacion.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 4,
        'settings' => [
          'rows' => 3,
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'basic_string',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('El tenant propietario de esta programacion.'))
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid']
      ->setLabel(t('Creado por'))
      ->setDescription(t('Usuario que creo la programacion.'))
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 10,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'))
      ->setDescription(t('Fecha de creacion de la programacion.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'))
      ->setDescription(t('Fecha de la ultima modificacion.'));

    return $fields;
  }

  /**
   * Obtiene la pagina referenciada.
   *
   * @return \Drupal\jaraba_page_builder\Entity\PageContent|null
   *   La pagina o NULL.
   */
  public function getPageContent(): ?PageContent {
    return $this->get('page_content_id')->entity;
  }

  /**
   * Obtiene la accion programada.
   *
   * @return string
   *   'publish' o 'unpublish'.
   */
  public function getAction(): string {
    return $this->get('action')->value ?? self::ACTION_PUBLISH;
  }

  /**
   * Obtiene el timestamp de la fecha programada.
   *
   * @return int|null
   *   Unix timestamp o NULL.
   */
  public function getScheduledTimestamp(): ?int {
    $date = $this->get('scheduled_at')->value;
    return $date ? strtotime($date) : NULL;
  }

  /**
   * Obtiene el estado actual de la programacion.
   *
   * @return string
   *   Uno de los valores STATUS_*.
   */
  public function getScheduleStatus(): string {
    return $this->get('schedule_status')->value ?? self::STATUS_PENDING;
  }

  /**
   * Verifica si esta pendiente de ejecucion.
   *
   * @return bool
   *   TRUE si esta pendiente.
   */
  public function isPending(): bool {
    return $this->getScheduleStatus() === self::STATUS_PENDING;
  }

  /**
   * Marca como completada.
   *
   * @return $this
   */
  public function markCompleted(): static {
    $this->set('schedule_status', self::STATUS_COMPLETED);
    $this->set('executed_at', \Drupal::time()->getRequestTime());
    return $this;
  }

  /**
   * Marca como fallida.
   *
   * @param string $error
   *   Mensaje de error.
   *
   * @return $this
   */
  public function markFailed(string $error): static {
    $this->set('schedule_status', self::STATUS_FAILED);
    $this->set('executed_at', \Drupal::time()->getRequestTime());
    $this->set('error_message', $error);
    return $this;
  }

  /**
   * Marca como cancelada.
   *
   * @return $this
   */
  public function markCancelled(): static {
    $this->set('schedule_status', self::STATUS_CANCELLED);
    return $this;
  }

}
