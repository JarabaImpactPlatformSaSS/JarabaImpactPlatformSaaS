<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de notificaciones push para firma y programa +ei.
 *
 * Sprint 12 — Plan Maestro Andalucía +ei Clase Mundial.
 *
 * Enruta notificaciones a push y/o email según prioridad del evento.
 * Soporta notificación individual y masiva con 8 tipos de evento.
 */
class EiPushNotificationService {

  /**
   * Tipos de evento con prioridad y canal de entrega.
   *
   * Prioridades: critica > alta > media > baja.
   * Canales: push (notificación push), email, ambos.
   */
  public const EVENT_TYPES = [
    'firma_pendiente' => [
      'label' => 'Firma pendiente',
      'prioridad' => 'alta',
      'canales' => ['push', 'email'],
      'icono' => 'firma',
      'titulo_template' => 'Tienes un documento pendiente de firma',
    ],
    'firma_completada' => [
      'label' => 'Firma completada',
      'prioridad' => 'media',
      'canales' => ['push'],
      'icono' => 'check',
      'titulo_template' => 'Documento firmado correctamente',
    ],
    'cambio_fase' => [
      'label' => 'Cambio de fase',
      'prioridad' => 'media',
      'canales' => ['push', 'email'],
      'icono' => 'fase',
      'titulo_template' => 'Has avanzado a una nueva fase del programa',
    ],
    'sesion_mentoring' => [
      'label' => 'Sesión de mentoría',
      'prioridad' => 'alta',
      'canales' => ['push', 'email'],
      'icono' => 'calendario',
      'titulo_template' => 'Tienes una sesión de mentoría programada',
    ],
    'badge_obtenido' => [
      'label' => 'Badge obtenido',
      'prioridad' => 'baja',
      'canales' => ['push'],
      'icono' => 'estrella',
      'titulo_template' => '¡Has obtenido un nuevo badge!',
    ],
    'pill_dia' => [
      'label' => 'Píldora del día',
      'prioridad' => 'baja',
      'canales' => ['push'],
      'icono' => 'bombilla',
      'titulo_template' => 'Tu píldora formativa del día',
    ],
    'match_empresa' => [
      'label' => 'Match con empresa',
      'prioridad' => 'alta',
      'canales' => ['push', 'email'],
      'icono' => 'enlace',
      'titulo_template' => 'Nueva oportunidad laboral compatible contigo',
    ],
    'derivacion_urgente' => [
      'label' => 'Derivación urgente',
      'prioridad' => 'critica',
      'canales' => ['push', 'email'],
      'icono' => 'alerta',
      'titulo_template' => 'Derivación urgente: acción requerida',
    ],
  ];

  /**
   * Orden de prioridad para comparaciones.
   */
  protected const PRIORIDAD_ORDEN = [
    'critica' => 4,
    'alta' => 3,
    'media' => 2,
    'baja' => 1,
  ];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ?object $pushService = NULL,
    protected ?object $notificationService = NULL,
  ) {}

  /**
   * Envía notificación a un usuario según tipo de evento y prioridad.
   *
   * Enruta automáticamente a push, email o ambos según la configuración
   * del tipo de evento. Los eventos críticos siempre usan ambos canales.
   *
   * @param int $uid
   *   ID del usuario destinatario.
   * @param string $tipo
   *   Tipo de evento (clave de EVENT_TYPES).
   * @param array $data
   *   Datos contextuales: titulo, mensaje, url, entity_type, entity_id.
   */
  public function notificar(int $uid, string $tipo, array $data = []): void {
    try {
      $eventConfig = self::EVENT_TYPES[$tipo] ?? NULL;
      if (!$eventConfig) {
        $this->logger->warning('Tipo de notificación desconocido: @tipo.', [
          '@tipo' => $tipo,
        ]);
        return;
      }

      // Construir payload de notificación.
      $payload = $this->construirPayload($tipo, $eventConfig, $data);

      // Guardar en sistema de notificaciones internas.
      $this->guardarNotificacionInterna($uid, $tipo, $payload);

      $canales = $eventConfig['canales'];

      // Eventos críticos siempre usan ambos canales.
      if ($eventConfig['prioridad'] === 'critica') {
        $canales = ['push', 'email'];
      }

      // Enviar por push.
      if (in_array('push', $canales, TRUE) && $this->pushService) {
        try {
          $this->pushService->sendToUser($uid, [
            'title' => $payload['titulo'],
            'body' => $payload['mensaje'],
            'icon' => $eventConfig['icono'],
            'url' => $payload['url'] ?? '',
            'priority' => $eventConfig['prioridad'],
          ]);
        }
        catch (\Throwable $e) {
          $this->logger->warning('Error enviando push a uid @uid tipo @tipo: @msg', [
            '@uid' => $uid,
            '@tipo' => $tipo,
            '@msg' => $e->getMessage(),
          ]);
        }
      }

      // Enviar por email para prioridad alta y crítica, o si está en canales.
      if (in_array('email', $canales, TRUE) && $this->notificationService) {
        try {
          $this->notificationService->sendEmail($uid, [
            'subject' => $payload['titulo'],
            'body' => $payload['mensaje'],
            'tipo' => $tipo,
            'prioridad' => $eventConfig['prioridad'],
          ]);
        }
        catch (\Throwable $e) {
          $this->logger->warning('Error enviando email a uid @uid tipo @tipo: @msg', [
            '@uid' => $uid,
            '@tipo' => $tipo,
            '@msg' => $e->getMessage(),
          ]);
        }
      }

      $this->logger->info('Notificación @tipo enviada a uid @uid (prioridad: @prio).', [
        '@tipo' => $tipo,
        '@uid' => $uid,
        '@prio' => $eventConfig['prioridad'],
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error enviando notificación @tipo a uid @uid: @msg', [
        '@tipo' => $tipo,
        '@uid' => $uid,
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Envía notificación masiva a múltiples usuarios.
   *
   * @param array $uids
   *   Lista de IDs de usuario.
   * @param string $tipo
   *   Tipo de evento.
   * @param array $data
   *   Datos contextuales comunes.
   *
   * @return int
   *   Número de notificaciones enviadas con éxito.
   */
  public function notificarMasivo(array $uids, string $tipo, array $data = []): int {
    $exitosos = 0;

    foreach ($uids as $uid) {
      if (!is_int($uid) && !is_numeric($uid)) {
        continue;
      }

      try {
        $this->notificar((int) $uid, $tipo, $data);
        $exitosos++;
      }
      catch (\Throwable $e) {
        $this->logger->warning('Error en notificación masiva @tipo para uid @uid: @msg', [
          '@tipo' => $tipo,
          '@uid' => $uid,
          '@msg' => $e->getMessage(),
        ]);
      }
    }

    $this->logger->info('Notificación masiva @tipo: @ok/@total enviadas.', [
      '@tipo' => $tipo,
      '@ok' => $exitosos,
      '@total' => count($uids),
    ]);

    return $exitosos;
  }

  /**
   * Obtiene notificaciones no leídas de un usuario.
   *
   * @param int $uid
   *   ID del usuario.
   *
   * @return array
   *   Lista de notificaciones con tipo, titulo, mensaje, fecha, leida.
   */
  public function getPendientesNoLeidas(int $uid): array {
    try {
      // Intentar desde el servicio de notificaciones si disponible.
      if ($this->notificationService && method_exists($this->notificationService, 'getUnreadForUser')) {
        try {
          return $this->notificationService->getUnreadForUser($uid);
        }
        catch (\Throwable $e) {
          $this->logger->warning('Error obteniendo no leídas desde servicio para uid @uid: @msg', [
            '@uid' => $uid,
            '@msg' => $e->getMessage(),
          ]);
        }
      }

      // Fallback: buscar en entidad de notificaciones internas.
      $storage = $this->entityTypeManager->getStorage('notificacion_ei');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', $uid)
        ->condition('leida', FALSE)
        ->sort('created', 'DESC')
        ->range(0, 50)
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $notificaciones = $storage->loadMultiple($ids);
      $resultado = [];

      foreach ($notificaciones as $notificacion) {
        $resultado[] = [
          'id' => (int) $notificacion->id(),
          'tipo' => $notificacion->hasField('tipo')
            ? ($notificacion->get('tipo')->value ?? '')
            : '',
          'titulo' => $notificacion->hasField('titulo')
            ? ($notificacion->get('titulo')->value ?? '')
            : '',
          'mensaje' => $notificacion->hasField('mensaje')
            ? ($notificacion->get('mensaje')->value ?? '')
            : '',
          'url' => $notificacion->hasField('url')
            ? ($notificacion->get('url')->value ?? '')
            : '',
          'prioridad' => $notificacion->hasField('prioridad')
            ? ($notificacion->get('prioridad')->value ?? 'baja')
            : 'baja',
          'created' => $notificacion->hasField('created')
            ? ($notificacion->get('created')->value ?? 0)
            : 0,
          'leida' => FALSE,
        ];
      }

      return $resultado;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error obteniendo notificaciones no leídas uid @uid: @msg', [
        '@uid' => $uid,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Construye el payload de notificación desde tipo y datos.
   *
   * @param string $tipo
   *   Tipo de evento.
   * @param array $eventConfig
   *   Configuración del evento de EVENT_TYPES.
   * @param array $data
   *   Datos contextuales proporcionados por el llamante.
   *
   * @return array
   *   Payload con titulo, mensaje, url, prioridad.
   */
  protected function construirPayload(string $tipo, array $eventConfig, array $data): array {
    return [
      'tipo' => $tipo,
      'titulo' => $data['titulo'] ?? $eventConfig['titulo_template'],
      'mensaje' => $data['mensaje'] ?? $eventConfig['label'],
      'url' => $data['url'] ?? '',
      'prioridad' => $eventConfig['prioridad'],
      'icono' => $eventConfig['icono'],
      'entity_type' => $data['entity_type'] ?? '',
      'entity_id' => $data['entity_id'] ?? 0,
    ];
  }

  /**
   * Guarda notificación en almacenamiento interno.
   *
   * @param int $uid
   *   ID del usuario.
   * @param string $tipo
   *   Tipo de evento.
   * @param array $payload
   *   Datos de la notificación.
   */
  protected function guardarNotificacionInterna(int $uid, string $tipo, array $payload): void {
    try {
      $storage = $this->entityTypeManager->getStorage('notificacion_ei');
      $notificacion = $storage->create([
        'uid' => $uid,
        'tipo' => $tipo,
        'titulo' => $payload['titulo'] ?? '',
        'mensaje' => $payload['mensaje'] ?? '',
        'url' => $payload['url'] ?? '',
        'prioridad' => $payload['prioridad'] ?? 'baja',
        'leida' => FALSE,
        'entity_type_ref' => $payload['entity_type'] ?? '',
        'entity_id_ref' => $payload['entity_id'] ?? 0,
      ]);
      $notificacion->save();
    }
    catch (\Throwable $e) {
      // No bloquear la notificación externa si falla el almacenamiento interno.
      $this->logger->warning('Error guardando notificación interna para uid @uid: @msg', [
        '@uid' => $uid,
        '@msg' => $e->getMessage(),
      ]);
    }
  }

}
