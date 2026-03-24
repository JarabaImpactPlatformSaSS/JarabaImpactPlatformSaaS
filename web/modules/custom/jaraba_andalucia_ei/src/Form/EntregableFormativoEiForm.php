<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for EntregableFormativoEi.
 *
 * PREMIUM-FORMS-PATTERN-001: Extiende PremiumEntityFormBase.
 */
class EntregableFormativoEiForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'entregable' => [
        'title' => $this->t('Datos del Entregable'),
        'icon' => ['category' => 'education', 'name' => 'clipboard'],
        'weight' => 0,
        'fields' => [
          'numero',
          'titulo',
          'sesion_origen',
          'modulo',
          'estado',
          'generado_con_ia',
          'archivo_url',
          'notas_participante',
        ],
      ],
      'validacion' => [
        'title' => $this->t('Validación'),
        'icon' => ['category' => 'actions', 'name' => 'check-circle'],
        'weight' => 1,
        'fields' => [
          'validado_por',
          'validado_fecha',
          'notas_validacion',
          'participante_id',
          'tenant_id',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'education', 'name' => 'clipboard'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();
    $this->messenger()->addStatus($this->t('Entregable formativo %label guardado.', [
      '%label' => $entity->label() ?? $this->t('(sin título)'),
    ]));

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
