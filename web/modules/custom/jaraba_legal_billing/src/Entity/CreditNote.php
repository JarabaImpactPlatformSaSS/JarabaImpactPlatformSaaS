<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Define la entidad Nota de Credito (CreditNote) — FASE B3.
 *
 * ESTRUCTURA:
 * Nota de credito asociada a una factura. Permite rectificar o anular
 * total o parcialmente una factura emitida, cumpliendo normativa SII/AEAT.
 *
 * LOGICA:
 * Se genera a partir de una factura existente. Auto-numera como NC-YYYY-NNNN.
 * Si Stripe esta activo, inicia refund automatico. El refund_status sigue
 * el ciclo: pending -> processed | failed.
 *
 * RELACIONES:
 * - CreditNote -> LegalInvoice (invoice_id): factura rectificada.
 * - CreditNote -> TaxonomyTerm (tenant_id): tenant multi-tenant.
 *
 * @ContentEntityType(
 *   id = "credit_note",
 *   label = @Translation("Nota de Credito"),
 *   label_collection = @Translation("Notas de Credito"),
 *   label_singular = @Translation("nota de credito"),
 *   label_plural = @Translation("notas de credito"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_legal_billing\Access\LegalInvoiceAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "credit_note",
 *   admin_permission = "administer billing",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/credit-notes",
 *     "canonical" = "/admin/content/credit-notes/{credit_note}",
 *     "delete-form" = "/admin/content/credit-notes/{credit_note}/delete",
 *   },
 *   field_ui_base_route = "entity.credit_note.settings",
 * )
 */
class CreditNote extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOQUE 1: REFERENCIAS PRINCIPALES
    // =========================================================================

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['invoice_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Factura Rectificada'))
      ->setDescription(new TranslatableMarkup('Factura original asociada a esta nota de credito.'))
      ->setSetting('target_type', 'legal_invoice')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: NUMERACION
    // =========================================================================

    $fields['credit_note_number'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Numero de Nota de Credito'))
      ->setDescription(new TranslatableMarkup('Auto-generado: NC-YYYY-NNNN.'))
      ->setSetting('max_length', 32)
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: DATOS ECONOMICOS
    // =========================================================================

    $fields['reason'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Motivo'))
      ->setSetting('allowed_values', [
        'error' => 'Error en factura',
        'refund' => 'Devolucion',
        'discount' => 'Descuento posterior',
        'cancellation' => 'Anulacion completa',
      ])
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Base Imponible'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tax_amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('IVA'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Total'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 4: STRIPE REFUND
    // =========================================================================

    $fields['refund_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado del Reembolso'))
      ->setSetting('allowed_values', [
        'pending' => 'Pendiente',
        'processed' => 'Procesado',
        'failed' => 'Fallido',
      ])
      ->setDefaultValue('pending')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['stripe_refund_id'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Stripe Refund ID'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 5: TIMESTAMPS
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave($storage): void {
    parent::preSave($storage);

    // Auto-generar numero de nota de credito: NC-YYYY-NNNN.
    if ($this->isNew() && empty($this->get('credit_note_number')->value)) {
      $year = date('Y');
      $query = $storage->getQuery()
        ->condition('credit_note_number', "NC-{$year}-", 'STARTS_WITH')
        ->accessCheck(FALSE)
        ->sort('id', 'DESC')
        ->range(0, 1);
      $ids = $query->execute();
      $next = 1;
      if (!empty($ids)) {
        $last = $storage->load(reset($ids));
        if ($last && preg_match('/NC-\d{4}-(\d{4})/', $last->get('credit_note_number')->value, $m)) {
          $next = (int) $m[1] + 1;
        }
      }
      $this->set('credit_note_number', sprintf('NC-%s-%04d', $year, $next));
    }
  }

}
