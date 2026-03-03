SPEC-001: Dashboard Admin Unificado (Financial Operations Center)
Estado: Especificación para Implementación | Versión: 1.0.0
Relacionado con: jaraba_foc, jaraba_billing, jaraba_ai_agents
1. Propósito y Alcance
Proporcionar a los administradores de la plataforma (PED S.L.) una visión consolidada de los 10 verticales. El objetivo es centralizar la toma de decisiones basada en datos (Data-Driven), monitorizando el "burn rate" de IA, la rentabilidad por tenant (Unit Economics) y la detección temprana de Churn.
2. Nuevas Entidades y Modelado (Drupal 11)
2.1 Entity: foc_metric_snapshot (Content Entity)
Almacenará capturas diarias de métricas para análisis histórico sin penalizar el rendimiento de las queries en tiempo real.
●	Storage: Tabla foc_metric_snapshot_field_data.
●	Campos Clave:
○	scope: (String) platform | vertical | tenant.
○	vertical_id: (Target Vertical) Taxonomy Term reference.
○	tenant_id: (Target Tenant) Group reference (NULLEABLE si es platform).
○	metric_key: (String) mrr | arr | ai_tokens_consumed | churn_risk_score.
○	metric_value: (Decimal 12,4).
○	snapshot_date: (Timestamp).
3. Capa de Servicios (PHP 8.4)
3.1 FocDataAggregatorService
Servicio encargado de recolectar datos de jaraba_billing y jaraba_ai_agents.
declare(strict_types=1);

namespace Drupal\jaraba_foc\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_ai_agents\Service\AIUsageTracker;

/**
 * Agregador de métricas para el Dashboard Global.
 */
final class FocDataAggregatorService {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AIUsageTracker $aiUsageTracker,
  ) {}

  /**
   * Calcula el MRR real descontando comisiones de Stripe.
   */
  public function getGlobalMrr(): float {
    // Lógica para sumar suscripciones activas via jaraba_billing.
    return 125000.45; // Ejemplo
  }

  /**
   * Obtiene el consumo de tokens agregado por modelo (Haiku/Sonnet/Opus).
   */
  public function getAiCostAnalysis(): array {
    return $this->aiUsageTracker->getAggregatedCosts();
  }
}

4. Frontend y UX (Vanilla JS + Design Tokens)
4.1 UI Pattern: Zero Region & Gin Integration
●	Layout: Pantalla completa dividida en 4 cuadrantes (Patrón Z).
●	Componentes: Inyección de drupalSettings para alimentar gráficos via ECharts.
●	Tokens CSS: Uso obligatorio de --ej-azul-corporativo para ejes y --ej-naranja-impulso para alertas.
5. Directrices de Seguridad
●	Permiso: access platform financial dashboard.
●	Aislamiento: Aunque el dashboard es global, las funciones de "drill-down" deben validar que el tenant_id consultado está activo.
6. Validación Runtime (RUNTIME-VERIFY-001)
1.	PHP: Verificar que el cálculo de Decimal no use floats intermedios.
2.	Twig: Usar _foc_metric_card.html.twig con variantes positive|negative.
3.	JS: Validar que Drupal.behaviors.focCharts se ejecute tras el renderizado de Gin.
