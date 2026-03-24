<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form for PackServicioEi entities.
 *
 * PREMIUM-FORMS-PATTERN-001: Extiende PremiumEntityFormBase.
 */
class PackServicioEiForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'servicio' => [
        'label' => $this->t('Servicio'),
        'icon' => ['category' => 'commerce', 'name' => 'store'],
        'description' => $this->t('Tipo de pack, modalidad y descripción del servicio.'),
        'fields' => [
          'pack_tipo',
          'modalidad',
          'titulo_personalizado',
          'descripcion',
          'sector_cliente',
        ],
      ],
      'precios' => [
        'label' => $this->t('Precios y entregables'),
        'icon' => ['category' => 'finance', 'name' => 'coins'],
        'description' => $this->t('Configuración de precios y entregables mensuales.'),
        'fields' => [
          'precio_mensual',
          'precio_setup',
          'entregables_mensuales',
        ],
      ],
      'publicacion' => [
        'label' => $this->t('Publicación'),
        'icon' => ['category' => 'actions', 'name' => 'check-circle'],
        'description' => $this->t('Estado de publicación, integración Stripe y asignación.'),
        'fields' => [
          'publicado',
          'url_catalogo',
          'stripe_product_id',
          'stripe_price_id',
          'participante_id',
          'tenant_id',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'commerce', 'name' => 'store'];
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
