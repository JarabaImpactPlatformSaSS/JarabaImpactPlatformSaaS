<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\jaraba_andalucia_ei\Entity\SolicitudEiInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de triaje IA para solicitudes Andalucía +ei.
 *
 * Evalúa cada solicitud usando LLM y propone una calificación
 * de 0-100 con justificación y recomendación (admitir/revisar/rechazar).
 *
 * Usa @ai.provider con failover Anthropic → OpenAI.
 * Modelo: claude-haiku-4-5 (económico, clasificación/tareas simples).
 *
 * @see \Drupal\ai\AiProviderPluginManager
 */
class SolicitudTriageService
{

    /**
     * Proveedores con failover ordenados por preferencia.
     */
    private const PROVIDERS = [
        ['id' => 'anthropic', 'model' => 'claude-3-haiku-20240307'],
        ['id' => 'openai', 'model' => 'gpt-4o-mini'],
    ];

    public function __construct(
        private AiProviderPluginManager $aiProvider,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Evalúa una solicitud con IA y retorna score + justificación.
     *
     * @param \Drupal\jaraba_andalucia_ei\Entity\SolicitudEiInterface $solicitud
     *   La solicitud a evaluar.
     *
     * @return array{score: ?int, justificacion: string, recomendacion: string}
     *   Array con score (0-100), justificación textual y recomendación.
     */
    public function triageSolicitud(SolicitudEiInterface $solicitud): array
    {
        $prompt = $this->buildTriagePrompt($solicitud);
        $systemPrompt = $this->getSystemPrompt();

        foreach (self::PROVIDERS as $providerConfig) {
            try {
                $llm = $this->aiProvider->createInstance($providerConfig['id']);
                $input = new ChatInput([
                    new ChatMessage('user', $prompt),
                ]);
                $input->setSystemPrompt($systemPrompt);
                $response = $llm->chat($input, $providerConfig['model']);

                $text = $response->getNormalized()->getText();
                $result = $this->parseResponse($text);

                $this->logger->info('Triaje IA completado para solicitud @nombre (score: @score, rec: @rec)', [
                    '@nombre' => $solicitud->getNombre(),
                    '@score' => $result['score'] ?? 'N/A',
                    '@rec' => $result['recomendacion'],
                ]);

                return $result;
            } catch (\Throwable $e) {
                $this->logger->warning('Proveedor IA @id falló para triaje: @msg', [
                    '@id' => $providerConfig['id'],
                    '@msg' => $e->getMessage(),
                ]);
                continue;
            }
        }

        // Fallback si todos los proveedores fallan.
        $this->logger->error('Todos los proveedores IA fallaron para triaje de solicitud @nombre', [
            '@nombre' => $solicitud->getNombre(),
        ]);

        return [
            'score' => NULL,
            'justificacion' => 'Evaluación IA no disponible temporalmente. Requiere revisión manual.',
            'recomendacion' => 'revisar',
        ];
    }

    /**
     * Construye el prompt de triaje con datos de la solicitud.
     */
    private function buildTriagePrompt(SolicitudEiInterface $solicitud): string
    {
        $provincias = [
            'almeria' => 'Almería',
            'cadiz' => 'Cádiz',
            'cordoba' => 'Córdoba',
            'granada' => 'Granada',
            'huelva' => 'Huelva',
            'jaen' => 'Jaén',
            'malaga' => 'Málaga',
            'sevilla' => 'Sevilla',
        ];

        $edad = '';
        $fechaNac = $solicitud->get('fecha_nacimiento')->value ?? '';
        if ($fechaNac) {
            try {
                $birth = new \DateTime($fechaNac);
                $now = new \DateTime();
                $edad = (string) $now->diff($birth)->y . ' años';
            } catch (\Exception $e) {
                $edad = 'No disponible';
            }
        }

        $colectivos = [
            'larga_duracion' => 'Desempleados larga duración (+12 meses)',
            'mayores_45' => 'Mayores de 45 años en desempleo',
            'migrantes' => 'Personas migrantes',
            'perceptores_prestaciones' => 'Perceptores de prestaciones/subsidio/RAI',
            'otros' => 'Otros',
        ];

        $provincia = $provincias[$solicitud->getProvincia()] ?? $solicitud->getProvincia();
        $colectivoKey = $solicitud->getColectivoInferido();
        $colectivo = $colectivos[$colectivoKey] ?? 'Sin determinar';

        return <<<PROMPT
DATOS DE LA SOLICITUD:
- Nombre: {$solicitud->getNombre()}
- Edad: {$edad}
- Provincia: {$provincia}
- Municipio: {$solicitud->get('municipio')->value}
- Situación laboral: {$solicitud->get('situacion_laboral')->value}
- Tiempo en desempleo: {$solicitud->get('tiempo_desempleo')->value}
- Nivel de estudios: {$solicitud->get('nivel_estudios')->value}
- Es migrante: {$this->boolToStr((bool) $solicitud->get('es_migrante')->value)}
- Percibe prestación/subsidio/RAI: {$this->boolToStr((bool) $solicitud->get('percibe_prestacion')->value)}
- Colectivo inferido: {$colectivo}
- Experiencia profesional: {$solicitud->get('experiencia_sector')->value}
- Motivación: {$solicitud->get('motivacion')->value}

Evalúa esta solicitud para el programa Andalucía +ei de emprendimiento.
PROMPT;
    }

    /**
     * System prompt para el triaje IA.
     */
    private function getSystemPrompt(): string
    {
        return <<<SYSTEM
Eres un asistente de triaje para el programa público Andalucía +ei de emprendimiento e inserción laboral.

Tu tarea es evaluar solicitudes de participación y proporcionar:
1. Una PUNTUACIÓN de 0 a 100 indicando la idoneidad del candidato
2. Una JUSTIFICACIÓN breve (máximo 3 frases) explicando tu evaluación
3. Una RECOMENDACIÓN: "admitir", "revisar" o "rechazar"

CRITERIOS DE EVALUACIÓN:
- Pertenencia a colectivo prioritario (larga duración, mayores 45, migrantes, perceptores): +20 puntos
- Situación de desempleo: +15 puntos
- Motivación clara y articulada: +15 puntos
- Experiencia profesional relevante: +10 puntos
- Residencia en Andalucía (todas las provincias son elegibles): +10 puntos
- Nivel formativo (se valora más a quien más necesita el programa): +10 puntos
- Coherencia general de la solicitud: +20 puntos

UMBRALES:
- Score ≥ 70: recomendación "admitir"
- Score 40-69: recomendación "revisar"
- Score < 40: recomendación "rechazar"

RESPONDE EXCLUSIVAMENTE en formato JSON válido, sin markdown:
{"score": <número>, "justificacion": "<texto>", "recomendacion": "<admitir|revisar|rechazar>"}
SYSTEM;
    }

    /**
     * Parsea la respuesta del LLM.
     */
    private function parseResponse(string $text): array
    {
        // Limpiar posible markdown wrapping.
        $text = preg_replace('/^```(?:json)?\s*/', '', trim($text));
        $text = preg_replace('/\s*```$/', '', $text);

        $data = json_decode(trim($text), TRUE);

        if (is_array($data) && isset($data['score'])) {
            return [
                'score' => max(0, min(100, (int) $data['score'])),
                'justificacion' => (string) ($data['justificacion'] ?? ''),
                'recomendacion' => in_array($data['recomendacion'] ?? '', ['admitir', 'revisar', 'rechazar'])
                    ? $data['recomendacion']
                    : 'revisar',
            ];
        }

        // Si no se pudo parsear, intentar extraer score del texto.
        $this->logger->warning('Respuesta IA no JSON válido: @text', ['@text' => substr($text, 0, 500)]);

        return [
            'score' => NULL,
            'justificacion' => 'Respuesta IA no estructurada. ' . substr($text, 0, 300),
            'recomendacion' => 'revisar',
        ];
    }

    /**
     * Convierte boolean a string legible.
     */
    private function boolToStr(bool $value): string
    {
        return $value ? 'Sí' : 'No';
    }
}
