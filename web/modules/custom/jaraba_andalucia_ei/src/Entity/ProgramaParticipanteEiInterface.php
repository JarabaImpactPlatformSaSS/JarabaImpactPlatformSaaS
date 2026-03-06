<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface para la entidad ProgramaParticipanteEi.
 *
 * Define los métodos de acceso a los datos del participante del programa
 * Andalucía +ei según la especificación Doc 45.
 */
interface ProgramaParticipanteEiInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface
{

    /**
     * Obtiene el DNI/NIE del participante.
     *
     * @return string
     *   Documento identificativo del participante.
     */
    public function getDniNie(): string;

    /**
     * Establece el DNI/NIE del participante.
     *
     * @param string $dni_nie
     *   Documento identificativo.
     *
     * @return $this
     */
    public function setDniNie(string $dni_nie): self;

    /**
     * Obtiene el colectivo del participante.
     *
     * @return string
     *   Colectivo: larga_duracion, mayores_45, migrantes, perceptores_prestaciones.
     */
    public function getColectivo(): string;

    /**
     * Obtiene la fase actual del participante.
     *
     * @return string
     *   Fase PIIL: acogida, diagnostico, atencion, insercion, seguimiento, baja.
     */
    public function getFaseActual(): string;

    /**
     * Establece la fase actual del participante.
     *
     * @param string $fase
     *   Nueva fase PIIL.
     *
     * @return $this
     */
    public function setFaseActual(string $fase): self;

    /**
     * Obtiene el total de horas de mentoría IA.
     *
     * @return float
     *   Horas acumuladas con el Tutor IA.
     */
    public function getHorasMentoriaIa(): float;

    /**
     * Obtiene el total de horas de mentoría humana.
     *
     * @return float
     *   Horas acumuladas con mentor humano.
     */
    public function getHorasMentoriaHumana(): float;

    /**
     * Obtiene el total de horas de orientación (individual + grupal).
     *
     * @return float
     *   Suma de horas de orientación.
     */
    public function getTotalHorasOrientacion(): float;

    /**
     * Verifica si el participante puede transitar a fase Inserción.
     *
     * Según Doc 45 § 4.3: mínimo 10h orientación + 50h formación.
     *
     * @return bool
     *   TRUE si cumple requisitos mínimos.
     */
    public function canTransitToInsercion(): bool;

    /**
     * Verifica si el participante ha recibido el incentivo económico.
     *
     * @return bool
     *   TRUE si ha recibido el incentivo de €528.
     */
    public function hasReceivedIncentivo(): bool;

    /**
     * Indica si el DACI ha sido firmado.
     *
     * @return bool
     *   TRUE si el participante ha firmado el DACI.
     */
    public function isDaciFirmado(): bool;

    /**
     * Indica si los indicadores FSE+ de entrada están completados.
     *
     * @return bool
     *   TRUE si la recogida de datos FSE+ de entrada está completa.
     */
    public function isFseEntradaCompletado(): bool;

    /**
     * Obtiene la semana actual del participante en el programa.
     *
     * @return int
     *   Número de semana (0 si no ha iniciado).
     */
    public function getSemanaActual(): int;

    /**
     * Obtiene el motivo de baja del participante.
     *
     * @return string
     *   Motivo de baja o cadena vacía si no aplica.
     */
    public function getMotivoBaja(): string;

}
