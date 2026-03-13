<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serves entity forms via slide-panel for the Coordinador Hub.
 *
 * SLIDE-PANEL-RENDER-001: Uses renderPlain() for AJAX slide-panel requests.
 * This controller enables coordinadores to create/edit entities WITHOUT
 * navigating to /admin/content/* routes, keeping them in the frontend hub.
 */
class CoordinadorFormController extends ControllerBase {

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityFormBuilderInterface $entity_form_builder,
    protected RendererInterface $renderer,
    protected LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('renderer'),
      $container->get('logger.channel.jaraba_andalucia_ei'),
    );
  }

  /**
   * Add form for PlanFormativoEi via slide-panel.
   */
  public function addPlanFormativo(Request $request): Response|array {
    return $this->handleEntityForm($request, 'plan_formativo_ei', NULL, 'add');
  }

  /**
   * Edit form for PlanFormativoEi via slide-panel.
   */
  public function editPlanFormativo(Request $request, string $id): Response|array {
    return $this->handleEntityForm($request, 'plan_formativo_ei', $id, 'edit');
  }

  /**
   * Add form for AccionFormativaEi via slide-panel.
   */
  public function addAccionFormativa(Request $request): Response|array {
    return $this->handleEntityForm($request, 'accion_formativa_ei', NULL, 'add');
  }

  /**
   * Edit form for AccionFormativaEi via slide-panel.
   */
  public function editAccionFormativa(Request $request, string $id): Response|array {
    return $this->handleEntityForm($request, 'accion_formativa_ei', $id, 'edit');
  }

  /**
   * Add form for SesionProgramadaEi via slide-panel.
   */
  public function addSesionProgramada(Request $request): Response|array {
    return $this->handleEntityForm($request, 'sesion_programada_ei', NULL, 'add');
  }

  /**
   * Edit form for SesionProgramadaEi via slide-panel.
   */
  public function editSesionProgramada(Request $request, string $id): Response|array {
    return $this->handleEntityForm($request, 'sesion_programada_ei', $id, 'edit');
  }

  /**
   * Handles entity form rendering for both slide-panel and full-page.
   *
   * SLIDE-PANEL-RENDER-001: renderPlain() for AJAX, render array for full page.
   * FORM-CACHE-001: Does NOT call setCached(TRUE) — Drupal handles form cache.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $entityTypeId
   *   The entity type ID.
   * @param string|null $entityId
   *   The entity ID for edit, NULL for add.
   * @param string $operation
   *   The form operation: 'add' or 'edit'.
   *
   * @return \Symfony\Component\HttpFoundation\Response|array
   *   HTML Response for slide-panel or render array for full page.
   */
  protected function handleEntityForm(Request $request, string $entityTypeId, ?string $entityId, string $operation): Response|array {
    try {
      $storage = $this->entityTypeManager->getStorage($entityTypeId);

      if ($entityId !== NULL) {
        $entity = $storage->load($entityId);
        if (!$entity) {
          if ($this->isSlidePanelRequest($request)) {
            return new JsonResponse([
              'success' => FALSE,
              'message' => (string) $this->t('Entidad no encontrada.'),
            ], 404);
          }
          throw new NotFoundHttpException();
        }
      }
      else {
        $entity = $storage->create();
      }

      $form = $this->entityFormBuilder->getForm($entity, $operation);

      // SLIDE-PANEL-RENDER-001: Check if this is a slide-panel AJAX request.
      if ($this->isSlidePanelRequest($request)) {
        $form['#action'] = $request->getRequestUri();
        $html = (string) $this->renderer->renderPlain($form);
        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
      }

      return $form;
    }
    catch (NotFoundHttpException $e) {
      throw $e;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error loading entity form @type/@id: @msg', [
        '@type' => $entityTypeId,
        '@id' => $entityId ?? 'new',
        '@msg' => $e->getMessage(),
      ]);

      if ($this->isSlidePanelRequest($request)) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => (string) $this->t('Error al cargar el formulario.'),
        ], 500);
      }

      throw $e;
    }
  }

  /**
   * Detects slide-panel (AJAX) requests.
   *
   * SLIDE-PANEL-RENDER-001: isXmlHttpRequest() AND NOT _wrapper_format.
   * Distinguishes from Drupal modal dialogs which use _wrapper_format.
   */
  private function isSlidePanelRequest(Request $request): bool {
    return $request->isXmlHttpRequest() && !$request->query->has('_wrapper_format');
  }

}
