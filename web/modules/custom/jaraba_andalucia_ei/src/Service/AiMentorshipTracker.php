<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para tracking de horas de mentoría con el Copiloto IA.
 *
 * Contabiliza las sesiones de interacción con el Tutor IA y las convierte
 * en horas computables para el programa Andalucía +ei.
 */
class AiMentorshipTracker
{

    /**
     * Constructor del servicio.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   Entity type manager.
     * @param \Drupal\Core\Database\Connection $database
     *   Conexión a la base de datos.
     * @param \Psr\Log\LoggerInterface $logger
     *   Logger del módulo.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected Connection $database,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Registra una sesión de interacción con el Tutor IA.
     *
     * @param int $participante_id
     *   ID del participante Andalucía +ei.
     * @param float $duracion_minutos
     *   Duración de la sesión en minutos.
     * @param string $tipo_interaccion
     *   Tipo: 'chat', 'canvas', 'diagnostic'.
     *
     * @return bool
     *   TRUE si la sesión se registró correctamente.
     */
    public function registrarSesionIa(int $participante_id, float $duracion_minutos, string $tipo_interaccion = 'chat'): bool
    {
        try {
            $participante = $this->entityTypeManager
                ->getStorage('programa_participante_ei')
                ->load($participante_id);

            if (!$participante) {
                $this->logger->warning('Participante no encontrado: @id', ['@id' => $participante_id]);
                return FALSE;
            }

            // Obtener configuración de horas.
            $config = \Drupal::config('jaraba_andalucia_ei.settings');
            $horasPorSesion = (float) ($config->get('horas_por_sesion_ia') ?? 0.25);
            $maximoHorasDia = (float) ($config->get('maximo_horas_ia_dia') ?? 4);

            // Calcular horas de la sesión (mínimo 15 minutos = $horasPorSesion).
            $horasSesion = max($horasPorSesion, $duracion_minutos / 60);

            // Verificar límite diario.
            $horasHoy = $this->getHorasIaHoy($participante_id);
            if ($horasHoy + $horasSesion > $maximoHorasDia) {
                $horasSesion = max(0, $maximoHorasDia - $horasHoy);
                $this->logger->notice('Límite diario alcanzado para participante @id', ['@id' => $participante_id]);
            }

            if ($horasSesion <= 0) {
                return FALSE;
            }

            // Actualizar horas acumuladas.
            $horasActuales = (float) ($participante->get('horas_mentoria_ia')->value ?? 0);
            $participante->set('horas_mentoria_ia', $horasActuales + $horasSesion);
            $participante->save();

            // Registrar en tabla de auditoría.
            $this->registrarEnAuditoria($participante_id, $horasSesion, $tipo_interaccion);

            $this->logger->info('Sesión IA registrada: @horas h para participante @id', [
                '@horas' => number_format($horasSesion, 2),
                '@id' => $participante_id,
            ]);

            return TRUE;
        } catch (\Exception $e) {
            $this->logger->error('Error registrando sesión IA: @message', ['@message' => $e->getMessage()]);
            return FALSE;
        }
    }

    /**
     * Obtiene las horas de mentoría IA registradas hoy para un participante.
     *
     * @param int $participante_id
     *   ID del participante.
     *
     * @return float
     *   Horas IA registradas hoy.
     */
    protected function getHorasIaHoy(int $participante_id): float
    {
        $result = $this->database->select('jaraba_andalucia_ei_ia_audit', 'a')
            ->fields('a', ['horas'])
            ->condition('participante_id', $participante_id)
            ->condition('fecha', date('Y-m-d'), '=')
            ->execute()
            ->fetchCol();

        return array_sum($result);
    }

    /**
     * Registra la sesión en la tabla de auditoría.
     *
     * @param int $participante_id
     *   ID del participante.
     * @param float $horas
     *   Horas a registrar.
     * @param string $tipo
     *   Tipo de interacción.
     */
    protected function registrarEnAuditoria(int $participante_id, float $horas, string $tipo): void
    {
        $this->database->insert('jaraba_andalucia_ei_ia_audit')
            ->fields([
                'participante_id' => $participante_id,
                'horas' => $horas,
                'tipo_interaccion' => $tipo,
                'fecha' => date('Y-m-d'),
                'timestamp' => \Drupal::time()->getRequestTime(),
            ])
            ->execute();
    }

    /**
     * Obtiene el resumen de horas IA para un participante.
     *
     * @param int $participante_id
     *   ID del participante.
     *
     * @return array
     *   Array con total_horas, sesiones_count, ultima_sesion.
     */
    public function getResumenHorasIa(int $participante_id): array
    {
        $query = $this->database->select('jaraba_andalucia_ei_ia_audit', 'a')
            ->condition('participante_id', $participante_id);

        $query->addExpression('SUM(horas)', 'total_horas');
        $query->addExpression('COUNT(*)', 'sesiones_count');
        $query->addExpression('MAX(timestamp)', 'ultima_sesion');

        $result = $query->execute()->fetchAssoc();

        return [
            'total_horas' => (float) ($result['total_horas'] ?? 0),
            'sesiones_count' => (int) ($result['sesiones_count'] ?? 0),
            'ultima_sesion' => $result['ultima_sesion'] ? (int) $result['ultima_sesion'] : NULL,
        ];
    }

}
