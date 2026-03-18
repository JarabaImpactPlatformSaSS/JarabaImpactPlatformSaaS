<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario premium para Ficha Técnica PIIL ICV 2025.
 *
 * PREMIUM-FORMS-PATTERN-001: 4 secciones con iconografía duotone.
 */
class FichaTecnicaEiForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'expediente' => [
        'label' => $this->t('Expediente'),
        'icon' => ['category' => 'compliance', 'name' => 'document'],
        'description' => $this->t('Datos del expediente y provincia de actuación.'),
        'fields' => ['expediente_ref', 'provincia', 'proyectos_concedidos', 'tenant_id'],
      ],
      'sede' => [
        'label' => $this->t('Sede operativa'),
        'icon' => ['category' => 'ui', 'name' => 'building'],
        'description' => $this->t('Dirección donde se desarrollan las acciones del programa.'),
        'fields' => ['sede_direccion', 'sede_operativa'],
      ],
      'directivo' => [
        'label' => $this->t('Personal directivo'),
        'icon' => ['category' => 'ui', 'name' => 'user'],
        'description' => $this->t('Representante legal y coordinador/a del programa.'),
        'fields' => ['representante_nombre', 'representante_nif', 'coordinador_nombre', 'coordinador_nif'],
      ],
      'equipo' => [
        'label' => $this->t('Equipo técnico'),
        'icon' => ['category' => 'ui', 'name' => 'users'],
        'description' => $this->t('Personal técnico con titulación universitaria. Ratio: 1 técnico por cada 60 proyectos.'),
        'fields' => ['personal_tecnico', 'estado_validacion', 'fecha_envio_sae', 'fecha_validacion_sae', 'observaciones'],
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
