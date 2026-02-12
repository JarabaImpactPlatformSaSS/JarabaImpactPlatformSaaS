<?php

declare(strict_types=1);

namespace Drupal\jaraba_sepe_teleformacion\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_sepe_teleformacion\Service\SepeDataMapper;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * REST resource for SEPE participantes.
 *
 * @RestResource(
 *   id = "sepe_participantes_resource",
 *   label = @Translation("SEPE Participantes"),
 *   uri_paths = {
 *     "canonical" = "/api/sepe/participantes/{id}",
 *     "collection" = "/api/sepe/acciones/{accion_id}/participantes"
 *   }
 * )
 */
class SepeParticipantesResource extends ResourceBase
{

    /**
     * The entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * The data mapper.
     */
    protected SepeDataMapper $dataMapper;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        array $serializer_formats,
        LoggerInterface $logger,
        EntityTypeManagerInterface $entity_type_manager,
        SepeDataMapper $data_mapper
    ) {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
        $this->entityTypeManager = $entity_type_manager;
        $this->dataMapper = $data_mapper;
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
            $container->get('entity_type.manager'),
            $container->get('jaraba_sepe_teleformacion.data_mapper')
        );
    }

    /**
     * Responds to GET requests.
     *
     * @param string|null $id
     *   The participante ID or NULL for collection.
     *
     * @return \Drupal\rest\ResourceResponse
     *   The HTTP response.
     */
    public function get(?string $id = NULL): ResourceResponse
    {
        if ($id !== NULL) {
            return $this->getParticipante($id);
        }

        // For collection, need accion_id from route.
        $accion_id = \Drupal::routeMatch()->getParameter('accion_id');
        if ($accion_id) {
            return $this->getParticipantesByAccion($accion_id);
        }

        return new ResourceResponse(['error' => 'accion_id required'], 400);
    }

    /**
     * Get participantes by accion.
     */
    protected function getParticipantesByAccion(string $accion_id): ResourceResponse
    {
        $storage = $this->entityTypeManager->getStorage('sepe_participante');

        $ids = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('accion_id', $accion_id)
            ->sort('fecha_alta', 'DESC')
            ->execute();

        $participantes = $storage->loadMultiple($ids);
        $data = [];

        foreach ($participantes as $participante) {
            $data[] = $this->serializeParticipante($participante);
        }

        return new ResourceResponse([
            'accion_id' => $accion_id,
            'participantes' => $data,
            'total' => count($data),
        ]);
    }

    /**
     * Get single participante.
     */
    protected function getParticipante(string $id): ResourceResponse
    {
        $storage = $this->entityTypeManager->getStorage('sepe_participante');
        $participante = $storage->load($id);

        if (!$participante) {
            throw new NotFoundHttpException("Participante $id no encontrado");
        }

        $response = new ResourceResponse($this->serializeParticipante($participante, TRUE));
        $response->addCacheableDependency($participante);

        return $response;
    }

    /**
     * Serialize participante entity.
     */
    protected function serializeParticipante($participante, bool $detailed = FALSE): array
    {
        $data = [
            'id' => $participante->id(),
            'dni' => $participante->get('dni')->value,
            'nombre' => $participante->get('nombre')->value,
            'apellidos' => $participante->get('apellidos')->value,
            'estado' => $participante->get('estado')->value,
            'fecha_alta' => $participante->get('fecha_alta')->value,
        ];

        if ($detailed) {
            $data['fecha_baja'] = $participante->get('fecha_baja')->value ?? NULL;
            $data['horas_conectado'] = (float) ($participante->get('horas_conectado')->value ?? 0);
            $data['porcentaje_progreso'] = (int) ($participante->get('porcentaje_progreso')->value ?? 0);
            $data['num_actividades'] = (int) ($participante->get('num_actividades')->value ?? 0);
            $data['nota_media'] = (float) ($participante->get('nota_media')->value ?? 0);
            $data['ultima_conexion'] = $participante->get('ultima_conexion')->value ?? NULL;
            $data['apto'] = (bool) ($participante->get('apto')->value ?? FALSE);
        }

        return $data;
    }

}
