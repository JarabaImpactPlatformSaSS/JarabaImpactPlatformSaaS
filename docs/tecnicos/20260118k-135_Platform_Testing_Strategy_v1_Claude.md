TESTING STRATEGY
Estrategia de Calidad y Testing del Ecosistema

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Código:	135_Platform_Testing_Strategy
 
1. Pirámide de Testing
┌─────────────┐
                          │    E2E      │  10%
                          │  (Cypress)  │  ~50 tests
                          ├─────────────┤
                      ┌───┴─────────────┴───┐
                      │   Integration       │  20%
                      │   (Kernel Tests)    │  ~200 tests
                      ├─────────────────────┤
                  ┌───┴─────────────────────┴───┐
                  │       Unit Tests            │  70%
                  │       (PHPUnit)             │  ~1000 tests
                  └─────────────────────────────┘
1.1 Objetivos de Cobertura
Tipo	Framework	Cobertura Target	Tiempo Ejecución
Unit Tests	PHPUnit	> 80%	< 5 min
Kernel/Integration	PHPUnit + Drupal	> 60%	< 15 min
E2E / Funcional	Cypress	Flujos críticos 100%	< 20 min
Visual Regression	Percy/Chromatic	Páginas clave	< 10 min
Performance	k6 / Lighthouse	Core Web Vitals pass	< 5 min
Security	OWASP ZAP	0 High/Critical	Weekly
 
2. Tests Unitarios (PHPUnit)
2.1 Estructura de Directorios
web/modules/custom/jaraba_*/tests/
├── src/
│   ├── Unit/                    # Tests unitarios puros
│   │   ├── Service/
│   │   │   ├── SubscriptionServiceTest.php
│   │   │   └── BillingCalculatorTest.php
│   │   └── Entity/
│   │       └── TenantValidationTest.php
│   ├── Kernel/                  # Tests con Drupal bootstrap
│   │   ├── EntityCrudTest.php
│   │   └── ApiEndpointTest.php
│   └── Functional/              # Tests con browser
│       └── CheckoutFlowTest.php
└── fixtures/
    └── test-data.yml
2.2 Ejemplo de Test Unitario
<?php
// tests/src/Unit/Service/BillingCalculatorTest.php
 
namespace Drupal\Tests\jaraba_billing\Unit\Service;
 
use Drupal\jaraba_billing\Service\BillingCalculator;
use PHPUnit\Framework\TestCase;
 
class BillingCalculatorTest extends TestCase {
 
  private BillingCalculator $calculator;
 
  protected function setUp(): void {
    parent::setUp();
    $this->calculator = new BillingCalculator();
  }
 
  /**
   * @dataProvider prorataDataProvider
   */
  public function testCalculateProrata(
    int $monthlyPrice,
    int $daysRemaining,
    int $daysInMonth,
    int $expected
  ): void {
    $result = $this->calculator->calculateProrata(
      $monthlyPrice,
      $daysRemaining,
      $daysInMonth
    );
    
    $this->assertEquals($expected, $result);
  }
 
  public function prorataDataProvider(): array {
    return [
      'full month' => [7900, 30, 30, 7900],
      'half month' => [7900, 15, 30, 3950],
      'one day' => [7900, 1, 30, 263],
      'upgrade mid-month' => [14900, 15, 30, 7450],
    ];
  }
 
  public function testMarketplaceCommission(): void {
    // AgroConecta: 8% comisión
    $result = $this->calculator->calculatePlatformFee(10000, 'agroconecta');
    $this->assertEquals(800, $result);
 
    // ComercioConecta: 6% comisión
    $result = $this->calculator->calculatePlatformFee(10000, 'comercioconecta');
    $this->assertEquals(600, $result);
  }
}
 
3. Tests E2E (Cypress)
3.1 Flujos Críticos a Cubrir
Flujo	Archivo	Prioridad	Duración
Registro de usuario	auth/register.cy.js	P0	30s
Login / Logout	auth/login.cy.js	P0	20s
Checkout suscripción	billing/checkout.cy.js	P0	60s
Crear tenant	tenant/create.cy.js	P0	45s
Publicar producto (Agro)	agro/publish-product.cy.js	P0	40s
Completar compra (Agro)	agro/purchase.cy.js	P0	60s
Crear oferta empleo	empleabilidad/job-post.cy.js	P1	35s
Aplicar a oferta	empleabilidad/apply.cy.js	P1	30s
Diagnóstico negocio	emprendimiento/diagnostic.cy.js	P1	50s
Chat con IA	ai/copilot-chat.cy.js	P1	25s
3.2 Ejemplo de Test Cypress
// cypress/e2e/billing/checkout.cy.js
 
describe('Subscription Checkout', () => {
  beforeEach(() => {
    cy.login('test@example.com', 'password123');
  });
 
  it('should complete Growth plan subscription', () => {
    // Navegar a pricing
    cy.visit('/pricing');
    
    // Seleccionar plan Growth
    cy.get('[data-plan="growth"]')
      .find('[data-action="subscribe"]')
      .click();
    
    // Verificar redirect a checkout
    cy.url().should('include', '/checkout');
    
    // Rellenar datos de facturación
    cy.get('#billing-name').type('Empresa Test S.L.');
    cy.get('#billing-nif').type('B12345678');
    cy.get('#billing-address').type('Calle Test 123');
    cy.get('#billing-city').type('Córdoba');
    cy.get('#billing-postal').type('14001');
    
    // Usar tarjeta de test de Stripe
    cy.getStripeElement('cardNumber').type('4242424242424242');
    cy.getStripeElement('cardExpiry').type('1230');
    cy.getStripeElement('cardCvc').type('123');
    
    // Confirmar pago
    cy.get('[data-action="pay"]').click();
    
    // Esperar procesamiento
    cy.get('[data-status="processing"]', { timeout: 10000 })
      .should('not.exist');
    
    // Verificar éxito
    cy.url().should('include', '/subscription/success');
    cy.contains('¡Bienvenido al plan Growth!');
    
    // Verificar que el plan está activo
    cy.visit('/account/subscription');
    cy.get('[data-current-plan]').should('contain', 'Growth');
  });
 
  it('should handle payment failure gracefully', () => {
    cy.visit('/checkout?plan=growth');
    
    // Usar tarjeta que será rechazada
    cy.getStripeElement('cardNumber').type('4000000000000002');
    cy.getStripeElement('cardExpiry').type('1230');
    cy.getStripeElement('cardCvc').type('123');
    
    cy.get('[data-action="pay"]').click();
    
    // Verificar mensaje de error
    cy.get('[data-error="payment"]')
      .should('be.visible')
      .and('contain', 'tarjeta fue rechazada');
  });
});
 
4. Tests de Performance
4.1 Script k6 para Load Testing
// tests/performance/load-test.js
import http from 'k6/http';
import { check, sleep } from 'k6';
 
export const options = {
  stages: [
    { duration: '2m', target: 50 },   // Ramp up
    { duration: '5m', target: 50 },   // Steady state
    { duration: '2m', target: 100 },  // Peak
    { duration: '5m', target: 100 },  // Sustained peak
    { duration: '2m', target: 0 },    // Ramp down
  ],
  thresholds: {
    http_req_duration: ['p(95)<500', 'p(99)<1000'],
    http_req_failed: ['rate<0.01'],
  },
};
 
const BASE_URL = __ENV.BASE_URL || 'https://staging.jarabaimpact.com';
 
export default function() {
  // Homepage
  let res = http.get(BASE_URL);
  check(res, {
    'homepage status 200': (r) => r.status === 200,
    'homepage < 500ms': (r) => r.timings.duration < 500,
  });
 
  sleep(1);
 
  // API endpoint
  res = http.get(`${BASE_URL}/api/v1/products?limit=20`);
  check(res, {
    'api status 200': (r) => r.status === 200,
    'api < 200ms': (r) => r.timings.duration < 200,
  });
 
  sleep(1);
 
  // Search
  res = http.get(`${BASE_URL}/search?q=vino`);
  check(res, {
    'search status 200': (r) => r.status === 200,
    'search < 300ms': (r) => r.timings.duration < 300,
  });
 
  sleep(2);
}
4.2 Thresholds de Performance
Métrica	Target	Crítico	Herramienta
TTFB	< 200ms	> 500ms	k6, Lighthouse
LCP	< 2.5s	> 4s	Lighthouse
FID	< 100ms	> 300ms	Lighthouse
CLS	< 0.1	> 0.25	Lighthouse
API p95	< 500ms	> 1s	k6
Error Rate	< 0.1%	> 1%	k6
Throughput	> 100 rps	< 50 rps	k6
5. Checklist de QA Pre-Release
•	[ ] Todos los tests unitarios pasan (> 80% coverage)
•	[ ] Todos los tests de integración pasan
•	[ ] Todos los tests E2E de flujos críticos pasan
•	[ ] Lighthouse score > 90 en Performance, Accessibility
•	[ ] Load test pasa con thresholds definidos
•	[ ] Security scan sin vulnerabilidades High/Critical
•	[ ] Smoke tests manuales en staging
•	[ ] Revisión de código aprobada
•	[ ] Documentación actualizada

--- Fin del Documento ---
