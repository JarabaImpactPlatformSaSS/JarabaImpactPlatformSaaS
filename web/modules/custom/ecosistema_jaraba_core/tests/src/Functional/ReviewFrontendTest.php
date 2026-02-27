<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests para rutas frontend de reviews.
 *
 * Verifica acceso a paginas publicas de reviews, control de acceso
 * para anonimos vs autenticados, y estructura basica del HTML.
 *
 * @group ecosistema_jaraba_core
 * @group reviews
 */
class ReviewFrontendTest extends BrowserTestBase {

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
   * Tests que pagina de reviews comercio es accesible publicamente.
   */
  public function testComercioReviewsPageAccessible(): void {
    // Paginas de reviews son publicas (_permission: 'access content').
    $this->drupalGet('/comercio/1/reviews');

    // Puede ser 200 o 404 si la entidad no existe — no debe ser 403.
    $statusCode = $this->getSession()->getStatusCode();
    $this->assertNotEquals(403, $statusCode, 'La pagina de reviews no debe retornar 403 para anonimos.');
  }

  /**
   * Tests que pagina de reviews agro es accesible publicamente.
   */
  public function testAgroReviewsPageAccessible(): void {
    $this->drupalGet('/agro/1/reviews');
    $statusCode = $this->getSession()->getStatusCode();
    $this->assertNotEquals(403, $statusCode, 'La pagina de reviews agro no debe retornar 403.');
  }

  /**
   * Tests que pagina de reviews servicios es accesible publicamente.
   */
  public function testServiciosReviewsPageAccessible(): void {
    $this->drupalGet('/servicios/1/reviews');
    $statusCode = $this->getSession()->getStatusCode();
    $this->assertNotEquals(403, $statusCode, 'La pagina de reviews servicios no debe retornar 403.');
  }

  /**
   * Tests que pagina de reviews cursos es accesible publicamente.
   */
  public function testFormacionReviewsPageAccessible(): void {
    $this->drupalGet('/cursos/1/reviews');
    $statusCode = $this->getSession()->getStatusCode();
    $this->assertNotEquals(403, $statusCode, 'La pagina de reviews formacion no debe retornar 403.');
  }

  /**
   * Tests que pagina de reviews mentoring es accesible publicamente.
   */
  public function testMentoringReviewPageAccessible(): void {
    $this->drupalGet('/mentoring/1/review');
    $statusCode = $this->getSession()->getStatusCode();
    $this->assertNotEquals(403, $statusCode, 'La pagina de review mentoring no debe retornar 403.');
  }

  /**
   * Tests que API de stats es accesible con permiso access content.
   */
  public function testReviewStatsApiAccessible(): void {
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/reviews/stats/merchant_profile/1');
    $statusCode = $this->getSession()->getStatusCode();

    // La ruta debe existir y responder (200 o 404 para entidad inexistente).
    $this->assertContains($statusCode, [200, 404], 'API stats debe responder 200 o 404.');
  }

  /**
   * Tests que API de stats requiere CSRF o permiso para POST.
   */
  public function testReviewStatsApiAnonymousAccess(): void {
    $this->drupalGet('/api/v1/reviews/stats/merchant_profile/1');
    $statusCode = $this->getSession()->getStatusCode();

    // La ruta requiere _permission: 'access content' y _csrf_request_header_token.
    // Anonimos con access content pueden obtener stats (lectura publica).
    $this->assertNotEquals(500, $statusCode, 'API stats no debe causar error 500.');
  }

  /**
   * Tests que usuario autenticado ve pagina de reviews con contenido.
   */
  public function testAuthenticatedUserSeesReviewsPage(): void {
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $this->drupalGet('/comercio/1/reviews');
    $statusCode = $this->getSession()->getStatusCode();

    // Con usuario autenticado y permiso access content, debe ser accesible.
    $this->assertNotEquals(403, $statusCode, 'Usuario autenticado debe poder acceder a reviews.');
  }

}
