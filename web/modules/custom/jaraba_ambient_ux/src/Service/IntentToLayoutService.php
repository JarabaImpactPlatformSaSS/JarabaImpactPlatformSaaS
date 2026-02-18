<?php

declare(strict_types=1);

namespace Drupal\jaraba_ambient_ux\Service;

use Drupal\jaraba_predictive\Service\ChurnPredictorService;
use Psr\Log\LoggerInterface;

/**
 * Traductor de Intención a Diseño (Liquid UI).
 *
 * Decide qué "Modo de Interfaz" debe mostrarse al usuario
 * basándose en la inteligencia predictiva y de mercado.
 *
 * F197 — Ambient UX Engine.
 */
class IntentToLayoutService {

  public function __construct(
    protected readonly ChurnPredictorService $churnPredictor,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Determina el modo de UI para un tenant.
   *
   * @return array Configuración de UI {theme_mode, layout_priority, fab_action}.
   */
  public function determineUiMode(int $tenantId): array {
    // 1. Consultar estado de riesgo interno.
    // Usamos el servicio predictivo real que ya implementamos.
    $churnRisk = $this->churnPredictor->calculateChurnRisk($tenantId);
    $riskLevel = $churnRisk['risk_score'];

    // 2. Lógica de Decisión Líquida.
    
    // CASO A: CRISIS (Riesgo alto de abandono).
    // La UI debe simplificarse, ocultar distracciones y enfocar en ayuda.
    if ($riskLevel > 70) {
      return [
        'mode' => 'crisis',
        'css_class' => 'theme--mode-focus',
        'layout_priority' => 'support_first',
        'fab_action' => 'contact_csm', // Contactar Customer Success.
        'hidden_regions' => ['marketing_banner', 'upsell_suggestions'],
      ];
    }

    // CASO B: CRECIMIENTO (Riesgo bajo, salud excelente).
    // La UI debe ser expansiva, mostrar oportunidades y nuevos productos.
    if ($riskLevel < 20) {
      return [
        'mode' => 'growth',
        'css_class' => 'theme--mode-expansion',
        'layout_priority' => 'marketplace_first',
        'fab_action' => 'explore_new_features',
        'hidden_regions' => [],
      ];
    }

    // CASO C: MANTENIMIENTO (Estándar).
    return [
      'mode' => 'maintenance',
      'css_class' => 'theme--mode-standard',
      'layout_priority' => 'dashboard_first',
      'fab_action' => 'copilot_chat',
      'hidden_regions' => [],
    ];
  }

}
