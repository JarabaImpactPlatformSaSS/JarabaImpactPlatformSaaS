<?php

namespace Drupal\jaraba_page_builder\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\jaraba_page_builder\PageContentInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad de contenido para Páginas del Page Builder.
 *
 * PROPÓSITO:
 * Almacena las páginas creadas por los tenants usando el Constructor de Páginas.
 * Cada página está asociada a una plantilla (PageTemplate) y contiene los
 * datos configurados por el usuario según el schema de la plantilla.
 *
 * DIRECTRIZ:
 * Es una Content Entity porque:
 * - Es creada/editada por usuarios del tenant
 * - Soporta Field UI para campos adicionales
 * - Integración nativa con Views
 * - Revisiones para historial de cambios
 * - Traducciones para multi-idioma
 *
 * @ContentEntityType(
 *   id = "page_content",
 *   label = @Translation("Página"),
 *   label_collection = @Translation("Páginas"),
 *   label_singular = @Translation("página"),
 *   label_plural = @Translation("páginas"),
 *   handlers = {
 *     "view_builder" = "Drupal\jaraba_page_builder\PageContentViewBuilder",
 *     "list_builder" = "Drupal\jaraba_page_builder\PageContentListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_page_builder\Form\PageContentForm",
 *       "add" = "Drupal\jaraba_page_builder\Form\PageContentForm",
 *       "edit" = "Drupal\jaraba_page_builder\Form\PageContentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_page_builder\PageContentAccessControlHandler",
 *   },
 *   base_table = "page_content",
 *   data_table = "page_content_field_data",
 *   revision_table = "page_content_revision",
 *   revision_data_table = "page_content_field_revision",
 *   translatable = TRUE,
 *   revisionable = TRUE,
 *   admin_permission = "administer page builder",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "owner" = "uid",
 *     "published" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log",
 *   },
 *   links = {
 *     "collection" = "/admin/content/pages",
 *     "add-form" = "/admin/content/pages/add",
 *     "canonical" = "/page/{page_content}",
 *     "edit-form" = "/admin/content/pages/{page_content}/edit",
 *     "delete-form" = "/admin/content/pages/{page_content}/delete",
 *     "version-history" = "/admin/content/pages/{page_content}/revisions",
 *   },
 *   field_ui_base_route = "entity.page_content.collection",
 * )
 */
class PageContent extends ContentEntityBase implements PageContentInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     */
    public function preSave(EntityStorageInterface $storage)
    {
        parent::preSave($storage);

        // Si no hay autor, asignar el usuario actual.
        if (!$this->getOwnerId()) {
            $this->setOwnerId(\Drupal::currentUser()->id());
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        // Título de la página.
        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setDescription(t('El título de la página.'))
            ->setRequired(TRUE)
            ->setTranslatable(TRUE)
            ->setRevisionable(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'string',
                'weight' => -10,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Plantilla usada.
        $fields['template_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Plantilla'))
            ->setDescription(t('La plantilla de página usada.'))
            ->setRequired(TRUE)
            ->setRevisionable(TRUE)
            ->setSetting('max_length', 64)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Referencia al tenant (Group).
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('El tenant propietario de esta página.'))
            ->setSetting('target_type', 'group')
            ->setRequired(FALSE)
            ->setRevisionable(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -6,
                'settings' => [
                    'match_operator' => 'CONTAINS',
                    'size' => 60,
                    'placeholder' => '',
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Datos del contenido (JSON con los valores del formulario).
        $fields['content_data'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Datos del Contenido'))
            ->setDescription(t('Datos JSON configurados según el schema de la plantilla.'))
            ->setRevisionable(TRUE)
            ->setTranslatable(TRUE)
            ->setDefaultValue('{}')
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 0,
                'settings' => [
                    'rows' => 10,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // URL amigable (path alias).
        $fields['path_alias'] = BaseFieldDefinition::create('string')
            ->setLabel(t('URL amigable'))
            ->setDescription(t('La URL personalizada para esta página (ej: /about-us).'))
            ->setRevisionable(TRUE)
            ->setTranslatable(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Meta título para SEO.
        $fields['meta_title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Meta Título'))
            ->setDescription(t('Título para SEO (máx 60 caracteres).'))
            ->setRevisionable(TRUE)
            ->setTranslatable(TRUE)
            ->setSetting('max_length', 70)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Meta descripción para SEO.
        $fields['meta_description'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Meta Descripción'))
            ->setDescription(t('Descripción para SEO (máx 160 caracteres).'))
            ->setRevisionable(TRUE)
            ->setTranslatable(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 11,
                'settings' => [
                    'rows' => 3,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Estado de publicación.
        $fields['status'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Publicado'))
            ->setDescription(t('Si la página está publicada y visible.'))
            ->setRevisionable(TRUE)
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 20,
                'settings' => [
                    'display_label' => TRUE,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Menú destino.
        $fields['menu_link'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Añadir a menú'))
            ->setDescription(t('Menú donde se mostrará un enlace a esta página.'))
            ->setRevisionable(TRUE)
            ->setSetting('allowed_values', [
                '' => '- Ninguno -',
                'main' => 'Menú principal',
                'footer' => 'Menú del footer',
                'secondary' => 'Menú secundario',
            ])
            ->setDefaultValue('')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 15,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Propietario (user).
        $fields['uid']
            ->setLabel(t('Autor'))
            ->setDescription(t('El usuario que creó esta página.'))
            ->setRevisionable(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 25,
                'settings' => [
                    'match_operator' => 'CONTAINS',
                    'size' => 60,
                    'placeholder' => '',
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // =====================================================================
        // MULTI-BLOCK SYSTEM (Gap J)
        // Campos para soporte de múltiples secciones/bloques por página.
        // =====================================================================

        // Modo de layout: legacy (template único) o multiblock.
        $fields['layout_mode'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Modo de Layout'))
            ->setDescription(t('Tipo de composición de la página.'))
            ->setRevisionable(TRUE)
            ->setSetting('allowed_values', [
                'legacy' => 'Legacy (template único)',
                'multiblock' => 'Multi-Block (secciones)',
            ])
            ->setDefaultValue('legacy')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Array JSON de secciones (para modo multiblock).
        $fields['sections'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Secciones'))
            ->setDescription(t('Array JSON de secciones para páginas multi-block.'))
            ->setRevisionable(TRUE)
            ->setTranslatable(TRUE)
            ->setDefaultValue('[]')
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 1,
                'settings' => [
                    'rows' => 6,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // =====================================================================
        // CANVAS EDITOR GrapesJS (Gap J - Visual Editor v3)
        // Almacena el estado completo del editor visual para persistencia.
        // =====================================================================

        // Datos del Canvas GrapesJS (components + styles + html + css).
        $fields['canvas_data'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Datos del Canvas'))
            ->setDescription(t('JSON con el estado completo del Canvas Editor (components, styles, html, css).'))
            ->setRevisionable(TRUE)
            ->setTranslatable(TRUE)
            ->setDefaultValue('{}')
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 2,
                'settings' => [
                    'rows' => 10,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // HTML renderizado del canvas para el frontend público.
        $fields['rendered_html'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('HTML Renderizado'))
            ->setDescription(t('HTML final exportado del Canvas Editor para la vista pública.'))
            ->setRevisionable(TRUE)
            ->setTranslatable(TRUE)
            ->setDefaultValue('')
            ->setDisplayConfigurable('form', FALSE)
            ->setDisplayConfigurable('view', FALSE);


        // Timestamps.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'))
            ->setDescription(t('Fecha de creación de la página.'))
            ->setRevisionable(TRUE)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'))
            ->setDescription(t('Fecha de última modificación.'))
            ->setRevisionable(TRUE)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateId(): string
    {
        return $this->get('template_id')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getContentData(): array
    {
        $data = $this->get('content_data')->value ?? '{}';
        return json_decode($data, TRUE) ?: [];
    }

    /**
     * {@inheritdoc}
     */
    public function setContentData(array $data): PageContentInterface
    {
        $this->set('content_data', json_encode($data, JSON_UNESCAPED_UNICODE));
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPathAlias(): string
    {
        return $this->get('path_alias')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getMetaTitle(): string
    {
        return $this->get('meta_title')->value ?? $this->label();
    }

    /**
     * {@inheritdoc}
     */
    public function getMetaDescription(): string
    {
        return $this->get('meta_description')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getTenantId(): ?int
    {
        return $this->get('tenant_id')->target_id ?? NULL;
    }

    /**
     * {@inheritdoc}
     */
    public function isPublished(): bool
    {
        return (bool) $this->get('status')->value;
    }

    // =========================================================================
    // MULTI-BLOCK SYSTEM - Métodos helper para gestión de secciones.
    // =========================================================================

    /**
     * Verifica si la página está en modo multi-block.
     *
     * @return bool
     *   TRUE si usa múltiples secciones.
     */
    public function isMultiBlock(): bool
    {
        return $this->get('layout_mode')->value === 'multiblock';
    }

    /**
     * Obtiene las secciones de la página.
     *
     * Cada sección es un array con:
     * - uuid: Identificador único de la sección
     * - template_id: ID del template/bloque
     * - content: Array con datos del bloque
     * - weight: Orden de la sección
     * - visible: Si está visible o deshabilitada
     *
     * @return array
     *   Array de secciones.
     */
    public function getSections(): array
    {
        $data = $this->get('sections')->value ?? '[]';
        $sections = json_decode($data, TRUE);
        return is_array($sections) ? $sections : [];
    }

    /**
     * Establece las secciones de la página.
     *
     * @param array $sections
     *   Array de secciones.
     *
     * @return self
     */
    public function setSections(array $sections): self
    {
        $this->set('sections', json_encode($sections, JSON_UNESCAPED_UNICODE));
        return $this;
    }

    /**
     * Añade una nueva sección a la página.
     *
     * @param string $templateId
     *   ID del template a usar.
     * @param array $content
     *   Datos del contenido.
     * @param int|null $weight
     *   Peso/orden. Si es NULL, se añade al final.
     *
     * @return string
     *   UUID de la sección creada.
     */
    public function addSection(string $templateId, array $content = [], ?int $weight = NULL): string
    {
        $sections = $this->getSections();
        $uuid = \Drupal::service('uuid')->generate();

        // Si no se especifica weight, añadir al final.
        if ($weight === NULL) {
            $maxWeight = 0;
            foreach ($sections as $section) {
                if (($section['weight'] ?? 0) > $maxWeight) {
                    $maxWeight = $section['weight'];
                }
            }
            $weight = $maxWeight + 1;
        }

        $sections[] = [
            'uuid' => $uuid,
            'template_id' => $templateId,
            'content' => $content,
            'weight' => $weight,
            'visible' => TRUE,
        ];

        $this->setSections($sections);
        $this->set('layout_mode', 'multiblock');

        return $uuid;
    }

    /**
     * Actualiza una sección existente.
     *
     * @param string $uuid
     *   UUID de la sección.
     * @param array $updates
     *   Campos a actualizar (content, visible, weight).
     *
     * @return bool
     *   TRUE si se encontró y actualizó.
     */
    public function updateSection(string $uuid, array $updates): bool
    {
        $sections = $this->getSections();
        $found = FALSE;

        foreach ($sections as &$section) {
            if ($section['uuid'] === $uuid) {
                $section = array_merge($section, $updates);
                $found = TRUE;
                break;
            }
        }

        if ($found) {
            $this->setSections($sections);
        }

        return $found;
    }

    /**
     * Mueve una sección a un nuevo peso/posición.
     *
     * @param string $uuid
     *   UUID de la sección.
     * @param int $newWeight
     *   Nuevo peso.
     *
     * @return bool
     *   TRUE si se encontró y movió.
     */
    public function moveSection(string $uuid, int $newWeight): bool
    {
        return $this->updateSection($uuid, ['weight' => $newWeight]);
    }

    /**
     * Elimina una sección.
     *
     * @param string $uuid
     *   UUID de la sección.
     *
     * @return bool
     *   TRUE si se encontró y eliminó.
     */
    public function removeSection(string $uuid): bool
    {
        $sections = $this->getSections();
        $originalCount = count($sections);

        $sections = array_filter($sections, function ($section) use ($uuid) {
            return $section['uuid'] !== $uuid;
        });

        if (count($sections) < $originalCount) {
            $this->setSections(array_values($sections));
            return TRUE;
        }

        return FALSE;
    }

    /**
     * Obtiene una sección específica por UUID.
     *
     * @param string $uuid
     *   UUID de la sección.
     *
     * @return array|null
     *   La sección o NULL si no existe.
     */
    public function getSection(string $uuid): ?array
    {
        foreach ($this->getSections() as $section) {
            if ($section['uuid'] === $uuid) {
                return $section;
            }
        }
        return NULL;
    }

    /**
     * Obtiene las secciones ordenadas por peso.
     *
     * @return array
     *   Secciones ordenadas.
     */
    public function getSectionsSorted(): array
    {
        $sections = $this->getSections();
        usort($sections, fn($a, $b) => ($a['weight'] ?? 0) <=> ($b['weight'] ?? 0));
        return $sections;
    }

    /**
     * Reordena todas las secciones según un array de UUIDs.
     *
     * @param array $orderedUuids
     *   Array de UUIDs en el nuevo orden.
     *
     * @return bool
     *   TRUE si se reordenó correctamente.
     */
    public function reorderSections(array $orderedUuids): bool
    {
        $sections = $this->getSections();
        $sectionsByUuid = [];

        foreach ($sections as $section) {
            $sectionsByUuid[$section['uuid']] = $section;
        }

        $newSections = [];
        $weight = 0;

        foreach ($orderedUuids as $uuid) {
            if (isset($sectionsByUuid[$uuid])) {
                $sectionsByUuid[$uuid]['weight'] = $weight++;
                $newSections[] = $sectionsByUuid[$uuid];
            }
        }

        // Si hay secciones que no estaban en el orden, añadirlas al final.
        foreach ($sections as $section) {
            if (!in_array($section['uuid'], $orderedUuids)) {
                $section['weight'] = $weight++;
                $newSections[] = $section;
            }
        }

        $this->setSections($newSections);
        return TRUE;
    }

}
