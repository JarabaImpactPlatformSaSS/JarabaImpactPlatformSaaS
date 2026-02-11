<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Servicio de mensajería in-app adaptativa.
 *
 * PROPÓSITO:
 * Muestra mensajes contextuales y personalizados a los usuarios
 * basándose en su comportamiento, progreso y segmento.
 *
 * Q2 2026 - Sprint 5-6: Predictive Onboarding
 */
class InAppMessagingService
{

    /**
     * Tipos de mensaje.
     */
    public const TYPE_TOOLTIP = 'tooltip';
    public const TYPE_MODAL = 'modal';
    public const TYPE_BANNER = 'banner';
    public const TYPE_SLIDEOUT = 'slideout';
    public const TYPE_TOAST = 'toast';

    /**
     * Prioridades.
     */
    public const PRIORITY_LOW = 1;
    public const PRIORITY_MEDIUM = 5;
    public const PRIORITY_HIGH = 10;
    public const PRIORITY_URGENT = 15;

    /**
     * Constructor.
     */
    public function __construct(
        protected Connection $database,
        protected AccountProxyInterface $currentUser,
    ) {
    }

    /**
     * Obtiene mensajes pendientes para el usuario actual.
     */
    public function getPendingMessages(?string $context = NULL): array
    {
        $userId = $this->currentUser->id();

        $query = $this->database->select('in_app_messages', 'iam')
            ->fields('iam')
            ->condition('iam.status', 'active')
            ->condition('iam.start_date', time(), '<=')
            ->condition(
                $this->database->condition('OR')
                    ->isNull('iam.end_date')
                    ->condition('iam.end_date', time(), '>')
            )
            ->orderBy('iam.priority', 'DESC')
            ->orderBy('iam.created', 'DESC');

        if ($context) {
            $query->condition('iam.context', $context);
        }

        // Excluir mensajes ya vistos/cerrados.
        $query->leftJoin(
            'in_app_message_interactions',
            'iami',
            'iam.id = iami.message_id AND iami.user_id = :uid',
            [':uid' => $userId]
        );
        $query->isNull('iami.id');

        $results = $query->execute()->fetchAll();

        return array_map(function ($row) {
            return [
                'id' => $row->id,
                'type' => $row->type,
                'title' => $row->title,
                'content' => $row->content,
                'cta_text' => $row->cta_text,
                'cta_url' => $row->cta_url,
                'priority' => (int) $row->priority,
                'style' => json_decode($row->style ?? '{}', TRUE),
            ];
        }, $results);
    }

    /**
     * Registra interacción con un mensaje.
     */
    public function trackInteraction(string $messageId, string $action): void
    {
        $userId = $this->currentUser->id();

        $this->database->insert('in_app_message_interactions')
            ->fields([
                    'message_id' => $messageId,
                    'user_id' => $userId,
                    'action' => $action, // viewed, clicked, dismissed.
                    'created' => time(),
                ])
            ->execute();
    }

    /**
     * Crea un mensaje in-app.
     */
    public function createMessage(array $data): string
    {
        $messageId = \Drupal\Component\Utility\Crypt::randomBytesBase64(12);

        $this->database->insert('in_app_messages')
            ->fields([
                    'id' => $messageId,
                    'type' => $data['type'] ?? self::TYPE_TOAST,
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'cta_text' => $data['cta_text'] ?? NULL,
                    'cta_url' => $data['cta_url'] ?? NULL,
                    'context' => $data['context'] ?? 'global',
                    'priority' => $data['priority'] ?? self::PRIORITY_MEDIUM,
                    'target_segment' => $data['segment'] ?? NULL,
                    'start_date' => $data['start_date'] ?? time(),
                    'end_date' => $data['end_date'] ?? NULL,
                    'style' => json_encode($data['style'] ?? []),
                    'status' => 'active',
                    'created' => time(),
                ])
            ->execute();

        return $messageId;
    }

    /**
     * Genera mensajes basados en el contexto del usuario.
     */
    public function generateContextualMessages(array $userContext): array
    {
        $messages = [];

        // Mensaje de bienvenida.
        if (($userContext['visit_count'] ?? 0) <= 1) {
            $messages[] = [
                'type' => self::TYPE_SLIDEOUT,
                'title' => '¡Bienvenido!',
                'content' => 'Estamos encantados de tenerte aquí. ¿Necesitas ayuda?',
                'cta_text' => 'Ver Tour',
                'cta_url' => '#tour',
                'priority' => self::PRIORITY_MEDIUM,
            ];
        }

        // Usuario inactivo (no ha completado onboarding).
        if (($userContext['onboarding_step'] ?? 0) < 3 && ($userContext['days_since_signup'] ?? 0) > 2) {
            $messages[] = [
                'type' => self::TYPE_BANNER,
                'title' => '¡No te rindas!',
                'content' => 'Completa tu configuración para empezar a vender.',
                'cta_text' => 'Continuar',
                'cta_url' => '/onboarding/resume',
                'priority' => self::PRIORITY_HIGH,
            ];
        }

        // Trial expirando.
        if (($userContext['trial_days_remaining'] ?? 99) <= 3) {
            $messages[] = [
                'type' => self::TYPE_MODAL,
                'title' => '⏰ Tu prueba expira pronto',
                'content' => 'Tienes ' . $userContext['trial_days_remaining'] . ' días restantes. ¡Elige un plan!',
                'cta_text' => 'Ver Planes',
                'cta_url' => '/planes',
                'priority' => self::PRIORITY_URGENT,
            ];
        }

        // Éxito reciente.
        if ($userContext['recent_sale'] ?? FALSE) {
            $messages[] = [
                'type' => self::TYPE_TOAST,
                'title' => '🎉 ¡Nueva venta!',
                'content' => 'Acabas de realizar una venta. ¡Sigue así!',
                'priority' => self::PRIORITY_LOW,
            ];
        }

        return $messages;
    }

    /**
     * Renderiza un mensaje para frontend.
     */
    public function renderMessage(array $message): array
    {
        $baseStyles = [
            self::TYPE_TOAST => [
                'position' => 'bottom-right',
                'duration' => 5000,
                'dismissible' => TRUE,
            ],
            self::TYPE_MODAL => [
                'overlay' => TRUE,
                'closable' => TRUE,
                'size' => 'medium',
            ],
            self::TYPE_BANNER => [
                'position' => 'top',
                'sticky' => TRUE,
                'dismissible' => TRUE,
            ],
            self::TYPE_SLIDEOUT => [
                'position' => 'bottom-right',
                'width' => '360px',
            ],
        ];

        return [
            'message' => $message,
            'render' => [
                'styles' => array_merge(
                    $baseStyles[$message['type']] ?? [],
                    $message['style'] ?? []
                ),
            ],
        ];
    }

}
