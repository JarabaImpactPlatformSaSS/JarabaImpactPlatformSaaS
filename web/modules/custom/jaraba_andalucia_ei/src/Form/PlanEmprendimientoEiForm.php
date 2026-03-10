<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form for PlanEmprendimientoEi entities.
 *
 * PREMIUM-FORMS-PATTERN-001: Extiende PremiumEntityFormBase.
 */
class PlanEmprendimientoEiForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'idea' => [
        'label' => $this->t('Idea de negocio'),
        'icon' => ['category' => 'ui', 'name' => 'lightbulb'],
        'description' => $this->t('Descripción y sector del proyecto empresarial.'),
        'fields' => ['label', 'idea_negocio', 'sector', 'forma_juridica_objetivo'],
      ],
      'fase' => [
        'label' => $this->t('Fase y viabilidad'),
        'icon' => ['category' => 'ui', 'name' => 'chart'],
        'description' => $this->t('Estado actual del emprendimiento.'),
        'fields' => ['fase_emprendimiento', 'diagnostico_viabilidad'],
      ],
      'herramientas' => [
        'label' => $this->t('Herramientas de negocio'),
        'icon' => ['category' => 'ui', 'name' => 'tools'],
        'description' => $this->t('Canvas, MVP e inversión.'),
        'fields' => ['canvas_id', 'mvp_hypothesis_id', 'projection_id', 'inversion_inicial', 'fuentes_financiacion', 'necesita_microcredito'],
      ],
      'hitos' => [
        'label' => $this->t('Hitos y resultados'),
        'icon' => ['category' => 'ui', 'name' => 'trophy'],
        'description' => $this->t('Alta RETA/IAE, facturación, empleo generado.'),
        'fields' => ['fecha_alta_reta', 'fecha_alta_iae', 'primer_cliente_fecha', 'facturacion_acumulada', 'empleo_generado'],
      ],
      'metadata' => [
        'label' => $this->t('Metadatos'),
        'icon' => ['category' => 'ui', 'name' => 'info'],
        'description' => $this->t('Participante, mentor, estado.'),
        'fields' => ['participante_id', 'mentor_emprendimiento_uid', 'uid', 'tenant_id', 'status', 'notas'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'lightbulb'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->getEntity();
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
