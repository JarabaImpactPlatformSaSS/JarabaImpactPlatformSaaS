<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Controlador API para operaciones AJAX del slide-panel de Andalucia +ei.
 *
 * Estructura: Endpoints AJAX para el CRUD de participantes via
 * slide-panel (directriz del proyecto). Cada endpoint devuelve
 * HTML o JSON segun el tipo de operacion.
 *
 * Logica: Los endpoints sirven formularios en HTML para el slide-panel
 * (add, edit) y procesan submissions via POST. El slide-panel
 * JS (ecosistema_jaraba_theme/slide-panel.js) carga el contenido
 * y maneja el submit automaticamente.
 *
 * Sintaxis: Extiende ControllerBase con inyeccion de dependencias.
 */
class AndaluciaEiApiController extends ControllerBase
{

    /**
     * Constructor.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   El gestor de tipos de entidad.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
    ) {
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('entity_type.manager'),
        );
    }

    /**
     * Muestra el formulario de nuevo participante en el slide-panel.
     *
     * GET /api/v1/andalucia-ei/participant/add
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   HTML del formulario.
     */
    public function addForm(): Response
    {
        $form = $this->entityFormBuilder()->getForm(
            $this->entityTypeManager->getStorage('programa_participante_ei')->create([]),
            'default'
        );

        $renderer = \Drupal::service('renderer');
        $html = $renderer->renderRoot($form);

        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }

    /**
     * Muestra el formulario de edicion de participante en el slide-panel.
     *
     * GET /api/v1/andalucia-ei/participant/{id}/edit
     *
     * @param int $id
     *   El ID de la entidad participante.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   HTML del formulario pre-rellenado.
     */
    public function editForm(int $id): Response
    {
        $entity = $this->entityTypeManager->getStorage('programa_participante_ei')->load($id);

        if (!$entity) {
            return new Response(
                '<p class="messages messages--error">' . $this->t('Participante no encontrado.') . '</p>',
                404,
                ['Content-Type' => 'text/html']
            );
        }

        $form = $this->entityFormBuilder()->getForm($entity, 'default');

        $renderer = \Drupal::service('renderer');
        $html = $renderer->renderRoot($form);

        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }

    /**
     * Lista participantes con filtros basicos.
     *
     * GET /api/v1/andalucia-ei/participants
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La peticion con parametros de filtrado.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con la lista de participantes.
     */
    public function listParticipants(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;
        $fase = $request->query->get('fase');
        $provincia = $request->query->get('provincia');

        $storage = $this->entityTypeManager->getStorage('programa_participante_ei');

        $query = $storage->getQuery()
            ->accessCheck(TRUE)
            ->sort('changed', 'DESC')
            ->range($offset, $limit);

        if ($fase) {
            $query->condition('fase_actual', $fase);
        }

        if ($provincia) {
            $query->condition('provincia_participacion', $provincia);
        }

        $ids = $query->execute();
        $total = (int) $storage->getQuery()
            ->accessCheck(TRUE)
            ->count()
            ->execute();

        $items = [];
        if (!empty($ids)) {
            $entities = $storage->loadMultiple($ids);
            foreach ($entities as $entity) {
                $items[] = [
                    'id' => $entity->id(),
                    'dni_nie' => $entity->getDniNie(),
                    'colectivo' => $entity->getColectivo(),
                    'fase_actual' => $entity->getFaseActual(),
                    'horas_orientacion' => $entity->getTotalHorasOrientacion(),
                    'horas_formacion' => (float) ($entity->get('horas_formacion')->value ?? 0),
                    'can_transit_insercion' => $entity->canTransitToInsercion(),
                ];
            }
        }

        return new JsonResponse([
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * Obtiene datos de un participante especifico.
     *
     * GET /api/v1/andalucia-ei/participant/{id}
     *
     * @param int $id
     *   El ID de la entidad.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con los datos del participante.
     */
    public function getParticipant(int $id): JsonResponse
    {
        $entity = $this->entityTypeManager->getStorage('programa_participante_ei')->load($id);

        if (!$entity) {
            return new JsonResponse(['error' => 'Participante no encontrado.'], 404);
        }

        return new JsonResponse([
            'id' => $entity->id(),
            'dni_nie' => $entity->getDniNie(),
            'colectivo' => $entity->getColectivo(),
            'fase_actual' => $entity->getFaseActual(),
            'provincia_participacion' => $entity->get('provincia_participacion')->value ?? '',
            'horas_orientacion_ind' => (float) ($entity->get('horas_orientacion_ind')->value ?? 0),
            'horas_orientacion_grup' => (float) ($entity->get('horas_orientacion_grup')->value ?? 0),
            'horas_formacion' => (float) ($entity->get('horas_formacion')->value ?? 0),
            'horas_mentoria_ia' => $entity->getHorasMentoriaIa(),
            'horas_mentoria_humana' => $entity->getHorasMentoriaHumana(),
            'total_horas_orientacion' => $entity->getTotalHorasOrientacion(),
            'can_transit_insercion' => $entity->canTransitToInsercion(),
            'incentivo_recibido' => $entity->hasReceivedIncentivo(),
            'carril' => $entity->get('carril')->value ?? '',
        ]);
    }

    /**
     * Elimina un participante (soft delete).
     *
     * DELETE /api/v1/andalucia-ei/participant/{id}
     *
     * @param int $id
     *   El ID de la entidad.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON de confirmacion.
     */
    public function deleteParticipant(int $id): JsonResponse
    {
        $entity = $this->entityTypeManager->getStorage('programa_participante_ei')->load($id);

        if (!$entity) {
            return new JsonResponse(['error' => 'Participante no encontrado.'], 404);
        }

        $dniNie = $entity->getDniNie();
        $entity->delete();

        \Drupal::logger('jaraba_andalucia_ei')->info('Participante @dni eliminado.', [
            '@dni' => $dniNie,
        ]);

        return new JsonResponse([
            'status' => 'deleted',
            'id' => $id,
        ]);
    }

}
