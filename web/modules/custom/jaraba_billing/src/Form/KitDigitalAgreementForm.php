<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario premium para crear/editar acuerdos Kit Digital.
 *
 * PREMIUM-FORMS-PATTERN-001: Extiende PremiumEntityFormBase.
 * 5 secciones: identificación, beneficiario, paquete, ciclo de vida, justificación.
 */
class KitDigitalAgreementForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identification' => [
        'label' => $this->t('Identificación'),
        'icon' => ['category' => 'compliance', 'name' => 'document'],
        'description' => $this->t('Datos del acuerdo y referencia del bono digital.'),
        'fields' => ['agreement_number', 'bono_digital_ref', 'tenant_id'],
      ],
      'beneficiary' => [
        'label' => $this->t('Beneficiario'),
        'icon' => ['category' => 'ui', 'name' => 'user'],
        'description' => $this->t('Datos de la empresa beneficiaria del Kit Digital.'),
        'fields' => ['beneficiary_name', 'beneficiary_nif', 'segmento'],
      ],
      'package' => [
        'label' => $this->t('Paquete y plan'),
        'icon' => ['category' => 'commerce', 'name' => 'package'],
        'description' => $this->t('Solución de digitalización contratada y categorías Kit Digital cubiertas.'),
        'fields' => ['paquete', 'plan_tier', 'categorias_kit_digital', 'bono_digital_amount'],
      ],
      'lifecycle' => [
        'label' => $this->t('Ciclo de vida'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Fechas del servicio, estado y vinculación con Stripe.'),
        'fields' => ['start_date', 'end_date', 'status', 'stripe_subscription_id'],
      ],
      'justification' => [
        'label' => $this->t('Justificación'),
        'icon' => ['category' => 'fiscal', 'name' => 'receipt'],
        'description' => $this->t('Documentación para justificar el bono ante Red.es.'),
        'fields' => ['justification_date', 'justification_memory'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'compliance', 'name' => 'certificate'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
