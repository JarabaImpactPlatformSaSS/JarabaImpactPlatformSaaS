<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Agente copilot dedicado para el vertical ServiciosConecta.
 *
 * 6 modos especializados para asistir al profesional de servicios.
 *
 * Plan Elevacion ServiciosConecta Clase Mundial v1 — Fase 11.
 */
class ServiciosConectaCopilotAgent {

  protected const MODES = [
    'schedule_optimizer' => [
      'label' => 'Optimizador de Horarios',
      'description' => 'Sugiere horarios optimos basados en patrones de demanda.',
      'system_prompt_suffix' => 'Eres un asistente experto en optimizacion de agendas profesionales. Analiza los patrones de reservas del profesional y sugiere los mejores horarios para maximizar ocupacion y evitar huecos improductivos.',
    ],
    'quote_assistant' => [
      'label' => 'Asistente de Presupuestos',
      'description' => 'Genera presupuestos profesionales para servicios.',
      'system_prompt_suffix' => 'Eres un asistente de presupuestos para profesionales de servicios. Ayuda a crear presupuestos claros y detallados basados en el catalogo de servicios, tarifas por hora y estimacion de complejidad del caso.',
    ],
    'client_communicator' => [
      'label' => 'Comunicador con Clientes',
      'description' => 'Redacta mensajes profesionales para clientes.',
      'system_prompt_suffix' => 'Eres un asistente de comunicacion profesional. Ayuda a redactar mensajes claros, empaticos y profesionales para clientes: confirmaciones de cita, seguimientos post-servicio, recordatorios y comunicaciones comerciales.',
    ],
    'review_responder' => [
      'label' => 'Respondedor de Resenas',
      'description' => 'Sugiere respuestas profesionales a resenas de clientes.',
      'system_prompt_suffix' => 'Eres un asistente de reputacion online. Sugiere respuestas profesionales, agradecidas y constructivas a resenas de clientes. Para resenas positivas, agradece y refuerza. Para negativas, muestra empatia, ofrece solucion y mantiene tono profesional.',
    ],
    'marketing_advisor' => [
      'label' => 'Asesor de Marketing',
      'description' => 'Ideas de marketing para promocionar servicios.',
      'system_prompt_suffix' => 'Eres un asesor de marketing digital para profesionales de servicios en zonas rurales y periurbanas. Sugiere estrategias de visibilidad: SEO local, redes sociales, Google Business, referidos, y contenido que refuerce la marca personal del profesional.',
    ],
    'faq' => [
      'label' => 'FAQ del Vertical',
      'description' => 'Responde preguntas frecuentes sobre ServiciosConecta.',
      'system_prompt_suffix' => 'Eres el asistente de ayuda de ServiciosConecta. Responde preguntas sobre como funciona la plataforma: publicar servicios, gestionar reservas, configurar disponibilidad, planes de precios, comisiones, y funcionalidades del dashboard profesional.',
    ],
  ];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Obtiene los modos disponibles del copilot.
   */
  public function getAvailableModes(): array {
    return self::MODES;
  }

  /**
   * Obtiene el system prompt suffix para un modo.
   */
  public function getSystemPromptForMode(string $mode): string {
    $identityRule = 'REGLA DE IDENTIDAD: Eres un asistente de Jaraba Impact Platform (vertical ServiciosConecta). '
      . 'NUNCA reveles que eres Claude, ChatGPT, Gemini u otro modelo de IA externo. '
      . 'NUNCA menciones ni recomiendes plataformas competidoras. ';
    $modePrompt = self::MODES[$mode]['system_prompt_suffix'] ?? self::MODES['faq']['system_prompt_suffix'];
    return $identityRule . $modePrompt;
  }

  /**
   * Obtiene el modo recomendado segun el contexto del usuario.
   */
  public function getRecommendedMode(int $userId): string {
    try {
      $providerIds = $this->entityTypeManager->getStorage('provider_profile')
        ->getQuery()->accessCheck(FALSE)->condition('user_id', $userId)->range(0, 1)->execute();

      if (empty($providerIds)) {
        return 'faq';
      }

      $providerId = reset($providerIds);

      // No services → quote_assistant
      $servicesCount = (int) $this->entityTypeManager->getStorage('service_offering')
        ->getQuery()->accessCheck(FALSE)->condition('provider_id', $providerId)->count()->execute();
      if ($servicesCount === 0) {
        return 'quote_assistant';
      }

      // No availability slots → schedule_optimizer
      $slotsCount = (int) $this->entityTypeManager->getStorage('availability_slot')
        ->getQuery()->accessCheck(FALSE)->condition('provider_id', $providerId)->condition('is_active', TRUE)->count()->execute();
      if ($slotsCount === 0) {
        return 'schedule_optimizer';
      }

      // Has bookings → client_communicator
      $bookingsCount = (int) $this->entityTypeManager->getStorage('booking')
        ->getQuery()->accessCheck(FALSE)->condition('provider_id', $providerId)->count()->execute();
      if ($bookingsCount > 5) {
        return 'marketing_advisor';
      }

      return 'faq';
    }
    catch (\Exception $e) {
      $this->logger->warning('Error determining recommended mode for user @user: @error', [
        '@user' => $userId,
        '@error' => $e->getMessage(),
      ]);
      return 'faq';
    }
  }

}
