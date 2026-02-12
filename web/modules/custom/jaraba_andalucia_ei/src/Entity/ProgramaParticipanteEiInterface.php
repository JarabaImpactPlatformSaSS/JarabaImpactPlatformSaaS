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
     *   Colectivo: jovenes, mayores_45, larga_duracion.
     */
    public function getColectivo(): string;

    /**
     * Obtiene la fase actual del participante.
     *
     * @return string
     *   Fase PIIL: atencion, insercion, baja.
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

}
