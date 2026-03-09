<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\jaraba_andalucia_ei\Service\StoExportService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario de exportación de datos al STO.
 */
class StoExportForm extends FormBase {

  /**
   * Constructor.
   *
   * @param \Drupal\jaraba_andalucia_ei\Service\StoExportService $stoExportService
   *   Servicio de exportación STO.
   */
  public function __construct(
    protected StoExportService $stoExportService,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_andalucia_ei.sto_export')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_andalucia_ei_sto_export_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Exportar datos de participantes al Servicio Telemático de Orientación (STO). Se incluirán los participantes con estado de sincronización pendiente.') . '</p>',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['export'] = [
      '#type' => 'submit',
      '#value' => $this->t('Exportar XML'),
      '#button_type' => 'primary',
    ];

    $form['actions']['download'] = [
      '#type' => 'link',
      '#title' => $this->t('Descargar XML'),
      '#url' => Url::fromRoute('jaraba_andalucia_ei.sto_export_download'),
      '#attributes' => [
        'class' => ['button'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $resultado = $this->stoExportService->sincronizarConSto();

    if ($resultado['success']) {
      $this->messenger()->addStatus(
        $this->t('Sincronización completada: @count participantes exportados.', [
          '@count' => $resultado['count'] ?? 0,
        ])
      );
    }
    else {
      $this->messenger()->addWarning($resultado['message']);
    }
  }

}
