<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\PortfolioEntregablesService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public portfolio page for a programme participant.
 */
class PortfolioPublicoController extends ControllerBase {

  /**
   * The portfolio entregables service.
   */
  protected PortfolioEntregablesService $portfolioEntregables;

  /**
   * The logger channel.
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a PortfolioPublicoController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\jaraba_andalucia_ei\Service\PortfolioEntregablesService $portfolioEntregables
   *   The portfolio entregables service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    PortfolioEntregablesService $portfolioEntregables,
    LoggerInterface $logger,
  ) {
    // CONTROLLER-READONLY-001: ControllerBase::$entityTypeManager has no type
    // declaration. Do NOT use readonly in constructor promotion.
    $this->entityTypeManager = $entityTypeManager;
    $this->portfolioEntregables = $portfolioEntregables;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jaraba_andalucia_ei.portfolio_entregables'),
      $container->get('logger.channel.jaraba_andalucia_ei'),
    );
  }

  /**
   * Renders the public portfolio for a participant.
   *
   * @param int $participante_id
   *   The programme participant entity ID.
   *
   * @return array
   *   A render array.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   If the participant entity does not exist.
   */
  public function ver(int $participante_id): array {
    $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
    $participante = $storage->load($participante_id);

    if ($participante === NULL) {
      throw new NotFoundHttpException();
    }

    return [
      '#theme' => 'portfolio_publico',
      '#entregables' => $this->portfolioEntregables->getPortfolio($participante_id),
      '#progreso' => $this->portfolioEntregables->getProgreso($participante_id),
    ];
  }

  /**
   * Title callback for the portfolio page.
   *
   * @param int $participante_id
   *   The programme participant entity ID.
   *
   * @return string
   *   The page title.
   */
  public function title(int $participante_id): string {
    $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
    $participante = $storage->load($participante_id);

    if ($participante !== NULL) {
      $label = $participante->label();
      if ($label !== NULL) {
        return (string) $this->t('Portfolio de @name', ['@name' => $label]);
      }
    }

    return (string) $this->t('Portfolio del participante');
  }

}
