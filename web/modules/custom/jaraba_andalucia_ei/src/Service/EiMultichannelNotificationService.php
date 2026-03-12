<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Orquestador multicanal de notificaciones para Andalucía +ei.
 *
 * Sprint 17 — WhatsApp/SMS Integration.
 *
 * Enruta notificaciones a los canales apropiados según tipo,
 * prioridad y preferencias del participante. Canales:
 * - Push/email: via EiPushNotificationService (siempre)
 * - WhatsApp: via WhatsAppApiService (si disponible y consentido)
 * - In-app: via jaraba_notifications (siempre)
 *
 * GDPR: WhatsApp requiere consentimiento explícito del participante.
 * El campo 'acepta_whatsapp' en ProgramaParticipanteEi controla esto.
 */
class EiMultichannelNotificationService {

  /**
   * Tipos de notificación y sus canales por defecto.
   *
   * 'push' = push notification + email, 'wa' = WhatsApp, 'app' = in-app.
   */
  private const CHANNEL_ROUTING = [
    'sesion_recordatorio_24h' => ['push', 'wa'],
    'sesion_recordatorio_1h' => ['push', 'wa'],
    'sesion_cancelada' => ['push', 'wa', 'app'],
    'sesion_aplazada' => ['push', 'wa', 'app'],
    'cambio_fase' => ['push', 'app'],
    'documento_pendiente' => ['push', 'app'],
    'firma_pendiente' => ['push', 'wa'],
    'badge_obtenido' => ['push', 'app'],
    'insercion_confirmada' => ['push', 'wa', 'app'],
    'derivacion_urgente' => ['push', 'wa'],
  ];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ?EiPushNotificationService $pushService = NULL,
    protected ?object $whatsappService = NULL,
    protected ?object $notificationService = NULL,
  ) {}

  /**
   * Envía notificación multicanal a un participante.
   *
   * @param int $participanteId
   *   ID del ProgramaParticipanteEi.
   * @param string $tipo
   *   Tipo de notificación (key de CHANNEL_ROUTING).
   * @param array $data
   *   Datos del evento: titulo, mensaje, link, etc.
   *
   * @return array
   *   Resultado por canal: ['push' => bool, 'wa' => bool, 'app' => bool].
   */
  public function notificar(int $participanteId, string $tipo, array $data): array {
    $resultados = ['push' => FALSE, 'wa' => FALSE, 'app' => FALSE];

    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if (!$participante) {
        $this->logger->warning('Participante @id no encontrado para notificación @tipo.', [
          '@id' => $participanteId,
          '@tipo' => $tipo,
        ]);
        return $resultados;
      }

      $uid = (int) $participante->getOwnerId();
      $canales = self::CHANNEL_ROUTING[$tipo] ?? ['push', 'app'];

      // Push / email.
      if (in_array('push', $canales, TRUE) && $this->pushService) {
        try {
          $this->pushService->notificar($uid, $tipo, $data);
          $resultados['push'] = TRUE;
        }
        catch (\Throwable $e) {
          $this->logger->warning('Error push @tipo participante @id: @msg', [
            '@tipo' => $tipo,
            '@id' => $participanteId,
            '@msg' => $e->getMessage(),
          ]);
        }
      }

      // WhatsApp (requiere consentimiento + teléfono).
      if (in_array('wa', $canales, TRUE) && $this->whatsappService) {
        $resultados['wa'] = $this->enviarWhatsApp($participante, $tipo, $data);
      }

      // In-app notification.
      if (in_array('app', $canales, TRUE) && $this->notificationService) {
        try {
          $tenantId = (int) ($participante->get('tenant_id')->target_id ?? 0);
          $titulo = $data['titulo'] ?? $this->getTituloDefault($tipo);
          $mensaje = $data['mensaje'] ?? '';
          $link = $data['link'] ?? '';

          $this->notificationService->send($uid, $tenantId, $tipo, $titulo, $mensaje, $link);
          $resultados['app'] = TRUE;
        }
        catch (\Throwable $e) {
          $this->logger->warning('Error in-app @tipo participante @id: @msg', [
            '@tipo' => $tipo,
            '@id' => $participanteId,
            '@msg' => $e->getMessage(),
          ]);
        }
      }

      $this->logger->info('Notificación @tipo a participante @id: push=@push, wa=@wa, app=@app', [
        '@tipo' => $tipo,
        '@id' => $participanteId,
        '@push' => $resultados['push'] ? 'OK' : 'NO',
        '@wa' => $resultados['wa'] ? 'OK' : 'NO',
        '@app' => $resultados['app'] ? 'OK' : 'NO',
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error en notificación multicanal @tipo participante @id: @msg', [
        '@tipo' => $tipo,
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
    }

    return $resultados;
  }

  /**
   * Envía notificación masiva a múltiples participantes.
   *
   * @param array $participanteIds
   *   IDs de participantes.
   * @param string $tipo
   *   Tipo de notificación.
   * @param array $data
   *   Datos del evento.
   *
   * @return array
   *   Resumen: ['total' => N, 'push' => N, 'wa' => N, 'app' => N].
   */
  public function notificarMasivo(array $participanteIds, string $tipo, array $data): array {
    $resumen = ['total' => count($participanteIds), 'push' => 0, 'wa' => 0, 'app' => 0];

    foreach ($participanteIds as $id) {
      $resultado = $this->notificar((int) $id, $tipo, $data);
      if ($resultado['push']) {
        $resumen['push']++;
      }
      if ($resultado['wa']) {
        $resumen['wa']++;
      }
      if ($resultado['app']) {
        $resumen['app']++;
      }
    }

    $this->logger->info('Notificación masiva @tipo: @total destinos, push=@push, wa=@wa, app=@app', [
      '@tipo' => $tipo,
      '@total' => $resumen['total'],
      '@push' => $resumen['push'],
      '@wa' => $resumen['wa'],
      '@app' => $resumen['app'],
    ]);

    return $resumen;
  }

  /**
   * Envía recordatorios de sesión a todos los inscritos.
   *
   * Diseñado para ser invocado por cron o un comando Drush.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param int $horasAntes
   *   Horas antes de la sesión (24 o 1).
   *
   * @return int
   *   Número de notificaciones enviadas.
   */
  public function enviarRecordatoriosSesion(int $tenantId, int $horasAntes = 24): int {
    try {
      $storage = $this->entityTypeManager->getStorage('sesion_programada_ei');

      // Buscar sesiones que empiecen en la ventana.
      $ahora = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Madrid'));
      $ventanaInicio = $ahora->modify("+{$horasAntes} hours")->modify('-30 minutes');
      $ventanaFin = $ahora->modify("+{$horasAntes} hours")->modify('+30 minutes');

      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('fecha', $ventanaInicio->format('Y-m-d'), '>=')
        ->condition('fecha', $ventanaFin->format('Y-m-d'), '<=')
        ->condition('estado', ['programada', 'confirmada'], 'IN')
        ->execute();

      if (empty($ids)) {
        return 0;
      }

      $sesiones = $storage->loadMultiple($ids);
      $enviados = 0;

      foreach ($sesiones as $sesion) {
        // Obtener inscritos.
        $inscripcionIds = $this->entityTypeManager
          ->getStorage('inscripcion_sesion_ei')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('sesion_programada_id', $sesion->id())
          ->condition('estado', 'confirmada')
          ->execute();

        if (empty($inscripcionIds)) {
          continue;
        }

        $inscripciones = $this->entityTypeManager
          ->getStorage('inscripcion_sesion_ei')
          ->loadMultiple($inscripcionIds);

        $participanteIds = [];
        foreach ($inscripciones as $inscripcion) {
          $pId = $inscripcion->get('participante_id')->target_id ?? NULL;
          if ($pId !== NULL) {
            $participanteIds[] = (int) $pId;
          }
        }

        if (!empty($participanteIds)) {
          $tipo = $horasAntes >= 12 ? 'sesion_recordatorio_24h' : 'sesion_recordatorio_1h';
          $data = [
            'titulo' => 'Recordatorio: ' . $sesion->getTitulo(),
            'mensaje' => sprintf(
              '%s — %s, %s a %s (%s)',
              $sesion->getTitulo(),
              $sesion->getFecha(),
              $sesion->getHoraInicio(),
              $sesion->getHoraFin(),
              SesionProgramadaEiInterface::MODALIDADES[$sesion->getModalidad()] ?? $sesion->getModalidad()
            ),
            'sesion_id' => $sesion->id(),
          ];

          $this->notificarMasivo($participanteIds, $tipo, $data);
          $enviados += count($participanteIds);
        }
      }

      return $enviados;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error enviando recordatorios sesión tenant @tid: @msg', [
        '@tid' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return 0;
    }
  }

  /**
   * Envía WhatsApp si el participante tiene consentimiento y teléfono.
   *
   * @param object $participante
   *   Entidad ProgramaParticipanteEi.
   * @param string $tipo
   *   Tipo de notificación.
   * @param array $data
   *   Datos del evento.
   *
   * @return bool
   *   TRUE si se envió.
   */
  protected function enviarWhatsApp(object $participante, string $tipo, array $data): bool {
    // GDPR: verificar consentimiento explícito.
    if (!$participante->hasField('acepta_whatsapp') || !$participante->get('acepta_whatsapp')->value) {
      return FALSE;
    }

    // Obtener teléfono del usuario propietario.
    $telefono = '';
    if ($participante->hasField('telefono') && !$participante->get('telefono')->isEmpty()) {
      $telefono = $participante->get('telefono')->value ?? '';
    }

    if (empty($telefono)) {
      return FALSE;
    }

    try {
      $mensaje = $data['titulo'] ?? $this->getTituloDefault($tipo);
      if (!empty($data['mensaje'])) {
        $mensaje .= "\n" . $data['mensaje'];
      }

      $result = $this->whatsappService->sendTextMessage($telefono, $mensaje);
      return $result['success'] ?? FALSE;
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error WhatsApp @tipo a @phone: @msg', [
        '@tipo' => $tipo,
        '@phone' => $telefono,
        '@msg' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Devuelve un título por defecto según el tipo de notificación.
   */
  protected function getTituloDefault(string $tipo): string {
    $titulos = [
      'sesion_recordatorio_24h' => 'Recordatorio de sesión (mañana)',
      'sesion_recordatorio_1h' => 'Tu sesión empieza en 1 hora',
      'sesion_cancelada' => 'Sesión cancelada',
      'sesion_aplazada' => 'Sesión aplazada',
      'cambio_fase' => 'Has avanzado de fase',
      'documento_pendiente' => 'Documentación pendiente',
      'firma_pendiente' => 'Firma pendiente',
      'badge_obtenido' => '¡Nuevo logro desbloqueado!',
      'insercion_confirmada' => '¡Inserción laboral registrada!',
      'derivacion_urgente' => 'Derivación urgente',
    ];
    return $titulos[$tipo] ?? 'Notificación Andalucía +ei';
  }

}
