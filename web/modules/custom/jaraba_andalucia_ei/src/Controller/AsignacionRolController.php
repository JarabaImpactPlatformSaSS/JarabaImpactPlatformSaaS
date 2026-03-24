<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\jaraba_andalucia_ei\Form\AsignacionRolForm;
use Drupal\jaraba_andalucia_ei\Service\RolProgramaServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for program role assignment and team management.
 *
 * SLIDE-PANEL-RENDER-002: Uses _controller routes for slide-panel support.
 * SLIDE-PANEL-RENDER-001: Uses renderInIsolation() for AJAX slide-panel requests.
 * TENANT-001: All queries filter by tenant via RolProgramaService.
 * CONTROLLER-READONLY-001: entityTypeManager assigned in constructor body.
 */
class AsignacionRolController extends ControllerBase {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilderService
   *   The form builder.
   * @param \Drupal\jaraba_andalucia_ei\Service\RolProgramaServiceInterface $rolProgramaService
   *   The program role service.
   * @param \Drupal\Core\Render\RendererInterface $rendererService
   *   The renderer.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    protected FormBuilderInterface $formBuilderService,
    protected RolProgramaServiceInterface $rolProgramaService,
    protected RendererInterface $rendererService,
    protected LoggerInterface $logger,
  ) {
    // CONTROLLER-READONLY-001: ControllerBase::$entityTypeManager has no type
    // declaration. Subclasses MUST NOT use protected readonly in constructor
    // promotion for inherited properties.
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('form_builder'),
      $container->get('jaraba_andalucia_ei.rol_programa'),
      $container->get('renderer'),
      $container->get('logger.channel.jaraba_andalucia_ei'),
    );
  }

  /**
   * Opens the role assignment form in slide-panel or full page.
   *
   * SLIDE-PANEL-RENDER-001: renderInIsolation() for AJAX, render array for full page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\Response|array<string, mixed>
   *   HTML Response for slide-panel or render array for full page.
   */
  public function asignarRolForm(Request $request): Response|array {
    try {
      $form = $this->formBuilderService->getForm(AsignacionRolForm::class);

      if ($this->isSlidePanelRequest($request)) {
        $form['#action'] = $request->getRequestUri();
        $html = (string) $this->rendererService->renderInIsolation($form);
        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
      }

      return $form;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error loading role assignment form: @msg', [
        '@msg' => $e->getMessage(),
      ]);

      if ($this->isSlidePanelRequest($request)) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => (string) $this->t('Error al cargar el formulario de asignación de rol.'),
        ], 500);
      }

      throw $e;
    }
  }

  /**
   * Confirmation form for role revocation.
   *
   * Loads the user, verifies their current role, and processes revocation
   * via RolProgramaService.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $uid
   *   The user ID whose role will be revoked.
   * @param string $rol
   *   The role to revoke (must be in ROLES_STAFF).
   *
   * @return \Symfony\Component\HttpFoundation\Response|array<string, mixed>
   *   HTML Response for slide-panel or render array for full page.
   */
  public function revocarRolForm(Request $request, string $uid, string $rol): Response|array {
    try {
      $user = $this->entityTypeManager()->getStorage('user')->load($uid);
      if ($user === NULL) {
        if ($this->isSlidePanelRequest($request)) {
          return new JsonResponse([
            'success' => FALSE,
            'message' => (string) $this->t('Usuario no encontrado.'),
          ], 404);
        }
        throw new NotFoundHttpException();
      }

      // Validate the role is a valid staff role.
      if (!in_array($rol, RolProgramaServiceInterface::ROLES_STAFF, TRUE)) {
        if ($this->isSlidePanelRequest($request)) {
          return new JsonResponse([
            'success' => FALSE,
            'message' => (string) $this->t('Rol no válido para revocación.'),
          ], 400);
        }
        throw new NotFoundHttpException();
      }

      // Check if this is a confirmed revocation (POST).
      if ($request->isMethod('POST')) {
        $motivo = (string) $request->request->get('motivo', '');
        $result = $this->rolProgramaService->revocarRol((int) $uid, $rol, $motivo);

        if ($result) {
          $this->messenger()->addStatus(
            $this->t('Rol %rol revocado correctamente para @user.', [
              '%rol' => $this->getRolLabel($rol),
              '@user' => $user->getDisplayName(),
            ])
          );
        }
        else {
          $this->messenger()->addError(
            $this->t('No se pudo revocar el rol %rol para @user.', [
              '%rol' => $this->getRolLabel($rol),
              '@user' => $user->getDisplayName(),
            ])
          );
        }

        if ($this->isSlidePanelRequest($request)) {
          return new JsonResponse([
            'success' => $result,
            'message' => $result
              ? (string) $this->t('Rol revocado correctamente.')
              : (string) $this->t('Error al revocar el rol.'),
          ]);
        }

        return $this->redirect('jaraba_andalucia_ei.equipo_programa');
      }

      // GET: Show the confirmation form.
      $rolLabel = $this->getRolLabel($rol);
      $build = [
        '#theme' => 'revocar_rol_confirmacion',
        '#user' => $user,
        '#rol' => $rol,
        '#rol_label' => $rolLabel,
        '#action_url' => $request->getRequestUri(),
      ];

      if ($this->isSlidePanelRequest($request)) {
        $html = (string) $this->rendererService->renderInIsolation($build);
        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
      }

      return $build;
    }
    catch (NotFoundHttpException $e) {
      throw $e;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error in role revocation for uid @uid, rol @rol: @msg', [
        '@uid' => $uid,
        '@rol' => $rol,
        '@msg' => $e->getMessage(),
      ]);

      if ($this->isSlidePanelRequest($request)) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => (string) $this->t('Error al procesar la revocación del rol.'),
        ], 500);
      }

      throw $e;
    }
  }

  /**
   * Lists all staff members in the current program tenant.
   *
   * TENANT-001: Filtering by tenant is handled by RolProgramaService.
   * ICON-CONVENTION-001: Icons via jaraba_icon categories.
   *
   * @return array<string, mixed>
   *   Render array with #theme => 'equipo_programa'.
   */
  public function equipoPrograma(): array {
    $equipo = [];

    foreach (RolProgramaServiceInterface::ROLES_STAFF as $rol) {
      $usuarios = $this->rolProgramaService->getUsuariosPorRol($rol);
      $equipo[$rol] = [
        'label' => $this->getRolLabel($rol),
        'icon_category' => $this->getRolIconCategory($rol),
        'icon_name' => $this->getRolIconName($rol),
        'usuarios' => $usuarios,
        'count' => count($usuarios),
      ];
    }

    return [
      '#theme' => 'equipo_programa',
      '#equipo' => $equipo,
      '#total_staff' => array_sum(array_column($equipo, 'count')),
      '#cache' => [
        'tags' => ['user_list'],
        'contexts' => ['user.roles'],
      ],
    ];
  }

  /**
   * Returns a translatable label for a program role.
   *
   * @param string $rol
   *   The role constant.
   *
   * @return string
   *   The translated label.
   */
  protected function getRolLabel(string $rol): string {
    $labels = [
      RolProgramaServiceInterface::ROL_COORDINADOR => (string) $this->t('Coordinador/a'),
      RolProgramaServiceInterface::ROL_ORIENTADOR => (string) $this->t('Orientador/a'),
      RolProgramaServiceInterface::ROL_FORMADOR => (string) $this->t('Formador/a'),
    ];

    return $labels[$rol] ?? $rol;
  }

  /**
   * Returns the icon category for a program role.
   *
   * ICON-CONVENTION-001: Icons via jaraba_icon categories.
   *
   * @param string $rol
   *   The role constant.
   *
   * @return string
   *   The icon category.
   */
  protected function getRolIconCategory(string $rol): string {
    return match ($rol) {
      RolProgramaServiceInterface::ROL_COORDINADOR => 'users',
      RolProgramaServiceInterface::ROL_ORIENTADOR => 'education',
      RolProgramaServiceInterface::ROL_FORMADOR => 'education',
      default => 'general',
    };
  }

  /**
   * Returns the icon name for a program role.
   *
   * ICON-CONVENTION-001: Icons via jaraba_icon categories.
   *
   * @param string $rol
   *   The role constant.
   *
   * @return string
   *   The icon name.
   */
  protected function getRolIconName(string $rol): string {
    return match ($rol) {
      RolProgramaServiceInterface::ROL_COORDINADOR => 'team-lead',
      RolProgramaServiceInterface::ROL_ORIENTADOR => 'mentor',
      RolProgramaServiceInterface::ROL_FORMADOR => 'trainer',
      default => 'user',
    };
  }

  /**
   * Detects slide-panel (AJAX) requests.
   *
   * SLIDE-PANEL-RENDER-001: isXmlHttpRequest() AND NOT _wrapper_format.
   * Distinguishes from Drupal modal dialogs which use _wrapper_format.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return bool
   *   TRUE if the request comes from a slide-panel.
   */
  private function isSlidePanelRequest(Request $request): bool {
    return $request->isXmlHttpRequest() && !$request->query->has('_wrapper_format');
  }

}
