<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Lead magnet — Guía del Participante Andalucía +ei.
 *
 * Renderiza una página de descarga de la guía del participante
 * que captura email para enrolamiento en secuencia SEQ_AEI_006.
 */
class GuiaParticipanteController extends ControllerBase {

  /**
   * The email sequence service (optional).
   */
  protected ?object $sequenceService;

  /**
   * The logger.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static();
    $instance->sequenceService = $container->has('jaraba_messaging.sequence_enrollment')
      ? $container->get('jaraba_messaging.sequence_enrollment')
      : NULL;
    $instance->logger = $container->get('logger.channel.jaraba_andalucia_ei');
    return $instance;
  }

  /**
   * Renders the guide download page.
   *
   * @return array
   *   Render array.
   */
  public function guia(): array {
    $solicitarUrl = Url::fromRoute('jaraba_andalucia_ei.solicitar')->toString();

    return [
      '#theme' => 'andalucia_ei_guia_participante',
      '#solicitar_url' => $solicitarUrl,
      '#attached' => [
        'library' => [
          'jaraba_andalucia_ei/guia',
        ],
      ],
      '#cache' => [
        'contexts' => ['url.path'],
        'tags' => ['config:jaraba_andalucia_ei.settings'],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Handles the guia download API request.
   *
   * Validates email, enrolls in sequence SEQ_AEI_006 (if available),
   * and returns JSON response.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response.
   */
  public function handleGuiaDownload(Request $request): JsonResponse {
    $content = $request->getContent();
    $data = json_decode($content, TRUE);

    if (!is_array($data)) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Invalid request.'),
      ], 400);
    }

    $nombre = trim($data['nombre'] ?? '');
    $email = trim($data['email'] ?? '');

    // Validate name.
    if (empty($nombre)) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('El nombre es obligatorio.'),
      ], 422);
    }

    // Validate email.
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Introduce un email válido.'),
      ], 422);
    }

    // Enroll in sequence SEQ_AEI_006 if service is available.
    if ($this->sequenceService) {
      try {
        $this->sequenceService->enroll($email, 'SEQ_AEI_006', [
          'nombre' => $nombre,
          'source' => 'guia_participante',
        ]);
      }
      catch (\Exception $e) {
        $this->logger->warning('Sequence enrollment failed for @email: @msg', [
          '@email' => $email,
          '@msg' => $e->getMessage(),
        ]);
        // Non-blocking — still return success.
      }
    }

    $this->logger->info('Guia download requested: @nombre (@email)', [
      '@nombre' => $nombre,
      '@email' => $email,
    ]);

    return new JsonResponse([
      'success' => TRUE,
      'message' => $this->t('¡Guía enviada! Revisa tu bandeja de correo en los próximos minutos.'),
    ]);
  }

}
