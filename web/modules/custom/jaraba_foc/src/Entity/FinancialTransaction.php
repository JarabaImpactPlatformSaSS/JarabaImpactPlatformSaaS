<?php

declare(strict_types=1);

namespace Drupal\jaraba_foc\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad de contenido Transacción Financiera.
 *
 * PROPÓSITO:
 * Esta entidad actúa como libro mayor contable digital (append-only).
 * Almacena todas las transacciones financieras del ecosistema SaaS.
 *
 * INMUTABILIDAD (CRÍTICO):
 * ═══════════════════════════════════════════════════════════════════════════
 * Esta entidad es INMUTABLE (append-only). No se permiten operaciones de:
 * - Edición (update)
 * - Eliminación (delete)
 *
 * Los ajustes contables se realizan mediante asientos compensatorios.
 * Esto garantiza la integridad del libro mayor y la trazabilidad para auditorías.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * CAMPOS PRINCIPALES:
 * - amount: Monto en Decimal(10,4) - NUNCA usar float para dinero
 * - currency: Código ISO 4217 (EUR, USD)
 * - timestamp: DateTime en UTC - Sin conflictos de timezone
 * - transaction_type: Referencia a taxonomía (Ingreso, Gasto, Reembolso, etc.)
 * - source_system: Origen (stripe_connect, activecampaign, manual)
 * - external_id: ID en sistema origen para evitar duplicados
 * - related_tenant: Referencia a Group/Tenant para analítica
 * - related_vertical: Referencia a Vertical para P&L segmentado
 *
 * @ContentEntityType(
 *   id = "financial_transaction",
 *   label = @Translation("Transacción Financiera"),
 *   label_collection = @Translation("Transacciones Financieras"),
 *   label_singular = @Translation("transacción financiera"),
 *   label_plural = @Translation("transacciones financieras"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_foc\FinancialTransactionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_foc\FinancialTransactionAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\jaraba_foc\Form\FinancialTransactionForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "financial_transaction",
 *   admin_permission = "administer foc",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *   },
 *   links = {
 *     "collection" = "/admin/foc/transactions",
 *     "canonical" = "/admin/foc/transaction/{financial_transaction}",
 *     "add-form" = "/admin/foc/transaction/add",
 *   },
 * )
 */
class FinancialTransaction extends ContentEntityBase implements FinancialTransactionInterface
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public function getAmount(): string
    {
        return $this->get('amount')->value ?? '0.0000';
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrency(): string
    {
        return $this->get('currency')->value ?? 'EUR';
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionTimestamp(): int
    {
        return (int) $this->get('transaction_timestamp')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionType(): ?string
    {
        $type = $this->get('transaction_type')->target_id;
        return $type ? (string) $type : NULL;
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceSystem(): string
    {
        return $this->get('source_system')->value ?? 'manual';
    }

    /**
     * {@inheritdoc}
     */
    public function getExternalId(): ?string
    {
        return $this->get('external_id')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getRelatedTenantId(): ?int
    {
        $tid = $this->get('related_tenant')->target_id;
        return $tid ? (int) $tid : NULL;
    }

    /**
     * {@inheritdoc}
     */
    public function getRelatedVerticalId(): ?string
    {
        $value = $this->get('related_vertical')->value;
        return $value ?: NULL;
    }

    /**
     * {@inheritdoc}
     */
    public function isRevenue(): bool
    {
        // Los montos positivos son ingresos
        return bccomp($this->getAmount(), '0', 4) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isRecurring(): bool
    {
        return (bool) $this->get('is_recurring')->value;
    }

    /**
     * {@inheritdoc}
     *
     * Define los campos base de la entidad Transacción Financiera.
     *
     * ESTRUCTURA DE CAMPOS:
     * ═══════════════════════════════════════════════════════════════════════════
     * Campos de Identificación:
     * - id: ID interno autoincremental
     * - uuid: Identificador único universal para sincronización
     *
     * Campos Monetarios:
     * - amount: Decimal(10,4) - Precisión alta, NUNCA usar float
     * - currency: ISO 4217 (EUR, USD)
     *
     * Campos Temporales:
     * - transaction_timestamp: DateTime en UTC
     * - created: Fecha de creación del registro
     * - changed: Fecha de última modificación (para tracking, no edición)
     *
     * Campos de Clasificación:
     * - transaction_type: Taxonomía (Ingreso Recurrente, Venta Única, etc.)
     * - is_recurring: Booleano para MRR vs one-time
     *
     * Campos de Trazabilidad:
     * - source_system: Origen (stripe_connect, activecampaign, manual)
     * - external_id: ID en sistema origen
     *
     * Relaciones:
     * - related_tenant: Referencia a Group/Tenant
     * - related_vertical: Referencia a entidad Vertical
     * - related_campaign: Referencia opcional para atribución CAC
     * ═══════════════════════════════════════════════════════════════════════════
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // ═══════════════════════════════════════════════════════════════════════
        // CAMPOS MONETARIOS
        // ═══════════════════════════════════════════════════════════════════════

        $fields['amount'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Monto'))
            ->setDescription(t('Monto de la transacción con precisión alta. Positivo = ingreso, Negativo = gasto.'))
            ->setRequired(TRUE)
            ->setSetting('precision', 10)
            ->setSetting('scale', 4)
            ->setDefaultValue('0.0000')
            ->setDisplayOptions('view', [
                    'label' => 'inline',
                    'type' => 'number_decimal',
                    'weight' => 0,
                ])
            ->setDisplayOptions('form', [
                    'type' => 'number',
                    'weight' => 0,
                ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['currency'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Moneda'))
            ->setDescription(t('Código ISO 4217 de la moneda (EUR, USD).'))
            ->setRequired(TRUE)
            ->setDefaultValue('EUR')
            ->setSetting('max_length', 3)
            ->setDisplayOptions('view', [
                    'label' => 'inline',
                    'type' => 'string',
                    'weight' => 1,
                ])
            ->setDisplayOptions('form', [
                    'type' => 'string_textfield',
                    'weight' => 1,
                ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // ═══════════════════════════════════════════════════════════════════════
        // CAMPOS TEMPORALES
        // ═══════════════════════════════════════════════════════════════════════

        $fields['transaction_timestamp'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Fecha/Hora de Transacción'))
            ->setDescription(t('Momento exacto de la transacción en UTC.'))
            ->setRequired(TRUE)
            ->setDefaultValueCallback('Drupal\jaraba_foc\Entity\FinancialTransaction::getCurrentTimestamp')
            ->setDisplayOptions('view', [
                    'label' => 'inline',
                    'type' => 'timestamp',
                    'weight' => 2,
                ])
            ->setDisplayOptions('form', [
                    'type' => 'datetime_timestamp',
                    'weight' => 2,
                ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha de Registro'))
            ->setDescription(t('Momento en que se registró la transacción en el sistema.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Última Modificación'))
            ->setDescription(t('Momento de última modificación (solo para tracking, no edición).'));

        // ═══════════════════════════════════════════════════════════════════════
        // CAMPOS DE CLASIFICACIÓN
        // ═══════════════════════════════════════════════════════════════════════

        $fields['transaction_type'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tipo de Transacción'))
            ->setDescription(t('Clasificación: Ingreso Recurrente, Venta Única, Subvención, Coste Directo, Coste Indirecto, Reembolso.'))
            ->setSetting('target_type', 'taxonomy_term')
            ->setSetting('handler', 'default')
            ->setSetting('handler_settings', [
                    'target_bundles' => ['transaction_types' => 'transaction_types'],
                ])
            ->setDisplayOptions('view', [
                    'label' => 'inline',
                    'type' => 'entity_reference_label',
                    'weight' => 3,
                ])
            ->setDisplayOptions('form', [
                    'type' => 'options_select',
                    'weight' => 3,
                ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['is_recurring'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Es Recurrente'))
            ->setDescription(t('Indica si es un ingreso recurrente (MRR) o puntual.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('view', [
                    'label' => 'inline',
                    'type' => 'boolean',
                    'weight' => 4,
                ])
            ->setDisplayOptions('form', [
                    'type' => 'boolean_checkbox',
                    'weight' => 4,
                ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // ═══════════════════════════════════════════════════════════════════════
        // CAMPOS DE TRAZABILIDAD
        // ═══════════════════════════════════════════════════════════════════════

        $fields['source_system'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Sistema de Origen'))
            ->setDescription(t('Identificador del sistema que generó la transacción.'))
            ->setRequired(TRUE)
            ->setDefaultValue('manual')
            ->setSetting('max_length', 64)
            ->setDisplayOptions('view', [
                    'label' => 'inline',
                    'type' => 'string',
                    'weight' => 5,
                ])
            ->setDisplayOptions('form', [
                    'type' => 'string_textfield',
                    'weight' => 5,
                ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['external_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('ID Externo'))
            ->setDescription(t('ID en el sistema origen. Previene duplicados y permite auditorías.'))
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', [
                    'label' => 'inline',
                    'type' => 'string',
                    'weight' => 6,
                ])
            ->setDisplayOptions('form', [
                    'type' => 'string_textfield',
                    'weight' => 6,
                ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE)
            ->addConstraint('UniqueField');

        // ═══════════════════════════════════════════════════════════════════════
        // RELACIONES
        // ═══════════════════════════════════════════════════════════════════════

        $fields['related_tenant'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant Relacionado'))
            ->setDescription(t('Grupo/Tenant al que pertenece esta transacción para analítica.'))
            ->setSetting('target_type', 'group')
            ->setSetting('handler', 'default')
            ->setDisplayOptions('view', [
                    'label' => 'inline',
                    'type' => 'entity_reference_label',
                    'weight' => 7,
                ])
            ->setDisplayOptions('form', [
                    'type' => 'entity_reference_autocomplete',
                    'weight' => 7,
                ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['related_vertical'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Vertical Relacionado'))
            ->setDescription(t('ID o nombre del vertical de negocio para P&L segmentado.'))
            ->setSetting('max_length', 64)
            ->setDisplayOptions('view', [
                    'label' => 'inline',
                    'type' => 'string',
                    'weight' => 8,
                ])
            ->setDisplayOptions('form', [
                    'type' => 'string_textfield',
                    'weight' => 8,
                ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['related_campaign'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Campaña Relacionada'))
            ->setDescription(t('Identificador de campaña para atribución de CAC.'))
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', [
                    'label' => 'inline',
                    'type' => 'string',
                    'weight' => 9,
                ])
            ->setDisplayOptions('form', [
                    'type' => 'string_textfield',
                    'weight' => 9,
                ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // ═══════════════════════════════════════════════════════════════════════
        // DATOS ADICIONALES
        // ═══════════════════════════════════════════════════════════════════════

        $fields['description'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Descripción detallada de la transacción.'))
            ->setDisplayOptions('view', [
                    'label' => 'above',
                    'type' => 'string',
                    'weight' => 10,
                ])
            ->setDisplayOptions('form', [
                    'type' => 'string_textarea',
                    'weight' => 10,
                    'settings' => [
                        'rows' => 3,
                    ],
                ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['metadata'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Metadatos'))
            ->setDescription(t('JSON con datos adicionales contextuales de la transacción.'))
            ->setDisplayOptions('view', [
                    'label' => 'above',
                    'type' => 'string',
                    'weight' => 11,
                ])
            ->setDisplayConfigurable('view', TRUE);

        return $fields;
    }

    /**
     * Callback para obtener el timestamp actual.
     *
     * @return array
     *   Array con el timestamp actual.
     */
    public static function getCurrentTimestamp(): array
    {
        return [\Drupal::time()->getRequestTime()];
    }

}
