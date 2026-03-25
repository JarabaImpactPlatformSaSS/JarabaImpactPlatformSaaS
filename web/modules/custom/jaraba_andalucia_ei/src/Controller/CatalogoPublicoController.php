<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\CatalogoPacksService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public catalog page for participant's published packs.
 *
 * ZERO-REGION-001: Returns minimal markup; data via preprocess.
 * Route: /catalogo/{slug} (public, no auth required).
 */
class CatalogoPublicoController extends ControllerBase {

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    protected CatalogoPacksService $catalogoService,
    protected LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jaraba_andalucia_ei.catalogo_packs'),
      $container->get('logger.channel.jaraba_andalucia_ei'),
    );
  }

  /**
   * Renders the public catalog page for a participant.
   *
   * @param string $slug
   *   URL slug identifying the pack.
   *
   * @return array
   *   Render array.
   */
  public function ver(string $slug): array {
    $pack = $this->catalogoService->getPackPorSlug($slug);

    if ($pack === NULL) {
      throw new NotFoundHttpException();
    }

    return [
      '#theme' => 'catalogo_publico',
      '#pack' => $pack,
    ];
  }

  /**
   * Title callback for the catalog page.
   */
  public function title(string $slug): string {
    $pack = $this->catalogoService->getPackPorSlug($slug);
    if ($pack !== NULL) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $pack */
      $titulo = $pack->get('titulo_personalizado')->value;
      return is_string($titulo) ? $titulo : (string) $this->t('Catálogo de servicios');
    }
    return (string) $this->t('Catálogo de servicios');
  }

}
