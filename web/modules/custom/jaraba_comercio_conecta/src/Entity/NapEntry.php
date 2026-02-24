<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Entrada NAP.
 *
 * Estructura: Entidad de ComercioConecta que registra la presencia de un
 *   negocio local en un directorio externo (Google, Yelp, Paginas Amarillas,
 *   etc.). Almacena los datos NAP (Name, Address, Phone) tal como aparecen
 *   listados en cada directorio para detectar inconsistencias.
 *
 * Logica: La consistencia NAP es un factor critico de SEO local. Google
 *   penaliza los negocios cuyo nombre, direccion o telefono difiere entre
 *   directorios. Esta entidad permite:
 *   - Rastrear automaticamente los listados en directorios principales
 *   - Comparar listed_name/listed_address/listed_phone con el perfil canonico
 *   - Calcular is_consistent y alimentar el nap_consistency_score del
 *     LocalBusinessProfile asociado
 *   - Alertar al comerciante cuando se detectan inconsistencias
 *   La entidad es programatica (sin formularios) ya que se gestiona via
 *   servicios de scraping y verificacion automatica.
 *
 * @ContentEntityType(
 *   id = "comercio_nap_entry",
 *   label = @Translation("Entrada NAP"),
 *   label_collection = @Translation("Entradas NAP"),
 *   label_singular = @Translation("entrada NAP"),
 *   label_plural = @Translation("entradas NAP"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\NapEntryAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_nap_entry",
 *   admin_permission = "manage comercio local seo",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "directory_name",
 *   },
 *   links = {
 *     "collection" = "/admin/content/comercio-nap-entries",
 *   },
 *   field_ui_base_route = "entity.comercio_nap_entry.settings",
 * )
 */
class NapEntry extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece esta entrada para aislamiento multi-tenant.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['local_business_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Perfil de negocio local'))
      ->setDescription(t('Perfil de negocio local al que pertenece esta entrada de directorio.'))
      ->setSetting('target_type', 'comercio_local_business')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['directory_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre del directorio'))
      ->setDescription(t('Nombre del directorio externo (ej: Google, Yelp, Paginas Amarillas).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->setDisplayConfigurable('view', TRUE);

    $fields['directory_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL del listado'))
      ->setDescription(t('URL completa del listado del negocio en el directorio externo.'))
      ->setSetting('max_length', 512)
      ->setDisplayConfigurable('view', TRUE);

    $fields['listed_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre listado'))
      ->setDescription(t('Nombre del negocio tal como aparece en el directorio.'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('view', TRUE);

    $fields['listed_address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Direccion listada'))
      ->setDescription(t('Direccion del negocio tal como aparece en el directorio.'))
      ->setSetting('max_length', 512)
      ->setDisplayConfigurable('view', TRUE);

    $fields['listed_phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Telefono listado'))
      ->setDescription(t('Telefono del negocio tal como aparece en el directorio.'))
      ->setSetting('max_length', 20)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_consistent'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Consistente'))
      ->setDescription(t('Indica si los datos NAP del directorio coinciden con el perfil canonico.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['last_verified'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Ultima verificacion'))
      ->setDescription(t('Fecha y hora de la ultima verificacion automatica de este listado.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
