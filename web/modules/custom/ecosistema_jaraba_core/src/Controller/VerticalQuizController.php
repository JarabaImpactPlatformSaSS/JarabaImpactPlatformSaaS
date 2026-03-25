<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Flood\FloodInterface;
use Drupal\ecosistema_jaraba_core\Service\VerticalQuizService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller del Quiz de Recomendación de Vertical.
 *
 * Rutas:
 * - GET /test-vertical — Página del quiz (4 preguntas)
 * - POST /api/v1/quiz/submit — Procesar respuestas
 * - GET /test-vertical/resultado/{uuid} — Página de resultado.
 */
class VerticalQuizController extends ControllerBase {

  public function __construct(
    protected VerticalQuizService $quizService,
    protected FloodInterface $flood,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ecosistema_jaraba_core.vertical_quiz'),
      $container->get('flood'),
    );
  }

  /**
   * Página del quiz — ZERO-REGION-001: markup vacío, datos via preprocess.
   *
   * NO usar #theme con arrays complejos (keys sin # causan
   * InvalidArgumentException en Drupal renderer). Los datos se inyectan
   * via hook_preprocess_page() en el .theme.
   */
  public function quizPage(): array {
    return [
      '#type' => 'markup',
      '#markup' => '',
      '#attached' => [
        'library' => ['ecosistema_jaraba_theme/route-quiz'],
        'drupalSettings' => [
          'verticalQuiz' => [
            'submitEndpoint' => Url::fromRoute('ecosistema_jaraba_core.quiz_vertical.submit')->toString(),
            'resultBaseUrl' => Url::fromRoute('ecosistema_jaraba_core.quiz_vertical', [], ['absolute' => FALSE])->toString() . '/resultado/',
            'totalSteps' => 4,
          ],
        ],
      ],
    ];
  }

  /**
   * Procesar respuestas del quiz (API POST).
   */
  public function submitQuiz(Request $request): JsonResponse {
    // Rate limit: 10 req/min per IP.
    $ip = $request->getClientIp() ?? '0.0.0.0';
    $floodName = 'quiz_vertical_submit';
    if (!$this->flood->isAllowed($floodName, 10, 60, $ip)) {
      return new JsonResponse(['error' => 'Too many requests'], 429);
    }
    $this->flood->register($floodName, 60, $ip);

    $data = json_decode($request->getContent(), TRUE);
    if (!is_array($data)) {
      return new JsonResponse(['error' => 'Invalid JSON'], 400);
    }

    // Caso especial: guardar email en QuizResult existente.
    if (($data['perfil'] ?? '') === 'email_save' && !empty($data['email']) && !empty($data['quiz_uuid'])) {
      $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
      if (!$email) {
        return new JsonResponse(['error' => 'Invalid email'], 422);
      }
      $result = $this->quizService->getResultByUuid($data['quiz_uuid']);
      if ($result) {
        $result->set('email', $email);
        $result->save();
        $this->quizService->createCrmLead($result);
        return new JsonResponse(['success' => TRUE]);
      }
      return new JsonResponse(['error' => 'Result not found'], 404);
    }

    // Validar campo perfil (obligatorio) + campos adaptativos.
    if (empty($data['perfil']) || !is_string($data['perfil'])) {
      return new JsonResponse(['error' => 'Missing field: perfil'], 422);
    }
    // Aceptar todos los campos enviados (Q2 y Q3 varían según perfil).
    $allowedFields = [
      'perfil', 'situacion', 'fase', 'sector', 'despacho', 'programa',
      'tipo_contenido', 'urgencia', 'necesidad_empresa', 'necesidad_legal',
      'objetivo_contenido',
    ];
    $answers = [];
    foreach ($data as $key => $value) {
      if (in_array($key, $allowedFields, TRUE) && is_string($value)) {
        $answers[$key] = $value;
      }
    }

    // Email opcional.
    $email = NULL;
    if (!empty($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
      $email = $data['email'];
    }

    // Calcular scores y recomendación.
    $scores = $this->quizService->calculateScores($answers);
    $recommendation = $this->quizService->getRecommendation($scores);
    $vertical = $recommendation['recommended'];

    // Persistir.
    $result = $this->quizService->saveResult(
      $answers,
      $scores,
      $vertical,
      $recommendation['alternatives'],
      $email,
    );

    // CRM lead (async-safe, no bloquea response).
    if ($email) {
      $this->quizService->createCrmLead($result);
    }

    // IA explanation (sync — tier fast < 1s).
    $aiExplanation = $this->quizService->generateAiExplanation($answers, $vertical);
    $result->set('ai_explanation', $aiExplanation);
    $result->save();

    $verticalData = $this->quizService->getVerticalPresentation($vertical);

    return new JsonResponse([
      'uuid' => $result->uuid(),
      'vertical' => $vertical,
      'vertical_title' => $verticalData['title'] ?? $vertical,
      'ai_explanation' => $aiExplanation,
      'alternatives' => $recommendation['alternatives'],
    ]);
  }

  /**
   * Página de resultado — ZERO-REGION-001: markup vacío, datos via preprocess.
   */

  /**
   * Página de resultado — ZERO-REGION-001: markup vacío, datos via preprocess.
   *
   * El UUID se pasa como drupalSettings para que preprocess_page pueda
   * cargar el QuizResult y preparar las variables del template.
   */
  public function resultPage(string $uuid): array {
    // Validar que el resultado existe (404 si no).
    $result = $this->quizService->getResultByUuid($uuid);
    if (!$result) {
      throw new NotFoundHttpException();
    }

    return [
      '#type' => 'markup',
      '#markup' => '',
      '#attached' => [
        'library' => ['ecosistema_jaraba_theme/route-quiz'],
      ],
    ];
  }

}
