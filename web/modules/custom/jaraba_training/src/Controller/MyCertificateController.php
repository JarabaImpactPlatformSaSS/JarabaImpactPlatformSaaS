<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\jaraba_training\Service\MethodRubricService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CERT-04: Página "Mi certificado" para el participante.
 * CERT-12: Directorio público de certificados con búsqueda.
 */
class MyCertificateController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return parent::create($container);
  }

  /**
   * CERT-04: Mi certificado — vista del participante.
   *
   * Muestra las certificaciones activas del usuario actual con código,
   * nivel, fecha de validez y enlace de descarga PDF.
   *
   * @return array<string, mixed>
   */
  public function myCertificates(): array {
    $user = $this->currentUser();
    $certificates = [];

    try {
      $storage = $this->entityTypeManager()->getStorage('user_certification');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('user_id', $user->id())
        ->sort('created', 'DESC')
        ->execute();

      foreach ($storage->loadMultiple($ids) as $cert) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $cert */
        $levelNum = (int) ($cert->get('overall_level')->value ?? 0);
        $levelName = MethodRubricService::LEVELS[$levelNum] ?? '';
        $status = $cert->get('certification_status')->value ?? 'in_progress';
        $validUntil = $cert->get('valid_until')->value ?? NULL;
        $isExpired = $validUntil !== NULL && strtotime($validUntil) < time();

        $certificates[] = [
          'id' => $cert->id(),
          'status' => $isExpired ? 'expired' : $status,
          'status_label' => match (TRUE) {
            $isExpired => $this->t('Expirado'),
            $status === 'completed' => $this->t('Activo'),
            $status === 'in_progress' => $this->t('En progreso'),
            default => $this->t('Pendiente'),
          },
          'level' => $levelNum,
          'level_name' => ucfirst($levelName),
          'certificate_number' => $cert->get('certificate_number')->value ?? '',
          'certification_date' => $cert->get('certification_date')->value ?? '',
          'valid_until' => $validUntil ?? '',
          'program_name' => $cert->get('program_id')->entity?->label() ?? '',
          'has_pdf' => ($cert->get('certificate_pdf_path')->value ?? '') !== '',
          'verify_url' => ($cert->get('certificate_number')->value !== NULL && $cert->get('certificate_number')->value !== '')
            ? '/certificado/' . $cert->get('certificate_number')->value
            : '',
        ];
      }
    }
    catch (\Throwable $e) {
      $this->getLogger('jaraba_certificacion')->error('MyCertificates error: @e', ['@e' => $e->getMessage()]);
    }

    return [
      '#theme' => 'my_certificates',
      '#certificates' => $certificates,
      '#user_name' => $user->getDisplayName(),
      '#cache' => [
        'contexts' => ['user'],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * CERT-12: Directorio público de certificados verificados.
   *
   * Lista de certificados activos con opción de búsqueda por código.
   * Solo muestra certificados con status=completed y consent implícito.
   *
   * @return array<string, mixed>
   */
  public function publicDirectory(): array {
    $certificates = [];
    $searchCode = \Drupal::request()->query->get('code', '');

    try {
      $storage = $this->entityTypeManager()->getStorage('user_certification');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('certification_status', 'completed')
        ->condition('certificate_number', '', '<>')
        ->sort('certification_date', 'DESC')
        ->range(0, 50);

      if (is_string($searchCode) && $searchCode !== '') {
        $query->condition('certificate_number', '%' . $searchCode . '%', 'LIKE');
      }

      $ids = $query->execute();
      $userStorage = $this->entityTypeManager()->getStorage('user');

      foreach ($storage->loadMultiple($ids) as $cert) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $cert */
        $userId = $cert->get('user_id')->target_id;
        $user = ($userId !== '' && $userId !== NULL) ? $userStorage->load($userId) : NULL;
        $levelNum = (int) ($cert->get('overall_level')->value ?? 0);

        $certificates[] = [
          'holder_name' => ($user !== NULL) ? $user->getDisplayName() : '',
          'code' => $cert->get('certificate_number')->value ?? '',
          'level' => $levelNum,
          'level_name' => ucfirst(MethodRubricService::LEVELS[$levelNum] ?? ''),
          'date' => $cert->get('certification_date')->value ?? '',
          'verify_url' => '/certificado/' . ($cert->get('certificate_number')->value ?? ''),
        ];
      }
    }
    catch (\Throwable $e) {
      $this->getLogger('jaraba_certificacion')->error('PublicDirectory error: @e', ['@e' => $e->getMessage()]);
    }

    return [
      '#theme' => 'certificate_directory',
      '#certificates' => $certificates,
      '#search_code' => $searchCode,
      '#cache' => [
        'contexts' => ['url.query_args:code'],
        'max-age' => 300,
      ],
    ];
  }

}
