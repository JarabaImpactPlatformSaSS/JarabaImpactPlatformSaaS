<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\jaraba_andalucia_ei\Service\RolProgramaServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for assigning a staff role in the Andalucía +ei program.
 *
 * This is a standalone FormBase (NOT PremiumEntityFormBase) because it
 * does not create or edit an entity directly — it calls
 * RolProgramaService::asignarRol() to assign a Drupal role with audit trail.
 *
 * TENANT-001: Tenant filtering handled by RolProgramaService.
 */
class AsignacionRolForm extends FormBase {

  /**
   * Constructor.
   *
   * @param \Drupal\jaraba_andalucia_ei\Service\RolProgramaServiceInterface $rolProgramaService
   *   The program role service.
   */
  public function __construct(
    protected RolProgramaServiceInterface $rolProgramaService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_andalucia_ei.rol_programa'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_andalucia_ei_asignacion_rol_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Asignar un rol de equipo técnico a un usuario del programa. El rol determina los permisos y el acceso a funcionalidades específicas de Andalucía +ei.') . '</p>',
    ];

    $form['usuario'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Usuario'),
      '#description' => $this->t('Buscar por nombre o correo electrónico del usuario al que se asignará el rol.'),
      '#target_type' => 'user',
      '#required' => TRUE,
      '#selection_settings' => [
        'include_anonymous' => FALSE,
      ],
    ];

    $form['rol_programa'] = [
      '#type' => 'radios',
      '#title' => $this->t('Rol en el programa'),
      '#description' => $this->t('Seleccionar el rol que se asignará al usuario.'),
      '#options' => [
        RolProgramaServiceInterface::ROL_COORDINADOR => $this->t('Coordinador/a'),
        RolProgramaServiceInterface::ROL_ORIENTADOR => $this->t('Orientador/a'),
        RolProgramaServiceInterface::ROL_FORMADOR => $this->t('Formador/a'),
      ],
      '#required' => TRUE,
      '#default_value' => RolProgramaServiceInterface::ROL_ORIENTADOR,
    ];

    // Role descriptions as markup after radios for context.
    $form['rol_descriptions'] = [
      '#type' => 'markup',
      '#markup' => '<div class="rol-descriptions">'
      . '<p><strong>' . $this->t('Coordinador/a') . ':</strong> '
      . $this->t('Gestión global del programa, acceso a todos los participantes, informes y configuración. Puede asignar y revocar roles.') . '</p>'
      . '<p><strong>' . $this->t('Orientador/a') . ':</strong> '
      . $this->t('Seguimiento individualizado de participantes asignados, itinerarios personalizados, hojas de servicio y mentorías.') . '</p>'
      . '<p><strong>' . $this->t('Formador/a') . ':</strong> '
      . $this->t('Impartición de acciones formativas, control de asistencia, evaluaciones y material didáctico.') . '</p>'
      . '</div>',
    ];

    $form['motivo'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Motivo de la asignación'),
      '#description' => $this->t('Opcional pero recomendado. Se registrará en el historial de auditoría FSE+ del programa.'),
      '#required' => FALSE,
      '#rows' => 3,
      '#maxlength' => 500,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Asignar rol'),
      '#button_type' => 'primary',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancelar'),
      '#url' => Url::fromRoute('jaraba_andalucia_ei.equipo_programa'),
      '#attributes' => [
        'class' => ['button', 'button--secondary'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $uid = $form_state->getValue('usuario');
    $rol = $form_state->getValue('rol_programa');

    if ($uid !== NULL && $uid !== '' && $uid !== 0) {
      // Verify the user exists.
      $user = \Drupal::entityTypeManager()->getStorage('user')->load($uid);
      if ($user === NULL) {
        $form_state->setErrorByName('usuario', $this->t('El usuario seleccionado no existe.'));
        return;
      }

      // Check if the user already has this role.
      if ($rol !== NULL && $rol !== '' && $this->rolProgramaService->tieneRol($user, (string) $rol)) {
        $form_state->setErrorByName('rol_programa', $this->t('El usuario @user ya tiene el rol %rol asignado.', [
          '@user' => $user->getDisplayName(),
          '%rol' => $form['rol_programa']['#options'][$rol] ?? $rol,
        ]));
      }
    }

    // Validate the role is a valid staff role.
    if ($rol !== NULL && $rol !== '' && !in_array($rol, RolProgramaServiceInterface::ROLES_STAFF, TRUE)) {
      $form_state->setErrorByName('rol_programa', $this->t('El rol seleccionado no es válido.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $uid = (int) $form_state->getValue('usuario');
    $rol = (string) $form_state->getValue('rol_programa');
    $motivo = (string) $form_state->getValue('motivo');

    $result = $this->rolProgramaService->asignarRol($uid, $rol, $motivo);

    if ($result) {
      $user = \Drupal::entityTypeManager()->getStorage('user')->load($uid);
      $displayName = $user !== NULL ? $user->getDisplayName() : (string) $uid;

      $this->messenger()->addStatus(
        $this->t('Rol %rol asignado correctamente a @user.', [
          '%rol' => $form['rol_programa']['#options'][$rol] ?? $rol,
          '@user' => $displayName,
        ])
      );
    }
    else {
      $this->messenger()->addError(
        $this->t('No se pudo asignar el rol. Consulte los registros del sistema para más información.')
      );
    }

    $form_state->setRedirectUrl(
      Url::fromRoute('jaraba_andalucia_ei.equipo_programa')
    );
  }

}
