<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\EiMatchingBridgeService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Portal empresa: vista anonimizada de participantes compatibles.
 *
 * Sprint 9 — Plan Maestro Andalucía +ei Clase Mundial.
 *
 * NO muestra: nombre, DNI, datos personales (RGPD).
 * SÍ muestra: perfil competencial, sector, nivel experiencia,
 * disponibilidad geográfica, tipo inserción.
 */
class EmpresaCandidatosController extends ControllerBase {

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    protected LoggerInterface $logger,
    protected ?EiMatchingBridgeService $matchingBridge = NULL,
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.channel.jaraba_andalucia_ei'),
      $container->has('jaraba_andalucia_ei.ei_matching_bridge')
        ? $container->get('jaraba_andalucia_ei.ei_matching_bridge')
        : NULL,
    );
  }

  /**
   * Listado de candidatos anonimizados para una empresa.
   */
  public function listado(string $prospeccion_empresarial): Response {
    $prospeccionId = (int) $prospeccion_empresarial;

    // Verificar que la prospección existe y es colaboradora.
    try {
      $prospeccion = $this->entityTypeManager
        ->getStorage('prospeccion_empresarial')
        ->load($prospeccionId);

      if (!$prospeccion) {
        throw new NotFoundHttpException('Empresa no encontrada.');
      }

      $estado = $prospeccion->get('estado')->value ?? '';
      if ($estado !== 'colaborador') {
        throw new NotFoundHttpException('La empresa no tiene acceso a candidatos.');
      }
    }
    catch (NotFoundHttpException $e) {
      throw $e;
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error cargando prospección @id: @msg', [
        '@id' => $prospeccionId,
        '@msg' => $e->getMessage(),
      ]);
      throw new NotFoundHttpException('Error al cargar datos de empresa.');
    }

    $candidatos = [];
    if ($this->matchingBridge) {
      try {
        $candidatos = $this->matchingBridge->getMatchesPorEmpresa($prospeccionId, 20);
      }
      catch (\Throwable $e) {
        $this->logger->warning('Error matching empresa @id: @msg', [
          '@id' => $prospeccionId,
          '@msg' => $e->getMessage(),
        ]);
      }
    }

    $data = [
      'empresa' => $prospeccion->label() ?? '',
      'candidatos' => $candidatos,
      'total' => count($candidatos),
    ];

    return new Response(
      json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
      200,
      ['Content-Type' => 'application/json'],
    );
  }

}
