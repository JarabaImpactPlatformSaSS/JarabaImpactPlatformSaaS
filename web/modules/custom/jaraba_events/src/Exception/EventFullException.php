<?php

namespace Drupal\jaraba_events\Exception;

/**
 * Excepción cuando un evento ha alcanzado su aforo máximo.
 *
 * Estructura: Excepción de dominio que extiende RuntimeException.
 *
 * Lógica: Se lanza al intentar registrar un asistente cuando
 *   current_attendees >= max_attendees en el evento.
 *
 * Sintaxis: PHP estándar — herencia de \RuntimeException.
 */
class EventFullException extends \RuntimeException {

}
