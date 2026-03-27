<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_training\Service\MethodRubricService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Verificación pública de certificados Método Jaraba.
 *
 * CERT-11: URL pública /certificado/{codigo} que muestra nombre, tipo,
 * nivel, fecha, estado (válido/expirado). Sin datos sensibles.
 *
 * Ruta pública accesible sin autenticación.
 */
class CertificateVerificationController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return parent::create($container);
  }

  /**
   * Muestra la verificación pública de un certificado.
   *
   * @param string $code
   *   Código de verificación (ej: MJ-2026-00042).
   *
   * @return array<string, mixed>
   *   Render array con datos públicos del certificado.
   */
  public function verify(string $code): array {
    $storage = $this->entityTypeManager()->getStorage('user_certification');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('certificate_number', $code)
      ->range(0, 1)
      ->execute();

    if ($ids === []) {
      throw new NotFoundHttpException();
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $cert */
    $cert = $storage->load(reset($ids));
    if ($cert === NULL) { // @phpstan-ignore identical.alwaysFalse
      throw new NotFoundHttpException();
    }

    $userId = $cert->get('user_id')->target_id;
    $user = ($userId !== '') ? $this->entityTypeManager()->getStorage('user')->load($userId) : NULL;

    $status = $cert->get('certification_status')->value ?? 'unknown';
    $validUntil = $cert->get('valid_until')->value ?? NULL;
    $isExpired = $validUntil !== NULL && strtotime($validUntil) < time();

    $levelNum = (int) ($cert->get('overall_level')->value ?? 0);
    $levelName = MethodRubricService::LEVELS[$levelNum] ?? '';

    return [
      '#theme' => 'certificate_verification',
      '#certificate' => [
        'code' => $code,
        'holder_name' => ($user !== NULL) ? $user->getDisplayName() : $this->t('Titular'),
        'status' => $isExpired ? 'expired' : $status,
        'status_label' => $isExpired
          ? $this->t('Expirado')
          : ($status === 'completed' ? $this->t('Válido') : $this->t('En progreso')),
        'level' => $levelNum,
        'level_name' => ucfirst($levelName),
        'certification_date' => $cert->get('certification_date')->value ?? '',
        'valid_until' => $validUntil ?? '',
        'program_name' => $cert->get('program_id')->entity?->label() ?? '',
      ],
      '#cache' => [
        'contexts' => ['url.path'],
        'max-age' => 3600,
      ],
    ];
  }

}
