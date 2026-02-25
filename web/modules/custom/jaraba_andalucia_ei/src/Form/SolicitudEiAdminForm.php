<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium admin form for managing Andalucía +ei applications.
 */
class SolicitudEiAdminForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'personal' => [
        'label' => $this->t('Personal Data'),
        'icon' => ['category' => 'ui', 'name' => 'user'],
        'description' => $this->t('Applicant personal information.'),
        'fields' => ['nombre', 'email', 'telefono', 'fecha_nacimiento', 'dni_nie'],
      ],
      'territorial' => [
        'label' => $this->t('Location'),
        'icon' => ['category' => 'ui', 'name' => 'map-pin'],
        'description' => $this->t('Province and municipality.'),
        'fields' => ['provincia', 'municipio'],
      ],
      'professional' => [
        'label' => $this->t('Professional'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'description' => $this->t('Employment and education details.'),
        'fields' => ['situacion_laboral', 'tiempo_desempleo', 'nivel_estudios', 'es_migrante', 'percibe_prestacion', 'experiencia_sector'],
      ],
      'motivation' => [
        'label' => $this->t('Motivation'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Applicant motivation.'),
        'fields' => ['motivacion'],
      ],
      'admin' => [
        'label' => $this->t('Admin'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Administrative fields and AI assessment.'),
        'fields' => ['estado', 'colectivo_inferido', 'notas_admin', 'tenant_id', 'ai_score', 'ai_justificacion', 'ai_recomendacion'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'document'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    /** @var \Drupal\jaraba_andalucia_ei\Entity\SolicitudEiInterface $entity */
    $entity = $this->getEntity();

    // Inferir colectivo automáticamente si no se ha asignado.
    if (!$entity->get('colectivo_inferido')->value) {
      $entity->setColectivoInferido($entity->inferirColectivo());
    }

    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
