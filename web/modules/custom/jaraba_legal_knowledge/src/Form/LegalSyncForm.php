<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\jaraba_legal_knowledge\Service\LegalIngestionService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario de sincronizacion manual con el BOE.
 *
 * PROPOSITO:
 * Permite al administrador lanzar una sincronizacion manual con la
 * API del BOE, especificando un rango de fechas opcional. Muestra
 * el estado de la ultima sincronizacion y permite ejecucion inmediata.
 *
 * FLUJO:
 * 1. Se muestra la informacion de la ultima sincronizacion
 * 2. El admin selecciona fecha_desde (requerida) y fecha_hasta (opcional)
 * 3. Submit encola la sincronizacion via LegalIngestionService
 * 4. Boton "Sync Now" ejecuta inmediatamente
 *
 * RUTA:
 * - /admin/config/services/legal-knowledge/sync
 *
 * @package Drupal\jaraba_legal_knowledge\Form
 */
class LegalSyncForm extends FormBase {

  /**
   * El servicio de estado.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * El servicio de ingestion legal.
   *
   * @var \Drupal\jaraba_legal_knowledge\Service\LegalIngestionService
   */
  protected LegalIngestionService $legalIngestion;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static();
    $instance->state = $container->get('state');
    $instance->legalIngestion = $container->get('jaraba_legal_knowledge.legal_ingestion');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jaraba_legal_knowledge_sync_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // --- Informacion de la ultima sincronizacion ---
    $last_sync = $this->state->get('jaraba_legal_knowledge.boe_last_sync', 0);
    $last_sync_formatted = $last_sync
      ? \Drupal::service('date.formatter')->format((int) $last_sync, 'long')
      : $this->t('Nunca');

    $last_sync_norms = $this->state->get('jaraba_legal_knowledge.boe_last_sync_norms_count', 0);
    $last_sync_status = $this->state->get('jaraba_legal_knowledge.boe_last_sync_status', 'unknown');

    $form['sync_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Estado de la ultima sincronizacion'),
      '#open' => TRUE,
    ];

    $status_label = match ($last_sync_status) {
      'success' => $this->t('Completada'),
      'partial' => $this->t('Parcial (con errores)'),
      'failed' => $this->t('Fallida'),
      'running' => $this->t('En ejecucion'),
      default => $this->t('Desconocido'),
    };

    $form['sync_info']['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Parametro'),
        $this->t('Valor'),
      ],
      '#rows' => [
        [
          $this->t('Ultima sincronizacion'),
          $last_sync_formatted,
        ],
        [
          $this->t('Estado'),
          $status_label,
        ],
        [
          $this->t('Normas procesadas'),
          (string) $last_sync_norms,
        ],
      ],
    ];

    // Enlace al estado detallado de sincronizacion.
    $form['sync_info']['status_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Ver estado detallado de sincronizacion'),
      '#url' => Url::fromRoute('jaraba_legal_knowledge.admin_sync_status'),
      '#attributes' => [
        'class' => ['button', 'button--small'],
      ],
    ];

    // --- Formulario de sincronizacion manual ---
    $form['manual_sync'] = [
      '#type' => 'details',
      '#title' => $this->t('Sincronizacion manual'),
      '#open' => TRUE,
      '#description' => $this->t('Ejecute una sincronizacion manual con la API del BOE para un rango de fechas especifico. Si no se especifica fecha de fin, se usa la fecha actual.'),
    ];

    $form['manual_sync']['date_from'] = [
      '#type' => 'date',
      '#title' => $this->t('Fecha desde'),
      '#required' => TRUE,
      '#description' => $this->t('Fecha de inicio del rango a sincronizar (formato: AAAA-MM-DD).'),
      '#default_value' => date('Y-m-d', strtotime('-7 days')),
    ];

    $form['manual_sync']['date_to'] = [
      '#type' => 'date',
      '#title' => $this->t('Fecha hasta'),
      '#required' => FALSE,
      '#description' => $this->t('Fecha de fin del rango (opcional, por defecto la fecha actual).'),
      '#default_value' => date('Y-m-d'),
    ];

    // Acciones: Encolar y Sync Now.
    $form['manual_sync']['actions'] = [
      '#type' => 'actions',
    ];

    $form['manual_sync']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Encolar sincronizacion'),
      '#button_type' => 'primary',
      '#submit' => ['::submitForm'],
    ];

    $form['manual_sync']['actions']['sync_now'] = [
      '#type' => 'submit',
      '#value' => $this->t('Sincronizar ahora'),
      '#submit' => ['::submitSyncNow'],
      '#attributes' => [
        'class' => ['button--danger'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $date_from = $form_state->getValue('date_from');
    $date_to = $form_state->getValue('date_to');

    // Validar que date_from no este en el futuro.
    if (!empty($date_from) && strtotime($date_from) > time()) {
      $form_state->setErrorByName('date_from', $this->t('La fecha de inicio no puede ser futura.'));
    }

    // Validar que date_to >= date_from si ambos estan presentes.
    if (!empty($date_from) && !empty($date_to)) {
      if (strtotime($date_to) < strtotime($date_from)) {
        $form_state->setErrorByName('date_to', $this->t('La fecha de fin debe ser igual o posterior a la fecha de inicio.'));
      }
    }

    // Validar que el rango no sea superior a 90 dias.
    if (!empty($date_from)) {
      $to = !empty($date_to) ? strtotime($date_to) : time();
      $diff_days = ($to - strtotime($date_from)) / 86400;
      if ($diff_days > 90) {
        $form_state->setErrorByName('date_from', $this->t('El rango de sincronizacion no puede superar los 90 dias.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * Encola la sincronizacion para procesamiento asincrono via Queue API.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $date_from = $form_state->getValue('date_from');
    $date_to = $form_state->getValue('date_to') ?: date('Y-m-d');

    try {
      $this->legalIngestion->syncFromBoe($date_from, $date_to, FALSE);

      $this->messenger()->addStatus($this->t('Sincronizacion con el BOE encolada correctamente para el rango @from a @to. Se procesara en la proxima ejecucion de cron.', [
        '@from' => $date_from,
        '@to' => $date_to,
      ]));
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_legal_knowledge')->error('Error al encolar sincronizacion BOE: @message', [
        '@message' => $e->getMessage(),
      ]);
      $this->messenger()->addError($this->t('Error al encolar la sincronizacion. Revise los logs para mas detalles.'));
    }
  }

  /**
   * Submit handler: Ejecuta la sincronizacion inmediatamente.
   *
   * Lanza la sincronizacion con el BOE de forma sincrona (directa),
   * sin pasar por la cola. Util para sincronizaciones urgentes o
   * rangos de fecha pequenos.
   *
   * @param array $form
   *   El formulario.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   El estado del formulario.
   */
  public function submitSyncNow(array &$form, FormStateInterface $form_state) {
    $date_from = $form_state->getValue('date_from');
    $date_to = $form_state->getValue('date_to') ?: date('Y-m-d');

    try {
      $result = $this->legalIngestion->syncFromBoe($date_from, $date_to, TRUE);

      $norms_count = $result['norms_processed'] ?? 0;
      $this->state->set('jaraba_legal_knowledge.boe_last_sync', \Drupal::time()->getRequestTime());
      $this->state->set('jaraba_legal_knowledge.boe_last_sync_norms_count', $norms_count);
      $this->state->set('jaraba_legal_knowledge.boe_last_sync_status', 'success');

      $this->messenger()->addStatus($this->t('Sincronizacion completada. @count normas procesadas del rango @from a @to.', [
        '@count' => $norms_count,
        '@from' => $date_from,
        '@to' => $date_to,
      ]));
    }
    catch (\Exception $e) {
      $this->state->set('jaraba_legal_knowledge.boe_last_sync_status', 'failed');

      $this->getLogger('jaraba_legal_knowledge')->error('Error en sincronizacion inmediata BOE: @message', [
        '@message' => $e->getMessage(),
      ]);
      $this->messenger()->addError($this->t('Error durante la sincronizacion inmediata: @message', [
        '@message' => $e->getMessage(),
      ]));
    }
  }

}
