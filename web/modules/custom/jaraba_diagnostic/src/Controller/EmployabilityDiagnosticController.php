<?php

declare(strict_types=1);

namespace Drupal\jaraba_diagnostic\Controller;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_diagnostic\Service\EmployabilityScoringService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Controller para el diagnostico express de empleabilidad.
 *
 * PROPOSITO:
 * Gestiona el flujo del diagnostico express de 3 preguntas:
 * 1. Landing con wizard (GET /empleabilidad/diagnostico)
 * 2. Procesar respuestas y guardar (POST /empleabilidad/diagnostico)
 * 3. Mostrar resultados por UUID (GET /empleabilidad/diagnostico/{uuid}/resultados)
 *
 * ESTRUCTURA:
 * - landing(): Renderiza el wizard de 3 pasos
 * - processAndShowResults(): Procesa respuestas, calcula score, guarda entidad
 * - showResults(): Muestra resultados por UUID (publico, con token anonimo)
 *
 * SPEC: 20260120b S3
 */
class EmployabilityDiagnosticController extends ControllerBase {

  /**
   * Servicio de scoring.
   */
  protected EmployabilityScoringService $scoringService;

  /**
   * Servicio UUID.
   */
  protected UuidInterface $uuidService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->scoringService = $container->get('jaraba_diagnostic.employability_scoring');
    $instance->uuidService = $container->get('uuid');
    return $instance;
  }

  /**
   * Renderiza la landing del diagnostico con wizard de 3 pasos.
   *
   * @return array
   *   Render array con template y libraries.
   */
  public function landing(): array {
    return [
      '#theme' => 'employability_diagnostic_landing',
      '#questions' => $this->getQuestions(),
      '#estimated_time' => '2 minutos',
      '#attached' => [
        'library' => [
          'jaraba_diagnostic/employability-diagnostic',
        ],
      ],
    ];
  }

  /**
   * Procesa las respuestas del wizard y redirige a resultados.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP con las respuestas.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con UUID de resultados o errores.
   */
  public function processAndShowResults(Request $request): JsonResponse {
    $content = $request->getContent();
    $data = json_decode($content, TRUE);

    if (!$data) {
      return new JsonResponse(['error' => $this->t('Datos invalidos.')], 400);
    }

    // Validar respuestas.
    $qLinkedin = (int) ($data['q_linkedin'] ?? 0);
    $qCvAts = (int) ($data['q_cv_ats'] ?? 0);
    $qEstrategia = (int) ($data['q_estrategia'] ?? 0);

    if ($qLinkedin < 1 || $qLinkedin > 5 || $qCvAts < 1 || $qCvAts > 5 || $qEstrategia < 1 || $qEstrategia > 5) {
      return new JsonResponse(['error' => $this->t('Las respuestas deben estar entre 1 y 5.')], 400);
    }

    // Calcular score y perfil.
    $result = $this->scoringService->calculate($qLinkedin, $qCvAts, $qEstrategia);

    // Crear token anonimo.
    $anonymousToken = bin2hex(random_bytes(16));

    // Guardar entidad.
    $storage = $this->entityTypeManager()->getStorage('employability_diagnostic');
    $diagnostic = $storage->create([
      'q_linkedin' => $qLinkedin,
      'q_cv_ats' => $qCvAts,
      'q_estrategia' => $qEstrategia,
      'score' => $result['score'],
      'profile_type' => $result['profile_type'],
      'primary_gap' => $result['primary_gap'],
      'anonymous_token' => $anonymousToken,
      'email_remarketing' => $data['email'] ?? NULL,
      'avatar_confirmed' => 'jobseeker',
    ]);

    // Asignar propietario si esta autenticado.
    if ($this->currentUser()->isAuthenticated()) {
      $diagnostic->setOwnerId((int) $this->currentUser()->id());
    }

    $diagnostic->save();

    return new JsonResponse([
      'uuid' => $diagnostic->uuid(),
      'token' => $anonymousToken,
      'results_url' => Url::fromRoute('jaraba_diagnostic.employability.results', [
        'uuid' => $diagnostic->uuid(),
      ])->toString(),
      'score' => $result['score'],
      'profile_type' => $result['profile_type'],
      'profile_label' => $result['profile_label'],
      'profile_description' => $result['profile_description'],
      'primary_gap' => $result['primary_gap'],
      'dimension_scores' => $result['dimension_scores'],
      'recommendations' => $result['recommendations'],
    ]);
  }

  /**
   * Muestra los resultados de un diagnostico por UUID.
   *
   * @param string $uuid
   *   UUID del diagnostico.
   *
   * @return array
   *   Render array con template de resultados.
   */
  public function showResults(string $uuid): array {
    $storage = $this->entityTypeManager()->getStorage('employability_diagnostic');
    $entities = $storage->loadByProperties(['uuid' => $uuid]);

    if (empty($entities)) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    $diagnostic = reset($entities);

    // Recalcular datos para la vista.
    $result = $this->scoringService->calculate(
      (int) $diagnostic->get('q_linkedin')->value,
      (int) $diagnostic->get('q_cv_ats')->value,
      (int) $diagnostic->get('q_estrategia')->value
    );

    return [
      '#theme' => 'employability_diagnostic_results',
      '#diagnostic' => $diagnostic,
      '#score' => $result['score'],
      '#profile_type' => $result['profile_type'],
      '#profile_label' => $result['profile_label'],
      '#profile_description' => $result['profile_description'],
      '#primary_gap' => $result['primary_gap'],
      '#dimension_scores' => $result['dimension_scores'],
      '#recommendations' => $result['recommendations'],
      '#attached' => [
        'library' => [
          'jaraba_diagnostic/employability-diagnostic',
        ],
      ],
    ];
  }

  /**
   * Define las 3 preguntas del diagnostico express.
   *
   * @return array
   *   Array de preguntas con opciones de respuesta.
   */
  protected function getQuestions(): array {
    return [
      [
        'id' => 'q_linkedin',
        'title' => $this->t('Tu presencia en LinkedIn'),
        'description' => $this->t('Evalua el estado actual de tu perfil profesional en LinkedIn.'),
        'options' => [
          1 => $this->t('No tengo perfil o esta vacio'),
          2 => $this->t('Tengo perfil basico sin optimizar'),
          3 => $this->t('Perfil con foto y experiencia basica'),
          4 => $this->t('Perfil completo con recomendaciones'),
          5 => $this->t('Perfil optimizado con marca personal activa'),
        ],
        'icon' => 'linkedin',
      ],
      [
        'id' => 'q_cv_ats',
        'title' => $this->t('Tu CV y compatibilidad ATS'),
        'description' => $this->t('Los sistemas ATS filtran el 75% de los CVs automaticamente.'),
        'options' => [
          1 => $this->t('No tengo CV actualizado'),
          2 => $this->t('CV basico sin formato profesional'),
          3 => $this->t('CV con formato pero sin keywords'),
          4 => $this->t('CV con keywords y logros cuantificados'),
          5 => $this->t('CV optimizado ATS con multiples versiones por sector'),
        ],
        'icon' => 'cv',
      ],
      [
        'id' => 'q_estrategia',
        'title' => $this->t('Tu estrategia de busqueda'),
        'description' => $this->t('Una busqueda de empleo efectiva requiere planificacion y constancia.'),
        'options' => [
          1 => $this->t('Envio CVs a cualquier oferta sin criterio'),
          2 => $this->t('Busco en portales sin seguimiento'),
          3 => $this->t('Tengo alertas y aplico regularmente'),
          4 => $this->t('Combino portales, networking y candidaturas espontaneas'),
          5 => $this->t('Estrategia multicanal con seguimiento y personal branding'),
        ],
        'icon' => 'strategy',
      ],
    ];
  }

}
