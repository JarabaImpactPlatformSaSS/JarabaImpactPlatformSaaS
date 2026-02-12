<?php

declare(strict_types=1);

namespace Drupal\jaraba_sepe_teleformacion\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio SOAP para comunicación con el SEPE.
 *
 * Implementa las 6 operaciones requeridas por la Orden TMS/369/2019:
 * - ObtenerDatosCentro
 * - CrearAccion
 * - ObtenerListaAcciones
 * - ObtenerDatosAccion
 * - ObtenerParticipantes
 * - ObtenerSeguimiento
 */
class SepeSoapService
{

    /**
     * El gestor de tipos de entidad.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * El mapeador de datos.
     */
    protected SepeDataMapper $dataMapper;

    /**
     * La fábrica de configuración.
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * El logger.
     */
    protected LoggerInterface $logger;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        SepeDataMapper $data_mapper,
        ConfigFactoryInterface $config_factory,
        LoggerChannelInterface $logger
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->dataMapper = $data_mapper;
        $this->configFactory = $config_factory;
        $this->logger = $logger;
    }

    /**
     * Obtiene datos del centro de formación.
     *
     * @return array
     *   Estructura DatosCentro.
     */
    public function obtenerDatosCentro(): array
    {
        $config = $this->configFactory->get('jaraba_sepe_teleformacion.settings');
        $centro_id = $config->get('centro_activo_id');

        if (!$centro_id) {
            $this->logger->error('No hay centro SEPE configurado.');
            return ['error' => 'Centro no configurado'];
        }

        $datos = $this->dataMapper->mapearDatosCentro((int) $centro_id);
        $this->logger->info('ObtenerDatosCentro ejecutado para centro @id', ['@id' => $centro_id]);

        return $datos;
    }

    /**
     * Crea una nueva acción formativa.
     *
     * @param string $id_accion
     *   ID de la acción a crear.
     *
     * @return array
     *   Resultado de la operación.
     */
    public function crearAccion(string $id_accion): array
    {
        // Verificar si ya existe.
        $existentes = $this->entityTypeManager
            ->getStorage('sepe_accion_formativa')
            ->loadByProperties(['id_accion_sepe' => $id_accion]);

        if (!empty($existentes)) {
            return [
                'resultado' => 'ERROR',
                'mensaje' => 'La acción ya existe',
            ];
        }

        // La creación real se hace desde el admin UI.
        // Esta operación solo valida que el ID está disponible.
        $this->logger->info('CrearAccion: ID @id disponible', ['@id' => $id_accion]);

        return [
            'resultado' => 'OK',
            'mensaje' => 'ID disponible para creación',
        ];
    }

    /**
     * Obtiene la lista de IDs de acciones del centro.
     *
     * @return array
     *   Lista de IDs.
     */
    public function obtenerListaAcciones(): array
    {
        $config = $this->configFactory->get('jaraba_sepe_teleformacion.settings');
        $centro_id = $config->get('centro_activo_id');

        if (!$centro_id) {
            return [];
        }

        $ids = $this->dataMapper->obtenerListaAcciones((int) $centro_id);
        $this->logger->info('ObtenerListaAcciones: @count acciones', ['@count' => count($ids)]);

        return $ids;
    }

    /**
     * Obtiene datos de una acción formativa.
     *
     * @param string $id_accion
     *   ID de la acción SEPE.
     *
     * @return array
     *   Estructura DatosAccion.
     */
    public function obtenerDatosAccion(string $id_accion): array
    {
        $acciones = $this->entityTypeManager
            ->getStorage('sepe_accion_formativa')
            ->loadByProperties(['id_accion_sepe' => $id_accion]);

        if (empty($acciones)) {
            $this->logger->warning('ObtenerDatosAccion: Acción @id no encontrada', ['@id' => $id_accion]);
            return ['error' => 'Acción no encontrada'];
        }

        $accion = reset($acciones);
        $datos = $this->dataMapper->mapearDatosAccion((int) $accion->id());
        $this->logger->info('ObtenerDatosAccion ejecutado para @id', ['@id' => $id_accion]);

        return $datos;
    }

    /**
     * Obtiene participantes de una acción.
     *
     * @param string $id_accion
     *   ID de la acción SEPE.
     *
     * @return array
     *   Lista de participantes con seguimiento básico.
     */
    public function obtenerParticipantes(string $id_accion): array
    {
        $participantes = $this->dataMapper->obtenerParticipantesAccion($id_accion);
        $this->logger->info('ObtenerParticipantes: @count para acción @id', [
            '@count' => count($participantes),
            '@id' => $id_accion,
        ]);

        return $participantes;
    }

    /**
     * Obtiene seguimiento detallado de un participante.
     *
     * @param string $id_accion
     *   ID de la acción SEPE.
     * @param string $dni
     *   DNI del participante.
     *
     * @return array
     *   Estructura DatosSeguimiento.
     */
    public function obtenerSeguimiento(string $id_accion, string $dni): array
    {
        // Buscar la acción.
        $acciones = $this->entityTypeManager
            ->getStorage('sepe_accion_formativa')
            ->loadByProperties(['id_accion_sepe' => $id_accion]);

        if (empty($acciones)) {
            return ['error' => 'Acción no encontrada'];
        }

        $accion = reset($acciones);

        // Buscar el participante por DNI en esa acción.
        $participantes = $this->entityTypeManager
            ->getStorage('sepe_participante')
            ->loadByProperties([
                'accion_id' => $accion->id(),
                'dni' => $dni,
            ]);

        if (empty($participantes)) {
            $this->logger->warning('ObtenerSeguimiento: Participante @dni no encontrado en @accion', [
                '@dni' => $dni,
                '@accion' => $id_accion,
            ]);
            return ['error' => 'Participante no encontrado'];
        }

        $participante = reset($participantes);
        $datos = $this->dataMapper->mapearDatosSeguimiento((int) $participante->id(), TRUE);

        $this->logger->info('ObtenerSeguimiento ejecutado para @dni en @accion', [
            '@dni' => $dni,
            '@accion' => $id_accion,
        ]);

        return $datos;
    }

}
