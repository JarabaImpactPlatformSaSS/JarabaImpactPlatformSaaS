<?php

declare(strict_types=1);

namespace Drupal\jaraba_foc\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad de contenido Cost Allocation.
 *
 * PROPÓSITO:
 * Resuelve el desafío de calcular la rentabilidad real en multi-tenancy,
 * distribuyendo costes compartidos entre tenants y verticales según drivers
 * medibles (uso de disco, usuarios activos, bandwidth, etc.).
 *
 * CASO DE USO:
 * ═══════════════════════════════════════════════════════════════════════════
 * Ejemplo: Factura de hosting de €1.000/mes
 * 
 * Sin Cost Allocation:
 * - No se puede saber qué tenant consume más recursos
 * - Imposible calcular Tenant Margin real
 * 
 * Con Cost Allocation:
 * - Tenant A (60% uso disco) → €600
 * - Tenant B (30% uso disco) → €300
 * - Tenant C (10% uso disco) → €100
 * 
 * Ahora podemos calcular si Tenant B es rentable o "noisy neighbor".
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * DRIVERS SOPORTADOS:
 * - disk_usage: Uso de disco por tenant
 * - bandwidth: Ancho de banda consumido
 * - active_users: Usuarios activos
 * - api_calls: Llamadas a API (Qdrant, OpenAI, etc.)
 * - flat_rate: Tarifa plana por tenant
 * - revenue_percentage: % del revenue del tenant
 *
 * @ContentEntityType(
 *   id = "cost_allocation",
 *   label = @Translation("Asignación de Costes"),
 *   label_collection = @Translation("Asignaciones de Costes"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_foc\CostAllocationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_foc\Form\CostAllocationForm",
 *       "add" = "Drupal\jaraba_foc\Form\CostAllocationForm",
 *       "edit" = "Drupal\jaraba_foc\Form\CostAllocationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "cost_allocation",
 *   admin_permission = "manage cost allocation",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "collection" = "/admin/foc/cost-allocations",
 *     "canonical" = "/admin/foc/cost-allocation/{cost_allocation}",
 *     "add-form" = "/admin/foc/cost-allocation/add",
 *     "edit-form" = "/admin/foc/cost-allocation/{cost_allocation}/edit",
 *     "delete-form" = "/admin/foc/cost-allocation/{cost_allocation}/delete",
 *   },
 * )
 */
class CostAllocation extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // ═══════════════════════════════════════════════════════════════════════
        // IDENTIFICACIÓN
        // ═══════════════════════════════════════════════════════════════════════

        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setDescription(t('Nombre descriptivo de la asignación de costes (ej: "Hosting Q1 2026").'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'string',
                'weight' => 0,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // ═══════════════════════════════════════════════════════════════════════
        // COSTE TOTAL
        // ═══════════════════════════════════════════════════════════════════════

        $fields['total_cost'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Coste Total'))
            ->setDescription(t('Gasto global a distribuir (ej: €1.000 de factura de hosting).'))
            ->setRequired(TRUE)
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_decimal',
                'weight' => 1,
            ])
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['currency'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Moneda'))
            ->setDescription(t('Código ISO 4217.'))
            ->setRequired(TRUE)
            ->setDefaultValue('EUR')
            ->setSetting('max_length', 3)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // ═══════════════════════════════════════════════════════════════════════
        // PERÍODO
        // ═══════════════════════════════════════════════════════════════════════

        $fields['period_start'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Inicio del Período'))
            ->setDescription(t('Fecha de inicio del período de aplicación.'))
            ->setRequired(TRUE)
            ->setSetting('datetime_type', 'date')
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'datetime_default',
                'weight' => 3,
            ])
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => 3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['period_end'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fin del Período'))
            ->setDescription(t('Fecha de fin del período de aplicación.'))
            ->setRequired(TRUE)
            ->setSetting('datetime_type', 'date')
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'datetime_default',
                'weight' => 4,
            ])
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => 4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // ═══════════════════════════════════════════════════════════════════════
        // DRIVER DE ASIGNACIÓN
        // ═══════════════════════════════════════════════════════════════════════

        $fields['allocation_driver'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Driver de Asignación'))
            ->setDescription(t('Métrica base para distribución del coste.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'disk_usage' => 'Uso de Disco',
                'bandwidth' => 'Ancho de Banda',
                'active_users' => 'Usuarios Activos',
                'api_calls' => 'Llamadas API',
                'flat_rate' => 'Tarifa Plana',
                'revenue_percentage' => '% del Revenue',
            ])
            ->setDefaultValue('active_users')
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'list_default',
                'weight' => 5,
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // ═══════════════════════════════════════════════════════════════════════
        // REGLAS DE REPARTO (JSON)
        // ═══════════════════════════════════════════════════════════════════════

        $fields['allocation_rules'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Reglas de Reparto'))
            ->setDescription(t('JSON con las reglas de distribución por tenant/vertical.'))
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'string',
                'weight' => 6,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 6,
                'settings' => [
                    'rows' => 10,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // ═══════════════════════════════════════════════════════════════════════
        // METADATOS
        // ═══════════════════════════════════════════════════════════════════════

        $fields['category'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Categoría de Coste'))
            ->setDescription(t('Clasificación del tipo de coste compartido.'))
            ->setSetting('allowed_values', [
                'hosting' => 'Hosting/Servidor',
                'support' => 'Soporte Técnico',
                'devops' => 'DevOps/Mantenimiento',
                'licenses' => 'Licencias Software',
                'payment_processing' => 'Procesamiento de Pagos',
                'marketing' => 'Marketing',
                'other' => 'Otros',
            ])
            ->setDefaultValue('hosting')
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'list_default',
                'weight' => 7,
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['notes'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Notas'))
            ->setDescription(t('Notas adicionales sobre esta asignación de costes.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 8,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

}
