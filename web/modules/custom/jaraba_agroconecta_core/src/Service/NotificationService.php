<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jaraba_agroconecta_core\Entity\NotificationLogAgro;
use Drupal\jaraba_agroconecta_core\Entity\NotificationTemplateAgro;
use Psr\Log\LoggerInterface;

/**
 * Servicio de notificaciones AgroConecta.
 *
 * PROPÓSITO:
 * Centraliza el envío de notificaciones multi-canal (email, push, in-app, SMS).
 * Renderiza plantillas con tokens Twig, respeta preferencias del usuario,
 * y registra cada envío en NotificationLogAgro para auditoría y métricas.
 *
 * FLUJO:
 * 1. send() → busca plantilla + verifica preferencias + renderiza + envía
 * 2. Canal email → usa MailManager de Drupal / Symfony Mailer
 * 3. Canal in_app → crea registro visible en el portal del usuario
 * 4. Cada envío → NotificationLogAgro con estado y tracking
 */
class NotificationService
{

    /**
     * El logger.
     */
    protected LoggerInterface $logger;

    /**
     * Constructor del servicio.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected AccountInterface $currentUser,
        protected MailManagerInterface $mailManager,
        LoggerChannelFactoryInterface $loggerFactory,
    ) {
        $this->logger = $loggerFactory->get('jaraba_agroconecta');
    }

    /**
     * Envía una notificación a un usuario.
     *
     * Busca la plantilla por tipo y canal, verifica las preferencias del
     * usuario, renderiza el contenido con los tokens y registra el envío.
     *
     * @param string $type
     *   Tipo de notificación (ej: order_confirmed, new_review).
     * @param int $recipientUserId
     *   ID del usuario destinatario.
     * @param array $context
     *   Datos de contexto para los tokens de la plantilla.
     * @param string|null $channel
     *   Canal específico. Si NULL, se envía por todos los canales habilitados.
     *
     * @return array
     *   Array de IDs de NotificationLogAgro creados.
     */
    public function send(string $type, int $recipientUserId, array $context = [], ?string $channel = NULL): array
    {
        $logIds = [];

        // Determinar canales habilitados para el usuario.
        $channels = $channel ? [$channel] : $this->getEnabledChannels($recipientUserId, $type);

        if (empty($channels)) {
            $this->logger->info('Notificación @type para usuario @uid omitida: sin canales habilitados.', [
                '@type' => $type,
                '@uid' => $recipientUserId,
            ]);
            return $logIds;
        }

        foreach ($channels as $ch) {
            $template = $this->findTemplate($type, $ch);

            if (!$template) {
                $this->logger->warning('Plantilla no encontrada: tipo=@type, canal=@channel.', [
                    '@type' => $type,
                    '@channel' => $ch,
                ]);
                continue;
            }

            // Renderizar contenido con tokens.
            $subject = $this->renderTokens($template->get('subject')->value ?? '', $context);
            $body = $this->renderTokens($template->get('body')->value ?? '', $context);
            $bodyHtml = $this->renderTokens($template->get('body_html')->value ?? '', $context);

            // Enviar por el canal correspondiente.
            $result = $this->dispatchToChannel($ch, $recipientUserId, $subject, $body, $bodyHtml, $context);

            // Registrar en el log.
            $logId = $this->createLog($template, $ch, $recipientUserId, $subject, $body, $context, $result);
            if ($logId) {
                $logIds[] = $logId;
            }
        }

        return $logIds;
    }

    /**
     * Envía una notificación masiva a múltiples usuarios.
     *
     * @param string $type
     *   Tipo de notificación.
     * @param array $recipientUserIds
     *   Array de IDs de usuarios destinatarios.
     * @param array $context
     *   Datos de contexto comunes.
     *
     * @return int
     *   Número total de notificaciones enviadas.
     */
    public function sendBulk(string $type, array $recipientUserIds, array $context = []): int
    {
        $total = 0;
        foreach ($recipientUserIds as $userId) {
            $logIds = $this->send($type, (int) $userId, $context);
            $total += count($logIds);
        }
        return $total;
    }

    /**
     * Obtiene las preferencias de un usuario para un tipo de notificación.
     *
     * @param int $userId
     *   ID del usuario.
     * @param string $notificationType
     *   Tipo de notificación.
     *
     * @return array
     *   Array de canales habilitados.
     */
    public function getEnabledChannels(int $userId, string $notificationType): array
    {
        $storage = $this->entityTypeManager->getStorage('notification_preference_agro');
        $preferences = $storage->loadByProperties([
            'uid' => $userId,
            'notification_type' => $notificationType,
        ]);

        if (!empty($preferences)) {
            /** @var \Drupal\jaraba_agroconecta_core\Entity\NotificationPreferenceAgro $preference */
            $preference = reset($preferences);
            return $preference->getEnabledChannels();
        }

        // Sin preferencias configuradas: usar defaults (email + in-app).
        return [
            NotificationTemplateAgro::CHANNEL_EMAIL,
            NotificationTemplateAgro::CHANNEL_IN_APP,
        ];
    }

    /**
     * Crea o actualiza las preferencias de un usuario.
     *
     * @param int $userId
     *   ID del usuario.
     * @param string $notificationType
     *   Tipo de notificación.
     * @param array $channels
     *   Array con clave = canal, valor = bool.
     *   Ejemplo: ['email' => TRUE, 'push' => FALSE, 'sms' => FALSE, 'in_app' => TRUE]
     *
     * @return bool
     *   TRUE si se guardó correctamente.
     */
    public function updatePreferences(int $userId, string $notificationType, array $channels): bool
    {
        try {
            $storage = $this->entityTypeManager->getStorage('notification_preference_agro');
            $existing = $storage->loadByProperties([
                'uid' => $userId,
                'notification_type' => $notificationType,
            ]);

            if (!empty($existing)) {
                /** @var \Drupal\jaraba_agroconecta_core\Entity\NotificationPreferenceAgro $preference */
                $preference = reset($existing);
            } else {
                /** @var \Drupal\jaraba_agroconecta_core\Entity\NotificationPreferenceAgro $preference */
                $preference = $storage->create([
                    'uid' => $userId,
                    'notification_type' => $notificationType,
                ]);
            }

            foreach ($channels as $channel => $enabled) {
                $fieldName = 'channel_' . $channel;
                if ($preference->hasField($fieldName)) {
                    $preference->set($fieldName, (bool) $enabled);
                }
            }

            $preference->save();
            return TRUE;
        } catch (\Exception $e) {
            $this->logger->error('Error al actualizar preferencias: @message', [
                '@message' => $e->getMessage(),
            ]);
            return FALSE;
        }
    }

    /**
     * Obtiene los logs de notificaciones con paginación.
     *
     * @param int $limit
     *   Límite de resultados.
     * @param int $offset
     *   Offset de paginación.
     * @param string|null $type
     *   Filtrar por tipo de notificación.
     * @param string|null $status
     *   Filtrar por estado de entrega.
     *
     * @return array
     *   Array con 'data' y 'meta'.
     */
    public function getLogs(int $limit = 50, int $offset = 0, ?string $type = NULL, ?string $status = NULL): array
    {
        $storage = $this->entityTypeManager->getStorage('notification_log_agro');

        // Contar total.
        $countQuery = $storage->getQuery()->accessCheck(FALSE)->count();
        if ($type) {
            $countQuery->condition('type', $type);
        }
        if ($status) {
            $countQuery->condition('status', $status);
        }
        $total = (int) $countQuery->execute();

        // Consultar con paginación.
        $query = $storage->getQuery()
            ->accessCheck(FALSE)
            ->sort('created', 'DESC')
            ->range($offset, $limit);
        if ($type) {
            $query->condition('type', $type);
        }
        if ($status) {
            $query->condition('status', $status);
        }
        $ids = $query->execute();

        $logs = [];
        if (!empty($ids)) {
            $entities = $storage->loadMultiple($ids);
            foreach ($entities as $entity) {
                /** @var \Drupal\jaraba_agroconecta_core\Entity\NotificationLogAgro $entity */
                $logs[] = $this->serializeLog($entity);
            }
        }

        return [
            'data' => $logs,
            'meta' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ];
    }

    /**
     * Obtiene métricas de notificaciones.
     *
     * @return array
     *   Array con métricas: total enviadas, tasa de apertura, tasa de clic,
     *   tasa de error, distribución por canal.
     */
    public function getMetrics(): array
    {
        $storage = $this->entityTypeManager->getStorage('notification_log_agro');

        $total = (int) $storage->getQuery()->accessCheck(FALSE)->count()->execute();

        if ($total === 0) {
            return [
                'total' => 0,
                'sent' => 0,
                'failed' => 0,
                'open_rate' => 0.0,
                'click_rate' => 0.0,
                'channels' => [],
            ];
        }

        $sent = (int) $storage->getQuery()
            ->condition('status', [NotificationLogAgro::STATUS_SENT, NotificationLogAgro::STATUS_DELIVERED], 'IN')
            ->accessCheck(FALSE)->count()->execute();

        $failed = (int) $storage->getQuery()
            ->condition('status', [NotificationLogAgro::STATUS_FAILED, NotificationLogAgro::STATUS_BOUNCED], 'IN')
            ->accessCheck(FALSE)->count()->execute();

        // Cargar todos para calcular open/click (eficiente solo con volúmenes bajos).
        $allLogs = $storage->loadMultiple();
        $opened = 0;
        $clicked = 0;
        $channelCounts = [];

        foreach ($allLogs as $log) {
            /** @var \Drupal\jaraba_agroconecta_core\Entity\NotificationLogAgro $log */
            if ($log->wasOpened()) {
                $opened++;
            }
            if ($log->wasClicked()) {
                $clicked++;
            }
            $ch = $log->get('channel')->value;
            $channelCounts[$ch] = ($channelCounts[$ch] ?? 0) + 1;
        }

        return [
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
            'open_rate' => $total > 0 ? round(($opened / $total) * 100, 1) : 0.0,
            'click_rate' => $total > 0 ? round(($clicked / $total) * 100, 1) : 0.0,
            'channels' => $channelCounts,
        ];
    }

    /**
     * Busca una plantilla por tipo y canal.
     *
     * @param string $type
     *   Tipo de notificación.
     * @param string $channel
     *   Canal.
     *
     * @return \Drupal\jaraba_agroconecta_core\Entity\NotificationTemplateAgro|null
     *   Plantilla encontrada o NULL.
     */
    protected function findTemplate(string $type, string $channel): ?NotificationTemplateAgro
    {
        $storage = $this->entityTypeManager->getStorage('notification_template_agro');
        $templates = $storage->loadByProperties([
            'type' => $type,
            'channel' => $channel,
            'is_active' => TRUE,
        ]);

        if (!empty($templates)) {
            /** @var \Drupal\jaraba_agroconecta_core\Entity\NotificationTemplateAgro $template */
            $template = reset($templates);
            return $template;
        }

        return NULL;
    }

    /**
     * Renderiza tokens en un string.
     *
     * Reemplaza {{ key }} con valores del contexto (procesamiento simple).
     *
     * @param string $template
     *   String con tokens.
     * @param array $context
     *   Datos de contexto.
     *
     * @return string
     *   String con tokens reemplazados.
     */
    protected function renderTokens(string $template, array $context): string
    {
        foreach ($context as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $template = str_replace('{{ ' . $key . ' }}', (string) $value, $template);
                // Variante sin espacios.
                $template = str_replace('{{' . $key . '}}', (string) $value, $template);
            }
            // Soporte para notación con punto: {{ order.number }}.
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    if (is_string($subValue) || is_numeric($subValue)) {
                        $template = str_replace('{{ ' . $key . '.' . $subKey . ' }}', (string) $subValue, $template);
                        $template = str_replace('{{' . $key . '.' . $subKey . '}}', (string) $subValue, $template);
                    }
                }
            }
        }
        return $template;
    }

    /**
     * Despacha al canal correspondiente.
     *
     * @param string $channel
     *   Canal de envío.
     * @param int $recipientUserId
     *   ID del usuario.
     * @param string $subject
     *   Asunto renderizado.
     * @param string $body
     *   Cuerpo en texto plano.
     * @param string $bodyHtml
     *   Cuerpo en HTML.
     * @param array $context
     *   Datos de contexto adicionales.
     *
     * @return array
     *   Array con 'success' (bool), 'error' (string|null), 'external_id' (string|null).
     */
    protected function dispatchToChannel(string $channel, int $recipientUserId, string $subject, string $body, string $bodyHtml, array $context): array
    {
        try {
            switch ($channel) {
                case NotificationTemplateAgro::CHANNEL_EMAIL:
                    return $this->sendEmail($recipientUserId, $subject, $body, $bodyHtml);

                case NotificationTemplateAgro::CHANNEL_IN_APP:
                    // In-App: el propio log con status=delivered sirve como notificación visible.
                    return ['success' => TRUE, 'error' => NULL, 'external_id' => NULL];

                case NotificationTemplateAgro::CHANNEL_PUSH:
                    // Push: pendiente de integración con FCM/APNs.
                    $this->logger->info('Push notification queued: @subject', ['@subject' => $subject]);
                    return ['success' => TRUE, 'error' => NULL, 'external_id' => 'push_queued'];

                case NotificationTemplateAgro::CHANNEL_SMS:
                    // SMS: pendiente de integración con proveedor SMS.
                    $this->logger->info('SMS notification queued: @subject', ['@subject' => $subject]);
                    return ['success' => TRUE, 'error' => NULL, 'external_id' => 'sms_queued'];

                default:
                    return ['success' => FALSE, 'error' => 'Canal no soportado: ' . $channel, 'external_id' => NULL];
            }
        } catch (\Exception $e) {
            return ['success' => FALSE, 'error' => $e->getMessage(), 'external_id' => NULL];
        }
    }

    /**
     * Envía un email usando el sistema de correo de Drupal.
     *
     * @param int $recipientUserId
     *   ID del usuario destinatario.
     * @param string $subject
     *   Asunto.
     * @param string $body
     *   Cuerpo en texto plano.
     * @param string $bodyHtml
     *   Cuerpo en HTML.
     *
     * @return array
     *   Resultado del envío.
     */
    protected function sendEmail(int $recipientUserId, string $subject, string $body, string $bodyHtml): array
    {
        try {
            $userStorage = $this->entityTypeManager->getStorage('user');
            /** @var \Drupal\user\UserInterface|null $user */
            $user = $userStorage->load($recipientUserId);

            if (!$user) {
                return ['success' => FALSE, 'error' => 'Usuario no encontrado', 'external_id' => NULL];
            }

            $email = $user->getEmail();
            if (!$email) {
                return ['success' => FALSE, 'error' => 'Usuario sin email', 'external_id' => NULL];
            }

            $params = [
                'subject' => $subject,
                'body' => $bodyHtml ?: $body,
            ];

            $result = $this->mailManager->mail(
                'jaraba_agroconecta_core',
                'agro_notification',
                $email,
                $user->getPreferredLangcode(),
                $params,
                NULL,
                TRUE
            );

            return [
                'success' => !empty($result['result']),
                'error' => empty($result['result']) ? 'Error de envío de email' : NULL,
                'external_id' => $result['message_id'] ?? NULL,
            ];
        } catch (\Exception $e) {
            return ['success' => FALSE, 'error' => $e->getMessage(), 'external_id' => NULL];
        }
    }

    /**
     * Crea un registro de log de notificación.
     *
     * @param \Drupal\jaraba_agroconecta_core\Entity\NotificationTemplateAgro $template
     *   Plantilla usada.
     * @param string $channel
     *   Canal de envío.
     * @param int $recipientUserId
     *   ID del usuario destinatario.
     * @param string $subject
     *   Asunto renderizado.
     * @param string $body
     *   Cuerpo renderizado.
     * @param array $context
     *   Datos de contexto.
     * @param array $result
     *   Resultado del envío.
     *
     * @return int|null
     *   ID del log creado o NULL si falla.
     */
    protected function createLog(NotificationTemplateAgro $template, string $channel, int $recipientUserId, string $subject, string $body, array $context, array $result): ?int
    {
        try {
            $storage = $this->entityTypeManager->getStorage('notification_log_agro');

            // Obtener email del destinatario.
            $userStorage = $this->entityTypeManager->getStorage('user');
            /** @var \Drupal\user\UserInterface|null $user */
            $user = $userStorage->load($recipientUserId);

            /** @var \Drupal\jaraba_agroconecta_core\Entity\NotificationLogAgro $log */
            $log = $storage->create([
                'template_id' => (int) $template->id(),
                'type' => $template->get('type')->value,
                'channel' => $channel,
                'recipient_type' => 'user',
                'recipient_id' => $recipientUserId,
                'recipient_email' => $user ? $user->getEmail() : NULL,
                'subject' => $subject,
                'body_preview' => mb_substr($body, 0, 200),
                'context' => json_encode($context, JSON_UNESCAPED_UNICODE),
                'status' => ($result['success'] ?? FALSE) ? NotificationLogAgro::STATUS_SENT : NotificationLogAgro::STATUS_FAILED,
                'error_message' => $result['error'] ?? NULL,
                'external_id' => $result['external_id'] ?? NULL,
            ]);
            $log->save();

            return (int) $log->id();
        } catch (\Exception $e) {
            $this->logger->error('Error al crear log de notificación: @message', [
                '@message' => $e->getMessage(),
            ]);
            return NULL;
        }
    }

    /**
     * Serializa un log para respuesta API.
     *
     * @param \Drupal\jaraba_agroconecta_core\Entity\NotificationLogAgro $log
     *   El log a serializar.
     *
     * @return array
     *   Datos del log en formato array.
     */
    protected function serializeLog(NotificationLogAgro $log): array
    {
        return [
            'id' => (int) $log->id(),
            'type' => $log->get('type')->value,
            'channel' => $log->get('channel')->value,
            'recipient_id' => (int) ($log->get('recipient_id')->value ?? 0),
            'recipient_email' => $log->get('recipient_email')->value,
            'subject' => $log->get('subject')->value,
            'status' => $log->get('status')->value,
            'status_label' => $log->getStatusLabel(),
            'error' => $log->get('error_message')->value,
            'opened' => $log->wasOpened(),
            'clicked' => $log->wasClicked(),
            'created' => date('c', (int) $log->get('created')->value),
        ];
    }

}
