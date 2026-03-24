<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form handler for StaffProfileEi entity.
 *
 * PREMIUM-FORMS-PATTERN-001: Extends PremiumEntityFormBase (NUNCA ContentEntityForm).
 * Implements getSectionDefinitions() and getFormIcon().
 */
class StaffProfileEiForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identidad' => [
        'label' => $this->t('Identidad'),
        'icon' => ['category' => 'users', 'name' => 'user-edit'],
        'description' => $this->t('Datos de identificación del profesional'),
        'fields' => ['user_id', 'display_name', 'rol_programa', 'status'],
      ],
      'cualificacion' => [
        'label' => $this->t('Cualificación'),
        'icon' => ['category' => 'education', 'name' => 'graduation-cap'],
        'description' => $this->t('Formación académica y experiencia profesional'),
        'fields' => ['titulacion', 'experiencia_anios', 'especialidades', 'certificaciones'],
      ],
      'programa' => [
        'label' => $this->t('Programa'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'description' => $this->t('Vinculación con el programa Andalucía +ei'),
        'fields' => ['fecha_incorporacion', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'users', 'name' => 'user-shield'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getCharacterLimits(): array {
    return [
      'display_name' => 255,
      'titulacion' => 255,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $entity = $this->entity;
    $name = $entity->get('display_name')->value ?? '';

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Perfil profesional %name creado.', [
        '%name' => $name,
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('Perfil profesional %name actualizado.', [
        '%name' => $name,
      ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
