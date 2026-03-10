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
     * Obtiene la fecha de pago del incentivo económico.
     *
     * @return string|null
     *   Fecha en formato 'Y-m-d' o NULL si no se ha pagado.
     */
    public function getIncentivoFechaPago(): ?string;

    /**
     * Indica si el participante ha renunciado al incentivo económico.
     *
     * @return bool
     *   TRUE si ha renunciado al incentivo de €528.
     */
    public function hasRenunciadoIncentivo(): bool;

    /**
     * Obtiene la fecha de renuncia al incentivo económico.
     *
     * @return string|null
     *   Fecha en formato 'Y-m-d' o NULL si no ha renunciado.
     */
    public function getIncentivoRenunciaFecha(): ?string;

    /**
     * Indica si el Acuerdo de Participación bilateral ha sido firmado.
     *
     * Documento oficial: Acuerdo_participacion_ICV25.odt
     *
     * @return bool
     *   TRUE si el participante ha firmado el Acuerdo de Participación.
     */
    public function isAcuerdoParticipacionFirmado(): bool;

    /**
     * Obtiene la fecha de firma del Acuerdo de Participación.
     *
     * @return string|null
     *   Fecha Y-m-d o NULL si no firmado.
     */
    public function getAcuerdoParticipacionFecha(): ?string;

    /**
     * Indica si el DACI (Documento de Aceptación de Compromisos e Información) ha sido firmado.
     *
     * Documento oficial: Anexo_DACI_ICV25.odt
     * DISTINTO del Acuerdo de Participación.
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

    /**
     * Obtiene la puntuación del diagnóstico DIME.
     *
     * @return int|null
     *   Score 0-20 o NULL si no se ha completado.
     */
    public function getDimeScore(): ?int;

    /**
     * Obtiene las horas de orientación específicas para inserción.
     *
     * @return float
     *   Horas acumuladas (de las 40h requeridas para módulo inserción).
     */
    public function getHorasOrientacionInsercion(): float;

    /**
     * Obtiene el porcentaje de asistencia calculado.
     *
     * @return float
     *   Porcentaje 0-100.
     */
    public function getAsistenciaPorcentaje(): float;

    /**
     * Indica si cumple requisitos de persona atendida (módulo económico 3.500€).
     *
     * Requisitos: ≥10h orientación (≥2h individual) + ≥50h formación + ≥75% asistencia.
     *
     * @return bool
     *   TRUE si cumple.
     */
    public function isPersonaAtendida(): bool;

    /**
     * Indica si cumple requisitos de persona insertada (módulo económico 2.500€).
     *
     * Requisitos: persona atendida + ≥40h orientación inserción + ≥4 meses alta SS.
     *
     * @return bool
     *   TRUE si cumple.
     */
    public function isPersonaInsertada(): bool;

    /**
     * Indica si el participante es alumni del programa.
     *
     * @return bool
     *   TRUE si completó el programa y forma parte del Club Alumni.
     */
    public function isAlumni(): bool;

    /**
     * Obtiene el ID del CandidateProfile cross-vertical.
     *
     * @return int|null
     *   ID en jaraba_candidate o NULL si no vinculado.
     */
    public function getCandidateProfileId(): ?int;

    /**
     * Obtiene el ID del BusinessModelCanvas cross-vertical.
     *
     * @return int|null
     *   ID en jaraba_business_tools o NULL si no vinculado (solo carril Acelera).
     */
    public function getCanvasId(): ?int;

}
