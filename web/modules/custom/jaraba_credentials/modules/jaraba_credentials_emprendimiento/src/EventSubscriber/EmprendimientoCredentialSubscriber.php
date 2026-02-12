<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials_emprendimiento\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_credentials_emprendimiento\Service\EmprendimientoCredentialService;
use Drupal\jaraba_credentials_emprendimiento\Service\EmprendimientoExpertiseService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

/**
 * Event subscriber para integración de credenciales con gamificación.
 *
 * Se suscribe a KernelEvents::TERMINATE para evaluar diplomas compuestos
 * después de cada request donde se pudo haber emitido una credencial.
 */
class EmprendimientoCredentialSubscriber implements EventSubscriberInterface {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected EmprendimientoCredentialService $credentialService;
  protected EmprendimientoExpertiseService $expertiseService;
  protected LoggerInterface $logger;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    EmprendimientoCredentialService $credentialService,
    EmprendimientoExpertiseService $expertiseService,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->credentialService = $credentialService;
    $this->expertiseService = $expertiseService;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::TERMINATE => ['onTerminate', -100],
    ];
  }

  /**
   * Evalúa diplomas compuestos al finalizar la petición.
   */
  public function onTerminate(TerminateEvent $event): void {
    // Solo procesar si hay credenciales recién emitidas en este request.
    $newCredentials = &drupal_static('jaraba_credentials_emprendimiento_new_credentials', []);
    if (empty($newCredentials)) {
      return;
    }

    foreach ($newCredentials as $uid) {
      try {
        $this->credentialService->evaluateDiplomas((int) $uid);
      }
      catch (\Exception $e) {
        $this->logger->error('Error evaluando diplomas para usuario #@uid: @msg', [
          '@uid' => $uid,
          '@msg' => $e->getMessage(),
        ]);
      }
    }

    $newCredentials = [];
  }

}
