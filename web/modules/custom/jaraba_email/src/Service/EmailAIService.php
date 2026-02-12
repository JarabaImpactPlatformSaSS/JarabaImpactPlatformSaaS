<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Service;

use Drupal\jaraba_ai_agents\Service\AgentOrchestrator;
use Psr\Log\LoggerInterface;

/**
 * Servicio de IA para Email Marketing.
 *
 * FUNCIONALIDADES:
 * - Generación de subject lines optimizados
 * - Generación de copy para campañas
 * - Personalización con variables de contacto
 * - Optimización A/B testing
 */
class EmailAIService
{

    /**
     * Constructor.
     */
    public function __construct(
        protected AgentOrchestrator $orchestrator,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Genera subject lines para una campaña.
     *
     * @param string $topic
     *   Tema o descripción de la campaña.
     * @param array $context
     *   Contexto adicional (brand_voice, tenant_id, etc.).
     * @param int $count
     *   Número de variantes a generar.
     *
     * @return array
     *   Array de subject lines.
     */
    public function generateSubjectLines(string $topic, array $context = [], int $count = 5): array
    {
        $agentContext = array_merge($context, [
            'action' => 'generate_email_subjects',
            'topic' => $topic,
            'count' => $count,
        ]);

        try {
            $result = $this->orchestrator->execute('marketing', 'generate_email_subjects', $agentContext);

            if ($result['success'] && !empty($result['data']['subjects'])) {
                return [
                    'success' => TRUE,
                    'subjects' => $result['data']['subjects'],
                ];
            }

            return [
                'success' => FALSE,
                'error' => $result['error'] ?? 'No subjects generated.',
                'subjects' => [],
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error generating email subjects: @error', [
                '@error' => $e->getMessage(),
            ]);
            return [
                'success' => FALSE,
                'error' => $e->getMessage(),
                'subjects' => [],
            ];
        }
    }

    /**
     * Genera el copy del email.
     *
     * @param string $topic
     *   Tema de la campaña.
     * @param string $style
     *   Estilo: 'promotional', 'newsletter', 'transactional', 'educational'.
     * @param array $context
     *   Contexto adicional.
     *
     * @return array
     *   Contenido generado.
     */
    public function generateEmailCopy(string $topic, string $style = 'newsletter', array $context = []): array
    {
        $agentContext = array_merge($context, [
            'action' => 'generate_email_copy',
            'topic' => $topic,
            'style' => $style,
        ]);

        try {
            $result = $this->orchestrator->execute('marketing', 'generate_email_copy', $agentContext);

            if ($result['success']) {
                return [
                    'success' => TRUE,
                    'subject' => $result['data']['subject'] ?? '',
                    'preheader' => $result['data']['preheader'] ?? '',
                    'body' => $result['data']['body'] ?? '',
                    'cta_text' => $result['data']['cta_text'] ?? '',
                ];
            }

            return [
                'success' => FALSE,
                'error' => $result['error'] ?? 'Generation failed.',
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error generating email copy: @error', [
                '@error' => $e->getMessage(),
            ]);
            return [
                'success' => FALSE,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Personaliza contenido con variables de contacto.
     *
     * @param string $content
     *   Contenido con placeholders {{nombre}}, {{empresa}}, etc.
     * @param array $contactData
     *   Datos del contacto.
     *
     * @return string
     *   Contenido personalizado.
     */
    public function personalizeContent(string $content, array $contactData): string
    {
        $replacements = [
            '{{nombre}}' => $contactData['name'] ?? 'Cliente',
            '{{empresa}}' => $contactData['company'] ?? '',
            '{{email}}' => $contactData['email'] ?? '',
            '{{cargo}}' => $contactData['position'] ?? '',
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $content
        );
    }

    /**
     * Genera variantes A/B para testing.
     *
     * @param string $originalSubject
     *   Subject line original.
     * @param int $count
     *   Número de variantes.
     *
     * @return array
     *   Variantes generadas.
     */
    public function generateABVariants(string $originalSubject, int $count = 3): array
    {
        $context = [
            'action' => 'generate_ab_variants',
            'original' => $originalSubject,
            'count' => $count,
        ];

        try {
            $result = $this->orchestrator->execute('marketing', 'generate_ab_variants', $context);

            if ($result['success'] && !empty($result['data']['variants'])) {
                return [
                    'success' => TRUE,
                    'original' => $originalSubject,
                    'variants' => $result['data']['variants'],
                ];
            }

            return [
                'success' => FALSE,
                'error' => 'No variants generated.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => FALSE,
                'error' => $e->getMessage(),
            ];
        }
    }

}
