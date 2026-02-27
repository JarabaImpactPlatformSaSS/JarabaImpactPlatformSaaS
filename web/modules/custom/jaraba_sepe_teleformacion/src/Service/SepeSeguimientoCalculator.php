<?php

declare(strict_types=1);

namespace Drupal\jaraba_sepe_teleformacion\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Calcula métricas de seguimiento para participantes SEPE.
 *
 * Agrega datos desde progress_record y enrollment para generar
 * los campos requeridos por el modelo de datos SEPE.
 */
class SepeSeguimientoCalculator
{

    /**
     * El gestor de tipos de entidad.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * La conexión a base de datos.
     */
    protected Connection $database;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        Connection $database
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->database = $database;
    }

    /**
     * Calcula las horas de conexión de un participante.
     *
     * @param int $enrollment_id
     *   El ID de la matrícula.
     *
     * @return float
     *   Total de horas conectado.
     */
    public function calcularHorasConectado(int $enrollment_id): float
    {
        // Sumar duración de todos los progress_record del enrollment.
        $query = $this->database->select('progress_record', 'pr')
            ->condition('pr.enrollment_id', $enrollment_id);
        $query->addExpression('SUM(pr.duration_seconds)', 'total_seconds');
        $result = $query->execute()->fetchField();

        return $result ? round((float) $result / 3600, 2) : 0.0;
    }

    /**
     * Calcula el porcentaje de progreso.
     *
     * @param int $enrollment_id
     *   El ID de la matrícula.
     *
     * @return int
     *   Porcentaje de progreso (0-100).
     */
    public function calcularPorcentajeProgreso(int $enrollment_id): int
    {
        // Obtener progreso del enrollment.
        $enrollment = $this->entityTypeManager
            ->getStorage('lms_enrollment')
            ->load($enrollment_id);

        if ($enrollment && $enrollment->hasField('progress_percent')) {
            return (int) $enrollment->get('progress_percent')->value;
        }

        return 0;
    }

    /**
     * Cuenta las actividades realizadas.
     *
     * @param int $enrollment_id
     *   El ID de la matrícula.
     *
     * @return int
     *   Número de actividades completadas.
     */
    public function contarActividadesRealizadas(int $enrollment_id): int
    {
        $query = $this->database->select('progress_record', 'pr')
            ->condition('pr.enrollment_id', $enrollment_id)
            ->condition('pr.completed', 1);
        $query->addExpression('COUNT(*)', 'count');

        return (int) $query->execute()->fetchField();
    }

    /**
     * Calcula la nota media de evaluaciones.
     *
     * @param int $enrollment_id
     *   El ID de la matrícula.
     *
     * @return float|null
     *   Nota media o NULL si no hay evaluaciones.
     */
    public function calcularNotaMedia(int $enrollment_id): ?float
    {
        $query = $this->database->select('progress_record', 'pr')
            ->condition('pr.enrollment_id', $enrollment_id)
            ->isNotNull('pr.score');
        $query->addExpression('AVG(pr.score)', 'avg_score');
        $result = $query->execute()->fetchField();

        return $result ? round((float) $result, 2) : NULL;
    }

    /**
     * Obtiene la fecha de última conexión.
     *
     * @param int $enrollment_id
     *   El ID de la matrícula.
     *
     * @return string|null
     *   Fecha ISO o NULL.
     */
    public function obtenerUltimaConexion(int $enrollment_id): ?string
    {
        $query = $this->database->select('progress_record', 'pr')
            ->condition('pr.enrollment_id', $enrollment_id)
            ->orderBy('pr.timestamp', 'DESC')
            ->range(0, 1);
        $query->addField('pr', 'timestamp');
        $result = $query->execute()->fetchField();

        return $result ? date('c', (int) $result) : NULL;
    }

    /**
     * Actualiza todos los datos de seguimiento de un participante.
     *
     * @param int $participante_id
     *   El ID del sepe_participante.
     */
    public function actualizarSeguimientoParticipante(int $participante_id): void
    {
        $participante = $this->entityTypeManager
            ->getStorage('sepe_participante')
            ->load($participante_id);

        if (!$participante) {
            return;
        }

        $enrollment_id = $participante->get('enrollment_id')->target_id;
        if (!$enrollment_id) {
            return;
        }

        $participante->set('horas_conectado', $this->calcularHorasConectado($enrollment_id));
        $participante->set('porcentaje_progreso', $this->calcularPorcentajeProgreso($enrollment_id));
        $participante->set('num_actividades', $this->contarActividadesRealizadas($enrollment_id));
        $participante->set('nota_media', $this->calcularNotaMedia($enrollment_id));

        $ultimaConexion = $this->obtenerUltimaConexion($enrollment_id);
        if ($ultimaConexion) {
            $participante->set('ultima_conexion', $ultimaConexion);
        }

        $participante->save();
    }

}
