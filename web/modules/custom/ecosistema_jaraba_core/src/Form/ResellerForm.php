<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Premium form for creating/editing Reseller entities.
 */
class ResellerForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'general' => [
        'label' => $this->t('Información General'),
        'icon' => ['category' => 'business', 'name' => 'building'],
        'description' => $this->t('Nombre, empresa y contacto.'),
        'fields' => ['name', 'company_name', 'contact_email'],
      ],
      'commercial' => [
        'label' => $this->t('Configuración Comercial'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Comisión, modelo de ingresos y territorio.'),
        'fields' => ['commission_rate', 'revenue_share_model', 'territory'],
      ],
      'tenants' => [
        'label' => $this->t('Tenants'),
        'icon' => ['category' => 'users', 'name' => 'group'],
        'description' => $this->t('Tenants gestionados.'),
        'fields' => ['managed_tenant_ids'],
      ],
      'contract' => [
        'label' => $this->t('Contrato'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('Fechas de contrato y estado.'),
        'fields' => ['contract_start', 'contract_end', 'status_reseller'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'business', 'name' => 'building'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validate territory JSON if provided.
    $territoryRaw = $form_state->getValue('territory')[0]['value'] ?? '';
    if (!empty($territoryRaw)) {
      $decoded = json_decode($territoryRaw, TRUE);
      if (!is_array($decoded)) {
        $form_state->setErrorByName('territory', $this->t('El territorio debe ser un JSON válido.'));
      }
    }
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
