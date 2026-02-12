<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad OrderItemAgro.
 *
 * Representa una línea de pedido que referencia un producto específico
 * con cantidad y precio. Items se agrupan en sub-pedidos por productor.
 *
 * @ContentEntityType(
 *   id = "order_item_agro",
 *   label = @Translation("Línea de Pedido Agro"),
 *   label_collection = @Translation("Líneas de Pedido Agro"),
 *   label_singular = @Translation("línea de pedido agro"),
 *   label_plural = @Translation("líneas de pedido agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\OrderAgroAccessControlHandler",
 *   },
 *   base_table = "order_item_agro",
 *   admin_permission = "administer agroconecta",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 * )
 */
class OrderItemAgro extends ContentEntityBase
{

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Referencia al pedido padre
        $fields['order_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Pedido'))
            ->setDescription(t('Pedido al que pertenece esta línea.'))
            ->setSetting('target_type', 'order_agro')
            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Referencia al sub-pedido (por productor)
        $fields['suborder_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Sub-pedido'))
            ->setDescription(t('Sub-pedido del productor correspondiente.'))
            ->setSetting('target_type', 'suborder_agro')
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Referencia al producto
        $fields['product_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Producto'))
            ->setDescription(t('Producto agro comprado.'))
            ->setSetting('target_type', 'product_agro')
            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Referencia al productor
        $fields['producer_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Productor'))
            ->setDescription(t('Productor del item.'))
            ->setSetting('target_type', 'producer_profile')
            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Título snapshot (captura en momento de compra)
        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setDescription(t('Nombre del producto al momento de la compra.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // SKU snapshot
        $fields['sku'] = BaseFieldDefinition::create('string')
            ->setLabel(t('SKU'))
            ->setDescription(t('SKU del producto al momento de la compra.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 64)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Cantidad
        $fields['quantity'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Cantidad'))
            ->setDescription(t('Cantidad pedida.'))
            ->setRequired(TRUE)
            ->setDefaultValue(1)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Precio unitario (al momento de compra)
        $fields['unit_price'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Precio unitario'))
            ->setDescription(t('Precio unitario al momento de la compra.'))
            ->setRequired(TRUE)
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Precio total (quantity * unit_price)
        $fields['total_price'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Precio total'))
            ->setDescription(t('Precio total de la línea (cantidad × precio unitario).'))
            ->setRequired(TRUE)
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Tipo de IVA
        $fields['tax_rate'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Tipo IVA'))
            ->setDescription(t('Porcentaje de IVA aplicado.'))
            ->setDefaultValue('10.00')
            ->setSetting('precision', 4)
            ->setSetting('scale', 2)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Importe de IVA
        $fields['tax_amount'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Importe IVA'))
            ->setDescription(t('Importe de IVA calculado.'))
            ->setDefaultValue('0.00')
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Peso total
        $fields['weight_total'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Peso total'))
            ->setDescription(t('Peso total del item en kg.'))
            ->setSetting('precision', 8)
            ->setSetting('scale', 3)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Número de lote
        $fields['lot_number'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Lote'))
            ->setDescription(t('Número de lote asignado.'))
            ->setSetting('max_length', 32)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Estado del item
        $fields['item_state'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Estado del item'))
            ->setDescription(t('Estado individual: pending, confirmed, preparing, shipped, delivered, cancelled.'))
            ->setDefaultValue('pending')
            ->setSetting('max_length', 32)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Campos de sistema
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        return $fields;
    }

    /**
     * Calcula el precio total a partir de cantidad y precio unitario.
     *
     * @return float
     *   El precio total calculado.
     */
    public function calculateTotalPrice(): float
    {
        $quantity = (int) $this->get('quantity')->value;
        $unitPrice = (float) $this->get('unit_price')->value;
        return round($quantity * $unitPrice, 2);
    }

}
