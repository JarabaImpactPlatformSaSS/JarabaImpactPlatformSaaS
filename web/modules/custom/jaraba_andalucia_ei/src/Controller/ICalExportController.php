<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Drupal\jaraba_andalucia_ei\Service\ICalExportService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for iCal calendar feed exports.
 *
 * Sprint 17 — Integración Calendarios Externos.
 *
 * Exposes .ics feeds at token-protected URLs for external
 * calendar subscription (Google Calendar, Outlook, Apple Calendar).
 *
 * Token-based auth allows calendar apps to poll without session cookies.
 * The token is HMAC-SHA256(tenant_id:scope, site hash_salt).
 */
class ICalExportController extends ControllerBase {

  public function __construct(
    protected ICalExportService $icalService,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_andalucia_ei.ical_export'),
      $container->get('logger.channel.jaraba_andalucia_ei'),
    );
  }

  /**
   * Feed iCal de todas las sesiones de un tenant.
   *
   * URL: /api/v1/andalucia-ei/calendar/{tenant_id}/sesiones.ics?token=...
   */
  public function feedTenant(Request $request, int $tenant_id): Response {
    $this->validateToken($request, $tenant_id, 'tenant');

    $dias = min((int) ($request->query->get('dias', 90)), 365);
    $content = $this->icalService->generarFeedTenant($tenant_id, $dias);

    return $this->icalResponse($content, "sesiones-tenant-{$tenant_id}.ics");
  }

  /**
   * Feed iCal de sesiones de un orientador.
   *
   * URL: /api/v1/andalucia-ei/calendar/{tenant_id}/orientador/{uid}.ics?token=...
   */
  public function feedOrientador(Request $request, int $tenant_id, int $uid): Response {
    $this->validateToken($request, $tenant_id, "orientador:{$uid}");

    $dias = min((int) ($request->query->get('dias', 90)), 365);
    $content = $this->icalService->generarFeedOrientador($tenant_id, $uid, $dias);

    return $this->icalResponse($content, "sesiones-orientador-{$uid}.ics");
  }

  /**
   * Genera un token de suscripción para un feed.
   *
   * Endpoint interno (requiere autenticación) para obtener la URL
   * tokenizada que el usuario pegará en Google Calendar / Outlook.
   *
   * URL: /api/v1/andalucia-ei/calendar/subscribe-url
   */
  public function getSubscribeUrl(Request $request): Response {
    $tenantId = (int) $request->query->get('tenant_id', 0);
    $scope = $request->query->get('scope', 'tenant');
    $uid = (int) $request->query->get('uid', 0);

    if ($tenantId <= 0) {
      throw new NotFoundHttpException('Missing tenant_id');
    }

    $tokenScope = $scope === 'orientador' && $uid > 0 ? "orientador:{$uid}" : 'tenant';
    $token = $this->generateToken($tenantId, $tokenScope);

    if ($tokenScope === 'tenant') {
      $path = "/api/v1/andalucia-ei/calendar/{$tenantId}/sesiones.ics";
    }
    else {
      $path = "/api/v1/andalucia-ei/calendar/{$tenantId}/orientador/{$uid}.ics";
    }

    // Build absolute URL for the feed.
    $baseUrl = $request->getSchemeAndHttpHost();
    $feedUrl = $baseUrl . $path . '?token=' . $token;

    return new Response(
      json_encode(['url' => $feedUrl, 'scope' => $tokenScope], JSON_THROW_ON_ERROR),
      200,
      ['Content-Type' => 'application/json']
    );
  }

  /**
   * Validates HMAC token from query string.
   *
   * AUDIT-SEC-001: HMAC + hash_equals for webhook/feed auth.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  protected function validateToken(Request $request, int $tenantId, string $scope): void {
    $token = $request->query->get('token', '');
    if (empty($token)) {
      throw new AccessDeniedHttpException('Missing calendar token');
    }

    $expected = $this->generateToken($tenantId, $scope);
    if (!hash_equals($expected, $token)) {
      $this->logger->warning('Invalid iCal token for tenant @tid scope @scope.', [
        '@tid' => $tenantId,
        '@scope' => $scope,
      ]);
      throw new AccessDeniedHttpException('Invalid calendar token');
    }
  }

  /**
   * Generates HMAC-SHA256 token for a feed.
   *
   * @param int $tenantId
   *   Tenant ID.
   * @param string $scope
   *   Feed scope (e.g., 'tenant' or 'orientador:123').
   *
   * @return string
   *   Hex-encoded HMAC token.
   */
  protected function generateToken(int $tenantId, string $scope): string {
    $salt = Settings::getHashSalt();
    $payload = "ical:{$tenantId}:{$scope}";
    return hash_hmac('sha256', $payload, $salt);
  }

  /**
   * Returns a Response with iCal content headers.
   *
   * @param string $content
   *   iCalendar content.
   * @param string $filename
   *   Suggested filename.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  protected function icalResponse(string $content, string $filename): Response {
    return new Response($content, 200, [
      'Content-Type' => 'text/calendar; charset=utf-8',
      'Content-Disposition' => 'inline; filename="' . $filename . '"',
      'Cache-Control' => 'public, max-age=900',
    ]);
  }

}
