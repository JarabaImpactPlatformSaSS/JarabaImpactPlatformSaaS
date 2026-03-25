<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for MaterialDidacticoEi.
 *
 * PREMIUM-FORMS-PATTERN-001: Extiende PremiumEntityFormBase.
 */
class MaterialDidacticoEiForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'datos_principales' => [
        'title' => $this->t('Datos del Material'),
        'weight' => 0,
        'fields' => ['titulo', 'descripcion', 'tipo_material'],
      ],
      'contenido' => [
        'title' => $this->t('Contenido'),
        'weight' => 1,
        'fields' => ['archivo', 'url_externa', 'duracion_estimada'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'content', 'name' => 'book-open'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();
    $this->messenger()->addStatus($this->t('Material didáctico %label guardado.', [
      '%label' => $entity->label() ?? $this->t('(sin título)'),
    ]));

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
