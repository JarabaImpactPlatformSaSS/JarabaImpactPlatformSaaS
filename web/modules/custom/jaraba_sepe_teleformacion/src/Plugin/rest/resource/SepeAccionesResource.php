<?php

declare(strict_types=1);

namespace Drupal\jaraba_sepe_teleformacion\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * REST resource for SEPE acciones formativas.
 *
 * @RestResource(
 *   id = "sepe_acciones_resource",
 *   label = @Translation("SEPE Acciones Formativas"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/sepe/acciones/{id}",
 *     "collection" = "/api/v1/sepe/acciones"
 *   }
 * )
 */
class SepeAccionesResource extends ResourceBase
{

    /**
     * The entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        array $serializer_formats,
        LoggerInterface $logger,
        EntityTypeManagerInterface $entity_type_manager
    ) {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
        $this->entityTypeManager = $entity_type_manager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->getParameter('serializer.formats'),
            $container->get('logger.channel.sepe_teleformacion'),
            $container->get('entity_type.manager')
        );
    }

    /**
     * Responds to GET requests for a collection.
     *
     * @return \Drupal\rest\ResourceResponse
     *   The HTTP response.
     */
    public function get(?string $id = NULL): ResourceResponse
    {
        if ($id !== NULL) {
            return $this->getAccion($id);
        }

        return $this->getAcciones();
    }

    /**
     * Get all acciones.
     */
    protected function getAcciones(): ResourceResponse
    {
        $storage = $this->entityTypeManager->getStorage('sepe_accion_formativa');
        $ids = $storage->getQuery()
            ->accessCheck(TRUE)
            ->sort('created', 'DESC')
            ->range(0, 100)
            ->execute();

        $acciones = $storage->loadMultiple($ids);
        $data = [];

        foreach ($acciones as $accion) {
            $data[] = $this->serializeAccion($accion);
        }

        $response = new ResourceResponse(['acciones' => $data, 'total' => count($data)]);
        $response->addCacheableDependency($storage);

        return $response;
    }

    /**
     * Get single accion.
     */
    protected function getAccion(string $id): ResourceResponse
    {
        $storage = $this->entityTypeManager->getStorage('sepe_accion_formativa');
        $accion = $storage->load($id);

        if (!$accion) {
            throw new NotFoundHttpException("Acción $id no encontrada");
        }

        $response = new ResourceResponse($this->serializeAccion($accion));
        $response->addCacheableDependency($accion);

        return $response;
    }

    /**
     * Serialize accion entity.
     */
    protected function serializeAccion($accion): array
    {
        // Count participantes.
        $numParticipantes = $this->entityTypeManager
            ->getStorage('sepe_participante')
            ->getQuery()
            ->accessCheck(FALSE)
            ->condition('accion_id', $accion->id())
            ->count()
            ->execute();

        return [
            'id' => $accion->id(),
            'id_accion_sepe' => $accion->get('id_accion_sepe')->value,
            'denominacion' => $accion->get('denominacion')->value,
            'codigo_especialidad' => $accion->get('codigo_especialidad')->value ?? '',
            'modalidad' => $accion->get('modalidad')->value,
            'numero_horas' => (int) $accion->get('numero_horas')->value,
            'fecha_inicio' => $accion->get('fecha_inicio')->value,
            'fecha_fin' => $accion->get('fecha_fin')->value,
            'estado' => $accion->get('estado')->value,
            'num_participantes' => (int) $numParticipantes,
        ];
    }

}
