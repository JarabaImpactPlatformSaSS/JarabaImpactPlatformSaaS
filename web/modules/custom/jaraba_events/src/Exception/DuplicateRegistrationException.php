<?php

namespace Drupal\jaraba_events\Exception;

/**
 * Excepción cuando un usuario ya está registrado en un evento.
 *
 * Estructura: Excepción de dominio que extiende RuntimeException.
 *
 * Lógica: Se lanza al intentar crear un registro duplicado
 *   para el mismo usuario y evento (email + event_id ya existen).
 *
 * Sintaxis: PHP estándar — herencia de \RuntimeException.
 */
class DuplicateRegistrationException extends \RuntimeException {

}
