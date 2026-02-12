<?php

/**
 * @file
 * Callbacks para allowed_values de campos list_string configurables.
 *
 * Directriz Nuclear #20: Mandato de Configurabilidad Zero-Code.
 * Prohibido el "Hardcoding de Supervivencia". Todo valor debe nacer
 * de un token configurable.
 *
 * @see Drupal\jaraba_training\Entity\TrainingProduct
 * @see Drupal\jaraba_training\Entity\CertificationProgram
 * @see Drupal\jaraba_training\Entity\UserCertification
 */

use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Obtiene los tipos de producto configurables.
 *
 * Callback para allowed_values_function del campo product_type.
 *
 * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $definition
 *   Definición del campo.
 *
 * @return array
 *   Array asociativo de valores permitidos.
 */
function jaraba_training_allowed_product_types(FieldStorageDefinitionInterface $definition): array {
  return _jaraba_training_get_allowed_values('product_types');
}

/**
 * Obtiene los tipos de facturación configurables.
 *
 * Callback para allowed_values_function del campo billing_type.
 *
 * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $definition
 *   Definición del campo.
 *
 * @return array
 *   Array asociativo de valores permitidos.
 */
function jaraba_training_allowed_billing_types(FieldStorageDefinitionInterface $definition): array {
  return _jaraba_training_get_allowed_values('billing_types');
}

/**
 * Obtiene los tipos de certificación configurables.
 *
 * Callback para allowed_values_function del campo certification_type.
 *
 * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $definition
 *   Definición del campo.
 *
 * @return array
 *   Array asociativo de valores permitidos.
 */
function jaraba_training_allowed_certification_types(FieldStorageDefinitionInterface $definition): array {
  return _jaraba_training_get_allowed_values('certification_types');
}

/**
 * Obtiene los estados de certificación de usuario configurables.
 *
 * Callback para allowed_values_function del campo certification_status.
 *
 * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $definition
 *   Definición del campo.
 *
 * @return array
 *   Array asociativo de valores permitidos.
 */
function jaraba_training_allowed_certification_statuses(FieldStorageDefinitionInterface $definition): array {
  return _jaraba_training_get_allowed_values('certification_statuses');
}

/**
 * Helper interno para obtener valores de configuración.
 *
 * @param string $key
 *   Clave de configuración (product_types, billing_types, etc.).
 *
 * @return array
 *   Array asociativo de valores permitidos.
 */
function _jaraba_training_get_allowed_values(string $key): array {
  $config = \Drupal::config('jaraba_training.allowed_values');
  $values = $config->get($key);

  if (empty($values) || !is_array($values)) {
    // Fallback vacío si la configuración no existe.
    \Drupal::logger('jaraba_training')->warning(
      'No se encontraron allowed_values para @key. Revise jaraba_training.allowed_values.yml',
      ['@key' => $key]
    );
    return [];
  }

  return $values;
}
