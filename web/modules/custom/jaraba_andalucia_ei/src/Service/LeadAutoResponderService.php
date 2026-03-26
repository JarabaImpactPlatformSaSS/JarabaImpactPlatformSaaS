<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Service\CopilotOrchestratorService;
use Psr\Log\LoggerInterface;

/**
 * Auto-responde a leads entrantes en menos de 5 minutos usando IA.
 *
 * Flujo:
 * 1. Carga NegocioProspectadoEi por ID.
 * 2. Genera respuesta personalizada via copilot (si disponible).
 * 3. Si copilot no disponible, usa template estático.
 * 4. Envía respuesta al negocio y notifica al coordinador.
 *
 * PRESAVE-RESILIENCE-001: Servicios opcionales con try-catch.
 */
class LeadAutoResponderService {

  /**
   * Packs disponibles con descripción para templates.
   *
   * @var array<string, string>
   */
  private const PACK_DESCRIPCIONES = [
    'pack_basico' => 'Pack Básico: diagnóstico digital inicial, presencia web básica y primeros pasos en redes sociales',
    'pack_intermedio' => 'Pack Intermedio: tienda online, gestión de inventario digital y marketing básico',
    'pack_avanzado' => 'Pack Avanzado: estrategia digital integral, automatizaciones y analítica avanzada',
    'pack_premium' => 'Pack Premium: transformación digital completa, IA aplicada y mentorización personalizada',
    'pack_especializado' => 'Pack Especializado: solución vertical adaptada a las necesidades específicas del sector',
  ];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ?CopilotOrchestratorService $copilotOrchestrator = NULL,
    protected ?EiMultichannelNotificationService $notificationService = NULL,
  ) {}

  /**
   * Procesa un nuevo lead y envía auto-respuesta.
   *
   * @param int $negocioId
   *   ID de la entidad NegocioProspectadoEi.
   *
   * @return bool
   *   TRUE si se procesó y envió correctamente, FALSE en caso contrario.
   */
  public function procesarNuevoLead(int $negocioId): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('negocio_prospectado_ei');
      $negocio = $storage->load($negocioId);

      if ($negocio === NULL) {
        $this->logger->warning('LeadAutoResponder: NegocioProspectadoEi @id no encontrado.', [
          '@id' => $negocioId,
        ]);
        return FALSE;
      }

      $nombreNegocio = (string) ($negocio->get('nombre_negocio')->value ?? '');
      $sector = (string) ($negocio->get('sector')->value ?? '');
      $packCompatible = (string) ($negocio->get('pack_compatible')->value ?? '');
      $emailNegocio = (string) ($negocio->get('email')->value ?? '');
      $personaContacto = (string) ($negocio->get('persona_contacto')->value ?? '');

      if ($nombreNegocio === '') {
        $this->logger->warning('LeadAutoResponder: NegocioProspectadoEi @id sin nombre de negocio.', [
          '@id' => $negocioId,
        ]);
        return FALSE;
      }

      // Generar respuesta personalizada (IA o template).
      $respuesta = $this->generarRespuestaPersonalizada($nombreNegocio, $sector, $packCompatible);

      // Enviar respuesta al negocio via email.
      $enviado = FALSE;
      if ($emailNegocio !== '' && $this->notificationService !== NULL) {
        try {
          $destinatarioNombre = $personaContacto !== '' ? $personaContacto : $nombreNegocio;
          $this->notificationService->notificar(0, 'lead_auto_respuesta', [
            'titulo' => 'Respuesta automática — ' . $nombreNegocio,
            'mensaje' => $respuesta,
            'email_destino' => $emailNegocio,
            'nombre_destino' => $destinatarioNombre,
          ]);
          $enviado = TRUE;
        }
        catch (\Throwable $e) {
          $this->logger->warning('LeadAutoResponder: Error enviando respuesta a @negocio: @error', [
            '@negocio' => $nombreNegocio,
            '@error' => $e->getMessage(),
          ]);
        }
      }

      // Notificar al coordinador del nuevo lead.
      $this->notificarCoordinador($negocioId, $nombreNegocio, $sector);

      $this->logger->info('LeadAutoResponder: Lead @id (@nombre) procesado. Respuesta enviada: @enviado.', [
        '@id' => $negocioId,
        '@nombre' => $nombreNegocio,
        '@enviado' => $enviado ? 'sí' : 'no',
      ]);

      return TRUE;
    }
    catch (\Throwable $e) {
      $this->logger->error('LeadAutoResponder: Error procesando lead @id: @error', [
        '@id' => $negocioId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Genera una respuesta personalizada para el lead.
   *
   * Intenta usar el copilot para generar una respuesta con IA.
   * Si no está disponible, usa un template estático.
   *
   * @param string $nombreNegocio
   *   Nombre comercial del negocio.
   * @param string $sector
   *   Sector de actividad.
   * @param string $packSugerido
   *   Clave del pack compatible sugerido.
   *
   * @return string
   *   Texto de la respuesta personalizada.
   */
  public function generarRespuestaPersonalizada(string $nombreNegocio, string $sector, string $packSugerido): string {
    if ($this->copilotOrchestrator === NULL) {
      return $this->getTemplateRespuesta($nombreNegocio, $packSugerido);
    }

    try {
      $prompt = sprintf(
        'Genera una respuesta comercial breve y profesional (máximo 200 palabras) para el negocio "%s" del sector "%s". '
        . 'El pack recomendado es "%s". '
        . 'La respuesta debe: saludar cordialmente, mencionar brevemente el pack y sus beneficios para su sector, '
        . 'invitar a una reunión de diagnóstico gratuita, e incluir datos de contacto del programa Andalucía +ei. '
        . 'Tono: cercano, profesional, orientado a resultados. En español.',
        $nombreNegocio,
        $sector !== '' ? $sector : 'general',
        $packSugerido !== '' ? $packSugerido : 'pack_basico'
      );

      $response = $this->copilotOrchestrator->chat($prompt, [
        'vertical' => 'andalucia_ei',
        'mode' => 'lead_response',
      ], 'asistente');

      $text = (string) ($response['text'] ?? '');
      if ($text !== '') {
        return $text;
      }

      $this->logger->notice('LeadAutoResponder: Copilot devolvió respuesta vacía para @negocio, usando template.', [
        '@negocio' => $nombreNegocio,
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->notice('LeadAutoResponder: Copilot no disponible para @negocio: @error. Usando template.', [
        '@negocio' => $nombreNegocio,
        '@error' => $e->getMessage(),
      ]);
    }

    return $this->getTemplateRespuesta($nombreNegocio, $packSugerido);
  }

  /**
   * Template estático de respuesta (fallback sin IA).
   *
   * @param string $nombreNegocio
   *   Nombre comercial del negocio.
   * @param string $packSugerido
   *   Clave del pack compatible sugerido.
   *
   * @return string
   *   Texto formateado de la respuesta.
   */
  public function getTemplateRespuesta(string $nombreNegocio, string $packSugerido): string {
    $packKey = $packSugerido !== '' ? $packSugerido : 'pack_basico';
    $packDescripcion = self::PACK_DESCRIPCIONES[$packKey] ?? self::PACK_DESCRIPCIONES['pack_basico'];

    return sprintf(
      "Estimado/a responsable de %s,\n\n"
      . "Gracias por su interés en el programa Andalucía +ei de transformación digital.\n\n"
      . "Tras un análisis preliminar de su perfil, le recomendamos el %s. "
      . "Este pack está diseñado para ayudar a negocios como el suyo a dar el salto digital "
      . "con acompañamiento personalizado y sin coste gracias a la financiación del programa.\n\n"
      . "Le invitamos a una reunión de diagnóstico gratuita donde analizaremos juntos las "
      . "necesidades específicas de su negocio y diseñaremos un plan de acción a medida.\n\n"
      . "Para agendar su reunión, puede:\n"
      . "- Responder a este correo indicando su disponibilidad\n"
      . "- Llamar al +34 623 174 304\n"
      . "- Escribir a contacto@plataformadeecosistemas.es\n\n"
      . "Quedamos a su disposición.\n\n"
      . "Un cordial saludo,\n"
      . "Equipo Andalucía +ei\n"
      . "Plataforma de Ecosistemas",
      $nombreNegocio,
      $packDescripcion
    );
  }

  /**
   * Notifica al coordinador sobre un nuevo lead recibido.
   *
   * @param int $negocioId
   *   ID de la entidad NegocioProspectadoEi.
   * @param string $nombreNegocio
   *   Nombre del negocio.
   * @param string $sector
   *   Sector de actividad.
   */
  protected function notificarCoordinador(int $negocioId, string $nombreNegocio, string $sector): void {
    if ($this->notificationService === NULL) {
      return;
    }

    try {
      // Buscar coordinador asignado (owner del negocio o coordinador del programa).
      $negocio = $this->entityTypeManager
        ->getStorage('negocio_prospectado_ei')
        ->load($negocioId);

      if ($negocio === NULL) {
        return;
      }

      $ownerId = (int) $negocio->getOwnerId();
      if ($ownerId <= 0) {
        $this->logger->notice('LeadAutoResponder: No hay coordinador asignado para negocio @id.', [
          '@id' => $negocioId,
        ]);
        return;
      }

      $sectorTexto = $sector !== '' ? ' (sector: ' . $sector . ')' : '';
      $this->notificationService->notificar(0, 'lead_nuevo_coordinador', [
        'titulo' => 'Nuevo lead: ' . $nombreNegocio,
        'mensaje' => sprintf(
          'Se ha recibido un nuevo lead del negocio "%s"%s. '
          . 'Se ha enviado una auto-respuesta. Revise el lead en el pipeline de prospección.',
          $nombreNegocio,
          $sectorTexto
        ),
        'uid_destino' => $ownerId,
        'link' => '/admin/content/negocio-prospectado-ei/' . $negocioId,
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->warning('LeadAutoResponder: Error notificando coordinador para lead @id: @error', [
        '@id' => $negocioId,
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
