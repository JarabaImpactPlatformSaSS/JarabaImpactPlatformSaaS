<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_page_builder\Service\AccessibilityValidatorService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador API para validacion de accesibilidad WCAG.
 *
 * Proporciona un endpoint REST para validar HTML de bloques
 * del Page Builder contra reglas ARIA y WCAG 2.1 AA.
 *
 * PROPOSITO:
 * El Canvas Editor envia el HTML de un bloque y recibe las
 * violations/warnings para mostrar al usuario en tiempo real.
 *
 * DIRECTRICES:
 * - Spec 20260126 §7.1 (Accesibilidad ARIA)
 * - Respuestas JSON estandar con status + data
 * - Autenticacion via cookie o basic_auth
 *
 * @see docs/implementacion/2026-02-12_Plan_Cierre_Gaps_Specs_20260126_v1.md P0-02
 */
class AccessibilityApiController extends ControllerBase {

  /**
   * Servicio de validacion de accesibilidad.
   */
  protected AccessibilityValidatorService $accessibilityValidator;

  /**
   * Constructor.
   *
   * @param \Drupal\jaraba_page_builder\Service\AccessibilityValidatorService $accessibility_validator
   *   Servicio de validacion.
   */
  public function __construct(AccessibilityValidatorService $accessibility_validator) {
    $this->accessibilityValidator = $accessibility_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_page_builder.accessibility_validator'),
    );
  }

  /**
   * POST: Valida un fragmento HTML contra reglas WCAG.
   *
   * Request body (JSON):
   * - html: string (requerido) - HTML a validar
   * - block_type: string (opcional) - Tipo de bloque para reglas especificas
   *
   * Response:
   * - violations: array de violations
   * - warnings: array de advertencias
   * - passes: array de reglas que pasan
   * - score: int 0-100
   * - level: string ('none', 'A', 'AA', 'AAA')
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Peticion HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Resultado de la validacion.
   */
  public function validate(Request $request): JsonResponse {
    $content = $request->getContent();
    $data = json_decode($content, TRUE);

    if (empty($data['html'])) {
      return new JsonResponse([
        'status' => 'error',
        'message' => $this->t('El campo html es obligatorio'),
      ], 400);
    }

    $html = $data['html'];
    $block_type = $data['block_type'] ?? '';

    // Limitar tamano del HTML para evitar DoS.
    if (strlen($html) > 500000) {
      return new JsonResponse([
        'status' => 'error',
        'message' => $this->t('El HTML excede el tamano maximo permitido (500KB)'),
      ], 413);
    }

    $result = $this->accessibilityValidator->validate($html, $block_type);

    return new JsonResponse([
      'status' => 'ok',
      'data' => $result,
    ]);
  }

}
