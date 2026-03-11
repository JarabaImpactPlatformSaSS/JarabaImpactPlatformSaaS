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
        private ?AiProviderPluginManager $aiProvider,
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
        if ($this->aiProvider === NULL) {
            $this->logger->warning('AI provider not available for triage of @nombre', [
                '@nombre' => $solicitud->getNombre(),
            ]);
            return [
                'score' => NULL,
                'justificacion' => 'Evaluación IA no disponible. Requiere revisión manual.',
                'recomendacion' => 'revisar',
            ];
        }

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

        $nivelesDigitales = [
            'ninguno' => 'Ninguno (no usa ordenador ni móvil)',
            'basico' => 'Básico (email, WhatsApp, navegación)',
            'intermedio' => 'Intermedio (ofimática, redes, apps)',
            'avanzado' => 'Avanzado (herramientas profesionales)',
        ];

        $nivelesIA = [
            'no_conozco' => 'No conoce la IA',
            'he_oido' => 'Ha oído hablar pero no la ha usado',
            'uso_basico' => 'Uso básico (ChatGPT o similar alguna vez)',
            'uso_habitual' => 'Uso habitual (IA regularmente)',
        ];

        $accesoOrdenador = [
            'no_tengo' => 'Sin acceso a ordenador',
            'compartido' => 'Ordenador compartido',
            'propio_antiguo' => 'Ordenador propio (antiguo/limitado)',
            'propio_reciente' => 'Ordenador propio (reciente)',
        ];

        $accesoInternet = [
            'sin_acceso' => 'Sin Internet en casa',
            'movil_solo' => 'Solo datos móviles',
            'wifi_inestable' => 'Wi-Fi inestable',
            'fibra_estable' => 'Fibra/conexión estable',
        ];

        $disponibilidades = [
            'mananas' => 'Mañanas (9-14h)',
            'tardes' => 'Tardes (16-20h)',
            'flexible' => 'Flexible',
            'fines_semana' => 'Solo fines de semana',
        ];

        $canales = [
            'redes_sociales' => 'Redes sociales',
            'web' => 'Búsqueda web',
            'conocido' => 'Recomendación personal',
            'sae' => 'SAE',
            'ayuntamiento' => 'Ayuntamiento/admin pública',
            'otro' => 'Otro',
        ];

        $nivelDigitalKey = $solicitud->get('nivel_digital')->value ?? '';
        $conoceIaKey = $solicitud->get('conoce_ia')->value ?? '';
        $accesoOrdKey = $solicitud->get('acceso_ordenador')->value ?? '';
        $accesoIntKey = $solicitud->get('acceso_internet')->value ?? '';
        $dispKey = $solicitud->get('disponibilidad_horaria')->value ?? '';
        $canalKey = $solicitud->get('como_conocio')->value ?? '';

        // Pre-resolve for heredoc (no ?? inside heredoc).
        $nivelDigitalLabel = $nivelesDigitales[$nivelDigitalKey] ?? 'No indicado';
        $conoceIaLabel = $nivelesIA[$conoceIaKey] ?? 'No indicado';
        $accesoOrdLabel = $accesoOrdenador[$accesoOrdKey] ?? 'No indicado';
        $accesoIntLabel = $accesoInternet[$accesoIntKey] ?? 'No indicado';
        $dispLabel = $disponibilidades[$dispKey] ?? 'No indicada';
        $canalLabel = $canales[$canalKey] ?? 'No indicado';
        $municipio = $solicitud->get('municipio')->value ?? '';
        $situacionLaboral = $solicitud->get('situacion_laboral')->value ?? '';
        $tiempoDesempleo = $solicitud->get('tiempo_desempleo')->value ?? '';
        $nivelEstudios = $solicitud->get('nivel_estudios')->value ?? '';
        $esMigrante = $this->boolToStr((bool) $solicitud->get('es_migrante')->value);
        $percibePrestacion = $this->boolToStr((bool) $solicitud->get('percibe_prestacion')->value);
        $experiencia = $solicitud->get('experiencia_sector')->value ?? '';
        $motivacion = $solicitud->get('motivacion')->value ?? '';
        $nombre = $solicitud->getNombre();

        return <<<PROMPT
DATOS DE LA SOLICITUD:
- Nombre: {$nombre}
- Edad: {$edad}
- Provincia: {$provincia}
- Municipio: {$municipio}
- Situación laboral: {$situacionLaboral}
- Tiempo en desempleo: {$tiempoDesempleo}
- Nivel de estudios: {$nivelEstudios}
- Es migrante: {$esMigrante}
- Percibe prestación/subsidio/RAI: {$percibePrestacion}
- Colectivo inferido: {$colectivo}
- Experiencia profesional: {$experiencia}

ACCESO DIGITAL:
- Competencias digitales: {$nivelDigitalLabel}
- Conocimiento de IA: {$conoceIaLabel}
- Acceso a ordenador: {$accesoOrdLabel}
- Acceso a Internet: {$accesoIntLabel}

DISPONIBILIDAD Y MOTIVACIÓN:
- Disponibilidad horaria: {$dispLabel}
- Canal de conocimiento: {$canalLabel}
- Motivación: {$motivacion}

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
- Pertenencia a colectivo prioritario (larga duración, mayores 45, migrantes, perceptores): +15 puntos
- Situación de desempleo: +10 puntos
- Motivación clara y articulada: +15 puntos
- Experiencia profesional relevante: +10 puntos
- Residencia en Andalucía (todas las provincias son elegibles): +5 puntos
- Nivel formativo (se valora más a quien más necesita el programa): +5 puntos
- Acceso digital (se valora que NECESITE formación digital; sin acceso o nivel bajo = más necesidad = más puntos): +10 puntos
- Disponibilidad horaria (flexible o mañanas aporta más que solo fines de semana): +5 puntos
- Coherencia general de la solicitud: +15 puntos
- Brecha digital como oportunidad: candidatos con bajo nivel digital pero alta motivación DEBEN puntuarse alto (el programa existe para cerrar esa brecha)

NOTA IMPORTANTE sobre acceso digital:
Un nivel digital bajo NO es motivo de penalización. Al contrario, indica mayor necesidad del programa.
Lo que SÍ penaliza es la incoherencia (ej: dice nivel avanzado pero motivación vacía o genérica).

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
