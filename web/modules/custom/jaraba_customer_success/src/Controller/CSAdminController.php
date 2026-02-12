<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\jaraba_customer_success\Service\HealthScoreCalculatorService;

/**
 * Controlador admin para Customer Success.
 *
 * PROPÓSITO:
 * Dashboard administrativo en /admin/structure/customer-success
 * con vista general de health scores y acción de recálculo manual.
 */
class CSAdminController extends ControllerBase {

  public function __construct(
    protected HealthScoreCalculatorService $healthCalculator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_customer_success.health_calculator'),
    );
  }

  /**
   * Dashboard admin de Customer Success.
   */
  public function dashboard(): array {
    $category_counts = $this->healthCalculator->getByCategory();

    $build = [];

    // Resumen de categorías.
    $build['overview'] = [
      '#type' => 'markup',
      '#markup' => '<div class="cs-admin-overview">'
        . '<h3>' . $this->t('Health Score Distribution') . '</h3>'
        . '<ul>'
        . '<li><strong style="color:#00A9A5;">' . $this->t('Healthy') . ':</strong> ' . $category_counts['healthy'] . '</li>'
        . '<li><strong style="color:#FFB84D;">' . $this->t('Neutral') . ':</strong> ' . $category_counts['neutral'] . '</li>'
        . '<li><strong style="color:#FF8C42;">' . $this->t('At Risk') . ':</strong> ' . $category_counts['at_risk'] . '</li>'
        . '<li><strong style="color:#DC3545;">' . $this->t('Critical') . ':</strong> ' . $category_counts['critical'] . '</li>'
        . '</ul>'
        . '</div>',
    ];

    // Lista de health scores (usa entity list builder).
    $build['health_scores'] = $this->entityTypeManager()
      ->getListBuilder('customer_health')
      ->render();

    return $build;
  }

  /**
   * Recalcula health scores manualmente.
   */
  public function recalculate(): RedirectResponse {
    // Forzar recálculo ignorando el intervalo.
    \Drupal::state()->delete('jaraba_cs.last_calculation');

    $processed = $this->healthCalculator->runScheduledCalculation();

    $this->messenger()->addStatus($this->t('Health scores recalculated for @count tenants.', [
      '@count' => $processed,
    ]));

    return $this->redirect('jaraba_customer_success.admin.dashboard');
  }

}
