<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Servicio para gestionar transiciones de fase PIIL de participantes.
 *
 * 6 fases del itinerario PIIL CV 2025 según normativa:
 * acogida → diagnóstico → atención → inserción → seguimiento → baja
 *
 * Cada transición tiene prerrequisitos normativos verificables.
 */
class FaseTransitionManager
{

    /**
     * Las 6 fases canónicas del itinerario PIIL CV 2025.
     */
    public const FASES = [
        'acogida',
        'diagnostico',
        'atencion',
        'insercion',
        'seguimiento',
        'baja',
    ];

    /**
     * Mapa de transiciones válidas entre fases.
     *
     * Cada fase lista las fases destino permitidas.
     * 'baja' es absorbente desde cualquier fase activa.
     */
    private const TRANSICIONES_VALIDAS = [
        'acogida' => ['diagnostico', 'baja'],
        'diagnostico' => ['atencion', 'baja'],
        'atencion' => ['insercion', 'baja'],
        'insercion' => ['seguimiento', 'baja'],
        'seguimiento' => ['baja'],
        'baja' => [],
    ];

    /**
     * Constructor del servicio.
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
     *   Nueva fase destino.
     * @param array $contexto
     *   Datos adicionales (tipo_insercion, fecha, motivo_baja, etc.).
     *
     * @return array{success: bool, message: string}
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
            if (!in_array($nueva_fase, self::TRANSICIONES_VALIDAS[$fase_actual] ?? [])) {
                return [
                    'success' => FALSE,
                    'message' => t('Transición no permitida de @from a @to.', [
                        '@from' => $fase_actual,
                        '@to' => $nueva_fase,
                    ]),
                ];
            }

            // Verificar prerrequisitos por fase destino.
            $checkResult = $this->verificarPrerrequisitos($participante, $nueva_fase, $contexto);
            if ($checkResult !== NULL) {
                return $checkResult;
            }

            // Aplicar datos específicos de la transición.
            $this->aplicarDatosTransicion($participante, $nueva_fase, $contexto);

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
                'message' => t('Transición completada de @from a @to.', [
                    '@from' => $fase_actual,
                    '@to' => $nueva_fase,
                ]),
            ];
        }
        catch (\Throwable $e) {
            $this->logger->error('Error en transición de fase: @message', ['@message' => $e->getMessage()]);
            return [
                'success' => FALSE,
                'message' => t('Error interno: @error', ['@error' => $e->getMessage()]),
            ];
        }
    }

    /**
     * Verifica prerrequisitos normativos para la fase destino.
     *
     * @return array|null
     *   NULL si cumple, array de error si no cumple.
     */
    protected function verificarPrerrequisitos(object $participante, string $nueva_fase, array $contexto): ?array
    {
        switch ($nueva_fase) {
            case 'diagnostico':
                // Prerrequisito: DACI firmado.
                if (method_exists($participante, 'isDaciFirmado') && !$participante->isDaciFirmado()) {
                    return [
                        'success' => FALSE,
                        'message' => t('El DACI debe estar firmado antes de pasar a Diagnóstico.'),
                    ];
                }
                break;

            case 'atencion':
                // Prerrequisito: carril asignado (DIME completado).
                $carril = $participante->get('carril')->value ?? '';
                if ($carril === '') {
                    return [
                        'success' => FALSE,
                        'message' => t('Debe completar el diagnóstico DIME y asignar carril antes de pasar a Atención.'),
                    ];
                }
                break;

            case 'insercion':
                // Prerrequisito: mínimo 10h orientación + 50h formación.
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
                break;

            case 'seguimiento':
                // Prerrequisito: datos FSE+ de salida recogidos.
                if (method_exists($participante, 'get')) {
                    $fseSalida = (bool) ($participante->get('fse_salida_completado')->value ?? FALSE);
                    if (!$fseSalida) {
                        return [
                            'success' => FALSE,
                            'message' => t('Los indicadores FSE+ de salida deben estar completados.'),
                        ];
                    }
                }
                break;

            case 'baja':
                // Prerrequisito: motivo de baja especificado.
                if (empty($contexto['motivo_baja'])) {
                    return [
                        'success' => FALSE,
                        'message' => t('Debe especificar el motivo de baja.'),
                    ];
                }
                break;
        }

        return NULL;
    }

    /**
     * Aplica datos específicos de la transición en la entidad.
     */
    protected function aplicarDatosTransicion(object $participante, string $nueva_fase, array $contexto): void
    {
        switch ($nueva_fase) {
            case 'insercion':
                $participante->set('tipo_insercion', $contexto['tipo_insercion']);
                $participante->set('fecha_insercion', $contexto['fecha_insercion'] ?? date('Y-m-d'));
                break;

            case 'baja':
                $participante->set('motivo_baja', $contexto['motivo_baja'] ?? 'otro');
                $participante->set('fecha_fin_programa', date('Y-m-d'));
                break;
        }
    }

    /**
     * Comprueba si un participante puede transitar a fase Inserción.
     *
     * @param mixed $participant
     *   La entidad participante.
     *
     * @return bool
     *   TRUE si cumple los requisitos mínimos.
     */
    public function canTransitToInsercion($participant): bool
    {
        try {
            if (method_exists($participant, 'canTransitToInsercion')) {
                return $participant->canTransitToInsercion();
            }

            // Fallback: comprobar horas manualmente.
            $horasOrientacion = (float) ($participant->get('horas_orientacion_ind')->value ?? 0)
                + (float) ($participant->get('horas_orientacion_grup')->value ?? 0)
                + (float) ($participant->get('horas_mentoria_ia')->value ?? 0)
                + (float) ($participant->get('horas_mentoria_humana')->value ?? 0);
            $horasFormacion = (float) ($participant->get('horas_formacion')->value ?? 0);

            return $horasOrientacion >= 10 && $horasFormacion >= 50;
        }
        catch (\Throwable $e) {
            return FALSE;
        }
    }

    /**
     * Obtiene las fases destino válidas desde una fase dada.
     *
     * @param string $fase_actual
     *   Fase de origen.
     *
     * @return array
     *   Fases destino permitidas.
     */
    public function getTransicionesValidas(string $fase_actual): array
    {
        return self::TRANSICIONES_VALIDAS[$fase_actual] ?? [];
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
