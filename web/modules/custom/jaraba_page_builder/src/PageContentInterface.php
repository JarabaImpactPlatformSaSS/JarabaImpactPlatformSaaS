<?php

namespace Drupal\jaraba_page_builder;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface para la entidad PageContent.
 */
interface PageContentInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface
{

    /**
     * Obtiene el ID de la plantilla usada.
     *
     * @return string
     *   El ID de la plantilla.
     */
    public function getTemplateId(): string;

    /**
     * Obtiene los datos del contenido.
     *
     * @return array
     *   Array con los datos configurados.
     */
    public function getContentData(): array;

    /**
     * Establece los datos del contenido.
     *
     * @param array $data
     *   Los datos a guardar.
     *
     * @return $this
     */
    public function setContentData(array $data): PageContentInterface;

    /**
     * Obtiene el path alias.
     *
     * @return string
     *   El alias de la URL.
     */
    public function getPathAlias(): string;

    /**
     * Obtiene el meta título.
     *
     * @return string
     *   El meta título para SEO.
     */
    public function getMetaTitle(): string;

    /**
     * Obtiene la meta descripción.
     *
     * @return string
     *   La meta descripción para SEO.
     */
    public function getMetaDescription(): string;

    /**
     * Obtiene el ID del tenant.
     *
     * @return int|null
     *   El ID del grupo/tenant o NULL.
     */
    public function getTenantId(): ?int;

    /**
     * Indica si la página está publicada.
     *
     * @return bool
     *   TRUE si está publicada.
     */
    public function isPublished(): bool;

    // =========================================================================
    // MULTI-BLOCK SYSTEM (Gap J)
    // =========================================================================

    /**
     * Verifica si la página está en modo multi-block.
     *
     * @return bool
     *   TRUE si usa múltiples secciones.
     */
    public function isMultiBlock(): bool;

    /**
     * Obtiene las secciones de la página.
     *
     * @return array
     *   Array de secciones.
     */
    public function getSections(): array;

    /**
     * Establece las secciones de la página.
     *
     * @param array $sections
     *   Array de secciones.
     *
     * @return $this
     */
    public function setSections(array $sections): self;

    /**
     * Añade una nueva sección a la página.
     *
     * @param string $templateId
     *   ID del template.
     * @param array $content
     *   Datos del contenido.
     * @param int|null $weight
     *   Peso/orden.
     *
     * @return string
     *   UUID de la sección creada.
     */
    public function addSection(string $templateId, array $content = [], ?int $weight = NULL): string;

    /**
     * Actualiza una sección existente.
     *
     * @param string $uuid
     *   UUID de la sección.
     * @param array $updates
     *   Campos a actualizar.
     *
     * @return bool
     *   TRUE si se actualizó.
     */
    public function updateSection(string $uuid, array $updates): bool;

    /**
     * Mueve una sección a un nuevo peso.
     *
     * @param string $uuid
     *   UUID de la sección.
     * @param int $newWeight
     *   Nuevo peso.
     *
     * @return bool
     *   TRUE si se movió.
     */
    public function moveSection(string $uuid, int $newWeight): bool;

    /**
     * Elimina una sección.
     *
     * @param string $uuid
     *   UUID de la sección.
     *
     * @return bool
     *   TRUE si se eliminó.
     */
    public function removeSection(string $uuid): bool;

    /**
     * Obtiene una sección por UUID.
     *
     * @param string $uuid
     *   UUID de la sección.
     *
     * @return array|null
     *   La sección o NULL.
     */
    public function getSection(string $uuid): ?array;

    /**
     * Obtiene las secciones ordenadas por peso.
     *
     * @return array
     *   Secciones ordenadas.
     */
    public function getSectionsSorted(): array;

    /**
     * Reordena secciones según array de UUIDs.
     *
     * @param array $orderedUuids
     *   UUIDs en nuevo orden.
     *
     * @return bool
     *   TRUE si se reordenó.
     */
    public function reorderSections(array $orderedUuids): bool;

}
