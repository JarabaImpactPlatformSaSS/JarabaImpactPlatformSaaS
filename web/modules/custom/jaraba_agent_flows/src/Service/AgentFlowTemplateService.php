<?php

declare(strict_types=1);

namespace Drupal\jaraba_agent_flows\Service;

use Psr\Log\LoggerInterface;

/**
 * Servicio de templates predefinidos para flujos de agentes IA.
 *
 * PROPOSITO:
 * Proporciona plantillas de flujo predefinidas por vertical que
 * permiten a los tenants crear flujos rapidamente sin configurar
 * cada paso desde cero.
 *
 * USO:
 * @code
 * $templates = $this->templateService->getTemplates('agroconecta');
 * $flowConfig = $this->templateService->applyTemplate('content_generation');
 * @endcode
 */
class AgentFlowTemplateService {

  /**
   * Constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   El canal de log del modulo.
   */
  public function __construct(
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Obtiene los templates disponibles, opcionalmente filtrados por vertical.
   *
   * @param string|null $vertical
   *   Identificador de vertical para filtrar, o NULL para todos.
   *
   * @return array
   *   Array de templates con: id, name, description, vertical, category,
   *   steps_count, estimated_duration.
   */
  public function getTemplates(?string $vertical = NULL): array {
    $templates = $this->getBuiltInTemplates();

    if ($vertical !== NULL) {
      $templates = array_filter($templates, function (array $template) use ($vertical): bool {
        return $template['vertical'] === $vertical || $template['vertical'] === 'universal';
      });
    }

    return array_values($templates);
  }

  /**
   * Aplica un template y devuelve la configuracion de flujo resultante.
   *
   * @param string $templateId
   *   Identificador del template a aplicar.
   *
   * @return array
   *   Array con la configuracion del flujo generada desde el template.
   *   Contiene claves: name, description, flow_config, trigger_type,
   *   trigger_config. Vacio si el template no existe.
   */
  public function applyTemplate(string $templateId): array {
    $templates = $this->getBuiltInTemplates();

    $template = NULL;
    foreach ($templates as $t) {
      if ($t['id'] === $templateId) {
        $template = $t;
        break;
      }
    }

    if ($template === NULL) {
      $this->logger->warning('Template no encontrado: @id', ['@id' => $templateId]);
      return [];
    }

    $this->logger->info('Aplicando template @id para crear nuevo flujo.', ['@id' => $templateId]);

    return [
      'name' => $template['name'],
      'description' => $template['description'],
      'flow_config' => $template['config'],
      'trigger_type' => $template['default_trigger'] ?? 'manual',
      'trigger_config' => $template['default_trigger_config'] ?? [],
    ];
  }

  /**
   * Retorna las plantillas predefinidas.
   *
   * @return array
   *   Array de templates con definiciones completas.
   */
  protected function getBuiltInTemplates(): array {
    return [
      [
        'id' => 'content_generation',
        'name' => 'Generacion de Contenido',
        'description' => 'Genera contenido automatizado con revision y publicacion.',
        'vertical' => 'universal',
        'category' => 'content',
        'steps_count' => 3,
        'estimated_duration' => '30-60s',
        'default_trigger' => 'manual',
        'default_trigger_config' => [],
        'config' => [
          'steps' => [
            [
              'name' => 'generate_content',
              'type' => 'generate',
              'params' => [
                'prompt' => 'Genera contenido sobre {{topic}} para {{audience}}.',
                'model' => 'default',
              ],
            ],
            [
              'name' => 'validate_content',
              'type' => 'validate',
              'params' => [
                'rules' => ['length_min' => 200, 'length_max' => 2000],
              ],
            ],
            [
              'name' => 'publish_content',
              'type' => 'publish',
              'params' => [
                'target' => 'node',
                'content_type' => 'article',
              ],
            ],
          ],
          'settings' => [
            'timeout' => 120,
            'max_retries' => 2,
          ],
        ],
      ],
      [
        'id' => 'product_enrichment',
        'name' => 'Enriquecimiento de Productos',
        'description' => 'Mejora fichas de producto con IA: descripcion, SEO y categorias.',
        'vertical' => 'agroconecta',
        'category' => 'ecommerce',
        'steps_count' => 4,
        'estimated_duration' => '45-90s',
        'default_trigger' => 'event',
        'default_trigger_config' => ['event_name' => 'entity.insert.node.product'],
        'config' => [
          'steps' => [
            [
              'name' => 'fetch_product',
              'type' => 'api_call',
              'params' => [
                'url' => '/api/v1/products/{{entity_id}}',
                'method' => 'GET',
              ],
            ],
            [
              'name' => 'enrich_description',
              'type' => 'generate',
              'params' => [
                'prompt' => 'Mejora la descripcion del producto: {{previous_output.title}}. Destaca beneficios y origen.',
              ],
            ],
            [
              'name' => 'generate_seo',
              'type' => 'generate',
              'params' => [
                'prompt' => 'Genera meta title y meta description SEO para: {{previous_output}}.',
              ],
            ],
            [
              'name' => 'update_product',
              'type' => 'publish',
              'params' => [
                'target' => 'node',
                'update_existing' => TRUE,
              ],
            ],
          ],
          'settings' => [
            'timeout' => 180,
            'max_retries' => 3,
          ],
        ],
      ],
      [
        'id' => 'lead_qualification',
        'name' => 'Cualificacion de Leads',
        'description' => 'Evalua y clasifica leads automaticamente con scoring IA.',
        'vertical' => 'universal',
        'category' => 'crm',
        'steps_count' => 3,
        'estimated_duration' => '15-30s',
        'default_trigger' => 'webhook',
        'default_trigger_config' => ['webhook_id' => 'new_lead'],
        'config' => [
          'steps' => [
            [
              'name' => 'analyze_lead',
              'type' => 'generate',
              'params' => [
                'prompt' => 'Analiza el siguiente lead y asigna un score de 0-100: {{payload}}.',
              ],
            ],
            [
              'name' => 'check_threshold',
              'type' => 'condition',
              'params' => [
                'expression' => 'previous_output.score >= 70',
              ],
            ],
            [
              'name' => 'notify_sales',
              'type' => 'notify',
              'params' => [
                'channel' => 'email',
                'template' => 'hot_lead_notification',
              ],
            ],
          ],
          'settings' => [
            'timeout' => 60,
          ],
        ],
      ],
      [
        'id' => 'employability_report',
        'name' => 'Informe de Empleabilidad',
        'description' => 'Genera informes personalizados de empleabilidad para candidatos.',
        'vertical' => 'empleabilidad',
        'category' => 'report',
        'steps_count' => 3,
        'estimated_duration' => '60-120s',
        'default_trigger' => 'manual',
        'default_trigger_config' => [],
        'config' => [
          'steps' => [
            [
              'name' => 'collect_profile',
              'type' => 'api_call',
              'params' => [
                'url' => '/api/v1/candidates/{{user_id}}/profile',
                'method' => 'GET',
              ],
            ],
            [
              'name' => 'generate_report',
              'type' => 'generate',
              'params' => [
                'prompt' => 'Genera un informe de empleabilidad detallado basado en: {{previous_output}}.',
              ],
            ],
            [
              'name' => 'publish_report',
              'type' => 'publish',
              'params' => [
                'target' => 'pdf',
                'template' => 'employability_report',
              ],
            ],
          ],
          'settings' => [
            'timeout' => 180,
            'max_retries' => 2,
          ],
        ],
      ],
      [
        'id' => 'daily_digest',
        'name' => 'Resumen Diario',
        'description' => 'Genera y envia un resumen diario de actividad del tenant.',
        'vertical' => 'universal',
        'category' => 'notification',
        'steps_count' => 3,
        'estimated_duration' => '30-60s',
        'default_trigger' => 'cron',
        'default_trigger_config' => ['interval_seconds' => 86400],
        'config' => [
          'steps' => [
            [
              'name' => 'collect_metrics',
              'type' => 'api_call',
              'params' => [
                'url' => '/api/v1/tenants/{{tenant_id}}/usage',
                'method' => 'GET',
              ],
            ],
            [
              'name' => 'generate_digest',
              'type' => 'generate',
              'params' => [
                'prompt' => 'Genera un resumen ejecutivo de la actividad diaria basado en: {{previous_output}}.',
              ],
            ],
            [
              'name' => 'send_notification',
              'type' => 'notify',
              'params' => [
                'channel' => 'email',
                'template' => 'daily_digest',
              ],
            ],
          ],
          'settings' => [
            'timeout' => 120,
          ],
        ],
      ],
    ];
  }

}
