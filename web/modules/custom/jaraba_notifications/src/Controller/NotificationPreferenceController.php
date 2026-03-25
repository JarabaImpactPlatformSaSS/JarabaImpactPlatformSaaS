<?php

declare(strict_types=1);

namespace Drupal\jaraba_notifications\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\jaraba_notifications\Entity\NotificationPreference;
use Drupal\jaraba_notifications\Service\NotificationPreferenceService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador para el centro de preferencias de notificaciones.
 *
 * Ruta frontend: /mi-cuenta/notificaciones
 * API: GET/PATCH /api/v1/user/notification-preferences.
 *
 * Frontend route con _admin_route: FALSE.
 */
class NotificationPreferenceController extends ControllerBase {

  public function __construct(
    protected NotificationPreferenceService $preferenceService,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_notifications.preference'),
    );
  }

  /**
   * Pagina de preferencias de notificaciones — GET /mi-cuenta/notificaciones.
   *
   * CONTROLLER-READONLY-001: No readonly en propiedades heredadas.
   */
  public function preferences(): array {
    $userId = (int) $this->currentUser()->id();
    $matrix = $this->preferenceService->getPreferencesMatrix($userId);

    $typeLabels = [
      'system' => $this->t('Sistema'),
      'social' => $this->t('Social'),
      'workflow' => $this->t('Flujo de trabajo'),
      'ai' => $this->t('IA y Copilot'),
      'marketing' => $this->t('Marketing'),
    ];

    $typeDescriptions = [
      'system' => $this->t('Mantenimiento, actualizaciones y alertas de seguridad de la plataforma.'),
      'social' => $this->t('Menciones, respuestas, seguidores y actividad social.'),
      'workflow' => $this->t('Asignaciones, aprobaciones, completados y tareas pendientes.'),
      'ai' => $this->t('Sugerencias del copilot, resultados de analisis y alertas inteligentes.'),
      'marketing' => $this->t('Novedades, ofertas especiales y contenido destacado.'),
    ];

    $preferencesForTemplate = [];
    foreach (NotificationPreference::VALID_TYPES as $type) {
      $preferencesForTemplate[] = [
        'type' => $type,
        'label' => (string) ($typeLabels[$type] ?? $type),
        'description' => (string) ($typeDescriptions[$type] ?? ''),
        'email' => $matrix[$type]['email'] ?? TRUE,
        'push' => $matrix[$type]['push'] ?? FALSE,
        'in_app' => $matrix[$type]['in_app'] ?? TRUE,
      ];
    }

    $apiUrl = Url::fromRoute('jaraba_notifications.api.preferences.update')->toString();

    return [
      '#theme' => 'notification_preferences_center',
      '#preferences' => $preferencesForTemplate,
      '#channels' => [
        ['key' => 'email', 'label' => (string) $this->t('Email')],
        ['key' => 'push', 'label' => (string) $this->t('Push')],
        ['key' => 'in_app', 'label' => (string) $this->t('En la app')],
      ],
      '#attached' => [
        'library' => [
          'jaraba_notifications/notification-preferences',
        ],
        'drupalSettings' => [
          'jarabaNotifications' => [
            'preferencesApiUrl' => $apiUrl,
          ],
        ],
      ],
      '#cache' => [
        'tags' => ['notification_preference_list'],
        'contexts' => ['user'],
        'max-age' => 0,
      ],
    ];
  }

  /**
   * API: Obtiene las preferencias del usuario actual — GET.
   */
  public function apiGet(): JsonResponse {
    $userId = (int) $this->currentUser()->id();
    $matrix = $this->preferenceService->getPreferencesMatrix($userId);

    return new JsonResponse([
      'success' => TRUE,
      'preferences' => $matrix,
    ]);
  }

  /**
   * API: Actualiza una preferencia — PATCH.
   *
   * Body JSON esperado: {"type": "social", "channel": "email", "enabled": false}
   */
  public function apiUpdate(Request $request): JsonResponse {
    $userId = (int) $this->currentUser()->id();

    $content = $request->getContent();
    if (empty($content)) {
      return new JsonResponse(['error' => 'Empty request body.'], 400);
    }

    $data = json_decode($content, TRUE);
    if (!is_array($data)) {
      return new JsonResponse(['error' => 'Invalid JSON.'], 400);
    }

    $type = $data['type'] ?? '';
    $channel = $data['channel'] ?? '';
    $enabled = $data['enabled'] ?? NULL;

    if (!in_array($type, NotificationPreference::VALID_TYPES, TRUE)) {
      return new JsonResponse(['error' => 'Invalid notification type.'], 400);
    }

    if (!in_array($channel, NotificationPreference::VALID_CHANNELS, TRUE)) {
      return new JsonResponse(['error' => 'Invalid channel.'], 400);
    }

    if (!is_bool($enabled)) {
      return new JsonResponse(['error' => 'Field "enabled" must be boolean.'], 400);
    }

    try {
      $this->preferenceService->updatePreference($userId, $type, $channel, $enabled);
    }
    catch (\Throwable $e) {
      $this->getLogger('jaraba_notifications')->error('Failed to update preference: @error', [
        '@error' => $e->getMessage(),
      ]);
      return new JsonResponse(['error' => 'Failed to update preference.'], 500);
    }

    return new JsonResponse([
      'success' => TRUE,
      'type' => $type,
      'channel' => $channel,
      'enabled' => $enabled,
    ]);
  }

}
