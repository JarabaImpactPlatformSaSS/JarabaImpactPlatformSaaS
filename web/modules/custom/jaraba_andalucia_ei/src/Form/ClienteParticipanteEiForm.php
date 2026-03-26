<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form for ClienteParticipanteEi entities.
 *
 * PREMIUM-FORMS-PATTERN-001: Extiende PremiumEntityFormBase.
 */
class ClienteParticipanteEiForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'datos_negocio' => [
        'label' => $this->t('Datos del Negocio'),
        'icon' => ['category' => 'business', 'name' => 'clients'],
        'description' => $this->t('Datos identificativos y de contacto del negocio cliente.'),
        'fields' => [
          'nombre_negocio',
          'nombre_contacto',
          'email',
          'telefono',
          'sector',
        ],
      ],
      'pack_servicio' => [
        'label' => $this->t('Pack y Servicio'),
        'icon' => ['category' => 'commerce', 'name' => 'store'],
        'description' => $this->t('Pack de servicios digitales contratado y modalidad.'),
        'fields' => [
          'pack_contratado',
          'modalidad',
          'precio_mensual',
        ],
      ],
      'estado_relacion' => [
        'label' => $this->t('Estado de la Relación'),
        'icon' => ['category' => 'analytics', 'name' => 'gauge'],
        'description' => $this->t('Estado actual, fechas y periodo piloto del cliente.'),
        'fields' => [
          'estado',
          'fecha_inicio',
          'es_piloto',
        ],
      ],
      'notas' => [
        'label' => $this->t('Notas'),
        'icon' => ['category' => 'content', 'name' => 'notes'],
        'description' => $this->t('Observaciones generales y feedback del periodo piloto.'),
        'fields' => [
          'notas',
          'feedback_piloto',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'business', 'name' => 'clients'];
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
