<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;
use Drupal\jaraba_andalucia_ei\Service\SesionProgramadaService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for SesionProgramadaEi entity.
 *
 * PREMIUM-FORMS-PATTERN-001: Extends PremiumEntityFormBase.
 * Outlook-style granular recurrence with cascading #states visibility,
 * context-aware defaults, and server-side validation.
 */
class SesionProgramadaEiForm extends PremiumEntityFormBase {

  /**
   * Day key to ISO-8601 numeric day (1=Mon ... 7=Sun).
   */
  private const DAY_MAP = [
    'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4,
    'fri' => 5, 'sat' => 6, 'sun' => 7,
  ];

  /**
   * Reverse: ISO numeric day to key.
   */
  private const DAY_MAP_REVERSE = [
    1 => 'mon', 2 => 'tue', 3 => 'wed', 4 => 'thu',
    5 => 'fri', 6 => 'sat', 7 => 'sun',
  ];

  /**
   * The sesion programada service.
   */
  protected ?SesionProgramadaService $sesionService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    // OPTIONAL-CROSSMODULE-001: same module, safe hard reference.
    $instance->sesionService = $container->has('jaraba_andalucia_ei.sesion_programada')
      ? $container->get('jaraba_andalucia_ei.sesion_programada')
      : NULL;
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'datos_principales' => [
        'label' => $this->t('Datos Principales'),
        'icon' => ['category' => 'ui', 'name' => 'calendar'],
        'description' => $this->t('Título, tipo de sesión y fase del programa.'),
        'fields' => [
          'titulo',
          'descripcion',
          'tipo_sesion',
          'fase_programa',
          'accion_formativa_id',
          'contenido_sto',
          'subcontenido_sto',
        ],
      ],
      'horario' => [
        'label' => $this->t('Horario'),
        'icon' => ['category' => 'ui', 'name' => 'clock'],
        'description' => $this->t('Fecha, hora y lugar de la sesión.'),
        'fields' => [
          'fecha',
          'hora_inicio',
          'hora_fin',
          'modalidad',
          'lugar_descripcion',
          'lugar_url',
        ],
      ],
      'facilitador' => [
        'label' => $this->t('Facilitador/a'),
        'icon' => ['category' => 'users', 'name' => 'user-graduate'],
        'description' => $this->t('Profesional que facilita la sesión.'),
        'fields' => [
          'facilitador_id',
          'facilitador_nombre',
        ],
      ],
      'capacidad' => [
        'label' => $this->t('Capacidad y Estado'),
        'icon' => ['category' => 'ui', 'name' => 'users'],
        'description' => $this->t('Plazas disponibles y estado de la sesión.'),
        'fields' => [
          'max_plazas',
          'plazas_ocupadas',
          'estado',
        ],
      ],
      'recurrencia' => [
        'label' => $this->t('Recurrencia'),
        'icon' => ['category' => 'ui', 'name' => 'refresh'],
        'description' => $this->t('Configuración de sesiones recurrentes.'),
        'fields' => [
          'es_recurrente',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Sprint 19: Pre-fill fecha/hora from calendar dateClick query params.
    // When creating a session from the calendar, the JS passes ?fecha=&hora_inicio=&hora_fin=.
    if ($this->entity->isNew()) {
      $this->prefillFromCalendarParams($form);
    }

    // Attach premium recurrence CSS.
    $form['#attached']['library'][] = 'jaraba_andalucia_ei/recurrence-form';

    // PremiumEntityFormBase physically moves entity fields into
    // premium_section_* containers. We manipulate those directly.
    $section = &$form['premium_section_recurrencia'];

    // Hide raw JSON fields — replaced by structured UI below.
    foreach (['recurrencia_patron', 'sesion_padre_id'] as $hide) {
      if (isset($section[$hide])) {
        $section[$hide]['#access'] = FALSE;
      }
      if (isset($form[$hide])) {
        $form[$hide]['#access'] = FALSE;
      }
      if (isset($form['premium_section_other'][$hide])) {
        $form['premium_section_other'][$hide]['#access'] = FALSE;
      }
    }

    // Parse and normalize existing patron for defaults.
    $patron = $this->parsePatronDefaults();

    // Derive context-aware defaults from entity's fecha.
    $fechaDefaults = $this->deriveFechaDefaults();

    // Style es_recurrente as a prominent activation toggle card.
    if (isset($section['es_recurrente'])) {
      $section['es_recurrente']['#prefix'] = '<div class="recurrence-toggle-card">';
      $section['es_recurrente']['#suffix'] = '</div>';
    }

    // Master visibility condition: es_recurrente checkbox checked.
    $recChecked = [':input[name="es_recurrente[value]"]' => ['checked' => TRUE]];

    // ── Tipo de recurrencia — prominent 4-card selector ─────────
    // Drupal 11: #attributes on radios propagates to <input> children,
    // NOT to the wrapper div. Use #prefix/#suffix for our CSS hooks.
    $section['tipo_recurrencia'] = [
      '#type' => 'radios',
      '#title' => $this->t('Tipo de recurrencia'),
      '#options' => [
        'daily' => $this->t('Diaria'),
        'weekly' => $this->t('Semanal'),
        'monthly' => $this->t('Mensual'),
        'yearly' => $this->t('Anual'),
      ],
      '#default_value' => $patron['type'] ?? 'weekly',
      '#states' => ['visible' => $recChecked],
      '#weight' => 10,
      '#prefix' => '<div class="premium-radio-cards recurrence-tipo-selector">',
      '#suffix' => '</div>',
    ];

    // ── Panel: Recurrencia Diaria ────────────────────────────────
    $section['panel_daily'] = [
      '#type' => 'container',
      '#states' => ['visible' => $recChecked + [
        ':input[name="tipo_recurrencia"]' => ['value' => 'daily'],
      ]],
      '#weight' => 11,
      '#attributes' => ['class' => ['recurrence-subpanel', 'recurrence-subpanel--daily']],
    ];
    $section['panel_daily']['header'] = [
      '#type' => 'inline_template',
      '#template' => '<div class="recurrence-subpanel__header"><span class="recurrence-subpanel__title">{{ title }}</span></div>',
      '#context' => ['title' => $this->t('Configuración diaria')],
      '#weight' => -10,
    ];
    $section['panel_daily']['daily_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Repetir cada'),
      '#field_suffix' => $this->t('día(s)'),
      '#min' => 1,
      '#max' => 365,
      '#default_value' => ($patron['type'] ?? '') === 'daily' ? ($patron['interval'] ?? 1) : 1,
    ];
    $section['panel_daily']['daily_weekdays_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Solo días laborables (lunes a viernes)'),
      '#description' => $this->t('Si se activa, los fines de semana se omiten automáticamente.'),
      '#default_value' => $patron['daily_weekdays_only'] ?? FALSE,
    ];

    // ── Panel: Recurrencia Semanal ───────────────────────────────
    $dayOptions = [
      'mon' => $this->t('Lun'),
      'tue' => $this->t('Mar'),
      'wed' => $this->t('Mié'),
      'thu' => $this->t('Jue'),
      'fri' => $this->t('Vie'),
      'sat' => $this->t('Sáb'),
      'sun' => $this->t('Dom'),
    ];
    $weeklyDaysDefault = $patron['days_of_week'] ?? [];
    if (empty($weeklyDaysDefault) && $fechaDefaults['day_key']) {
      $weeklyDaysDefault = [$fechaDefaults['day_key']];
    }

    $section['panel_weekly'] = [
      '#type' => 'container',
      '#states' => ['visible' => $recChecked + [
        ':input[name="tipo_recurrencia"]' => ['value' => 'weekly'],
      ]],
      '#weight' => 12,
      '#attributes' => ['class' => ['recurrence-subpanel', 'recurrence-subpanel--weekly']],
    ];
    $section['panel_weekly']['header'] = [
      '#type' => 'inline_template',
      '#template' => '<div class="recurrence-subpanel__header"><span class="recurrence-subpanel__title">{{ title }}</span></div>',
      '#context' => ['title' => $this->t('Configuración semanal')],
      '#weight' => -10,
    ];
    $section['panel_weekly']['weekly_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Repetir cada'),
      '#field_suffix' => $this->t('semana(s)'),
      '#min' => 1,
      '#max' => 52,
      '#default_value' => ($patron['type'] ?? '') === 'weekly' ? ($patron['interval'] ?? 1) : 1,
    ];
    $section['panel_weekly']['weekly_days'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Los días'),
      '#options' => $dayOptions,
      '#default_value' => $weeklyDaysDefault,
      '#description' => $this->t('Selecciona al menos un día. Si no seleccionas ninguno, se usará el día de la fecha de la sesión.'),
      '#prefix' => '<div class="recurrence-day-chips">',
      '#suffix' => '</div>',
    ];

    // ── Panel: Recurrencia Mensual ───────────────────────────────
    $ordinalOptions = [
      'first' => $this->t('Primero/a'),
      'second' => $this->t('Segundo/a'),
      'third' => $this->t('Tercero/a'),
      'fourth' => $this->t('Cuarto/a'),
      'last' => $this->t('Último/a'),
    ];
    $weekdayOptions = [
      'mon' => $this->t('Lunes'),
      'tue' => $this->t('Martes'),
      'wed' => $this->t('Miércoles'),
      'thu' => $this->t('Jueves'),
      'fri' => $this->t('Viernes'),
      'sat' => $this->t('Sábado'),
      'sun' => $this->t('Domingo'),
    ];

    $section['panel_monthly'] = [
      '#type' => 'container',
      '#states' => ['visible' => $recChecked + [
        ':input[name="tipo_recurrencia"]' => ['value' => 'monthly'],
      ]],
      '#weight' => 13,
      '#attributes' => ['class' => ['recurrence-subpanel', 'recurrence-subpanel--monthly']],
    ];
    $section['panel_monthly']['header'] = [
      '#type' => 'inline_template',
      '#template' => '<div class="recurrence-subpanel__header"><span class="recurrence-subpanel__title">{{ title }}</span></div>',
      '#context' => ['title' => $this->t('Configuración mensual')],
      '#weight' => -10,
    ];
    $section['panel_monthly']['monthly_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Repetir cada'),
      '#field_suffix' => $this->t('mes(es)'),
      '#min' => 1,
      '#max' => 24,
      '#default_value' => ($patron['type'] ?? '') === 'monthly' ? ($patron['interval'] ?? 1) : 1,
    ];
    $section['panel_monthly']['monthly_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Repetir el'),
      '#options' => [
        'day_of_month' => $this->t('Día del mes'),
        'ordinal_weekday' => $this->t('Día ordinal (ej: «segundo martes»)'),
      ],
      '#default_value' => $patron['monthly_type'] ?? 'day_of_month',
      '#prefix' => '<div class="premium-radio-cards">',
      '#suffix' => '</div>',
    ];
    $section['panel_monthly']['monthly_day'] = [
      '#type' => 'number',
      '#title' => $this->t('Día'),
      '#description' => $this->t('Si el mes tiene menos días, se usará el último día del mes.'),
      '#min' => 1,
      '#max' => 31,
      '#default_value' => $patron['day_of_month'] ?? $fechaDefaults['day'],
      '#states' => ['visible' => [
        ':input[name="monthly_type"]' => ['value' => 'day_of_month'],
      ]],
    ];
    $section['panel_monthly']['monthly_ordinal'] = [
      '#type' => 'select',
      '#title' => $this->t('Ordinal'),
      '#options' => $ordinalOptions,
      '#default_value' => $patron['weekday_ordinal'] ?? 'first',
      '#states' => ['visible' => [
        ':input[name="monthly_type"]' => ['value' => 'ordinal_weekday'],
      ]],
    ];
    $section['panel_monthly']['monthly_weekday'] = [
      '#type' => 'select',
      '#title' => $this->t('Día de la semana'),
      '#options' => $weekdayOptions,
      '#default_value' => $patron['weekday'] ?? ($fechaDefaults['day_key'] ?: 'mon'),
      '#states' => ['visible' => [
        ':input[name="monthly_type"]' => ['value' => 'ordinal_weekday'],
      ]],
    ];

    // ── Panel: Recurrencia Anual ─────────────────────────────────
    $monthOptions = [
      1 => $this->t('Enero'), 2 => $this->t('Febrero'),
      3 => $this->t('Marzo'), 4 => $this->t('Abril'),
      5 => $this->t('Mayo'), 6 => $this->t('Junio'),
      7 => $this->t('Julio'), 8 => $this->t('Agosto'),
      9 => $this->t('Septiembre'), 10 => $this->t('Octubre'),
      11 => $this->t('Noviembre'), 12 => $this->t('Diciembre'),
    ];

    $section['panel_yearly'] = [
      '#type' => 'container',
      '#states' => ['visible' => $recChecked + [
        ':input[name="tipo_recurrencia"]' => ['value' => 'yearly'],
      ]],
      '#weight' => 14,
      '#attributes' => ['class' => ['recurrence-subpanel', 'recurrence-subpanel--yearly']],
    ];
    $section['panel_yearly']['header'] = [
      '#type' => 'inline_template',
      '#template' => '<div class="recurrence-subpanel__header"><span class="recurrence-subpanel__title">{{ title }}</span></div>',
      '#context' => ['title' => $this->t('Configuración anual')],
      '#weight' => -10,
    ];
    $section['panel_yearly']['yearly_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Repetir el'),
      '#options' => [
        'date' => $this->t('Fecha fija (ej: «15 de marzo»)'),
        'ordinal_weekday' => $this->t('Día ordinal (ej: «2.º martes de marzo»)'),
      ],
      '#default_value' => $patron['yearly_type'] ?? 'date',
      '#prefix' => '<div class="premium-radio-cards">',
      '#suffix' => '</div>',
    ];
    $section['panel_yearly']['yearly_month'] = [
      '#type' => 'select',
      '#title' => $this->t('Mes'),
      '#options' => $monthOptions,
      '#default_value' => $patron['yearly_month'] ?? $fechaDefaults['month'],
    ];
    $section['panel_yearly']['yearly_day'] = [
      '#type' => 'number',
      '#title' => $this->t('Día'),
      '#min' => 1,
      '#max' => 31,
      '#default_value' => $patron['yearly_day'] ?? $fechaDefaults['day'],
      '#states' => ['visible' => [
        ':input[name="yearly_type"]' => ['value' => 'date'],
      ]],
    ];
    $section['panel_yearly']['yearly_ordinal'] = [
      '#type' => 'select',
      '#title' => $this->t('Ordinal'),
      '#options' => $ordinalOptions,
      '#default_value' => $patron['yearly_ordinal'] ?? 'first',
      '#states' => ['visible' => [
        ':input[name="yearly_type"]' => ['value' => 'ordinal_weekday'],
      ]],
    ];
    $section['panel_yearly']['yearly_weekday'] = [
      '#type' => 'select',
      '#title' => $this->t('Día de la semana'),
      '#options' => $weekdayOptions,
      '#default_value' => $patron['yearly_weekday'] ?? ($fechaDefaults['day_key'] ?: 'mon'),
      '#states' => ['visible' => [
        ':input[name="yearly_type"]' => ['value' => 'ordinal_weekday'],
      ]],
    ];

    // ── Rango de repetición ──────────────────────────────────────
    $section['panel_range'] = [
      '#type' => 'container',
      '#states' => ['visible' => $recChecked],
      '#weight' => 20,
      '#attributes' => ['class' => ['recurrence-subpanel', 'recurrence-subpanel--range']],
    ];
    $section['panel_range']['header'] = [
      '#type' => 'inline_template',
      '#template' => '<div class="recurrence-subpanel__header"><span class="recurrence-subpanel__title">{{ title }}</span></div>',
      '#context' => ['title' => $this->t('Rango de repetición')],
      '#weight' => -10,
    ];
    $section['panel_range']['range_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Finalización'),
      '#options' => [
        'count' => $this->t('Después de N ocurrencias'),
        'end_date' => $this->t('En una fecha concreta'),
        'no_end' => $this->t('Sin fin (máx. 1 año)'),
      ],
      '#default_value' => $patron['range_type'] ?? 'count',
      '#prefix' => '<div class="premium-radio-cards">',
      '#suffix' => '</div>',
    ];
    $section['panel_range']['range_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Número de ocurrencias'),
      '#min' => 1,
      '#max' => 365,
      '#default_value' => $patron['count'] ?? 10,
      '#states' => ['visible' => [
        ':input[name="range_type"]' => ['value' => 'count'],
      ]],
    ];
    $section['panel_range']['range_end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Fecha de finalización'),
      '#description' => $this->t('Última fecha posible para sesiones generadas.'),
      '#default_value' => $patron['end_date'] ?? '',
      '#states' => ['visible' => [
        ':input[name="range_type"]' => ['value' => 'end_date'],
      ]],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $esRecurrente = (bool) $form_state->getValue(['es_recurrente', 'value']);
    if (!$esRecurrente) {
      return;
    }

    $tipo = $form_state->getValue('tipo_recurrencia') ?? 'weekly';

    // Weekly: at least one day must be selected (server-side fallback).
    if ($tipo === 'weekly') {
      $days = array_filter($form_state->getValue('weekly_days') ?? []);
      if (empty($days)) {
        // Not an error — service falls back to base date's day.
        // But warn the user for clarity.
        $this->messenger()->addWarning($this->t(
          'No seleccionaste días de la semana. Se usará el mismo día que la fecha de la sesión.'
        ));
      }
    }

    // Range: end_date must be after session date.
    $rangeType = $form_state->getValue('range_type') ?? 'count';
    if ($rangeType === 'end_date') {
      $endDate = $form_state->getValue('range_end_date');
      $fechaSesion = $form_state->getValue('fecha') ?? $this->entity->get('fecha')->value;
      if (!empty($endDate) && !empty($fechaSesion)) {
        // Normalize: fecha may come as array from entity field widget,
        // and may include time component (Y-m-d\TH:i:s). Truncate to Y-m-d.
        $fechaStr = is_array($fechaSesion) ? ($fechaSesion['value'] ?? '') : $fechaSesion;
        $fechaStr = substr((string) $fechaStr, 0, 10);
        if ($endDate <= $fechaStr) {
          $form_state->setErrorByName('range_end_date', $this->t(
            'La fecha de finalización debe ser posterior a la fecha de la sesión.'
          ));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;

    // Serialize Outlook-style recurrence fields into JSON patron.
    $esRecurrente = (bool) $form_state->getValue(['es_recurrente', 'value']);
    if ($esRecurrente) {
      $entity->set('recurrencia_patron', json_encode(
        $this->buildPatronFromFormState($form_state),
        JSON_THROW_ON_ERROR,
      ));
    }
    else {
      $entity->set('recurrencia_patron', NULL);
    }

    $isNew = $entity->isNew();
    $result = parent::save($form, $form_state);

    // Trigger recurrence expansion for new recurrent sessions.
    // PRESAVE-RESILIENCE-001: optional service with try-catch.
    if ($isNew && $esRecurrente && $this->sesionService !== NULL) {
      try {
        $sesionesHijas = $this->sesionService->expandirRecurrencia($entity);
        if (!empty($sesionesHijas)) {
          $this->messenger()->addStatus($this->t(
            'Se han generado @count sesiones recurrentes adicionales.',
            ['@count' => count($sesionesHijas)]
          ));
        }
      }
      catch (\Throwable) {
        $this->messenger()->addWarning($this->t(
          'La sesión se guardó pero hubo un error al generar las sesiones recurrentes.'
        ));
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'calendar'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getCharacterLimits(): array {
    return [
      'titulo' => 255,
    ];
  }

  /**
   * Parses and normalizes the entity's recurrencia_patron JSON.
   *
   * Handles both legacy {frequency, count} and new Outlook-style format.
   *
   * @return array<string, mixed>
   *   Normalized patron, or empty array if none/invalid.
   */
  private function parsePatronDefaults(): array {
    $patronJson = $this->entity->get('recurrencia_patron')->value ?? '';
    if (empty($patronJson)) {
      return [];
    }

    try {
      $patron = json_decode($patronJson, TRUE, 512, JSON_THROW_ON_ERROR);
    }
    catch (\JsonException) {
      return [];
    }

    // Normalize legacy format for form defaults.
    if (isset($patron['frequency']) && !isset($patron['type'])) {
      $legacyMap = [
        'weekly' => ['type' => 'weekly', 'interval' => 1],
        'biweekly' => ['type' => 'weekly', 'interval' => 2],
        'monthly' => ['type' => 'monthly', 'interval' => 1, 'monthly_type' => 'day_of_month'],
      ];
      $normalized = $legacyMap[$patron['frequency'] ?? 'weekly'] ?? $legacyMap['weekly'];
      $normalized['range_type'] = 'count';
      $normalized['count'] = (int) ($patron['count'] ?? 4);
      return $normalized;
    }

    return $patron;
  }

  /**
   * Derives context-aware defaults from the entity's fecha field.
   *
   * @return array{day: int, month: int, day_key: string|null}
   *   Day of month, month number, and day-of-week key.
   */
  /**
   * Pre-fills form fields from calendar query parameters.
   *
   * When creating a session from the calendar's dateClick/select handlers,
   * the JS passes query params: ?fecha=YYYY-MM-DD&hora_inicio=HH:MM&hora_fin=HH:MM.
   * This method reads those params and sets default values on the form fields.
   *
   * The fields live inside premium_section_* containers after PremiumEntityFormBase
   * moves them. We search both the section containers and the top level.
   */
  private function prefillFromCalendarParams(array &$form): void {
    $request = $this->getRequest();
    $fecha = $request->query->get('fecha', '');
    $horaInicio = $request->query->get('hora_inicio', '');
    $horaFin = $request->query->get('hora_fin', '');

    // Validate fecha format (Y-m-d) to prevent injection.
    if ($fecha && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
      $fecha = '';
    }
    // Validate time format (HH:MM).
    if ($horaInicio && !preg_match('/^\d{2}:\d{2}$/', $horaInicio)) {
      $horaInicio = '';
    }
    if ($horaFin && !preg_match('/^\d{2}:\d{2}$/', $horaFin)) {
      $horaFin = '';
    }

    if (!$fecha && !$horaInicio && !$horaFin) {
      return;
    }

    // PremiumEntityFormBase moves fields into premium_section_* containers.
    // We need to find and set defaults in the correct location.
    $fieldMap = [];
    if ($fecha) {
      $fieldMap['fecha'] = $fecha;
    }
    if ($horaInicio) {
      $fieldMap['hora_inicio'] = $horaInicio;
    }
    if ($horaFin) {
      $fieldMap['hora_fin'] = $horaFin;
    }

    foreach ($fieldMap as $fieldName => $value) {
      // Check top-level form.
      if (isset($form[$fieldName])) {
        $form[$fieldName]['widget'][0]['value']['#default_value'] = $value;
      }
      // Check inside premium sections.
      foreach ($form as $key => &$element) {
        if (str_starts_with($key, 'premium_section_') && isset($element[$fieldName])) {
          $element[$fieldName]['widget'][0]['value']['#default_value'] = $value;
          break;
        }
      }
      unset($element);
    }
  }

  private function deriveFechaDefaults(): array {
    $fecha = $this->entity->get('fecha')->value ?? '';
    if (empty($fecha)) {
      return ['day' => 1, 'month' => 1, 'day_key' => NULL];
    }

    try {
      $dt = new \DateTimeImmutable($fecha);
      return [
        'day' => (int) $dt->format('j'),
        'month' => (int) $dt->format('n'),
        'day_key' => self::DAY_MAP_REVERSE[(int) $dt->format('N')] ?? NULL,
      ];
    }
    catch (\Throwable) {
      return ['day' => 1, 'month' => 1, 'day_key' => NULL];
    }
  }

  /**
   * Builds the patron JSON array from submitted form values.
   *
   * @return array<string, mixed>
   *   The structured recurrence pattern.
   */
  private function buildPatronFromFormState(FormStateInterface $form_state): array {
    $tipo = $form_state->getValue('tipo_recurrencia') ?? 'weekly';
    $patron = ['type' => $tipo];

    switch ($tipo) {
      case 'daily':
        $patron['interval'] = max(1, (int) ($form_state->getValue('daily_interval') ?? 1));
        $patron['daily_weekdays_only'] = (bool) $form_state->getValue('daily_weekdays_only');
        break;

      case 'weekly':
        $patron['interval'] = max(1, (int) ($form_state->getValue('weekly_interval') ?? 1));
        $days = array_filter($form_state->getValue('weekly_days') ?? []);
        $patron['days_of_week'] = array_values($days);
        break;

      case 'monthly':
        $patron['interval'] = max(1, (int) ($form_state->getValue('monthly_interval') ?? 1));
        $patron['monthly_type'] = $form_state->getValue('monthly_type') ?? 'day_of_month';
        if ($patron['monthly_type'] === 'day_of_month') {
          $patron['day_of_month'] = min(31, max(1, (int) ($form_state->getValue('monthly_day') ?? 1)));
        }
        else {
          $patron['weekday_ordinal'] = $form_state->getValue('monthly_ordinal') ?? 'first';
          $patron['weekday'] = $form_state->getValue('monthly_weekday') ?? 'mon';
        }
        break;

      case 'yearly':
        $patron['yearly_type'] = $form_state->getValue('yearly_type') ?? 'date';
        $patron['yearly_month'] = (int) ($form_state->getValue('yearly_month') ?? 1);
        if ($patron['yearly_type'] === 'date') {
          $patron['yearly_day'] = min(31, max(1, (int) ($form_state->getValue('yearly_day') ?? 1)));
        }
        else {
          $patron['yearly_ordinal'] = $form_state->getValue('yearly_ordinal') ?? 'first';
          $patron['yearly_weekday'] = $form_state->getValue('yearly_weekday') ?? 'mon';
        }
        break;
    }

    // Range.
    $rangeType = $form_state->getValue('range_type') ?? 'count';
    $patron['range_type'] = $rangeType;
    if ($rangeType === 'count') {
      $patron['count'] = min(365, max(1, (int) ($form_state->getValue('range_count') ?? 10)));
    }
    elseif ($rangeType === 'end_date') {
      $patron['end_date'] = $form_state->getValue('range_end_date') ?? '';
    }

    return $patron;
  }

}
