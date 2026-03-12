<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface;
use Psr\Log\LoggerInterface;

/**
 * Genera feeds iCalendar (RFC 5545) de sesiones programadas.
 *
 * Sprint 17 — Integración Calendarios Externos.
 *
 * Produce output text/calendar suscribible desde Google Calendar,
 * Outlook y cualquier cliente iCal estándar.
 */
class ICalExportService {

  /**
   * Nombre del producto en PRODID.
   */
  private const PRODID = '-//Jaraba Impact Platform//Andalucia EI//ES';

  /**
   * Dominio para UIDs de eventos.
   */
  private const UID_DOMAIN = 'jaraba-saas.lndo.site';

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Genera un feed iCal con sesiones futuras de un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param int $dias
   *   Días hacia adelante a incluir (default 90).
   *
   * @return string
   *   Contenido iCalendar completo (text/calendar).
   */
  public function generarFeedTenant(int $tenantId, int $dias = 90): string {
    try {
      $storage = $this->entityTypeManager->getStorage('sesion_programada_ei');
      $hoy = date('Y-m-d');
      $hasta = date('Y-m-d', strtotime("+{$dias} days"));

      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('fecha', $hoy, '>=')
        ->condition('fecha', $hasta, '<=')
        ->condition('estado', ['cancelada'], 'NOT IN')
        ->sort('fecha', 'ASC')
        ->execute();

      $sesiones = !empty($ids) ? $storage->loadMultiple($ids) : [];

      return $this->buildVCalendar($sesiones, $tenantId);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error generando feed iCal tenant @tid: @msg', [
        '@tid' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return $this->buildVCalendar([], $tenantId);
    }
  }

  /**
   * Genera un feed iCal para un orientador específico.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param int $facilitadorUid
   *   UID del orientador/facilitador.
   * @param int $dias
   *   Días hacia adelante.
   *
   * @return string
   *   Contenido iCalendar.
   */
  public function generarFeedOrientador(int $tenantId, int $facilitadorUid, int $dias = 90): string {
    try {
      $storage = $this->entityTypeManager->getStorage('sesion_programada_ei');
      $hoy = date('Y-m-d');
      $hasta = date('Y-m-d', strtotime("+{$dias} days"));

      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('facilitador_id', $facilitadorUid)
        ->condition('fecha', $hoy, '>=')
        ->condition('fecha', $hasta, '<=')
        ->condition('estado', ['cancelada'], 'NOT IN')
        ->sort('fecha', 'ASC')
        ->execute();

      $sesiones = !empty($ids) ? $storage->loadMultiple($ids) : [];

      return $this->buildVCalendar($sesiones, $tenantId);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error generando feed iCal orientador @uid tenant @tid: @msg', [
        '@uid' => $facilitadorUid,
        '@tid' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return $this->buildVCalendar([], $tenantId);
    }
  }

  /**
   * Construye el VCALENDAR completo con VEVENTs.
   *
   * @param \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface[] $sesiones
   *   Sesiones a incluir.
   * @param int $tenantId
   *   ID del tenant para metadata.
   *
   * @return string
   *   Contenido iCalendar RFC 5545.
   */
  protected function buildVCalendar(array $sesiones, int $tenantId): string {
    $lines = [
      'BEGIN:VCALENDAR',
      'VERSION:2.0',
      'PRODID:' . self::PRODID,
      'CALSCALE:GREGORIAN',
      'METHOD:PUBLISH',
      'X-WR-CALNAME:Andalucía +ei - Sesiones (Tenant ' . $tenantId . ')',
      'X-WR-TIMEZONE:Europe/Madrid',
    ];

    // Timezone definition for Europe/Madrid.
    $lines[] = 'BEGIN:VTIMEZONE';
    $lines[] = 'TZID:Europe/Madrid';
    $lines[] = 'BEGIN:STANDARD';
    $lines[] = 'DTSTART:19701025T030000';
    $lines[] = 'RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10';
    $lines[] = 'TZOFFSETFROM:+0200';
    $lines[] = 'TZOFFSETTO:+0100';
    $lines[] = 'TZNAME:CET';
    $lines[] = 'END:STANDARD';
    $lines[] = 'BEGIN:DAYLIGHT';
    $lines[] = 'DTSTART:19700329T020000';
    $lines[] = 'RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=3';
    $lines[] = 'TZOFFSETFROM:+0100';
    $lines[] = 'TZOFFSETTO:+0200';
    $lines[] = 'TZNAME:CEST';
    $lines[] = 'END:DAYLIGHT';
    $lines[] = 'END:VTIMEZONE';

    foreach ($sesiones as $sesion) {
      $vevent = $this->buildVEvent($sesion);
      if ($vevent !== NULL) {
        $lines = array_merge($lines, $vevent);
      }
    }

    $lines[] = 'END:VCALENDAR';

    return implode("\r\n", $lines) . "\r\n";
  }

  /**
   * Construye un VEVENT para una sesión.
   *
   * @param \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface $sesion
   *   La sesión.
   *
   * @return string[]|null
   *   Líneas del VEVENT o NULL si datos insuficientes.
   */
  protected function buildVEvent(SesionProgramadaEiInterface $sesion): ?array {
    $fecha = $sesion->getFecha();
    $horaInicio = $sesion->getHoraInicio();
    $horaFin = $sesion->getHoraFin();

    if (empty($fecha) || empty($horaInicio)) {
      return NULL;
    }

    // Formato: YYYYMMDDTHHMMSS (local time with TZID).
    $dtStart = $this->formatDateTime($fecha, $horaInicio);
    $dtEnd = !empty($horaFin) ? $this->formatDateTime($fecha, $horaFin) : NULL;

    $uid = 'sesion-' . $sesion->id() . '@' . self::UID_DOMAIN;
    $titulo = $this->escapeIcalText($sesion->getTitulo());
    $estado = $sesion->getEstado();

    // Build description.
    $descParts = [];
    $tipoLabel = SesionProgramadaEiInterface::TIPOS_SESION[$sesion->getTipoSesion()] ?? $sesion->getTipoSesion();
    $descParts[] = 'Tipo: ' . $tipoLabel;
    $descParts[] = 'Modalidad: ' . (SesionProgramadaEiInterface::MODALIDADES[$sesion->getModalidad()] ?? $sesion->getModalidad());
    $descParts[] = 'Fase: ' . (SesionProgramadaEiInterface::FASES_PROGRAMA[$sesion->getFasePrograma()] ?? $sesion->getFasePrograma());
    $descParts[] = 'Plazas: ' . $sesion->getPlazasOcupadas() . '/' . $sesion->getMaxPlazas();
    if ($estado === 'aplazada') {
      $descParts[] = '⚠ SESIÓN APLAZADA';
    }
    $description = $this->escapeIcalText(implode('\n', $descParts));

    // Location.
    $location = '';
    if ($sesion->hasField('lugar_descripcion') && !$sesion->get('lugar_descripcion')->isEmpty()) {
      $location = $this->escapeIcalText($sesion->get('lugar_descripcion')->value ?? '');
    }
    if ($sesion->getModalidad() === 'online' && $sesion->hasField('lugar_url') && !$sesion->get('lugar_url')->isEmpty()) {
      $location = $this->escapeIcalText($sesion->get('lugar_url')->value ?? '');
    }

    $lines = [
      'BEGIN:VEVENT',
      'UID:' . $uid,
      'DTSTAMP:' . gmdate('Ymd\THis\Z'),
      'DTSTART;TZID=Europe/Madrid:' . $dtStart,
    ];

    if ($dtEnd !== NULL) {
      $lines[] = 'DTEND;TZID=Europe/Madrid:' . $dtEnd;
    }

    $lines[] = 'SUMMARY:' . $titulo;
    $lines[] = 'DESCRIPTION:' . $description;

    if (!empty($location)) {
      $lines[] = 'LOCATION:' . $location;
    }

    // Status mapping.
    $statusMap = [
      'programada' => 'TENTATIVE',
      'confirmada' => 'CONFIRMED',
      'en_curso' => 'CONFIRMED',
      'completada' => 'CONFIRMED',
      'aplazada' => 'TENTATIVE',
    ];
    $lines[] = 'STATUS:' . ($statusMap[$estado] ?? 'TENTATIVE');

    // Facilitador as organizer (name only, no email for privacy).
    if ($sesion->hasField('facilitador_nombre') && !$sesion->get('facilitador_nombre')->isEmpty()) {
      $facilitador = $sesion->get('facilitador_nombre')->value ?? '';
      if ($facilitador !== '') {
        $lines[] = 'ORGANIZER;CN=' . $this->escapeIcalText($facilitador) . ':MAILTO:noreply@' . self::UID_DOMAIN;
      }
    }

    $lines[] = 'END:VEVENT';

    return $lines;
  }

  /**
   * Formatea fecha y hora al formato iCal local.
   *
   * @param string $fecha
   *   Fecha Y-m-d.
   * @param string $hora
   *   Hora HH:MM.
   *
   * @return string
   *   YYYYMMDDTHHMMSS.
   */
  protected function formatDateTime(string $fecha, string $hora): string {
    $fecha = str_replace('-', '', $fecha);
    $hora = str_replace(':', '', $hora);
    if (strlen($hora) === 4) {
      $hora .= '00';
    }
    return $fecha . 'T' . $hora;
  }

  /**
   * Escapa texto para iCalendar (RFC 5545 §3.3.11).
   *
   * @param string $text
   *   Texto a escapar.
   *
   * @return string
   *   Texto escapado.
   */
  protected function escapeIcalText(string $text): string {
    $text = str_replace('\\', '\\\\', $text);
    $text = str_replace(';', '\;', $text);
    $text = str_replace(',', '\,', $text);
    return $text;
  }

}
