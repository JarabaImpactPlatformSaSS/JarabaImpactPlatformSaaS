<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_page_builder\Entity\PageContent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controlador API REST para gestión de secciones Multi-Block.
 *
 * PROPÓSITO:
 * Proporciona endpoints CRUD para manipular secciones dentro de una página
 * del Page Builder en modo multi-block.
 *
 * ENDPOINTS:
 * - GET    /api/v1/pages/{id}/sections          - Lista secciones
 * - POST   /api/v1/pages/{id}/sections          - Añade sección
 * - PUT    /api/v1/pages/{id}/sections/{uuid}   - Actualiza sección
 * - DELETE /api/v1/pages/{id}/sections/{uuid}   - Elimina sección
 * - PUT    /api/v1/pages/{id}/sections/reorder  - Reordena secciones
 *
 * @package Drupal\jaraba_page_builder\Controller
 */
class SectionApiController extends ControllerBase
{

    /**
     * Lista las secciones de una página.
     *
     * @param int $page_content
     *   ID de la página.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con las secciones ordenadas.
     */
    public function list(int $page_content): JsonResponse
    {
        $page = $this->loadPage($page_content);

        return new JsonResponse([
            'success' => TRUE,
            'layout_mode' => $page->get('layout_mode')->value ?? 'legacy',
            'sections' => $page->getSectionsSorted(),
            'count' => count($page->getSections()),
        ]);
    }

    /**
     * Añade una nueva sección a la página.
     *
     * Request body:
     * {
     *   "template_id": "hero_fullscreen",
     *   "content": {},
     *   "weight": 0 // opcional
     * }
     *
     * @param int $page_content
     *   ID de la página.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La request.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con la sección creada.
     */
    public function add(int $page_content, Request $request): JsonResponse
    {
        $page = $this->loadPage($page_content);
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['template_id'])) {
            throw new BadRequestHttpException('Se requiere template_id.');
        }

        // Validar que el template existe.
        $template = $this->entityTypeManager()
            ->getStorage('page_template')
            ->load($data['template_id']);

        if (!$template) {
            throw new BadRequestHttpException('Template no encontrado: ' . $data['template_id']);
        }

        $uuid = $page->addSection(
            $data['template_id'],
            $data['content'] ?? [],
            $data['weight'] ?? NULL
        );

        $page->save();

        return new JsonResponse([
            'success' => TRUE,
            'message' => 'Sección añadida.',
            'section' => $page->getSection($uuid),
        ], 201);
    }

    /**
     * Actualiza una sección existente.
     *
     * Request body:
     * {
     *   "content": {},
     *   "visible": true,
     *   "weight": 2
     * }
     *
     * @param int $page_content
     *   ID de la página.
     * @param string $uuid
     *   UUID de la sección.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La request.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con la sección actualizada.
     */
    public function update(int $page_content, string $uuid, Request $request): JsonResponse
    {
        $page = $this->loadPage($page_content);
        $data = json_decode($request->getContent(), TRUE);

        // Verificar que la sección existe.
        if (!$page->getSection($uuid)) {
            throw new NotFoundHttpException('Sección no encontrada: ' . $uuid);
        }

        // Solo permitir actualizar campos específicos.
        $allowedFields = ['content', 'visible', 'weight'];
        $updates = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updates)) {
            throw new BadRequestHttpException('No hay campos válidos para actualizar.');
        }

        $page->updateSection($uuid, $updates);
        $page->save();

        return new JsonResponse([
            'success' => TRUE,
            'message' => 'Sección actualizada.',
            'section' => $page->getSection($uuid),
        ]);
    }

    /**
     * Elimina una sección.
     *
     * @param int $page_content
     *   ID de la página.
     * @param string $uuid
     *   UUID de la sección.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON confirmando eliminación.
     */
    public function delete(int $page_content, string $uuid): JsonResponse
    {
        $page = $this->loadPage($page_content);

        if (!$page->removeSection($uuid)) {
            throw new NotFoundHttpException('Sección no encontrada: ' . $uuid);
        }

        $page->save();

        return new JsonResponse([
            'success' => TRUE,
            'message' => 'Sección eliminada.',
            'remaining_count' => count($page->getSections()),
        ]);
    }

    /**
     * Reordena todas las secciones.
     *
     * Request body:
     * {
     *   "order": ["uuid1", "uuid2", "uuid3"]
     * }
     *
     * @param int $page_content
     *   ID de la página.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La request.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con el nuevo orden.
     */
    public function reorder(int $page_content, Request $request): JsonResponse
    {
        $page = $this->loadPage($page_content);
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['order']) || !is_array($data['order'])) {
            throw new BadRequestHttpException('Se requiere array "order" con UUIDs.');
        }

        $page->reorderSections($data['order']);
        $page->save();

        return new JsonResponse([
            'success' => TRUE,
            'message' => 'Secciones reordenadas.',
            'sections' => $page->getSectionsSorted(),
        ]);
    }

    /**
     * Obtiene el template disponibles para añadir como sección.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Lista de templates disponibles.
     */
    public function availableTemplates(): JsonResponse
    {
        $templates = $this->entityTypeManager()
            ->getStorage('page_template')
            ->loadMultiple();

        $result = [];
        /** @var \Drupal\jaraba_page_builder\PageTemplateInterface $template */
        foreach ($templates as $template) {
            $result[] = [
                'id' => $template->id(),
                'label' => $template->label(),
                'category' => $template->getCategory(),
                'thumbnail' => $template->getThumbnail(),
                'premium' => $template->isPremium(),
            ];
        }

        return new JsonResponse([
            'success' => TRUE,
            'templates' => $result,
            'count' => count($result),
        ]);
    }

    /**
     * Carga y valida una página.
     *
     * @param int $id
     *   ID de la página.
     *
     * @return \Drupal\jaraba_page_builder\Entity\PageContent
     *   La entidad página.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    protected function loadPage(int $id): PageContent
    {
        $page = $this->entityTypeManager()
            ->getStorage('page_content')
            ->load($id);

        if (!$page || !$page instanceof PageContent) {
            throw new NotFoundHttpException('Página no encontrada: ' . $id);
        }

        // Verificar acceso de edición.
        if (!$page->access('update')) {
            throw new AccessDeniedHttpException('No tienes permiso para editar esta página.');
        }

        return $page;
    }

}
