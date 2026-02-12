<?php

/**
 * @file
 * Callbacks para allowed_values de campos del CRM.
 *
 * Siguiendo la Directriz #20 (Zero-Code Configurability), los valores
 * se cargan desde jaraba_crm.allowed_values.yml.
 *
 * @see jaraba_crm.allowed_values.yml
 */

declare(strict_types=1);

use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Carga valores desde el YAML de configuración.
 *
 * @param string $key
 *   Clave del grupo de valores en el YAML.
 *
 * @return array
 *   Array asociativo de valores permitidos.
 */
function _jaraba_crm_load_allowed_values(string $key): array {
  static $cache = [];

  if (!isset($cache[$key])) {
    $module_path = \Drupal::service('extension.list.module')->getPath('jaraba_crm');
    $yaml_path = $module_path . '/jaraba_crm.allowed_values.yml';

    if (file_exists($yaml_path)) {
      $yaml_content = file_get_contents($yaml_path);
      $all_values = \Symfony\Component\Yaml\Yaml::parse($yaml_content);
      $cache[$key] = $all_values[$key] ?? [];
    }
    else {
      $cache[$key] = [];
    }
  }

  return $cache[$key];
}

/**
 * Callback para obtener valores de industria.
 *
 * @param \Drupal\Core\Field\FieldStorageDefinitionInterface|null $definition
 *   Definición del campo (opcional para llamadas directas).
 *
 * @return array
 *   Valores permitidos para industria.
 */
function jaraba_crm_get_industry_values(?FieldStorageDefinitionInterface $definition = NULL): array {
  return _jaraba_crm_load_allowed_values('industry');
}

/**
 * Callback para obtener valores de tamaño de empresa.
 *
 * @param \Drupal\Core\Field\FieldStorageDefinitionInterface|null $definition
 *   Definición del campo (opcional para llamadas directas).
 *
 * @return array
 *   Valores permitidos para tamaño.
 */
function jaraba_crm_get_size_values(?FieldStorageDefinitionInterface $definition = NULL): array {
  return _jaraba_crm_load_allowed_values('size');
}

/**
 * Callback para obtener valores de etapas de oportunidad.
 *
 * @param \Drupal\Core\Field\FieldStorageDefinitionInterface|null $definition
 *   Definición del campo (opcional para llamadas directas).
 *
 * @return array
 *   Valores permitidos para etapas del pipeline.
 */
function jaraba_crm_get_opportunity_stage_values(?FieldStorageDefinitionInterface $definition = NULL): array {
  return _jaraba_crm_load_allowed_values('opportunity_stage');
}

/**
 * Callback para obtener valores de tipo de actividad.
 *
 * @param \Drupal\Core\Field\FieldStorageDefinitionInterface|null $definition
 *   Definición del campo (opcional para llamadas directas).
 *
 * @return array
 *   Valores permitidos para tipos de actividad.
 */
function jaraba_crm_get_activity_type_values(?FieldStorageDefinitionInterface $definition = NULL): array {
  return _jaraba_crm_load_allowed_values('activity_type');
}

/**
 * Callback para obtener valores de fuente de contacto.
 *
 * @param \Drupal\Core\Field\FieldStorageDefinitionInterface|null $definition
 *   Definición del campo (opcional para llamadas directas).
 *
 * @return array
 *   Valores permitidos para fuentes de contacto.
 */
function jaraba_crm_get_contact_source_values(?FieldStorageDefinitionInterface $definition = NULL): array {
  return _jaraba_crm_load_allowed_values('contact_source');
}

/**
 * Callback para obtener valores de presupuesto BANT.
 */
function jaraba_crm_get_bant_budget_values(?FieldStorageDefinitionInterface $definition = NULL): array {
  return _jaraba_crm_load_allowed_values('bant_budget');
}

/**
 * Callback para obtener valores de autoridad BANT.
 */
function jaraba_crm_get_bant_authority_values(?FieldStorageDefinitionInterface $definition = NULL): array {
  return _jaraba_crm_load_allowed_values('bant_authority');
}

/**
 * Callback para obtener valores de necesidad BANT.
 */
function jaraba_crm_get_bant_need_values(?FieldStorageDefinitionInterface $definition = NULL): array {
  return _jaraba_crm_load_allowed_values('bant_need');
}

/**
 * Callback para obtener valores de timeline BANT.
 */
function jaraba_crm_get_bant_timeline_values(?FieldStorageDefinitionInterface $definition = NULL): array {
  return _jaraba_crm_load_allowed_values('bant_timeline');
}

