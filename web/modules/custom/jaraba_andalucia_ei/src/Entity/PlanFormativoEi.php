<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad PlanFormativoEi.
 *
 * Representa un plan formativo del programa Andalucia +ei que agrupa
 * acciones formativas por carril. Los campos de horas y cumplimiento
 * son stored computed fields recalculados en preSave() para ser
 * consultables via Views/EntityQuery.
 *
 * @ContentEntityType(
 *   id = "plan_formativo_ei",
 *   label = @Translation("Plan Formativo"),
 *   label_collection = @Translation("Planes Formativos"),
 *   label_singular = @Translation("plan formativo"),
 *   label_plural = @Translation("planes formativos"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_andalucia_ei\PlanFormativoEiListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\PlanFormativoEiForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\PlanFormativoEiForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\PlanFormativoEiForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_andalucia_ei\Access\PlanFormativoEiAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "plan_formativo_ei",
 *   admin_permission = "administer andalucia ei",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "titulo",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/planes-formativos-ei/{plan_formativo_ei}",
 *     "add-form" = "/admin/content/planes-formativos-ei/add",
 *     "edit-form" = "/admin/content/planes-formativos-ei/{plan_formativo_ei}/edit",
 *     "delete-form" = "/admin/content/planes-formativos-ei/{plan_formativo_ei}/delete",
 *     "collection" = "/admin/content/planes-formativos-ei",
 *   },
 *   field_ui_base_route = "entity.plan_formativo_ei.settings",
 * )
 */
class PlanFormativoEi extends ContentEntityBase implements PlanFormativoEiInterface, EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * Tipos de accion formativa que computan como orientacion.
   */
  private const ORIENTACION_TIPOS = ['orientacion', 'tutoria'];

  /**
   * {@inheritdoc}
   */
  public function getTitulo(): string {
    return $this->get('titulo')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getCarril(): string {
    return $this->get('carril')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getEstado(): string {
    return $this->get('estado')->value ?? 'borrador';
  }

  /**
   * {@inheritdoc}
   */
  public function getHorasFormacionPrevistas(): float {
    return (float) ($this->get('horas_formacion_previstas')->value ?? 0);
  }

  /**
   * {@inheritdoc}
   */
  public function getHorasOrientacionPrevistas(): float {
    return (float) ($this->get('horas_orientacion_previstas')->value ?? 0);
  }

  /**
   * {@inheritdoc}
   */
  public function getHorasTotalesPrevistas(): float {
    return (float) ($this->get('horas_totales_previstas')->value ?? 0);
  }

  /**
   * {@inheritdoc}
   */
  public function cumpleMinimosFormacion(): bool {
    return (bool) ($this->get('cumple_minimo_formacion')->value ?? FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function cumpleMinimosOrientacion(): bool {
    return (bool) ($this->get('cumple_minimo_orientacion')->value ?? FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function cumpleMinimos(): bool {
    return $this->cumpleMinimosFormacion() && $this->cumpleMinimosOrientacion();
  }

  /**
   * {@inheritdoc}
   */
  public function getAccionFormativaIds(): array {
    $json = $this->get('accion_formativa_ids')->value;
    if (empty($json)) {
      return [];
    }
    $decoded = json_decode($json, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(\Drupal\Core\Entity\EntityStorageInterface $storage): void {
    parent::preSave($storage);
    $this->recalculateComputedFields();
  }

  /**
   * Recalcula los campos computed a partir de las acciones formativas.
   *
   * Carga las entidades accion_formativa_ei referenciadas en
   * accion_formativa_ids, suma horas por tipo (orientacion vs formacion)
   * y actualiza los campos de horas y cumplimiento de minimos.
   */
  protected function recalculateComputedFields(): void {
    $ids = $this->getAccionFormativaIds();
    $horasFormacion = 0.0;
    $horasOrientacion = 0.0;

    if (!empty($ids)) {
      try {
        $entityTypeManager = \Drupal::entityTypeManager();
        $accionStorage = $entityTypeManager->getStorage('accion_formativa_ei');

        // Extraer IDs numericos (accion_formativa_ids puede contener
        // objetos con metadatos o IDs planos).
        $numericIds = [];
        foreach ($ids as $item) {
          if (is_array($item) && isset($item['id'])) {
            $numericIds[] = (int) $item['id'];
          }
          elseif (is_numeric($item)) {
            $numericIds[] = (int) $item;
          }
        }

        if (!empty($numericIds)) {
          // TENANT-001: Solo cargar acciones del mismo tenant.
          $tenantId = $this->get('tenant_id')->target_id;
          $filteredIds = $numericIds;
          if ($tenantId) {
            $filteredIds = $accionStorage->getQuery()
              ->accessCheck(FALSE)
              ->condition('id', $numericIds, 'IN')
              ->condition('tenant_id', $tenantId)
              ->execute();
          }
          /** @var \Drupal\jaraba_andalucia_ei\Entity\AccionFormativaEiInterface[] $acciones */
          $acciones = !empty($filteredIds) ? $accionStorage->loadMultiple($filteredIds) : [];
          foreach ($acciones as $accion) {
            $tipo = $accion->getTipoFormacion();
            $horas = $accion->getHorasPrevistas();
            if (in_array($tipo, self::ORIENTACION_TIPOS, TRUE)) {
              $horasOrientacion += $horas;
            }
            else {
              $horasFormacion += $horas;
            }
          }
        }
      }
      catch (\Throwable) {
        // PRESAVE-RESILIENCE-001: If entity storage unavailable, keep defaults.
      }
    }

    $this->set('horas_formacion_previstas', number_format($horasFormacion, 2, '.', ''));
    $this->set('horas_orientacion_previstas', number_format($horasOrientacion, 2, '.', ''));
    $this->set('horas_totales_previstas', number_format($horasFormacion + $horasOrientacion, 2, '.', ''));
    $this->set('cumple_minimo_formacion', $horasFormacion >= 50.0);
    $this->set('cumple_minimo_orientacion', $horasOrientacion >= 10.0);

    // Sprint 14: Compute cumplimiento persona atendida/insertada.
    $horasOrientacionInsercion = (float) ($this->get('horas_orientacion_insercion_previstas')->value ?? 0);
    $this->set('cumple_persona_atendida', $horasOrientacion >= 10.0 && $horasFormacion >= 50.0);
    $this->set('cumple_persona_insertada',
      $horasOrientacion >= 10.0 && $horasFormacion >= 50.0 && $horasOrientacionInsercion >= 40.0
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // Owner.
    $fields['uid']
      ->setLabel(t('Creado por'))
      ->setDescription(t('Usuario que creo el plan formativo.'))
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece este plan formativo.'))
      ->setSetting('target_type', 'group')
      ->setRequired(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    // === DATOS PRINCIPALES ===

    $fields['titulo'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Titulo'))
      ->setDescription(t('Nombre del plan formativo.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['descripcion'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Descripcion'))
      ->setDescription(t('Descripcion general del plan formativo, objetivos y contexto.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['carril'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Carril'))
      ->setDescription(t('Carril del programa al que corresponde este plan.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', array_map('t', PlanFormativoEiInterface::CARRILES))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['estado'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual del plan formativo.'))
      ->setRequired(TRUE)
      ->setDefaultValue('borrador')
      ->setSetting('allowed_values', array_map('t', PlanFormativoEiInterface::ESTADOS))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === COMPOSICION (acciones formativas) ===

    $fields['accion_formativa_ids'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Acciones Formativas'))
      ->setDescription(t('JSON array con IDs de acciones formativas y metadatos de orden. No usar entity_reference porque se necesita orden y metadatos adicionales.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === HORAS (stored computed — recalculados en preSave) ===

    $fields['horas_formacion_previstas'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Horas Formacion Previstas'))
      ->setDescription(t('Suma de horas de formacion de las acciones asociadas. Calculado automaticamente.'))
      ->setDefaultValue('0.00')
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['horas_orientacion_previstas'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Horas Orientacion Previstas'))
      ->setDescription(t('Suma de horas de orientacion/tutoria de las acciones asociadas. Calculado automaticamente.'))
      ->setDefaultValue('0.00')
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['horas_totales_previstas'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Horas Totales Previstas'))
      ->setDescription(t('Suma total de todas las horas previstas. Calculado automaticamente.'))
      ->setDefaultValue('0.00')
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === CUMPLIMIENTO (stored computed — recalculados en preSave) ===

    $fields['cumple_minimo_formacion'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Cumple Minimo Formacion'))
      ->setDescription(t('TRUE cuando horas de formacion >= 50h. Calculado automaticamente.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['cumple_minimo_orientacion'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Cumple Minimo Orientacion'))
      ->setDescription(t('TRUE cuando horas de orientacion >= 10h. Calculado automaticamente.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === SPRINT 14: CUMPLIMIENTO PIIL (stored computed) ===

    $fields['horas_orientacion_insercion_previstas'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Horas Orientación Inserción Previstas'))
      ->setDescription(t('Horas de orientación para la inserción planificadas. Calculado automáticamente.'))
      ->setDefaultValue('0.00')
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('view', TRUE);

    $fields['cumple_persona_atendida'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('¿Cumple persona atendida?'))
      ->setDescription(t('TRUE cuando el plan cubre ≥10h orientación laboral + ≥50h formación.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['cumple_persona_insertada'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('¿Cumple persona insertada?'))
      ->setDescription(t('TRUE cuando cumple persona atendida + ≥40h orientación inserción.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    // === CALENDARIO ===

    $fields['fecha_inicio'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Inicio'))
      ->setDescription(t('Fecha de inicio prevista del plan formativo.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fecha_fin'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Fin'))
      ->setDescription(t('Fecha de finalizacion prevista del plan formativo.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === NOTAS ===

    $fields['notas'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notas'))
      ->setDescription(t('Notas internas sobre el plan formativo.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === TIMESTAMPS ===

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creacion'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Ultima actualizacion'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
