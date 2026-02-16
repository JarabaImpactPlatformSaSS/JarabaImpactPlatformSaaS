<?php

namespace Drupal\commerce_order\Plugin\views\area;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsArea;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an order total area handler.
 *
 * Shows the order total field with its components listed in the footer of a
 * View.
 *
 * @ingroup views_area_handlers
 */
#[ViewsArea("commerce_order_total")]
class OrderTotal extends AreaPluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['empty']['#description'] = $this->t("Even if selected, this area handler will never render if a valid order cannot be found in the View's arguments.");
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if (!$empty || !empty($this->options['empty'])) {
      /** @var \Drupal\commerce_order\OrderStorageInterface $order_storage */
      $order_storage = $this->entityTypeManager->getStorage('commerce_order');
      foreach ($this->view->argument as $name => $argument) {
        // First look for an order_id argument.
        if (!$argument instanceof NumericArgument) {
          continue;
        }
        if ($argument->getField() === 'commerce_order_item.order_item_id') {
          $order_item_storage = $this->entityTypeManager->getStorage('commerce_order_item');
          $order_item_ids = preg_split('/[+, ]+/', $argument->getValue(), -1, PREG_SPLIT_NO_EMPTY);
          $order_item = $order_item_storage->load(reset($order_item_ids));
          if ($order_item instanceof OrderItemInterface) {
            $order_id = $order_item->getOrderId();
            if ($order_id === NULL) {
              continue;
            }
          }
        }
        elseif (!in_array($argument->getField(), [
          'commerce_order.order_id',
          'commerce_order_item.order_id',
          'commerce_payment.order_id',
        ])) {
          continue;
        }
        $order_id = $order_id ?? $argument->getValue();
        /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
        if ($order = $order_storage->load($order_id)) {
          $order_total = $order->get('total_price')->view([
            'label' => 'hidden',
            'type' => 'commerce_order_total_summary',
            'weight' => $this->position,
          ]);
          $order_total['#prefix'] = '<div data-drupal-selector="order-total-summary">';
          $order_total['#suffix'] = '</div>';
          return $order_total;
        }
      }
    }

    return [];
  }

}
