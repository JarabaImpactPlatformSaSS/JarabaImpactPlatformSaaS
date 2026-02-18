<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador API para generación de contenido con IA.
 *
 * PROPÓSITO:
 * Proporciona endpoints REST para generar contenido textual
 * usando IA, contextualizado según el tipo de campo y bloque.
 *
 * ESPECIFICACIÓN: Gap 1 - Plan Elevación Clase Mundial
 *
 * ENDPOINT PRINCIPAL:
 * POST /api/v1/page-builder/generate-field
 *
 * SEGURIDAD:
 * - Requiere autenticación (usuario logueado)
 * - Rate limiting por tenant
 * - Validación de permisos de Page Builder
 *
 * @package Drupal\jaraba_page_builder\Controller
 */
class AiFieldGeneratorController extends ControllerBase implements ContainerInjectionInterface
{

    /**
     * Prompts base por tipo de campo y bloque.
     *
     * Estos prompts se combinan con el contexto del campo
     * para generar contenido relevante y de calidad.
     *
     * @var array
     */
    protected const FIELD_PROMPTS = [
        // Prompts por tipo de bloque.
        'hero' => [
            'title' => 'Crea un título impactante y memorable para un hero section. Debe ser conciso (máximo 8 palabras), inspirador y orientado a la acción.',
            'subtitle' => 'Crea un subtítulo que complemente el título del hero. Debe explicar brevemente el valor principal en 15-25 palabras.',
            'cta_text' => 'Genera un texto de botón CTA persuasivo y orientado a la acción. Máximo 3 palabras.',
        ],
        'features' => [
            'title' => 'Crea un título conciso para una característica o beneficio. Máximo 5 palabras, claro y directo.',
            'description' => 'Describe esta característica en 20-30 palabras, enfocándote en el beneficio para el usuario.',
        ],
        'testimonial' => [
            'quote' => 'Genera un testimonio creíble y emotivo de un cliente satisfecho. 30-50 palabras, en primera persona.',
            'author' => 'Genera un nombre y cargo profesional realista para el autor del testimonio.',
        ],
        'faq' => [
            'question' => 'Formula una pregunta frecuente clara y específica que un usuario típico haría.',
            'answer' => 'Proporciona una respuesta concisa, informativa y útil a esta pregunta. 30-50 palabras.',
        ],
        'cta' => [
            'title' => 'Crea un título persuasivo para una sección de llamada a la acción. Orientado a conversión.',
            'description' => 'Genera texto que motive al usuario a actuar. Incluye beneficio y urgencia sutil. 20-30 palabras.',
            'button_text' => 'Genera texto de botón efectivo. Verbos de acción, máximo 3 palabras.',
        ],
        // Prompt genérico para campos no específicos.
        'generic' => [
            'title' => 'Genera un título atractivo y profesional para esta sección.',
            'description' => 'Genera una descripción clara y convincente. 25-40 palabras.',
            'text' => 'Genera texto apropiado para este campo.',
        ],
    ];

    /**
     * Modificadores de tono para la generación.
     *
     * @var array
     */
    protected const TONE_MODIFIERS = [
        'professional' => 'Usa un tono profesional, serio y confiable. Evita coloquialismos.',
        'casual' => 'Usa un tono casual, cercano y amigable. Puedes usar expresiones coloquiales.',
        'persuasive' => 'Usa un tono persuasivo orientado a ventas. Enfócate en beneficios y urgencia.',
        'informative' => 'Usa un tono informativo y educativo. Sé claro y objetivo.',
        'creative' => 'Usa un tono creativo y original. Sé ingenioso y memorable.',
    ];

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        $instance = new static();
        // Inyectar servicios adicionales si es necesario.
        return $instance;
    }

    /**
     * Genera contenido para un campo usando IA.
     *
     * Este endpoint recibe el contexto del campo y devuelve
     * contenido generado apropiado para ese contexto.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La solicitud HTTP con los parámetros de generación.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con el contenido generado o error.
     */
    public function generateField(Request $request): JsonResponse
    {
        // Verificar que sea POST.
        if ($request->getMethod() !== 'POST') {
            // AUDIT-CONS-N08: Standardized JSON envelope.
            return new JsonResponse([
                'success' => FALSE,
                'error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => $this->t('Método no permitido.')->render()],
            ], 405);
        }

        // Verificar permisos.
        if (!$this->currentUser()->hasPermission('access page builder')) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => ['code' => 'ACCESS_DENIED', 'message' => $this->t('Acceso denegado.')->render()],
            ], 403);
        }

        // Parsear body JSON.
        $data = json_decode($request->getContent(), TRUE) ?? [];

        // Validar campos requeridos.
        if (empty($data['field_name']) && empty($data['field_label'])) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => ['code' => 'VALIDATION_ERROR', 'message' => $this->t('Campo no especificado.')->render()],
            ], 400);
        }

        try {
            // Construir el prompt contextual.
            $prompt = $this->buildPrompt($data);

            // Generar contenido con IA.
            $content = $this->generateWithAI($prompt, $data);

            // Respuesta exitosa.
            return new JsonResponse([
                'success' => TRUE,
                'data' => [
                    'content' => $content,
                    'field_name' => $data['field_name'] ?? '',
                    'generated_at' => date('c'),
                ],
                'meta' => ['timestamp' => time()],
            ]);

        } catch (\Exception $e) {
            // Log del error para debugging.
            $this->getLogger('jaraba_page_builder')->error(
                'Error generando campo con IA: @message',
                ['@message' => $e->getMessage()]
            );

            return new JsonResponse([
                'success' => FALSE,
                'error' => ['code' => 'INTERNAL_ERROR', 'message' => $this->t('Error al generar contenido. Inténtalo de nuevo.')->render()],
            ], 500);
        }
    }

    /**
     * Construye el prompt para la generación de IA.
     *
     * Combina el contexto del campo con los prompts predefinidos
     * y los modificadores de tono para crear un prompt efectivo.
     *
     * @param array $data
     *   Datos de la solicitud con contexto del campo.
     *
     * @return string
     *   El prompt completo para enviar a la IA.
     */
    protected function buildPrompt(array $data): string
    {
        $blockType = $data['block_type'] ?? 'generic';
        $fieldName = $this->normalizeFieldName($data['field_name'] ?? $data['field_label'] ?? 'text');
        $tone = $data['tone'] ?? 'professional';
        $vertical = $data['vertical'] ?? 'general';
        $instructions = $data['instructions'] ?? '';
        $maxLength = $data['max_length'] ?? null;
        $isLongText = $data['is_long_text'] ?? false;

        // Buscar prompt específico para el bloque y campo.
        $basePrompt = self::FIELD_PROMPTS[$blockType][$fieldName]
            ?? self::FIELD_PROMPTS['generic'][$fieldName]
            ?? self::FIELD_PROMPTS['generic']['text'];

        // Añadir modificador de tono.
        $toneModifier = self::TONE_MODIFIERS[$tone] ?? self::TONE_MODIFIERS['professional'];

        // Construir prompt completo.
        $prompt = $basePrompt . "\n\n" . $toneModifier;

        // Añadir contexto del vertical.
        if ($vertical !== 'general') {
            $verticalContext = $this->getVerticalContext($vertical);
            $prompt .= "\n\nContexto del sector: " . $verticalContext;
        }

        // Añadir límite de longitud si aplica.
        if ($maxLength && $maxLength > 0) {
            $prompt .= "\n\nIMPORTANTE: El texto no debe superar los {$maxLength} caracteres.";
        }

        // Añadir instrucciones del usuario.
        if (!empty($instructions)) {
            $prompt .= "\n\nInstrucciones adicionales del usuario: " . $instructions;
        }

        // Indicar longitud según tipo.
        if ($isLongText) {
            $prompt .= "\n\nPuedes usar varios párrafos si es apropiado.";
        } else {
            $prompt .= "\n\nMantén el texto conciso, preferiblemente en una sola línea.";
        }

        // Indicación final.
        $prompt .= "\n\nResponde SOLO con el texto generado, sin explicaciones ni formato adicional.";

        return $prompt;
    }

    /**
     * Normaliza el nombre del campo para buscar prompts.
     *
     * Convierte nombres como "hero_title" o "heroTitle" a "title".
     *
     * @param string $fieldName
     *   Nombre del campo original.
     *
     * @return string
     *   Nombre normalizado.
     */
    protected function normalizeFieldName(string $fieldName): string
    {
        // Convertir a minúsculas y reemplazar separadores.
        $normalized = strtolower(preg_replace('/[A-Z]/', '_$0', $fieldName));
        $normalized = str_replace(['_', '-'], ' ', $normalized);
        $normalized = trim($normalized);

        // Extraer la última palabra significativa.
        $parts = explode(' ', $normalized);
        $lastPart = end($parts);

        // Mapear a nombres conocidos.
        $mappings = [
            'titulo' => 'title',
            'subtitulo' => 'subtitle',
            'descripcion' => 'description',
            'texto' => 'text',
            'cta' => 'cta_text',
            'boton' => 'button_text',
            'pregunta' => 'question',
            'respuesta' => 'answer',
            'cita' => 'quote',
            'autor' => 'author',
        ];

        return $mappings[$lastPart] ?? $lastPart;
    }

    /**
     * Obtiene contexto específico del vertical para el prompt.
     *
     * Cada vertical tiene un contexto diferente que ayuda
     * a la IA a generar contenido más relevante.
     *
     * @param string $vertical
     *   Identificador del vertical.
     *
     * @return string
     *   Contexto del vertical.
     */
    protected function getVerticalContext(string $vertical): string
    {
        $contexts = [
            'agro' => 'Este es un sitio de productos agrícolas y agroalimentarios. Enfócate en frescura, origen local, sostenibilidad y tradición.',
            'empleo' => 'Este es un portal de empleo. Enfócate en oportunidades profesionales, desarrollo de carrera y matching talento-empresa.',
            'formacion' => 'Este es una plataforma de formación. Enfócate en aprendizaje, desarrollo de habilidades y certificaciones.',
            'ecommerce' => 'Esta es una tienda online. Enfócate en beneficios del producto, calidad y satisfacción del cliente.',
            'servicios' => 'Este es un sitio de servicios profesionales. Enfócate en experiencia, resultados y atención personalizada.',
        ];

        return $contexts[$vertical] ?? 'Genera contenido apropiado para un sitio web profesional.';
    }

    /**
     * Genera contenido usando el servicio de IA.
     *
     * Utiliza el servicio de agentes IA del módulo jaraba_ai_agents
     * si está disponible, o fallback a un generador simple.
     *
     * @param string $prompt
     *   El prompt para la generación.
     * @param array $data
     *   Datos adicionales de contexto.
     *
     * @return string
     *   El contenido generado.
     *
     * @throws \Exception
     *   Si la generación falla.
     */
    protected function generateWithAI(string $prompt, array $data): string
    {
        // Intentar usar el servicio de IA del módulo jaraba_ai_agents.
        if (\Drupal::hasService('jaraba_ai_agents.agent_engine')) {
            /** @var \Drupal\jaraba_ai_agents\Service\AgentEngineService $agentEngine */
            $agentEngine = \Drupal::service('jaraba_ai_agents.agent_engine');

            // Usar el modelo de generación de contenido.
            $response = $agentEngine->generate([
                'prompt' => $prompt,
                'model' => 'gpt-4o-mini', // Modelo rápido y económico.
                'max_tokens' => $data['is_long_text'] ? 300 : 100,
                'temperature' => 0.7,
            ]);

            if (!empty($response['content'])) {
                return trim($response['content']);
            }
        }

        // Fallback: usar servicio de generación simple si existe.
        if (\Drupal::hasService('jaraba_page_builder.content_generator')) {
            /** @var mixed $generator */
            $generator = \Drupal::service('jaraba_page_builder.content_generator');
            if (method_exists($generator, 'generate')) {
                return $generator->generate($prompt);
            }
        }

        // Fallback final: generar contenido placeholder inteligente.
        return $this->generatePlaceholder($data);
    }

    /**
     * Genera contenido placeholder cuando la IA no está disponible.
     *
     * Este es un fallback que proporciona contenido útil
     * cuando los servicios de IA no están configurados.
     *
     * @param array $data
     *   Datos del contexto del campo.
     *
     * @return string
     *   Contenido placeholder.
     */
    protected function generatePlaceholder(array $data): string
    {
        $fieldName = $this->normalizeFieldName($data['field_name'] ?? 'text');

        $placeholders = [
            'title' => 'Título impactante para tu sección',
            'subtitle' => 'Un subtítulo que complementa y expande tu mensaje principal',
            'description' => 'Descripción detallada que explica los beneficios y características de tu producto o servicio de manera clara y convincente.',
            'cta_text' => 'Actúa ahora',
            'button_text' => 'Comenzar',
            'quote' => 'Esta plataforma ha transformado la manera en que gestionamos nuestro negocio. Los resultados superaron todas nuestras expectativas.',
            'author' => 'María García, Directora de Operaciones',
            'question' => '¿Cómo puedo empezar a usar esta plataforma?',
            'answer' => 'Comenzar es muy sencillo. Solo necesitas registrarte, completar tu perfil y ya podrás acceder a todas las funcionalidades.',
        ];

        return $placeholders[$fieldName] ?? 'Contenido generado para ' . ($data['field_label'] ?? $data['field_name'] ?? 'este campo');
    }

}
