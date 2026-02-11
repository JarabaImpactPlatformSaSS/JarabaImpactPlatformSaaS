<?php

namespace Drupal\jaraba_events\Exception;

/**
 * Excepción cuando un evento no acepta registros.
 *
 * Estructura: Excepción de dominio que extiende RuntimeException.
 *
 * Lógica: Se lanza al intentar registrarse en un evento cuyo
 *   status_event no es 'registration_open' o cuya fecha ya pasó.
 *
 * Sintaxis: PHP estándar — herencia de \RuntimeException.
 */
class EventNotOpenException extends \RuntimeException {

}
