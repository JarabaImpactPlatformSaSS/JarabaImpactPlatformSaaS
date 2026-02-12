<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_ai_agents\Service\AgentOrchestrator;
use Psr\Log\LoggerInterface;

/**
 * Generates interactive content using AI agents.
 *
 * Uses the AgentOrchestrator from jaraba_ai_agents to:
 * - Generate quiz questions from source text
 * - Create branching scenarios
 * - Build flashcard decks
 * - Suggest improvements to existing content
 */
class ContentGenerator
{

    /**
     * The AI agent orchestrator.
     *
     * @var \Drupal\jaraba_ai_agents\Service\AgentOrchestrator
     */
    protected AgentOrchestrator $orchestrator;

    /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * The logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Constructs a new ContentGenerator.
     */
    public function __construct(
        AgentOrchestrator $orchestrator,
        EntityTypeManagerInterface $entity_type_manager,
        LoggerInterface $logger
    ) {
        $this->orchestrator = $orchestrator;
        $this->entityTypeManager = $entity_type_manager;
        $this->logger = $logger;
    }

    /**
     * Generate quiz questions from source text.
     *
     * @param string $source_text
     *   The text to generate questions from.
     * @param string $difficulty
     *   Difficulty level: beginner, intermediate, advanced.
     * @param int $count
     *   Number of questions to generate.
     * @param string $question_type
     *   Type: multiple_choice, true_false, fill_blank.
     *
     * @return array
     *   Generated questions in the InteractiveContent data format.
     */
    public function generateQuiz(
        string $source_text,
        string $difficulty = 'intermediate',
        int $count = 5,
        string $question_type = 'multiple_choice'
    ): array {
        $prompt = $this->buildQuizPrompt($source_text, $difficulty, $count, $question_type);

        try {
            $response = $this->orchestrator->execute('content_generation', 'generate_quiz', [
                'prompt' => $prompt,
                'output_format' => 'json',
            ]);

            $questions = json_decode($response['content'] ?? '[]', TRUE);

            $this->logger->info('Generated @count quiz questions with AI', [
                '@count' => count($questions),
            ]);

            return $this->formatQuizData($questions, $difficulty);
        } catch (\Exception $e) {
            $this->logger->error('AI quiz generation failed: @message', [
                '@message' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Generate a branching scenario.
     *
     * @param string $scenario_description
     *   Description of the scenario to create.
     * @param string $learning_objective
     *   What the learner should learn.
     * @param int $depth
     *   How many decision points deep (1-5).
     *
     * @return array
     *   Scenario data structure with nodes and branches.
     */
    public function generateScenario(
        string $scenario_description,
        string $learning_objective,
        int $depth = 3
    ): array {
        $prompt = $this->buildScenarioPrompt($scenario_description, $learning_objective, $depth);

        try {
            $response = $this->orchestrator->execute('content_generation', 'generate_scenario', [
                'prompt' => $prompt,
                'output_format' => 'json',
            ]);

            $scenario = json_decode($response['content'] ?? '{}', TRUE);

            $this->logger->info('Generated branching scenario with AI');

            return $this->formatScenarioData($scenario);
        } catch (\Exception $e) {
            $this->logger->error('AI scenario generation failed: @message', [
                '@message' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Generate flashcards from content.
     *
     * @param string $source_text
     *   Source text for flashcards.
     * @param int $count
     *   Number of cards to generate.
     *
     * @return array
     *   Flashcard data with front/back pairs.
     */
    public function generateFlashcards(string $source_text, int $count = 10): array
    {
        $prompt = <<<PROMPT
Genera exactamente {$count} flashcards educativas basadas en el siguiente texto.
Cada flashcard debe tener:
- "front": Una pregunta o concepto clave
- "back": La respuesta o explicación

Responde SOLO con un array JSON válido.

Texto fuente:
{$source_text}
PROMPT;

        try {
            $response = $this->orchestrator->execute('content_generation', 'generate_flashcards', [
                'prompt' => $prompt,
                'output_format' => 'json',
            ]);

            $cards = json_decode($response['content'] ?? '[]', TRUE);

            return [
                'cards' => $cards,
                'settings' => [
                    'shuffle' => TRUE,
                    'show_progress' => TRUE,
                ],
            ];
        } catch (\Exception $e) {
            $this->logger->error('AI flashcard generation failed: @message', [
                '@message' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Build the quiz generation prompt.
     */
    protected function buildQuizPrompt(string $source, string $difficulty, int $count, string $type): string
    {
        $type_instructions = match ($type) {
            'multiple_choice' => 'Cada pregunta debe tener 4 opciones (A, B, C, D) y una respuesta correcta.',
            'true_false' => 'Cada pregunta debe ser una afirmación que puede ser verdadera o falsa.',
            'fill_blank' => 'Cada pregunta debe tener un espacio en blanco (_____) para completar.',
            default => 'Cada pregunta debe tener opciones de respuesta.',
        };

        $difficulty_instructions = match ($difficulty) {
            'beginner' => 'Las preguntas deben ser directas y evaluar comprensión básica.',
            'intermediate' => 'Las preguntas deben requerir análisis y conexión de conceptos.',
            'advanced' => 'Las preguntas deben requerir pensamiento crítico y aplicación compleja.',
            default => 'Nivel intermedio de dificultad.',
        };

        return <<<PROMPT
Genera exactamente {$count} preguntas tipo {$type} basadas en el siguiente contenido.

INSTRUCCIONES:
- {$type_instructions}
- {$difficulty_instructions}
- Responde SOLO con un array JSON válido.
- Cada pregunta debe tener: "question", "options" (si aplica), "correct_answer", "explanation"

CONTENIDO FUENTE:
{$source}
PROMPT;
    }

    /**
     * Build the scenario generation prompt.
     */
    protected function buildScenarioPrompt(string $description, string $objective, int $depth): string
    {
        return <<<PROMPT
Crea un escenario de aprendizaje ramificado basado en la siguiente descripción.

DESCRIPCIÓN DEL ESCENARIO:
{$description}

OBJETIVO DE APRENDIZAJE:
{$objective}

REQUISITOS:
- El escenario debe tener {$depth} niveles de profundidad (decisiones)
- Cada nodo debe tener 2-3 opciones de decisión
- Incluye feedback para cada decisión
- Algunas rutas llevan al éxito, otras a consecuencias negativas educativas

Responde con un JSON con la estructura:
{
  "title": "Título del escenario",
  "introduction": "Contexto inicial",
  "nodes": [
    {"id": "start", "content": "...", "options": [{"text": "...", "next": "node_id", "feedback": "..."}]}
  ],
  "endings": [
    {"id": "success_1", "type": "success", "content": "..."},
    {"id": "fail_1", "type": "failure", "content": "..."}
  ]
}
PROMPT;
    }

    /**
     * Format quiz data for InteractiveContent entity.
     */
    protected function formatQuizData(array $questions, string $difficulty): array
    {
        return [
            'questions' => $questions,
            'settings' => [
                'shuffle_questions' => TRUE,
                'shuffle_answers' => TRUE,
                'show_feedback' => TRUE,
                'passing_score' => match ($difficulty) {
                    'beginner' => 60,
                    'intermediate' => 70,
                    'advanced' => 80,
                    default => 70,
                },
            ],
        ];
    }

    /**
     * Format scenario data for InteractiveContent entity.
     */
    protected function formatScenarioData(array $scenario): array
    {
        return [
            'title' => $scenario['title'] ?? 'Escenario sin título',
            'introduction' => $scenario['introduction'] ?? '',
            'nodes' => $scenario['nodes'] ?? [],
            'endings' => $scenario['endings'] ?? [],
            'settings' => [
                'allow_restart' => TRUE,
                'show_path_taken' => TRUE,
            ],
        ];
    }

    // =========================================================================
    // SMART IMPORT: URL & VIDEO (Sprint 5)
    // =========================================================================

    /**
     * Import content from a URL using Jina Reader API.
     *
     * Extracts text content from any web URL and generates interactive content.
     *
     * @param string $url
     *   The URL to extract content from.
     * @param string $content_type
     *   Type of content to generate: quiz, scenario, flashcards, presentation.
     * @param array $options
     *   Additional options (difficulty, count, language).
     *
     * @return array
     *   Generated content with source metadata.
     */
    public function importFromUrl(
        string $url,
        string $content_type = 'quiz',
        array $options = []
    ): array {
        $difficulty = $options['difficulty'] ?? 'intermediate';
        $count = $options['count'] ?? 5;

        try {
            // Step 1: Extract content using Jina Reader API
            $extracted = $this->extractFromUrl($url);

            if (empty($extracted['content'])) {
                throw new \Exception('No se pudo extraer contenido de la URL');
            }

            // Step 2: Generate content based on type
            $generated = match ($content_type) {
                'quiz' => $this->generateQuiz($extracted['content'], $difficulty, $count),
                'scenario' => $this->generateScenario(
                    $extracted['content'],
                    $options['learning_objective'] ?? 'Comprender el contenido',
                    $options['depth'] ?? 3
                ),
                'flashcards' => $this->generateFlashcards($extracted['content'], $count),
                'presentation' => $this->generatePresentation($extracted['content'], $options),
                default => $this->generateQuiz($extracted['content'], $difficulty, $count),
            };

            $this->logger->info('Smart Import from URL completed: @url -> @type', [
                '@url' => $url,
                '@type' => $content_type,
            ]);

            return [
                'success' => TRUE,
                'source' => [
                    'type' => 'url',
                    'url' => $url,
                    'title' => $extracted['title'] ?? '',
                    'extracted_text_length' => strlen($extracted['content']),
                ],
                'content_type' => $content_type,
                'content_data' => $generated,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Smart Import from URL failed: @message', [
                '@message' => $e->getMessage(),
            ]);
            return [
                'success' => FALSE,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Import content from a video using OpenAI Whisper API.
     *
     * Transcribes video audio and generates interactive content with timestamps.
     *
     * @param string $video_source
     *   YouTube URL or local video path.
     * @param string $content_type
     *   Type of content to generate.
     * @param array $options
     *   Additional options (include_timestamps, difficulty).
     *
     * @return array
     *   Generated content with transcription and source metadata.
     */
    public function importFromVideo(
        string $video_source,
        string $content_type = 'quiz',
        array $options = []
    ): array {
        $difficulty = $options['difficulty'] ?? 'intermediate';
        $count = $options['count'] ?? 5;
        $include_timestamps = $options['include_timestamps'] ?? TRUE;

        try {
            // Step 1: Detect source type and get transcript
            $transcript = $this->getVideoTranscript($video_source, $include_timestamps);

            if (empty($transcript['text'])) {
                throw new \Exception('No se pudo obtener la transcripción del video');
            }

            // Step 2: Generate content with timestamp awareness
            $generated = match ($content_type) {
                'quiz' => $this->generateVideoQuiz(
                    $transcript['text'],
                    $transcript['segments'] ?? [],
                    $difficulty,
                    $count
                ),
                'interactive_video' => $this->generateVideoCheckpoints(
                    $video_source,
                    $transcript['segments'] ?? [],
                    $options
                ),
                default => $this->generateQuiz($transcript['text'], $difficulty, $count),
            };

            $this->logger->info('Smart Import from Video completed: @source -> @type', [
                '@source' => $video_source,
                '@type' => $content_type,
            ]);

            return [
                'success' => TRUE,
                'source' => [
                    'type' => 'video',
                    'video_url' => $video_source,
                    'duration' => $transcript['duration'] ?? 'unknown',
                    'transcript_length' => strlen($transcript['text']),
                    'has_timestamps' => !empty($transcript['segments']),
                ],
                'content_type' => $content_type,
                'content_data' => $generated,
                'transcript' => $transcript['text'],
            ];
        } catch (\Exception $e) {
            $this->logger->error('Smart Import from Video failed: @message', [
                '@message' => $e->getMessage(),
            ]);
            return [
                'success' => FALSE,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Extract content from URL using Jina Reader API.
     *
     * Jina Reader (r.jina.ai) converts any URL to clean markdown,
     * removing navigation, ads, and boilerplate.
     *
     * @param string $url
     *   The URL to extract from.
     *
     * @return array
     *   Extracted content with title and body.
     */
    protected function extractFromUrl(string $url): array
    {
        $jina_url = 'https://r.jina.ai/' . $url;

        try {
            $client = \Drupal::httpClient();
            $response = $client->get($jina_url, [
                'headers' => [
                    'Accept' => 'text/plain',
                ],
                'timeout' => 30,
            ]);

            $body = (string) $response->getBody();

            // Jina returns markdown with title on first line
            $lines = explode("\n", $body);
            $title = '';
            if (!empty($lines[0]) && str_starts_with($lines[0], '# ')) {
                $title = substr($lines[0], 2);
                array_shift($lines);
            }

            return [
                'title' => trim($title),
                'content' => trim(implode("\n", $lines)),
            ];
        } catch (\Exception $e) {
            $this->logger->warning('Jina Reader failed, using fallback: @error', [
                '@error' => $e->getMessage(),
            ]);

            // Fallback: Direct fetch with basic extraction
            return $this->extractFromUrlFallback($url);
        }
    }

    /**
     * Fallback URL extraction using DOMDocument.
     */
    protected function extractFromUrlFallback(string $url): array
    {
        $client = \Drupal::httpClient();
        $response = $client->get($url, ['timeout' => 20]);
        $html = (string) $response->getBody();

        $dom = new \DOMDocument();
        @$dom->loadHTML($html, LIBXML_NOERROR);
        $xpath = new \DOMXPath($dom);

        // Extract title
        $title_nodes = $xpath->query('//title');
        $title = $title_nodes->length > 0 ? $title_nodes->item(0)->textContent : '';

        // Extract main content (article, main, or body)
        $content = '';
        foreach (['//article', '//main', '//div[@id="content"]', '//body'] as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                $content = $nodes->item(0)->textContent;
                break;
            }
        }

        // Clean up whitespace
        $content = preg_replace('/\s+/', ' ', $content);

        return [
            'title' => trim($title),
            'content' => trim($content),
        ];
    }

    /**
     * Get video transcript using OpenAI Whisper or YouTube captions.
     *
     * @param string $video_source
     *   YouTube URL or local file path.
     * @param bool $include_timestamps
     *   Whether to include word-level timestamps.
     *
     * @return array
     *   Transcription data with text and optional segments.
     */
    protected function getVideoTranscript(string $video_source, bool $include_timestamps = TRUE): array
    {
        // Detect if YouTube URL
        if ($this->isYouTubeUrl($video_source)) {
            return $this->getYouTubeTranscript($video_source);
        }

        // For local files or other sources, use Whisper API
        return $this->transcribeWithWhisper($video_source, $include_timestamps);
    }

    /**
     * Check if URL is a YouTube video.
     */
    protected function isYouTubeUrl(string $url): bool
    {
        return (bool) preg_match(
            '/^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/',
            $url
        );
    }

    /**
     * Extract YouTube video ID from URL.
     */
    protected function extractYouTubeId(string $url): ?string
    {
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            return $matches[1];
        }
        return NULL;
    }

    /**
     * Get YouTube transcript using YouTube Data API / timedtext.
     */
    protected function getYouTubeTranscript(string $url): array
    {
        $video_id = $this->extractYouTubeId($url);
        if (!$video_id) {
            throw new \Exception('Invalid YouTube URL');
        }

        // Try YouTube timedtext API (auto-captions)
        $caption_url = "https://www.youtube.com/api/timedtext?lang=es&v={$video_id}&fmt=json3";

        try {
            $client = \Drupal::httpClient();
            $response = $client->get($caption_url, ['timeout' => 15]);
            $data = json_decode((string) $response->getBody(), TRUE);

            if (empty($data['events'])) {
                // Try English captions as fallback
                $caption_url = "https://www.youtube.com/api/timedtext?lang=en&v={$video_id}&fmt=json3";
                $response = $client->get($caption_url, ['timeout' => 15]);
                $data = json_decode((string) $response->getBody(), TRUE);
            }

            if (empty($data['events'])) {
                throw new \Exception('No captions available for this video');
            }

            // Parse caption events into text and segments
            $text = '';
            $segments = [];
            foreach ($data['events'] as $event) {
                if (!isset($event['segs'])) {
                    continue;
                }
                $segment_text = '';
                foreach ($event['segs'] as $seg) {
                    $segment_text .= $seg['utf8'] ?? '';
                }
                $text .= $segment_text . ' ';
                $segments[] = [
                    'start' => ($event['tStartMs'] ?? 0) / 1000,
                    'end' => (($event['tStartMs'] ?? 0) + ($event['dDurationMs'] ?? 0)) / 1000,
                    'text' => trim($segment_text),
                ];
            }

            return [
                'text' => trim($text),
                'segments' => $segments,
                'duration' => $this->formatDuration(end($segments)['end'] ?? 0),
                'source' => 'youtube_captions',
            ];
        } catch (\Exception $e) {
            $this->logger->warning('YouTube captions failed: @error', [
                '@error' => $e->getMessage(),
            ]);

            // Fallback to Whisper if YouTube captions fail
            return $this->transcribeWithWhisper($url, TRUE);
        }
    }

    /**
     * Transcribe audio using Drupal AI module's speech_to_text provider.
     *
     * Uses the platform's AiProviderPluginManager which integrates with
     * KEY module for secure credential management.
     *
     * @param string $source
     *   Path to the audio/video file to transcribe.
     * @param bool $include_timestamps
     *   Whether to include segment timestamps.
     *
     * @return array
     *   Transcription result with text, segments, and duration.
     *
     * @throws \Exception
     *   If provider is not configured or file cannot be processed.
     */
    protected function transcribeWithWhisper(string $source, bool $include_timestamps = TRUE): array
    {
        // For YouTube URLs, we need to use captions API instead
        if ($this->isYouTubeUrl($source)) {
            throw new \Exception('Direct Whisper transcription for YouTube requires audio extraction. Use YouTube captions instead.');
        }

        // For local files, verify they exist
        if (!file_exists($source)) {
            throw new \Exception('Video file not found: ' . $source);
        }

        try {
            // Use Drupal AI module's provider system (integrates with KEY module)
            /** @var \Drupal\ai\AiProviderPluginManager $aiProvider */
            $aiProvider = \Drupal::service('plugin.manager.ai_provider');
            $defaults = $aiProvider->getDefaultProviderForOperationType('speech_to_text');

            if (empty($defaults)) {
                throw new \Exception('No speech_to_text provider configured. Configure in /admin/config/ai/settings');
            }

            $provider = $aiProvider->createInstance($defaults['provider_id']);

            // Read file content for STT input
            $audioContent = file_get_contents($source);

            // Use the provider's speechToText method
            // The method accepts a file path string or SpeechToTextInput
            // Using AudioFile for proper handling with metadata
            $audioFile = new \Drupal\ai\OperationType\GenericType\AudioFile(
                file_get_contents($source),
                $this->getMimeType($source),
                basename($source)
            );

            $input = new \Drupal\ai\OperationType\SpeechToText\SpeechToTextInput($audioFile);

            $response = $provider->speechToText($input, $defaults['model_id'], [
                'language' => 'es',
            ]);

            $text = $response->getNormalized()->getText();

            return [
                'text' => $text,
                'segments' => [], // AI module may not expose segments directly
                'duration' => $this->formatDuration(0), // Duration may need separate extraction
                'source' => 'whisper',
            ];
        } catch (\Exception $e) {
            $this->logger->error('Whisper transcription failed: @message', [
                '@message' => $e->getMessage(),
            ]);
            throw new \Exception('Transcription failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate quiz questions with video timestamp awareness.
     */
    protected function generateVideoQuiz(
        string $transcript,
        array $segments,
        string $difficulty,
        int $count
    ): array {
        // Build prompt that includes timestamp context
        $prompt = <<<PROMPT
Genera exactamente {$count} preguntas de comprensión basadas en esta transcripción de video.

TRANSCRIPCIÓN:
{$transcript}

INSTRUCCIONES:
- Cada pregunta debe estar vinculada a un momento específico del video
- Incluye el timestamp aproximado (en segundos) donde se menciona el concepto
- Nivel de dificultad: {$difficulty}
- Responde con JSON array

Formato de cada pregunta:
{
  "question": "...",
  "options": [{"id": "a", "text": "...", "correct": false}, ...],
  "timestamp": 120,
  "explanation": "..."
}
PROMPT;

        $response = $this->orchestrator->execute('content_generation', 'generate_video_quiz', [
            'prompt' => $prompt,
            'output_format' => 'json',
        ]);

        $questions = json_decode($response['content'] ?? '[]', TRUE);

        return [
            'questions' => $questions,
            'settings' => [
                'shuffle_questions' => FALSE, // Keep chronological order
                'show_timestamp_hints' => TRUE,
                'passing_score' => 70,
            ],
        ];
    }

    /**
     * Generate checkpoints for interactive video from transcript.
     */
    protected function generateVideoCheckpoints(
        string $video_url,
        array $segments,
        array $options
    ): array {
        $checkpoint_count = $options['checkpoint_count'] ?? 3;

        // Distribute checkpoints evenly across video duration
        $duration = end($segments)['end'] ?? 300;
        $interval = $duration / ($checkpoint_count + 1);

        $prompt = <<<PROMPT
Basándote en esta transcripción, genera {$checkpoint_count} checkpoints de comprensión.
Cada checkpoint debe aparecer aproximadamente en los segundos: 

SEGMENTOS:
PROMPT;
        foreach (array_slice($segments, 0, 50) as $seg) {
            $prompt .= "[{$seg['start']}s] {$seg['text']}\n";
        }

        $prompt .= <<<PROMPT

Genera checkpoints en formato JSON:
[
  {
    "time": 60,
    "type": "question",
    "text": "¿Qué concepto se acaba de explicar?",
    "options": [...],
    "required": true
  }
]
PROMPT;

        $response = $this->orchestrator->execute('content_generation', 'generate_checkpoints', [
            'prompt' => $prompt,
            'output_format' => 'json',
        ]);

        $checkpoints = json_decode($response['content'] ?? '[]', TRUE);

        return [
            'video_url' => $video_url,
            'checkpoints' => $checkpoints,
            'overlays' => [],
            'settings' => [
                'require_checkpoints' => TRUE,
                'allow_skip' => FALSE,
            ],
        ];
    }

    /**
     * Generate presentation slides from content.
     */
    protected function generatePresentation(string $content, array $options): array
    {
        $slide_count = $options['slide_count'] ?? 5;

        $prompt = <<<PROMPT
Convierte este contenido en {$slide_count} slides de presentación educativa.

CONTENIDO:
{$content}

INSTRUCCIONES:
- Cada slide debe tener un título claro y contenido conciso
- Incluye al menos 1 slide con quiz embebido
- Estructura lógica de introducción -> desarrollo -> conclusión

Responde con JSON:
{
  "slides": [
    {"type": "intro", "title": "...", "content": "..."},
    {"type": "content", "title": "...", "elements": [{"type": "text", "content": "..."}]},
    {"type": "quiz", "title": "...", "question": {"text": "...", "options": [...]}}
  ],
  "settings": {"keyboard_navigation": true}
}
PROMPT;

        $response = $this->orchestrator->execute('content_generation', 'generate_presentation', [
            'prompt' => $prompt,
            'output_format' => 'json',
        ]);

        return json_decode($response['content'] ?? '{}', TRUE);
    }

    /**
     * Format duration in seconds to MM:SS.
     */
    protected function formatDuration(float $seconds): string
    {
        $minutes = floor($seconds / 60);
        $secs = (int) ($seconds % 60);
        return sprintf('%02d:%02d', $minutes, $secs);
    }

    /**
     * Get MIME type for an audio/video file.
     *
     * @param string $path
     *   Path to the file.
     *
     * @return string
     *   MIME type string.
     */
    protected function getMimeType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $types = [
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
            'wav' => 'audio/wav',
            'm4a' => 'audio/mp4',
            'webm' => 'video/webm',
            'ogg' => 'audio/ogg',
            'flac' => 'audio/flac',
        ];
        return $types[$extension] ?? 'audio/mpeg';
    }

}
