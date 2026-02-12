<?php

declare(strict_types=1);

namespace Drupal\jaraba_events\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de generación y verificación de certificados de eventos.
 *
 * ESTRUCTURA:
 * Servicio encargado de generar certificados de asistencia para los
 * registros confirmados de eventos de marketing. Gestiona la creación
 * de PDFs de certificado, la recopilación de datos necesarios y la
 * verificación de autenticidad mediante código de ticket.
 *
 * LÓGICA:
 * Un certificado se genera para un registro (event_registration) que
 * tenga estado 'attended'. El certificado incluye datos del asistente,
 * del evento (título, fecha, duración) y un código de verificación
 * único (ticket_code). La verificación permite validar externamente
 * que un certificado es auténtico.
 *
 * RELACIONES:
 * - EventCertificateService -> EntityTypeManager (dependencia)
 * - EventCertificateService -> LoggerInterface (dependencia)
 * - EventCertificateService <- EventApiController (consumido por)
 *
 * @package Drupal\jaraba_events\Service
 */
class EventCertificateService {

  /**
   * Gestor de tipos de entidad de Drupal.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Canal de log dedicado para el módulo de eventos.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructor del servicio de certificados de eventos.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Gestor de tipos de entidad para acceso a storage de entidades.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log dedicado para trazar operaciones del módulo.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * Genera un certificado de asistencia para un registro.
   *
   * FLUJO DE EJECUCIÓN:
   * 1. Carga el registro y valida que existe
   * 2. Verifica que el registro tiene estado 'attended'
   * 3. Obtiene los datos del certificado (asistente + evento)
   * 4. Genera la URL del certificado y los datos para el PDF
   * 5. Registra la operación en el log
   *
   * REGLAS DE NEGOCIO:
   * - Solo se generan certificados para registros con estado 'attended'
   * - El certificado incluye el ticket_code como código de verificación
   * - La URL del certificado sigue el patrón /certificados/{ticket_code}
   *
   * @param int $registrationId
   *   ID del registro de evento para el que se genera el certificado.
   *
   * @return array
   *   Array con las claves:
   *   - 'success' (bool): TRUE si el certificado se generó correctamente.
   *   - 'certificate_url' (string): URL pública del certificado generado.
   *   - 'certificate_data' (array): Datos del certificado para renderizado.
   *   Si falla, solo contiene 'success' => FALSE y 'error' (string).
   */
  public function generateCertificate(int $registrationId): array {
    $registration = $this->entityTypeManager
      ->getStorage('event_registration')
      ->load($registrationId);

    if (!$registration) {
      $this->logger->warning('Intento de generar certificado para registro inexistente: @id', [
        '@id' => $registrationId,
      ]);
      return [
        'success' => FALSE,
        'certificate_url' => '',
        'certificate_data' => [],
        'error' => 'Registro no encontrado.',
      ];
    }

    // Verificar que el asistente asistió al evento.
    $status = $registration->get('registration_status')->value;
    if ($status !== 'attended') {
      $this->logger->warning('Intento de generar certificado para registro no asistido: @id (estado: @status)', [
        '@id' => $registrationId,
        '@status' => $status,
      ]);
      return [
        'success' => FALSE,
        'certificate_url' => '',
        'certificate_data' => [],
        'error' => sprintf('El registro no tiene estado "attended" (actual: %s).', $status),
      ];
    }

    // Obtener datos completos del certificado.
    $certificate_data = $this->getCertificateData($registrationId);

    if (empty($certificate_data)) {
      return [
        'success' => FALSE,
        'certificate_url' => '',
        'certificate_data' => [],
        'error' => 'No se pudieron obtener los datos del certificado.',
      ];
    }

    $ticket_code = $registration->get('ticket_code')->value;
    $certificate_url = '/certificados/' . $ticket_code;

    $this->logger->info('Certificado generado para registro #@id (@name) - evento: @event', [
      '@id' => $registrationId,
      '@name' => $certificate_data['attendee_name'] ?? 'N/A',
      '@event' => $certificate_data['event_title'] ?? 'N/A',
    ]);

    return [
      'success' => TRUE,
      'certificate_url' => $certificate_url,
      'certificate_data' => $certificate_data,
    ];
  }

  /**
   * Obtiene los datos necesarios para generar un certificado.
   *
   * LÓGICA:
   * Recopila datos del registro (asistente, ticket) y del evento
   * asociado (título, fecha, tipo, duración) para componer la
   * información que aparece en el certificado.
   *
   * @param int $registrationId
   *   ID del registro de evento.
   *
   * @return array
   *   Array con datos del certificado:
   *   - 'attendee_name' (string): Nombre del asistente.
   *   - 'attendee_email' (string): Email del asistente.
   *   - 'ticket_code' (string): Código único de verificación.
   *   - 'event_title' (string): Título del evento.
   *   - 'event_type' (string): Tipo de evento.
   *   - 'event_start_date' (string): Fecha de inicio del evento.
   *   - 'event_end_date' (string): Fecha de fin del evento.
   *   - 'event_format' (string): Formato del evento.
   *   - 'issued_at' (string): Fecha de emisión del certificado.
   *   Devuelve array vacío si el registro o el evento no existen.
   */
  public function getCertificateData(int $registrationId): array {
    $registration = $this->entityTypeManager
      ->getStorage('event_registration')
      ->load($registrationId);

    if (!$registration) {
      return [];
    }

    // Obtener el evento asociado al registro.
    $event_id = $registration->get('event_id')->target_id;
    $event = $this->entityTypeManager
      ->getStorage('marketing_event')
      ->load($event_id);

    if (!$event) {
      $this->logger->warning('Evento #@event_id no encontrado para registro #@reg_id', [
        '@event_id' => $event_id,
        '@reg_id' => $registrationId,
      ]);
      return [];
    }

    return [
      'attendee_name' => $registration->get('attendee_name')->value ?? '',
      'attendee_email' => $registration->get('attendee_email')->value ?? '',
      'ticket_code' => $registration->get('ticket_code')->value ?? '',
      'registration_id' => (int) $registration->id(),
      'event_title' => $event->get('title')->value ?? '',
      'event_type' => $event->get('event_type')->value ?? '',
      'event_start_date' => $event->get('start_date')->value ?? '',
      'event_end_date' => $event->get('end_date')->value ?? '',
      'event_format' => $event->get('format')->value ?? '',
      'tenant_id' => $registration->get('tenant_id')->target_id,
      'issued_at' => date('Y-m-d\TH:i:s'),
    ];
  }

  /**
   * Verifica la autenticidad de un certificado por código de ticket.
   *
   * LÓGICA:
   * Busca un registro de evento con el ticket_code proporcionado.
   * Si existe y tiene estado 'attended', el certificado es válido.
   * Devuelve los datos del certificado para verificación externa.
   *
   * @param string $code
   *   Código de ticket único del certificado (ej: EVT-5-A1B2).
   *
   * @return array
   *   Array con las claves:
   *   - 'valid' (bool): TRUE si el certificado es auténtico y válido.
   *   - 'certificate_data' (array): Datos del certificado (si es válido).
   *   - 'error' (string): Mensaje de error (si no es válido).
   */
  public function verifyCertificate(string $code): array {
    if (empty($code)) {
      return [
        'valid' => FALSE,
        'certificate_data' => [],
        'error' => 'Código de verificación vacío.',
      ];
    }

    $registrations = $this->entityTypeManager
      ->getStorage('event_registration')
      ->loadByProperties(['ticket_code' => $code]);

    if (empty($registrations)) {
      $this->logger->info('Verificación de certificado fallida: código @code no encontrado.', [
        '@code' => $code,
      ]);
      return [
        'valid' => FALSE,
        'certificate_data' => [],
        'error' => 'Certificado no encontrado con el código proporcionado.',
      ];
    }

    /** @var \Drupal\jaraba_events\Entity\EventRegistration $registration */
    $registration = reset($registrations);
    $status = $registration->get('registration_status')->value;

    if ($status !== 'attended') {
      return [
        'valid' => FALSE,
        'certificate_data' => [],
        'error' => sprintf('El registro asociado no tiene estado "attended" (actual: %s).', $status),
      ];
    }

    $certificate_data = $this->getCertificateData((int) $registration->id());

    $this->logger->info('Certificado verificado correctamente: @code para @name', [
      '@code' => $code,
      '@name' => $certificate_data['attendee_name'] ?? 'N/A',
    ]);

    return [
      'valid' => TRUE,
      'certificate_data' => $certificate_data,
    ];
  }

}
