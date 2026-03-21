<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar PromotionConfig entities.
 *
 * Estructura por secciones:
 * 1. Informacion General (label, description, type, vertical)
 * 2. Datos Destacados (highlight_values como textarea key=value)
 * 3. Llamadas a la Accion (cta_url, cta_label, secondary)
 * 4. Programacion (date_start, date_end, status, priority)
 * 5. Instrucciones IA (copilot_instruction)
 */
class PromotionConfigForm extends EntityForm {

  /**
   * Verticales canonicas (VERTICAL-CANONICAL-001) + global.
   */
  private const VERTICALS = [
    'global' => 'Global (todas las verticales)',
    'empleabilidad' => 'Empleabilidad',
    'emprendimiento' => 'Emprendimiento',
    'comercioconecta' => 'ComercioConecta',
    'agroconecta' => 'AgroConecta',
    'jarabalex' => 'JarabaLex',
    'serviciosconecta' => 'ServiciosConecta',
    'andalucia_ei' => 'Andalucía +ei',
    'formacion' => 'Formación',
    'jaraba_content_hub' => 'Content Hub',
    'demo' => 'Demo',
  ];

  /**
   * Tipos de promocion.
   */
  private const TYPES = [
    'program' => 'Programa (formación, inserción, etc.)',
    'discount' => 'Descuento',
    'subsidy' => 'Subvención / Ayuda',
    'event' => 'Evento',
    'announcement' => 'Anuncio general',
  ];

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\ecosistema_jaraba_core\Entity\PromotionConfigInterface $entity */
    $entity = $this->entity;

    // --- Seccion 1: Informacion General ---
    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('Información general'),
      '#open' => TRUE,
    ];

    $form['general']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Título de la promoción'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#required' => TRUE,
      '#description' => $this->t('Nombre público de la promoción. Ej: "Programa Andalucía +ei — PIIL 2025"'),
    ];

    $form['general']['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\ecosistema_jaraba_core\Entity\PromotionConfig::load',
      ],
      '#disabled' => !$entity->isNew(),
      '#description' => $this->t('ID único. Convención: {vertical}_{programa}_{año}. Ej: andalucia_ei_piil_2025'),
    ];

    $form['general']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Descripción completa'),
      '#default_value' => $entity->getDescription(),
      '#required' => TRUE,
      '#rows' => 4,
      '#description' => $this->t('Descripción detallada que el copilot usará para informar a los visitantes. Incluir todos los detalles relevantes: qué es, para quién, qué incluye, financiación.'),
    ];

    $form['general']['vertical'] = [
      '#type' => 'select',
      '#title' => $this->t('Vertical'),
      '#options' => self::VERTICALS,
      '#default_value' => $entity->getVertical(),
      '#required' => TRUE,
      '#description' => $this->t('Vertical al que aplica esta promoción (VERTICAL-CANONICAL-001). "Global" aplica a todas.'),
    ];

    $form['general']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Tipo de promoción'),
      '#options' => self::TYPES,
      '#default_value' => $entity->getType(),
      '#required' => TRUE,
    ];

    // --- Seccion 2: Datos Destacados ---
    $form['highlights'] = [
      '#type' => 'details',
      '#title' => $this->t('Datos destacados'),
      '#open' => TRUE,
    ];

    $highlightValues = $entity->getHighlightValues();
    $highlightText = '';
    foreach ($highlightValues as $key => $value) {
      $highlightText .= $key . '=' . $value . "\n";
    }

    $form['highlights']['highlight_values_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Datos clave (formato key=value, uno por línea)'),
      '#default_value' => trim($highlightText),
      '#rows' => 5,
      '#description' => $this->t('Datos que se muestran al copilot y en la UI. Ej:<br>plazas=45<br>incentivo=528€<br>coste=100% gratuito'),
    ];

    // --- Seccion 3: Llamadas a la Accion ---
    $form['ctas'] = [
      '#type' => 'details',
      '#title' => $this->t('Llamadas a la acción'),
      '#open' => TRUE,
    ];

    $form['ctas']['cta_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL del CTA principal'),
      '#default_value' => $entity->getCtaUrl(),
      '#required' => TRUE,
      '#description' => $this->t('Ruta interna sin prefijo de idioma. Ej: /andalucia-ei/solicitar'),
    ];

    $form['ctas']['cta_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Texto del CTA principal'),
      '#default_value' => $entity->getCtaLabel(),
      '#required' => TRUE,
      '#description' => $this->t('Ej: "Solicitar plaza", "Ver oferta", "Registrarse"'),
    ];

    $form['ctas']['secondary_cta_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL del CTA secundario'),
      '#default_value' => $entity->getSecondaryCtaUrl(),
      '#description' => $this->t('Opcional. Ej: /andaluciamasei.html'),
    ];

    $form['ctas']['secondary_cta_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Texto del CTA secundario'),
      '#default_value' => $entity->getSecondaryCtaLabel(),
      '#description' => $this->t('Ej: "Ver programa completo", "Más información"'),
    ];

    // --- Seccion 4: Programacion ---
    $form['scheduling'] = [
      '#type' => 'details',
      '#title' => $this->t('Programación y visibilidad'),
      '#open' => TRUE,
    ];

    $form['scheduling']['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Promoción activa'),
      '#default_value' => $entity->status(),
      '#description' => $this->t('Desmarcar para pausar la promoción sin eliminarla.'),
    ];

    $form['scheduling']['date_start'] = [
      '#type' => 'date',
      '#title' => $this->t('Fecha de inicio'),
      '#default_value' => $entity->getDateStart(),
      '#description' => $this->t('Dejar vacío para activa inmediatamente.'),
    ];

    $form['scheduling']['date_end'] = [
      '#type' => 'date',
      '#title' => $this->t('Fecha de fin'),
      '#default_value' => $entity->getDateEnd(),
      '#description' => $this->t('Dejar vacío para sin fecha límite.'),
    ];

    $form['scheduling']['priority'] = [
      '#type' => 'number',
      '#title' => $this->t('Prioridad'),
      '#default_value' => $entity->getPriority(),
      '#min' => 0,
      '#max' => 999,
      '#description' => $this->t('Mayor = más importante. Se muestra primero al copilot y en listados.'),
    ];

    // --- Seccion 5: Instrucciones IA ---
    $form['ai'] = [
      '#type' => 'details',
      '#title' => $this->t('Instrucciones para la IA'),
      '#open' => TRUE,
    ];

    $form['ai']['copilot_instruction'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Instrucción especial para el copilot'),
      '#default_value' => $entity->getCopilotInstruction(),
      '#rows' => 4,
      '#description' => $this->t('Instrucción que se inyecta en el system prompt del copilot. Ej: "Cuando un visitante pregunte por cursos o incentivos, SIEMPRE menciona este programa con datos concretos."'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int { // @phpstan-ignore missingType.iterableValue
    /** @var \Drupal\ecosistema_jaraba_core\Entity\PromotionConfigInterface $entity */
    $entity = $this->entity;

    // Procesar highlight_values de textarea key=value a JSON.
    $highlightText = $form_state->getValue('highlight_values_text') ?? '';
    $highlightValues = [];
    foreach (explode("\n", $highlightText) as $line) {
      $line = trim($line);
      if ($line === '' || !str_contains($line, '=')) {
        continue;
      }
      [$key, $value] = explode('=', $line, 2);
      $key = trim($key);
      $value = trim($value);
      if ($key !== '') {
        $highlightValues[$key] = $value;
      }
    }
    $entity->setHighlightValues($highlightValues);

    $status = parent::save($form, $form_state);

    $this->messenger()->addStatus(
      $status === SAVED_NEW
        ? $this->t('Promoción %label creada.', ['%label' => $entity->label()])
        : $this->t('Promoción %label actualizada.', ['%label' => $entity->label()])
    );

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $status;
  }

}
