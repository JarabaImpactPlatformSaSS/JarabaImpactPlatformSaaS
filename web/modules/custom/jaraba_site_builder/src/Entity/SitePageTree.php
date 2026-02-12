<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Define la entidad SitePageTree para jerarquía de páginas en el sitio.
 *
 * Cada entrada representa una página del Page Builder posicionada en el árbol
 * del sitio con información de navegación, visibilidad y orden.
 *
 * @ContentEntityType(
 *   id = "site_page_tree",
 *   label = @Translation("Nodo del Árbol de Páginas"),
 *   label_collection = @Translation("Árbol de Páginas"),
 *   label_singular = @Translation("nodo del árbol"),
 *   label_plural = @Translation("nodos del árbol"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_site_builder\SitePageTreeListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_site_builder\Form\SitePageTreeForm",
 *       "add" = "Drupal\jaraba_site_builder\Form\SitePageTreeForm",
 *       "edit" = "Drupal\jaraba_site_builder\Form\SitePageTreeForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_site_builder\SitePageTreeAccessControlHandler",
 *   },
 *   base_table = "site_page_tree",
 *   admin_permission = "administer site structure",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/site-builder/tree/{site_page_tree}",
 *     "add-form" = "/admin/structure/site-builder/tree/add",
 *     "edit-form" = "/admin/structure/site-builder/tree/{site_page_tree}/edit",
 *     "delete-form" = "/admin/structure/site-builder/tree/{site_page_tree}/delete",
 *   },
 * )
 */
class SitePageTree extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Tenant (Group) al que pertenece este nodo.
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('El tenant al que pertenece esta estructura.'))
            ->setSetting('target_type', 'group')
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -100,
            ]);

        // Página del Page Builder vinculada.
        $fields['page_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Página'))
            ->setDescription(t('La página del Page Builder asociada.'))
            ->setSetting('target_type', 'page_content')
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // --- Jerarquía ---

        $fields['parent_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Padre'))
            ->setDescription(t('Nodo padre en el árbol (NULL para raíz).'))
            ->setSetting('target_type', 'site_page_tree')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['weight'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Peso'))
            ->setDescription(t('Orden entre elementos hermanos (menor = primero).'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 11,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['depth'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Profundidad'))
            ->setDescription(t('Nivel en el árbol (0 = raíz).'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 12,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['path'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Path Materializado'))
            ->setDescription(t('Ruta completa de IDs: /1/5/12/ para queries eficientes.'))
            ->setSettings([
                'max_length' => 500,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 13,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // --- Visibilidad ---

        $fields['show_in_navigation'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Mostrar en Navegación'))
            ->setDescription(t('Incluir en el menú de navegación principal.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 20,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['show_in_sitemap'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Mostrar en Sitemap'))
            ->setDescription(t('Incluir en el sitemap XML.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 21,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['show_in_footer'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Mostrar en Footer'))
            ->setDescription(t('Incluir en el menú del pie de página.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 22,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['show_in_breadcrumbs'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Mostrar en Breadcrumbs'))
            ->setDescription(t('Incluir en la ruta de navegación (migas de pan).'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 23,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // --- Override de navegación ---

        $fields['nav_title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título de Navegación'))
            ->setDescription(t('Título corto para el menú (override del título de página).'))
            ->setSettings([
                'max_length' => 100,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 30,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['nav_icon'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Icono de Navegación'))
            ->setDescription(t('Nombre del icono Lucide/jaraba_icon para el menú.'))
            ->setSettings([
                'max_length' => 50,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 31,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['nav_highlight'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Destacar en Navegación'))
            ->setDescription(t('Resaltar visualmente en el menú.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 32,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['nav_external_url'] = BaseFieldDefinition::create('uri')
            ->setLabel(t('URL Externa'))
            ->setDescription(t('URL externa que abre en nueva pestaña (en lugar de la página).'))
            ->setDisplayOptions('form', [
                'type' => 'uri',
                'weight' => 33,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // --- Estado ---

        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setDescription(t('Estado de publicación.'))
            ->setDefaultValue('published')
            ->setSettings([
                'allowed_values' => [
                    'draft' => t('Borrador'),
                    'published' => t('Publicado'),
                    'archived' => t('Archivado'),
                ],
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 40,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['published_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Fecha de Publicación'))
            ->setDescription(t('Fecha en que se publicó.'))
            ->setDisplayOptions('form', [
                'type' => 'datetime_timestamp',
                'weight' => 41,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // --- Campos de sistema ---

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'))
            ->setDescription(t('Fecha de creación.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'))
            ->setDescription(t('Fecha de última modificación.'));

        return $fields;
    }

    /**
     * Obtiene la página asociada.
     */
    public function getPage(): ?object
    {
        return $this->get('page_id')->entity;
    }

    /**
     * Obtiene el título de navegación (o el de la página si no hay override).
     */
    public function getNavTitle(): string
    {
        $override = $this->get('nav_title')->value;
        if (!empty($override)) {
            return $override;
        }
        $page = $this->getPage();
        return $page ? $page->label() : '';
    }

    /**
     * Obtiene el nodo padre.
     */
    public function getParent(): ?SitePageTree
    {
        return $this->get('parent_id')->entity;
    }

    /**
     * Verifica si este nodo tiene hijos.
     */
    public function hasChildren(): bool
    {
        $storage = \Drupal::entityTypeManager()->getStorage('site_page_tree');
        $count = $storage->getQuery()
            ->condition('parent_id', $this->id())
            ->accessCheck(FALSE)
            ->count()
            ->execute();
        return $count > 0;
    }

    /**
     * Obtiene los hijos directos de este nodo.
     */
    public function getChildren(): array
    {
        $storage = \Drupal::entityTypeManager()->getStorage('site_page_tree');
        return $storage->loadByProperties([
            'parent_id' => $this->id(),
        ]);
    }

    /**
     * Verifica si el nodo está publicado.
     */
    public function isPublished(): bool
    {
        return $this->get('status')->value === 'published';
    }

    /**
     * Verifica si se muestra en navegación.
     */
    public function showInNavigation(): bool
    {
        return (bool) $this->get('show_in_navigation')->value;
    }

}
