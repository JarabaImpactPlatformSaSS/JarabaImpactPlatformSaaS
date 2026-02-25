<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar categorías AgroConecta.
 */
class AgroCategoryForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'category' => [
        'label' => $this->t('Datos de la categoría'),
        'icon' => ['category' => 'verticals', 'name' => 'agro'],
        'fields' => ['name', 'slug', 'description', 'parent_id', 'icon', 'image'],
      ],
      'config' => [
        'label' => $this->t('Configuración'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'fields' => ['position', 'is_featured', 'is_active', 'tenant_id'],
      ],
      'seo' => [
        'label' => $this->t('SEO'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'fields' => ['meta_title', 'meta_description'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'verticals', 'name' => 'agro'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validar slug único (si se proporciona).
    $slug = $form_state->getValue(['slug', 0, 'value']);
    if ($slug) {
      $entity = $this->getEntity();
      $storage = $this->entityTypeManager->getStorage('agro_category');
      $query = $storage->getQuery()
        ->condition('slug', $slug)
        ->accessCheck(FALSE);

      // Excluir la entidad actual si estamos editando.
      if ($entity->id()) {
        $query->condition('id', $entity->id(), '<>');
      }

      $existing = $query->count()->execute();
      if ($existing > 0) {
        $form_state->setErrorByName('slug', $this->t('El slug "@slug" ya está en uso por otra categoría.', [
          '@slug' => $slug,
        ]));
      }
    }

    // Validar que no se seleccione a sí misma como padre.
    $parent_id = $form_state->getValue(['parent_id', 0, 'target_id']);
    $entity = $this->getEntity();
    if ($parent_id && $entity->id() && (int) $parent_id === (int) $entity->id()) {
      $form_state->setErrorByName('parent_id', $this->t('Una categoría no puede ser su propio padre.'));
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
