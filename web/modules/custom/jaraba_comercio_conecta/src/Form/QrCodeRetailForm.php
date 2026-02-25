<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing QR codes.
 */
class QrCodeRetailForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'info_qr' => [
        'label' => $this->t('Informacion del Codigo QR'),
        'icon' => ['category' => 'ui', 'name' => 'link'],
        'description' => $this->t('Datos principales del codigo QR.'),
        'fields' => ['name', 'merchant_id', 'qr_type', 'short_code'],
      ],
      'destino' => [
        'label' => $this->t('Destino'),
        'icon' => ['category' => 'ui', 'name' => 'link'],
        'description' => $this->t('URL y entidad de destino del QR.'),
        'fields' => ['target_url', 'target_entity_type', 'target_entity_id'],
      ],
      'ab_testing' => [
        'label' => $this->t('Test A/B'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Configuracion de tests A/B.'),
        'fields' => ['ab_variant', 'ab_target_url'],
      ],
      'diseno' => [
        'label' => $this->t('Diseno'),
        'icon' => ['category' => 'media', 'name' => 'image'],
        'description' => $this->t('Configuracion visual del codigo QR.'),
        'fields' => ['design_config'],
      ],
      'estadisticas' => [
        'label' => $this->t('Estadisticas'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Metricas calculadas automaticamente.'),
        'fields' => ['scan_count'],
      ],
      'estado' => [
        'label' => $this->t('Estado'),
        'icon' => ['category' => 'ui', 'name' => 'check'],
        'description' => $this->t('Activacion del codigo QR.'),
        'fields' => ['is_active'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'link'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Computed field: scan_count is read-only.
    if (isset($form['premium_section_estadisticas']['scan_count'])) {
      $form['premium_section_estadisticas']['scan_count']['#disabled'] = TRUE;
    }
    elseif (isset($form['scan_count'])) {
      $form['scan_count']['#disabled'] = TRUE;
    }

    return $form;
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
