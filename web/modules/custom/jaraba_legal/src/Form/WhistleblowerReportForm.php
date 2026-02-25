<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para WhistleblowerReport.
 *
 * Permite crear reportes de denuncia. Una vez creados, los reportes
 * son de solo lectura (excepto estado, asignación y resolución).
 * Gestionado por WhistleblowerChannelService.
 */
class WhistleblowerReportForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identification' => [
        'label' => $this->t('Identification'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Tracking code and report category.'),
        'fields' => ['tracking_code', 'category'],
      ],
      'content' => [
        'label' => $this->t('Report Content'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('Encrypted description of the report.'),
        'fields' => ['description_encrypted'],
      ],
      'severity_status' => [
        'label' => $this->t('Severity & Status'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Severity level and investigation status.'),
        'fields' => ['severity', 'status'],
      ],
      'reporter' => [
        'label' => $this->t('Reporter Contact'),
        'icon' => ['category' => 'users', 'name' => 'users'],
        'description' => $this->t('Encrypted contact data and anonymity flag.'),
        'fields' => ['reporter_contact_encrypted', 'is_anonymous'],
      ],
      'investigation' => [
        'label' => $this->t('Investigation'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'description' => $this->t('Assigned investigator, resolution, and tenant.'),
        'fields' => ['assigned_to', 'resolution', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'shield'];
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
