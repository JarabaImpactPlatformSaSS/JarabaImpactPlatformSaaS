<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador del Lead Magnet: Diagnostico Legal Gratuito.
 *
 * Plan Elevacion JarabaLex Clase Mundial v1 — Fase 0.
 * Ruta publica /jarabalex/diagnostico-legal que ofrece un diagnostico
 * automatizado de situacion juridica como lead magnet de conversion.
 *
 * Arquitectura:
 * - Zero-region pattern (ZERO-REGION-001).
 * - Formulario renderizado via Twig, procesado via API endpoint.
 * - Resultado generado con reglas (placeholder para IA futura).
 */
class LegalLandingController extends ControllerBase {

  public function __construct(
    protected readonly LoggerChannelInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('logger.channel.jaraba_legal_intelligence'),
    );
  }

  /**
   * Pagina del diagnostico legal gratuito (lead magnet).
   *
   * @return array
   *   Render array con el formulario de diagnostico.
   */
  public function diagnostico(): array {
    return [
      '#theme' => 'legal_diagnostico',
      '#attached' => [
        'library' => ['jaraba_legal_intelligence/legal.diagnostico'],
      ],
    ];
  }

  /**
   * API: Procesar formulario de diagnostico legal.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La solicitud HTTP con datos del formulario.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Resultado del diagnostico con recomendaciones.
   */
  public function processDiagnostico(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);

    if (empty($data['area_legal']) || empty($data['situacion'])) {
      return new JsonResponse([
        'error' => $this->t('Los campos area legal y situacion son obligatorios.')->__toString(),
      ], 400);
    }

    $areaLegal = $data['area_legal'];
    $situacion = $data['situacion'];
    $urgencia = $data['urgencia'] ?? 'media';
    $email = $data['email'] ?? NULL;

    $result = $this->generateDiagnostico($areaLegal, $situacion, $urgencia);

    if ($email) {
      $this->logger->info('Lead magnet diagnostico: @email — area: @area', [
        '@email' => $email,
        '@area' => $areaLegal,
      ]);
    }

    return new JsonResponse(['data' => $result]);
  }

  /**
   * Genera el diagnostico legal basado en reglas.
   *
   * @param string $areaLegal
   *   Area legal seleccionada.
   * @param string $situacion
   *   Descripcion de la situacion.
   * @param string $urgencia
   *   Nivel de urgencia (baja/media/alta/critica).
   *
   * @return array
   *   Resultado del diagnostico con score, recomendaciones y CTA.
   */
  protected function generateDiagnostico(string $areaLegal, string $situacion, string $urgencia): array {
    $areaRecommendations = [
      'civil' => [
        'score' => 72,
        'resumen' => 'Su situacion en derecho civil requiere atencion profesional para proteger sus intereses patrimoniales.',
        'recomendaciones' => [
          'Documente todos los acuerdos por escrito con fecha y firma.',
          'Revise los plazos de prescripcion aplicables a su caso (art. 1964 CC).',
          'Considere la mediacion como via previa al litigio.',
        ],
      ],
      'penal' => [
        'score' => 85,
        'resumen' => 'Las situaciones penales requieren actuacion inmediata con asistencia letrada especializada.',
        'recomendaciones' => [
          'Contacte con un abogado penalista de forma inmediata.',
          'No realice declaraciones sin asistencia letrada (art. 520 LECrim).',
          'Recopile toda la documentacion y pruebas disponibles.',
        ],
      ],
      'laboral' => [
        'score' => 68,
        'resumen' => 'Los conflictos laborales tienen plazos estrictos. Actue con diligencia para no perder derechos.',
        'recomendaciones' => [
          'Verifique el plazo de 20 dias habiles para impugnar despido (art. 59.3 ET).',
          'Solicite la papeleta de conciliacion ante el SMAC.',
          'Conserve nominas, contrato y comunicaciones con la empresa.',
        ],
      ],
      'mercantil' => [
        'score' => 65,
        'resumen' => 'Las cuestiones mercantiles afectan a la actividad empresarial. Revise contratos y cumplimiento normativo.',
        'recomendaciones' => [
          'Revise las clausulas de responsabilidad y jurisdiccion en sus contratos.',
          'Verifique el cumplimiento de la Ley de Sociedades de Capital.',
          'Considere la inclusion de clausulas de arbitraje.',
        ],
      ],
      'administrativo' => [
        'score' => 70,
        'resumen' => 'Los procedimientos administrativos tienen plazos tasados. La inaccion puede consolidar actos desfavorables.',
        'recomendaciones' => [
          'Interponga recurso de alzada/reposicion dentro del plazo de 1 mes (art. 122 LPACAP).',
          'Solicite la suspension del acto si causa perjuicios de dificil reparacion.',
          'Documente todas las comunicaciones con la Administracion.',
        ],
      ],
      'familia' => [
        'score' => 75,
        'resumen' => 'El derecho de familia afecta a relaciones personales y patrimoniales. Busque siempre el acuerdo.',
        'recomendaciones' => [
          'Considere el procedimiento de mutuo acuerdo para reducir costes y plazos.',
          'Proteja los intereses de los menores como prioridad absoluta.',
          'Documente ingresos y patrimonio para las medidas economicas.',
        ],
      ],
    ];

    $result = $areaRecommendations[$areaLegal] ?? [
      'score' => 60,
      'resumen' => 'Su situacion requiere un analisis mas detallado por un profesional juridico.',
      'recomendaciones' => [
        'Consulte con un abogado especializado en su area.',
        'Recopile toda la documentacion relevante.',
        'Anote fechas y plazos importantes.',
      ],
    ];

    $urgencyMultiplier = match ($urgencia) {
      'critica' => 1.3,
      'alta' => 1.15,
      'media' => 1.0,
      'baja' => 0.85,
      default => 1.0,
    };

    $result['score'] = min(100, (int) round($result['score'] * $urgencyMultiplier));
    $result['area_legal'] = $areaLegal;
    $result['urgencia'] = $urgencia;
    $result['cta'] = [
      'text' => 'Activa tu cuenta gratuita en JarabaLex',
      'url' => '/jarabalex',
      'secondary_text' => 'Busca jurisprudencia relevante para tu caso',
      'secondary_url' => '/legal/search',
    ];

    return $result;
  }

}
