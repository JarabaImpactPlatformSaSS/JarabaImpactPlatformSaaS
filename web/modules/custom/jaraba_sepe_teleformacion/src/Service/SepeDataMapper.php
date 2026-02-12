<?php

declare(strict_types=1);

namespace Drupal\jaraba_sepe_teleformacion\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Mapea datos de entidades Drupal al modelo de datos SEPE.
 *
 * Transforma entidades sepe_centro, sepe_accion_formativa y sepe_participante
 * a las estructuras XML requeridas por el Web Service SOAP del SEPE.
 */
class SepeDataMapper
{

    /**
     * El gestor de tipos de entidad.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * El calculador de seguimiento.
     */
    protected SepeSeguimientoCalculator $seguimientoCalculator;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        SepeSeguimientoCalculator $seguimiento_calculator
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->seguimientoCalculator = $seguimiento_calculator;
    }

    /**
     * Mapea un SepeCentro al formato DatosCentro SEPE.
     *
     * @param int $centro_id
     *   ID del centro.
     *
     * @return array
     *   Datos en formato SEPE.
     */
    public function mapearDatosCentro(int $centro_id): array
    {
        $centro = $this->entityTypeManager
            ->getStorage('sepe_centro')
            ->load($centro_id);

        if (!$centro) {
            return [];
        }

        return [
            'CIF' => $centro->get('cif')->value,
            'RazonSocial' => $centro->get('razon_social')->value,
            'CodigoCentro' => $centro->get('codigo_sepe')->value ?? '',
            'Direccion' => $centro->get('direccion')->value,
            'CodigoPostal' => $centro->get('codigo_postal')->value,
            'Municipio' => $centro->get('municipio')->value,
            'Provincia' => $centro->get('provincia')->value,
            'Telefono' => $centro->get('telefono')->value,
            'Email' => $centro->get('email')->value,
            'URLPlataforma' => $centro->get('url_plataforma')->value,
        ];
    }

    /**
     * Mapea una SepeAccionFormativa al formato DatosAccion SEPE.
     *
     * @param int $accion_id
     *   ID de la acción formativa.
     *
     * @return array
     *   Datos en formato SEPE.
     */
    public function mapearDatosAccion(int $accion_id): array
    {
        $accion = $this->entityTypeManager
            ->getStorage('sepe_accion_formativa')
            ->load($accion_id);

        if (!$accion) {
            return [];
        }

        // Contar participantes activos.
        $participantes = $this->entityTypeManager
            ->getStorage('sepe_participante')
            ->loadByProperties(['accion_id' => $accion_id]);

        // Mapear estado interno a código SEPE.
        $estadoMap = [
            'pendiente' => 'P',
            'autorizada' => 'P',
            'en_curso' => 'E',
            'finalizada' => 'F',
            'cancelada' => 'C',
        ];

        return [
            'IdAccion' => $accion->get('id_accion_sepe')->value,
            'CodigoEspecialidad' => $accion->get('codigo_especialidad')->value,
            'Denominacion' => $accion->get('denominacion')->value,
            'Modalidad' => $accion->get('modalidad')->value,
            'NumeroHoras' => (int) $accion->get('numero_horas')->value,
            'FechaInicio' => $accion->get('fecha_inicio')->value,
            'FechaFin' => $accion->get('fecha_fin')->value,
            'NumParticipantes' => count($participantes),
            'Estado' => $estadoMap[$accion->get('estado')->value] ?? 'P',
        ];
    }

    /**
     * Mapea un SepeParticipante al formato DatosSeguimiento SEPE.
     *
     * @param int $participante_id
     *   ID del participante.
     * @param bool $actualizar
     *   Si debe actualizar los datos de seguimiento antes de mapear.
     *
     * @return array
     *   Datos en formato SEPE.
     */
    public function mapearDatosSeguimiento(int $participante_id, bool $actualizar = TRUE): array
    {
        if ($actualizar) {
            $this->seguimientoCalculator->actualizarSeguimientoParticipante($participante_id);
        }

        $participante = $this->entityTypeManager
            ->getStorage('sepe_participante')
            ->load($participante_id);

        if (!$participante) {
            return [];
        }

        // Mapear estado interno a código SEPE.
        $estadoMap = [
            'activo' => 'A',
            'baja' => 'B',
            'finalizado' => 'F',
            'certificado' => 'C',
        ];

        return [
            'DNI' => $participante->get('dni')->value,
            'Nombre' => $participante->get('nombre')->value,
            'Apellidos' => $participante->get('apellidos')->value,
            'FechaAlta' => $participante->get('fecha_alta')->value,
            'FechaBaja' => $participante->get('fecha_baja')->value ?? '',
            'HorasConectado' => (float) $participante->get('horas_conectado')->value,
            'PorcentajeProgreso' => (int) $participante->get('porcentaje_progreso')->value,
            'NumActividadesRealizadas' => (int) $participante->get('num_actividades')->value,
            'NotaMedia' => $participante->get('nota_media')->value ?? '',
            'Estado' => $estadoMap[$participante->get('estado')->value] ?? 'A',
            'UltimaConexion' => $participante->get('ultima_conexion')->value ?? '',
        ];
    }

    /**
     * Obtiene la lista de IDs de acciones de un centro.
     *
     * @param int $centro_id
     *   ID del centro.
     *
     * @return array
     *   Lista de IDs de acciones SEPE.
     */
    public function obtenerListaAcciones(int $centro_id): array
    {
        $acciones = $this->entityTypeManager
            ->getStorage('sepe_accion_formativa')
            ->loadByProperties(['centro_id' => $centro_id]);

        $ids = [];
        foreach ($acciones as $accion) {
            $ids[] = $accion->get('id_accion_sepe')->value;
        }

        return $ids;
    }

    /**
     * Obtiene participantes de una acción por ID SEPE.
     *
     * @param string $id_accion_sepe
     *   ID de la acción SEPE.
     *
     * @return array
     *   Lista de datos de seguimiento.
     */
    public function obtenerParticipantesAccion(string $id_accion_sepe): array
    {
        // Buscar la acción por su ID SEPE.
        $acciones = $this->entityTypeManager
            ->getStorage('sepe_accion_formativa')
            ->loadByProperties(['id_accion_sepe' => $id_accion_sepe]);

        if (empty($acciones)) {
            return [];
        }

        $accion = reset($acciones);
        $participantes = $this->entityTypeManager
            ->getStorage('sepe_participante')
            ->loadByProperties(['accion_id' => $accion->id()]);

        $resultado = [];
        foreach ($participantes as $participante) {
            $resultado[] = $this->mapearDatosSeguimiento((int) $participante->id());
        }

        return $resultado;
    }

}
