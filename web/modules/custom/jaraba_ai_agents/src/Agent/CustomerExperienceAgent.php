<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Agent;

/**
 * Agente de Experiencia del Cliente.
 *
 * PROPÓSITO:
 * Especializado en gestionar la experiencia post-venta del cliente:
 * respuestas a reseñas, emails de seguimiento y manejo de quejas.
 *
 * ACCIONES DISPONIBLES:
 * - 'review_response': Genera respuestas profesionales a reseñas
 * - 'followup_email': Crea emails de seguimiento post-compra
 * - 'complaint_response': Genera respuestas empáticas a quejas
 *
 * CARACTERÍSTICAS:
 * - Detección automática de sentimiento (positivo/neutral/negativo)
 * - Sugerencias de cross-sell naturales
 * - Solicitudes sutiles de reseñas
 * - Propuestas de compensación cuando aplica
 *
 * ESTILO:
 * Empático, solucionador de problemas, profesional pero cálido.
 *
 * ESPECIFICACIÓN: Doc 156 - World_Class_AI_Elevation_v3
 */
class CustomerExperienceAgent extends BaseAgent
{

    /**
     * {@inheritdoc}
     */
    public function getAgentId(): string
    {
        return 'customer_experience';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'Agente de Experiencia del Cliente';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Gestiona experiencia del cliente: respuestas a reseñas, seguimientos y comunicación post-venta.';
    }

    /**
     * {@inheritdoc}
     *
     * Define las acciones disponibles con sus parámetros.
     */
    public function getAvailableActions(): array
    {
        return [
            'review_response' => [
                'label' => 'Respuesta a Reseña',
                'description' => 'Genera respuesta profesional a reseñas.',
                'requires' => ['review_text', 'rating'],
                'optional' => ['customer_name', 'order_id'],
            ],
            'followup_email' => [
                'label' => 'Email de Seguimiento',
                'description' => 'Crea emails de seguimiento post-compra.',
                'requires' => ['purchase_type', 'days_since_purchase'],
                'optional' => ['product_name', 'customer_name'],
            ],
            'complaint_response' => [
                'label' => 'Respuesta a Queja',
                'description' => 'Genera respuestas empáticas a quejas.',
                'requires' => ['complaint_summary', 'severity'],
                'optional' => ['compensation_offered', 'previous_interactions'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Enruta la ejecución al método de acción correspondiente.
     */
    public function execute(string $action, array $context): array
    {
        $this->setCurrentAction($action);

        return match ($action) {
            'review_response' => $this->generateReviewResponse($context),
            'followup_email' => $this->generateFollowupEmail($context),
            'complaint_response' => $this->generateComplaintResponse($context),
            default => [
                'success' => FALSE,
                'error' => "Acción no soportada: {$action}",
            ],
        };
    }

    /**
     * Genera una respuesta a reseña.
     *
     * Analiza el sentimiento basado en la puntuación y genera
     * una respuesta apropiada: agradecimiento para positivas,
     * solución para negativas.
     *
     * @param array $context
     *   Contexto con 'review_text', 'rating' (1-5).
     *   Opcionales: 'customer_name', 'order_id'.
     *
     * @return array
     *   Resultado con 'response', 'sentiment_detected', 'key_points_addressed'.
     */
    protected function generateReviewResponse(array $context): array
    {
        $reviewText = $context['review_text'] ?? '';
        $rating = $context['rating'] ?? 5;
        $customerName = $context['customer_name'] ?? 'cliente';

        $sentiment = $rating >= 4 ? 'positiva' : ($rating >= 3 ? 'neutral' : 'negativa');
        $verticalContext = $this->getVerticalContext();

        $prompt = <<<EOT
CONTEXTO VERTICAL: {$verticalContext}

TAREA: Responder a reseña {$sentiment} de {$customerName}.

RESEÑA: "{$reviewText}"
PUNTUACIÓN: {$rating}/5

REQUISITOS:
- Tono empático y profesional
- Agradecer el feedback
- Abordar puntos específicos mencionados
- Si es negativa: ofrecer solución
- Si es positiva: reforzar la relación

FORMATO DE RESPUESTA (JSON):
{
  "response": "Respuesta completa",
  "sentiment_detected": "positivo|neutral|negativo",
  "key_points_addressed": ["Punto 1", "Punto 2"],
  "suggested_action": "Acción de seguimiento si aplica"
}
EOT;

        $response = $this->callAiApi($prompt);

        if ($response['success']) {
            $parsed = $this->parseJsonResponse($response['data']['text']);
            if ($parsed) {
                $response['data'] = $parsed;
                $response['data']['content_type'] = 'review_response';
            }
        }

        return $response;
    }

    /**
     * Genera un email de seguimiento post-compra.
     *
     * Crea un email personalizado para verificar satisfacción,
     * ofrecer ayuda y solicitar reseñas de manera sutil.
     *
     * @param array $context
     *   Contexto con 'purchase_type', 'days_since_purchase'.
     *   Opcionales: 'product_name', 'customer_name'.
     *
     * @return array
     *   Resultado con 'subject', 'body', 'review_request', 'cross_sell_suggestion'.
     */
    protected function generateFollowupEmail(array $context): array
    {
        $purchaseType = $context['purchase_type'] ?? 'producto';
        $daysSince = $context['days_since_purchase'] ?? 7;
        $productName = $context['product_name'] ?? '';
        $customerName = $context['customer_name'] ?? 'cliente';

        $verticalContext = $this->getVerticalContext();

        $prompt = <<<EOT
CONTEXTO VERTICAL: {$verticalContext}

TAREA: Email de seguimiento {$daysSince} días post-compra.

TIPO DE COMPRA: {$purchaseType}
PRODUCTO: {$productName}
CLIENTE: {$customerName}

REQUISITOS:
- Verificar satisfacción
- Ofrecer ayuda si la necesitan
- Solicitar reseña sutilmente
- Cross-sell natural si aplica

FORMATO DE RESPUESTA (JSON):
{
  "subject": "Asunto del email",
  "greeting": "Saludo personalizado",
  "body": "Cuerpo del email",
  "review_request": "Solicitud de reseña",
  "cross_sell_suggestion": "Sugerencia de producto relacionado",
  "cta": "Llamada a la acción"
}
EOT;

        $response = $this->callAiApi($prompt);

        if ($response['success']) {
            $parsed = $this->parseJsonResponse($response['data']['text']);
            if ($parsed) {
                $response['data'] = $parsed;
                $response['data']['content_type'] = 'followup_email';
            }
        }

        return $response;
    }

    /**
     * Genera una respuesta a queja de cliente.
     *
     * Crea una respuesta empática que reconoce el problema,
     * ofrece una solución concreta y establece próximos pasos.
     *
     * @param array $context
     *   Contexto con 'complaint_summary', 'severity' (baja/media/alta).
     *   Opcionales: 'compensation_offered', 'previous_interactions'.
     *
     * @return array
     *   Resultado con 'acknowledgment', 'empathy_statement', 'solution', etc.
     */
    protected function generateComplaintResponse(array $context): array
    {
        $complaint = $context['complaint_summary'] ?? '';
        $severity = $context['severity'] ?? 'media';
        $compensation = $context['compensation_offered'] ?? '';

        $verticalContext = $this->getVerticalContext();

        $prompt = <<<EOT
CONTEXTO VERTICAL: {$verticalContext}

TAREA: Responder a queja de cliente.

QUEJA: {$complaint}
SEVERIDAD: {$severity}
COMPENSACIÓN DISPONIBLE: {$compensation}

REQUISITOS:
- Empatía genuina primero
- Reconocer el problema
- Explicar sin excusarse
- Ofrecer solución concreta
- Establecer siguientes pasos

FORMATO DE RESPUESTA (JSON):
{
  "acknowledgment": "Reconocimiento del problema",
  "empathy_statement": "Declaración de empatía",
  "explanation": "Breve explicación si aplica",
  "solution": "Solución propuesta",
  "compensation": "Compensación si aplica",
  "next_steps": "Pasos a seguir",
  "closing": "Cierre profesional"
}
EOT;

        $response = $this->callAiApi($prompt);

        if ($response['success']) {
            $parsed = $this->parseJsonResponse($response['data']['text']);
            if ($parsed) {
                $response['data'] = $parsed;
                $response['data']['content_type'] = 'complaint_response';
            }
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     *
     * Define el Brand Voice por defecto para experiencia del cliente.
     */
    protected function getDefaultBrandVoice(): string
    {
        return <<<EOT
Eres un experto en experiencia del cliente con alta inteligencia emocional.

ESTILO:
- Empático y comprensivo
- Solucionador de problemas
- Profesional pero cálido
- Proactivo en la resolución

PRINCIPIOS:
- El cliente tiene razón en sus sentimientos
- Escuchar antes de responder
- Ofrecer soluciones concretas
- Convertir quejas en oportunidades
EOT;
    }

}
