<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\jaraba_integrations\Service\AppApprovalService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario de envio de apps/conectores para revision.
 *
 * PROPOSITO:
 * Permite a desarrolladores enviar sus conectores al marketplace
 * para revision y aprobacion por los administradores.
 */
class AppSubmissionForm extends FormBase {

  /**
   * Constructor.
   *
   * @param \Drupal\jaraba_integrations\Service\AppApprovalService $appApproval
   *   Servicio de aprobacion de apps.
   */
  public function __construct(
    protected AppApprovalService $appApproval,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_integrations.app_approval'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_integrations_app_submission';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $connector = NULL): array {
    if ($connector) {
      $connectorId = is_object($connector) ? $connector->id() : $connector;
      $form_state->set('connector_id', $connectorId);
    }

    $form['submission_notes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Notas de envio'),
      '#description' => $this->t('Describe los cambios o notas relevantes para los revisores.'),
      '#rows' => 4,
    ];

    $form['checklist'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Lista de verificacion'),
      '#options' => [
        'tested' => $this->t('He probado el conector en un entorno de desarrollo'),
        'docs' => $this->t('La documentacion esta completa y actualizada'),
        'security' => $this->t('He revisado las practicas de seguridad'),
        'tos' => $this->t('Acepto los terminos de servicio del marketplace'),
      ],
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Enviar a revision'),
      '#button_type' => 'primary',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancelar'),
      '#url' => \Drupal\Core\Url::fromRoute('jaraba_integrations.developer_portal'),
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
    $checklist = array_filter($form_state->getValue('checklist', []));
    if (count($checklist) < 4) {
      $form_state->setErrorByName('checklist', $this->t('Debes completar todos los elementos de la lista de verificacion.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $connectorId = (int) $form_state->get('connector_id');

    if ($this->appApproval->submitForReview($connectorId)) {
      $this->messenger()->addStatus($this->t('El conector se ha enviado a revision correctamente.'));
    }
    else {
      $this->messenger()->addError($this->t('No se pudo enviar el conector a revision.'));
    }

    $form_state->setRedirect('jaraba_integrations.developer_portal');
  }

}
