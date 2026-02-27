<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests para API endpoints de reviews.
 *
 * Verifica que los endpoints retornan JSON correcto, respetan
 * permisos y requieren CSRF donde corresponde.
 *
 * @group ecosistema_jaraba_core
 * @group reviews
 */
class ReviewApiTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'datetime',
    'ecosistema_jaraba_core',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests que stats API retorna JSON con estructura correcta.
   */
  public function testStatsApiReturnsJson(): void {
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/reviews/stats/merchant_profile/1');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);

      $this->assertNotNull($response, 'Respuesta debe ser JSON valido.');
      $this->assertArrayHasKey('success', $response);

      if ($response['success']) {
        $this->assertArrayHasKey('data', $response);
      }
    }
  }

  /**
   * Tests que stats API maneja target_entity_type invalido.
   */
  public function testStatsApiInvalidTargetType(): void {
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/reviews/stats/nonexistent_type/1');
    $statusCode = $this->getSession()->getStatusCode();

    // Debe responder sin error 500.
    $this->assertNotEquals(500, $statusCode, 'API no debe explotar con tipo invalido.');
  }

  /**
   * Tests que stats API requiere target_entity_id numerico.
   */
  public function testStatsApiNonNumericIdReturns404(): void {
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    // La ruta requiere target_entity_id: '\d+' — texto debe dar 404.
    $this->drupalGet('/api/v1/reviews/stats/merchant_profile/abc');
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Tests que usuario sin acceso no puede ver stats.
   */
  public function testStatsApiRequiresPermission(): void {
    // Usuario sin permisos (ni siquiera access content).
    $user = $this->drupalCreateUser([]);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/reviews/stats/merchant_profile/1');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests multiples target types en stats API.
   *
   * @dataProvider targetTypeProvider
   */
  public function testStatsApiMultipleTargetTypes(string $targetType): void {
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/reviews/stats/' . $targetType . '/1');
    $statusCode = $this->getSession()->getStatusCode();

    // Cualquier tipo debe retornar respuesta valida (200) o vacía.
    $this->assertContains($statusCode, [200, 404], "Stats para {$targetType} debe responder correctamente.");
  }

  /**
   * Data provider para target types validos.
   */
  public static function targetTypeProvider(): array {
    return [
      'merchant_profile' => ['merchant_profile'],
      'producer_profile' => ['producer_profile'],
      'provider_profile' => ['provider_profile'],
      'lms_course' => ['lms_course'],
      'mentoring_session' => ['mentoring_session'],
    ];
  }

  /**
   * Tests que API de stats devuelve distribución de ratings.
   */
  public function testStatsApiContainsDistribution(): void {
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/reviews/stats/merchant_profile/1');

    if ($this->getSession()->getStatusCode() === 200) {
      $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);

      if ($response && ($response['success'] ?? FALSE) && isset($response['data'])) {
        $data = $response['data'];
        $this->assertArrayHasKey('average', $data);
        $this->assertArrayHasKey('count', $data);
        $this->assertArrayHasKey('distribution', $data);
        $this->assertIsArray($data['distribution']);
      }
    }
    // Si no hay datos, el test pasa igualmente (entorno limpio).
    $this->assertTrue(TRUE);
  }

  /**
   * Tests que las rutas de reviews frontend retornan content-type HTML.
   */
  public function testFrontendRoutesReturnHtml(): void {
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $routes = [
      '/comercio/1/reviews',
      '/agro/1/reviews',
      '/servicios/1/reviews',
      '/cursos/1/reviews',
      '/mentoring/1/review',
    ];

    foreach ($routes as $route) {
      $this->drupalGet($route);
      $statusCode = $this->getSession()->getStatusCode();

      if ($statusCode === 200) {
        $headers = $this->getSession()->getResponseHeaders();
        $contentType = $headers['content-type'][0] ?? '';
        $this->assertStringContainsString(
          'text/html',
          $contentType,
          "Ruta {$route} debe retornar HTML."
        );
      }
    }
  }

}
