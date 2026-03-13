<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\jaraba_copilot_v2\Service\CopilotBridgeInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio puente para el vertical demo.
 *
 * Proporciona contexto genérico de demostración para el copilot,
 * permitiendo que usuarios en modo demo experimenten la IA del SaaS.
 */
class DemoCopilotBridgeService implements CopilotBridgeInterface {

  public function __construct(
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getVerticalKey(): string {
    return 'demo';
  }

  /**
   * {@inheritdoc}
   */
  public function getRelevantContext(int $userId): array {
    return [
      'vertical' => 'demo',
      'vertical_label' => 'Demo',
      '_system_prompt_addition' => $this->buildDemoPrompt(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSoftSuggestion(int $userId): ?array {
    return [
      'type' => 'upgrade',
      'message' => 'Estás en modo demo. Registra tu cuenta gratuita para acceder a todas las funcionalidades de la plataforma.',
      'cta' => [
        'label' => 'Crear cuenta gratis',
        'url' => '/user/register',
      ],
      'trigger' => 'demo_to_register',
    ];
  }

  /**
   * Construye el system prompt para el modo demo.
   */
  protected function buildDemoPrompt(): string {
    return <<<PROMPT
# ROL: ASISTENTE DE DEMOSTRACIÓN — JARABA IMPACT PLATFORM

Eres el asistente de demostración de la plataforma SaaS Jaraba Impact Platform. El usuario está explorando las capacidades de la plataforma en modo demo.

## COMPORTAMIENTO
- Presenta las funcionalidades de la plataforma de forma atractiva y profesional
- Responde preguntas sobre los 10 verticales disponibles: empleabilidad, emprendimiento, comercio, agro, legal, servicios, formación, contenido, Andalucía +EI
- Sugiere crear una cuenta gratuita cuando el usuario muestre interés en una funcionalidad específica
- Sé entusiasta pero honesto sobre las capacidades

## RESTRICCIONES
- NO inventes funcionalidades que no existen
- NO des acceso a datos reales — todo es demostración
- Siempre indica que está en modo demo cuando sea relevante
PROMPT;
  }

}
