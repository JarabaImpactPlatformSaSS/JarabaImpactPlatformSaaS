<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form for NegocioProspectadoEi entities.
 *
 * PREMIUM-FORMS-PATTERN-001: Extiende PremiumEntityFormBase.
 */
class NegocioProspectadoEiForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'negocio' => [
        'label' => $this->t('Negocio'),
        'icon' => ['category' => 'commerce', 'name' => 'store'],
        'description' => $this->t('Datos identificativos y de contacto del negocio prospectado.'),
        'fields' => [
          'nombre_negocio',
          'sector',
          'direccion',
          'provincia',
          'persona_contacto',
          'telefono',
          'email',
          'url_web',
        ],
      ],
      'evaluacion' => [
        'label' => $this->t('Evaluación'),
        'icon' => ['category' => 'analytics', 'name' => 'gauge'],
        'description' => $this->t('Presencia digital, urgencia y estado en el embudo comercial.'),
        'fields' => [
          'url_google_maps',
          'valoracion_google',
          'num_resenas',
          'clasificacion_urgencia',
          'estado_embudo',
          'pack_compatible',
        ],
      ],
      'programa' => [
        'label' => $this->t('Programa'),
        'icon' => ['category' => 'business', 'name' => 'target'],
        'description' => $this->t('Asignación, seguimiento y resultado del proceso de prospección.'),
        'fields' => [
          'participante_asignado',
          'fecha_primer_contacto',
          'fecha_acuerdo_prueba',
          'satisfaccion_prueba',
          'convertido_a_pago',
          'notas',
          'prospectado_por',
          'tenant_id',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'commerce', 'name' => 'store'];
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
