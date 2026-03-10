<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form para crear/editar ProspeccionEmpresarial.
 *
 * PREMIUM-FORMS-PATTERN-001: Extiende PremiumEntityFormBase.
 */
class ProspeccionEmpresarialForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'empresa' => [
        'label' => $this->t('Datos de la Empresa'),
        'icon' => ['category' => 'business', 'name' => 'building'],
        'description' => $this->t('Información identificativa de la empresa.'),
        'fields' => [
          'empresa_nombre',
          'cif',
          'sector',
          'tamano_empresa',
          'provincia',
        ],
      ],
      'contacto' => [
        'label' => $this->t('Contacto'),
        'icon' => ['category' => 'social', 'name' => 'contact'],
        'description' => $this->t('Persona de contacto en la empresa.'),
        'fields' => [
          'contacto_nombre',
          'contacto_cargo',
          'contacto_email',
          'contacto_telefono',
        ],
      ],
      'prospeccion' => [
        'label' => $this->t('Estado de Prospección'),
        'icon' => ['category' => 'ui', 'name' => 'target'],
        'description' => $this->t('Seguimiento del proceso de prospección.'),
        'fields' => [
          'estado',
          'tipo_colaboracion',
          'puestos_disponibles',
          'perfiles_demandados',
        ],
      ],
      'seguimiento' => [
        'label' => $this->t('Seguimiento'),
        'icon' => ['category' => 'ui', 'name' => 'calendar'],
        'description' => $this->t('Fechas y notas del seguimiento.'),
        'fields' => [
          'fecha_primer_contacto',
          'fecha_ultimo_seguimiento',
          'notas',
          'participantes_derivados',
          'participantes_insertados',
        ],
      ],
      'metadata' => [
        'label' => $this->t('Metadatos'),
        'icon' => ['category' => 'ui', 'name' => 'info'],
        'description' => $this->t('Estado y propietario.'),
        'fields' => ['uid', 'status'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'business', 'name' => 'handshake'];
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
