<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Servicio para gestionar transiciones de fase PIIL de participantes.
 *
 * Fases según Doc 45:
 * - Atención: Fase inicial, recibiendo orientación y formación.
 * - Inserción: Ha encontrado empleo, seguimiento activo.
 * - Baja: Abandono o finalización del programa.
 */
class FaseTransitionManager
{

    /**
     * Constructor del servicio.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   Entity type manager.
     * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $eventDispatcher
     *   Event dispatcher.
     * @param \Psr\Log\LoggerInterface $logger
     *   Logger del módulo.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected EventDispatcherInterface $eventDispatcher,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Verifica y ejecuta la transición de fase si procede.
     *
     * @param int $participante_id
     *   ID del participante.
     * @param string $nueva_fase
     *   Nueva fase: 'atencion', 'insercion', 'baja'.
     * @param array $contexto
     *   Datos adicionales del contexto (tipo_insercion, fecha, etc.).
     *
     * @return array
     *   Array con 'success' (bool) y 'message' (string).
     */
    public function transitarFase(int $participante_id, string $nueva_fase, array $contexto = []): array
    {
        try {
            $participante = $this->entityTypeManager
                ->getStorage('programa_participante_ei')
                ->load($participante_id);

            if (!$participante) {
                return [
                    'success' => FALSE,
                    'message' => t('Participante no encontrado.'),
                ];
            }

            $fase_actual = $participante->getFaseActual();

            // Validar transición permitida.
            $transiciones_validas = [
                'atencion' => ['insercion', 'baja'],
                'insercion' => ['baja'],
                'baja' => [],
            ];

            if (!in_array($nueva_fase, $transiciones_validas[$fase_actual] ?? [])) {
                return [
                    'success' => FALSE,
                    'message' => t('Transición no permitida de @from a @to.', [
                        '@from' => $fase_actual,
                        '@to' => $nueva_fase,
                    ]),
                ];
            }

            // Validar requisitos para transición a Inserción.
            if ($nueva_fase === 'insercion') {
                if (!$participante->canTransitToInsercion()) {
                    return [
                        'success' => FALSE,
                        'message' => t('No cumple requisitos mínimos: 10h orientación + 50h formación.'),
                    ];
                }

                // Verificar datos de inserción.
                if (empty($contexto['tipo_insercion'])) {
                    return [
                        'success' => FALSE,
                        'message' => t('Debe especificar el tipo de inserción.'),
                    ];
                }

                $participante->set('tipo_insercion', $contexto['tipo_insercion']);
                $participante->set('fecha_insercion', $contexto['fecha_insercion'] ?? date('Y-m-d'));
            }

            // Ejecutar transición.
            $participante->setFaseActual($nueva_fase);
            $participante->save();

            $this->logger->info('Transición de fase: Participante @id de @from a @to', [
                '@id' => $participante_id,
                '@from' => $fase_actual,
                '@to' => $nueva_fase,
            ]);

            return [
                'success' => TRUE,
                'message' => t('Transición completada correctamente.'),
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error en transición de fase: @message', ['@message' => $e->getMessage()]);
            return [
                'success' => FALSE,
                'message' => t('Error interno: @error', ['@error' => $e->getMessage()]),
            ];
        }
    }

    /**
     * Obtiene los participantes que pueden transitar a Inserción.
     *
     * @return array
     *   Array de IDs de participantes elegibles.
     */
    public function getParticipantesElegiblesInsercion(): array
    {
        $participantes = $this->entityTypeManager
            ->getStorage('programa_participante_ei')
            ->loadByProperties(['fase_actual' => 'atencion']);

        $elegibles = [];
        foreach ($participantes as $participante) {
            if ($participante->canTransitToInsercion()) {
                $elegibles[] = $participante->id();
            }
        }

        return $elegibles;
    }

}
