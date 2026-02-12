<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar métricas diarias de ads.
 *
 * ESTRUCTURA: Extiende ContentEntityForm para operaciones CRUD
 *   sobre la entidad AdsMetricsDaily.
 *
 * LÓGICA: Al guardar, muestra mensaje de estado (creada/actualizada)
 *   y redirige al listado de métricas diarias.
 *
 * RELACIONES:
 * - AdsMetricsDailyForm -> AdsMetricsDaily entity (gestiona)
 * - AdsMetricsDailyForm <- AdminHtmlRouteProvider (invocado por)
 */
class AdsMetricsDailyForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;
    $result = parent::save($form, $form_state);

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Métrica diaria de ads creada.'));
    }
    else {
      $this->messenger()->addStatus($this->t('Métrica diaria de ads actualizada.'));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
