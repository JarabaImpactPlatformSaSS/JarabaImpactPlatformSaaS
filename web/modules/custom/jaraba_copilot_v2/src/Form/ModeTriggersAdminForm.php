<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\jaraba_copilot_v2\Service\ModeDetectorService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario de administracion para gestionar triggers de modo del copiloto.
 */
class ModeTriggersAdminForm extends FormBase {

  /**
   * Database connection.
   */
  protected Connection $database;

  /**
   * Constructor.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_copilot_v2_mode_triggers_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $modeFilter = $this->getRequest()->query->get('mode', '');

    $form['filter'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form--inline', 'clearfix']],
    ];

    $modes = $this->getAvailableModes();
    $form['filter']['mode_filter'] = [
      '#type' => 'select',
      '#title' => $this->t('Filtrar por modo'),
      '#options' => ['' => $this->t('- Todos los modos -')] + array_combine($modes, $modes),
      '#default_value' => $modeFilter,
    ];

    $form['filter']['filter_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filtrar'),
      '#submit' => ['::filterSubmit'],
    ];

    // Tabla de triggers existentes.
    $form['triggers_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('ID'),
        $this->t('Modo'),
        $this->t('Palabra trigger'),
        $this->t('Peso'),
        $this->t('Activo'),
        $this->t('Acciones'),
      ],
      '#empty' => $this->t('No hay triggers configurados.'),
    ];

    $triggers = $this->loadTriggers($modeFilter);
    foreach ($triggers as $trigger) {
      $id = $trigger->id;
      $form['triggers_table'][$id]['id'] = [
        '#plain_text' => (string) $id,
      ];
      $form['triggers_table'][$id]['mode'] = [
        '#plain_text' => $trigger->mode,
      ];
      $form['triggers_table'][$id]['trigger_word'] = [
        '#type' => 'textfield',
        '#default_value' => $trigger->trigger_word,
        '#size' => 30,
      ];
      $form['triggers_table'][$id]['weight'] = [
        '#type' => 'number',
        '#default_value' => $trigger->weight,
        '#min' => 1,
        '#max' => 15,
        '#size' => 4,
      ];
      $form['triggers_table'][$id]['active'] = [
        '#type' => 'checkbox',
        '#default_value' => (bool) $trigger->active,
      ];
      $form['triggers_table'][$id]['delete'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Eliminar'),
      ];
    }

    // Seccion para anadir nuevo trigger.
    $form['new_trigger'] = [
      '#type' => 'details',
      '#title' => $this->t('Anadir nuevo trigger'),
      '#open' => FALSE,
    ];

    $form['new_trigger']['new_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Modo'),
      '#options' => array_combine($modes, $modes),
      '#required' => FALSE,
    ];

    $form['new_trigger']['new_word'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Palabra o frase trigger'),
      '#maxlength' => 100,
      '#required' => FALSE,
    ];

    $form['new_trigger']['new_weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Peso'),
      '#min' => 1,
      '#max' => 15,
      '#default_value' => 5,
      '#required' => FALSE,
    ];

    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Guardar cambios'),
    ];

    $form['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Restaurar valores por defecto'),
      '#submit' => ['::resetToDefaults'],
      '#attributes' => ['class' => ['button--danger']],
    ];

    $count = $this->database->select('copilot_mode_triggers', 't')
      ->countQuery()
      ->execute()
      ->fetchField();
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Total de triggers configurados: @count', ['@count' => $count]) . '</p>',
      '#weight' => -10,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $triggersTable = $form_state->getValue('triggers_table') ?? [];
    $now = \Drupal::time()->getRequestTime();

    foreach ($triggersTable as $id => $values) {
      // Eliminar si se marco para borrar.
      if (!empty($values['delete'])) {
        $this->database->delete('copilot_mode_triggers')
          ->condition('id', $id)
          ->execute();
        continue;
      }

      // Actualizar trigger existente.
      $this->database->update('copilot_mode_triggers')
        ->fields([
          'trigger_word' => $values['trigger_word'],
          'weight' => (int) $values['weight'],
          'active' => (int) $values['active'],
          'changed' => $now,
        ])
        ->condition('id', $id)
        ->execute();
    }

    // Anadir nuevo trigger si se proporcionaron datos.
    $newWord = trim($form_state->getValue('new_word') ?? '');
    $newMode = $form_state->getValue('new_mode') ?? '';
    if ($newWord !== '' && $newMode !== '') {
      $this->database->insert('copilot_mode_triggers')
        ->fields([
          'mode' => $newMode,
          'trigger_word' => $newWord,
          'weight' => (int) ($form_state->getValue('new_weight') ?? 5),
          'active' => 1,
          'created' => $now,
          'changed' => $now,
        ])
        ->execute();
      $this->messenger()->addStatus($this->t('Nuevo trigger "@word" anadido al modo @mode.', [
        '@word' => $newWord,
        '@mode' => $newMode,
      ]));
    }

    // Invalidar cache de triggers.
    \Drupal::cache('copilot_triggers')->invalidateAll();
    $this->messenger()->addStatus($this->t('Triggers actualizados correctamente.'));
  }

  /**
   * Submit handler for filter button.
   */
  public function filterSubmit(array &$form, FormStateInterface $form_state): void {
    $mode = $form_state->getValue('mode_filter') ?? '';
    $form_state->setRedirect('jaraba_copilot_v2.mode_triggers_admin', [], [
      'query' => $mode ? ['mode' => $mode] : [],
    ]);
  }

  /**
   * Submit handler to reset triggers to defaults.
   */
  public function resetToDefaults(array &$form, FormStateInterface $form_state): void {
    $this->database->truncate('copilot_mode_triggers')->execute();

    $triggers = ModeDetectorService::MODE_TRIGGERS;
    $now = \Drupal::time()->getRequestTime();
    $count = 0;

    foreach ($triggers as $mode => $modeTriggers) {
      foreach ($modeTriggers as $trigger) {
        $this->database->insert('copilot_mode_triggers')
          ->fields([
            'mode' => $mode,
            'trigger_word' => $trigger['word'],
            'weight' => $trigger['weight'],
            'active' => 1,
            'created' => $now,
            'changed' => $now,
          ])
          ->execute();
        $count++;
      }
    }

    \Drupal::cache('copilot_triggers')->invalidateAll();
    $this->messenger()->addStatus($this->t('@count triggers restaurados a valores por defecto.', ['@count' => $count]));
  }

  /**
   * Loads triggers from the database.
   */
  protected function loadTriggers(string $modeFilter = ''): array {
    $query = $this->database->select('copilot_mode_triggers', 't')
      ->fields('t')
      ->orderBy('mode')
      ->orderBy('weight', 'DESC');

    if ($modeFilter !== '') {
      $query->condition('mode', $modeFilter);
    }

    return $query->execute()->fetchAll();
  }

  /**
   * Gets all available mode names.
   */
  protected function getAvailableModes(): array {
    return array_keys(ModeDetectorService::MODE_TRIGGERS);
  }

}
