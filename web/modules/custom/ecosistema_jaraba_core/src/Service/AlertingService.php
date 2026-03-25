<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Servicio de alertas para Slack y Microsoft Teams.
 *
 * PROPÓSITO:
 * Envía notificaciones a canales de Slack o Teams cuando ocurren
 * eventos importantes en la plataforma (anomalías, errores, alertas FOC).
 *
 * PHASE 12: Observability & Alerting
 */
class AlertingService {

  /**
   * Tipos de alerta soportados.
   */
  public const ALERT_INFO = 'info';
  public const ALERT_WARNING = 'warning';
  public const ALERT_ERROR = 'error';
  public const ALERT_CRITICAL = 'critical';
  public const ALERT_SUCCESS = 'success';

  /**
   * Colores por tipo de alerta.
   */
  protected const COLORS = [
    self::ALERT_INFO => '#3b82f6',
    self::ALERT_WARNING => '#f59e0b',
    self::ALERT_ERROR => '#ef4444',
    self::ALERT_CRITICAL => '#7c3aed',
    self::ALERT_SUCCESS => '#10b981',
  ];

  /**
   * Emojis por tipo de alerta.
   */
  protected const EMOJIS = [
    self::ALERT_INFO => 'ℹ️',
    self::ALERT_WARNING => '⚠️',
    self::ALERT_ERROR => '🚨',
    self::ALERT_CRITICAL => '🔥',
    self::ALERT_SUCCESS => '✅',
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected ClientInterface $httpClient,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {
  }

  /**
   * Envía una alerta a todos los canales configurados.
   *
   * @param string $title
   *   Título de la alerta.
   * @param string $message
   *   Mensaje de la alerta.
   * @param string $type
   *   Tipo de alerta (info, warning, error, critical, success).
   * @param array $fields
   *   Campos adicionales [['title' => '', 'value' => '']].
   * @param string|null $link
   *   URL opcional para más información.
   */
  public function send(
    string $title,
    string $message,
    string $type = self::ALERT_INFO,
    array $fields = [],
    ?string $link = NULL,
  ): void {
    $config = $this->configFactory->get('ecosistema_jaraba_core.alerting');

    // Enviar a Slack si está configurado.
    $slackWebhook = $config->get('slack_webhook_url');
    if (!empty($slackWebhook)) {
      $this->sendToSlack($slackWebhook, $title, $message, $type, $fields, $link);
    }

    // Enviar a Teams si está configurado.
    $teamsWebhook = $config->get('teams_webhook_url');
    if (!empty($teamsWebhook)) {
      $this->sendToTeams($teamsWebhook, $title, $message, $type, $fields, $link);
    }

    // Log local siempre.
    $logger = $this->loggerFactory->get('alerting');
    $logger->notice('Alert [@type]: @title - @message', [
      '@type' => $type,
      '@title' => $title,
      '@message' => $message,
    ]);
  }

  /**
   * Envía alerta a Slack.
   */
  protected function sendToSlack(
    string $webhookUrl,
    string $title,
    string $message,
    string $type,
    array $fields,
    ?string $link,
  ): void {
    $color = self::COLORS[$type] ?? self::COLORS[self::ALERT_INFO];
    $emoji = self::EMOJIS[$type] ?? '';

    $attachment = [
      'color' => $color,
      'title' => $emoji . ' ' . $title,
      'text' => $message,
      'footer' => 'Jaraba Impact Platform',
      'footer_icon' => 'https://plataformadeecosistemas.es/favicon.ico',
      'ts' => time(),
    ];

    if (!empty($fields)) {
      $attachment['fields'] = array_map(function ($field) {
          return [
            'title' => $field['title'],
            'value' => $field['value'],
            'short' => $field['short'] ?? TRUE,
          ];
      }, $fields);
    }

    if ($link) {
      $attachment['title_link'] = $link;
    }

    $payload = [
      'attachments' => [$attachment],
    ];

    $this->sendWebhook($webhookUrl, $payload, 'Slack');
  }

  /**
   * Envía alerta a Microsoft Teams.
   */
  protected function sendToTeams(
    string $webhookUrl,
    string $title,
    string $message,
    string $type,
    array $fields,
    ?string $link,
  ): void {
    $color = str_replace('#', '', self::COLORS[$type] ?? self::COLORS[self::ALERT_INFO]);
    $emoji = self::EMOJIS[$type] ?? '';

    // Formato Adaptive Card para Teams.
    $facts = array_map(function ($field) {
        return [
          'title' => $field['title'],
          'value' => $field['value'],
        ];
    }, $fields);

    $payload = [
      '@type' => 'MessageCard',
      '@context' => 'http://schema.org/extensions',
      'themeColor' => $color,
      'summary' => $title,
      'sections' => [
              [
                'activityTitle' => $emoji . ' ' . $title,
                'activitySubtitle' => date('Y-m-d H:i:s'),
                'activityImage' => 'https://plataformadeecosistemas.es/favicon.ico',
                'facts' => array_merge([
                      ['name' => 'Mensaje', 'value' => $message],
                ], $facts),
                'markdown' => TRUE,
              ],
      ],
    ];

    if ($link) {
      $payload['potentialAction'] = [
            [
              '@type' => 'OpenUri',
              'name' => 'Ver detalles',
              'targets' => [
                    ['os' => 'default', 'uri' => $link],
              ],
            ],
      ];
    }

    $this->sendWebhook($webhookUrl, $payload, 'Teams');
  }

  /**
   * Envía el webhook.
   */
  protected function sendWebhook(string $url, array $payload, string $service): void {
    try {
      $this->httpClient->request('POST', $url, [
        'json' => $payload,
        'timeout' => 5,
      ]);
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('alerting')->error(
            'Failed to send alert to @service: @error',
            ['@service' => $service, '@error' => $e->getMessage()]
            );
    }
  }

  /**
   * Envía alerta de anomalía financiera.
   */
  public function alertFinancialAnomaly(
    string $metric,
    float $value,
    float $threshold,
    ?string $tenantId = NULL,
  ): void {
    $fields = [
          ['title' => 'Métrica', 'value' => $metric],
          ['title' => 'Valor actual', 'value' => number_format($value, 2)],
          ['title' => 'Umbral', 'value' => number_format($threshold, 2)],
    ];

    if ($tenantId) {
      $fields[] = ['title' => 'Tenant', 'value' => $tenantId];
    }

    $this->send(
          'Anomalía Financiera Detectada',
          "La métrica {$metric} ha superado el umbral configurado.",
          self::ALERT_WARNING,
          $fields,
          '/admin/foc/dashboard'
      );
  }

  /**
   * Envía alerta de error del sistema.
   */
  public function alertSystemError(string $component, string $error, array $context = []): void {
    $fields = [
          ['title' => 'Componente', 'value' => $component],
          ['title' => 'Error', 'value' => substr($error, 0, 200)],
    ];

    foreach ($context as $key => $value) {
      $fields[] = ['title' => $key, 'value' => (string) $value];
    }

    $this->send(
          'Error del Sistema',
          "Se ha producido un error en {$component}.",
          self::ALERT_ERROR,
          $fields
      );
  }

  /**
   * Envía alerta de nuevo tenant.
   */
  public function alertNewTenant(string $tenantName, string $plan): void {
    $this->send(
          '🎉 Nuevo Tenant Registrado',
          "Se ha registrado un nuevo tenant: {$tenantName}",
          self::ALERT_SUCCESS,
          [
              ['title' => 'Tenant', 'value' => $tenantName],
              ['title' => 'Plan', 'value' => $plan],
          ],
          '/admin/structure/tenants'
      );
  }

  /**
   * Envía alerta de pago recibido.
   */
  public function alertPaymentReceived(string $tenantName, float $amount, string $currency = 'EUR'): void {
    $this->send(
          '💰 Pago Recibido',
          "Se ha recibido un pago de {$tenantName}",
          self::ALERT_SUCCESS,
          [
              ['title' => 'Tenant', 'value' => $tenantName],
              ['title' => 'Monto', 'value' => $currency . ' ' . number_format($amount, 2)],
          ]
      );
  }

  /**
   * Envía alerta de trial expirando.
   */
  public function alertTrialExpiring(string $tenantName, int $daysRemaining): void {
    $type = $daysRemaining <= 3 ? self::ALERT_WARNING : self::ALERT_INFO;

    $this->send(
          '⏰ Trial Próximo a Expirar',
          "El período de prueba de {$tenantName} expira en {$daysRemaining} días.",
          $type,
          [
              ['title' => 'Tenant', 'value' => $tenantName],
              ['title' => 'Días restantes', 'value' => (string) $daysRemaining],
          ]
      );
  }

}
