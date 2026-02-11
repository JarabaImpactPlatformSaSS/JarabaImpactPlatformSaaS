<?php

namespace Drupal\jaraba_ads\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar campañas publicitarias.
 *
 * ESTRUCTURA: Extiende ContentEntityForm para operaciones CRUD
 *   sobre la entidad AdCampaign.
 *
 * LÓGICA: Al guardar, muestra mensaje de estado (creada/actualizada)
 *   y redirige al listado de campañas. Recalcula métricas derivadas
 *   (CTR, CPC) antes de guardar si hay datos de rendimiento.
 *
 * RELACIONES:
 * - AdCampaignForm -> AdCampaign entity (gestiona)
 * - AdCampaignForm <- AdminHtmlRouteProvider (invocado por)
 */
class AdCampaignForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    /** @var \Drupal\jaraba_ads\Entity\AdCampaign $entity */
    $entity = $this->entity;

    // Recalcular métricas derivadas si hay datos de rendimiento.
    $impressions = (int) $entity->get('impressions')->value;
    $clicks = (int) $entity->get('clicks')->value;
    if ($impressions > 0 || $clicks > 0) {
      $entity->recalculateMetrics();
    }

    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $entity->label()];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Campaña publicitaria %label creada.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Campaña publicitaria %label actualizada.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
