<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Presupuesto (Quote) — FASE B3.
 *
 * ESTRUCTURA:
 * Presupuesto juridico enviable al cliente con acceso via token publico.
 * Contiene datos del cliente, lineas de presupuesto, descuentos, IVA,
 * condiciones de pago y notas. Soporta generacion automatica via IA.
 *
 * LOGICA:
 * Ciclo de vida: draft -> sent -> viewed -> accepted | rejected | expired.
 * Al enviar se genera un access_token para que el cliente vea/acepte/rechace
 * el presupuesto sin autenticarse. Al aceptar se puede convertir
 * automaticamente en expediente (client_case) y/o factura.
 *
 * RELACIONES:
 * - Quote -> User (provider_id): profesional autor.
 * - Quote -> TaxonomyTerm (tenant_id): tenant multi-tenant.
 * - Quote -> ClientCase (converted_to_case_id): expediente generado.
 * - Quote <- QuoteLineItem (quote_id): lineas del presupuesto.
 *
 * @ContentEntityType(
 *   id = "quote",
 *   label = @Translation("Presupuesto"),
 *   label_collection = @Translation("Presupuestos"),
 *   label_singular = @Translation("presupuesto"),
 *   label_plural = @Translation("presupuestos"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal_billing\ListBuilder\QuoteListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_legal_billing\Form\QuoteForm",
 *       "add" = "Drupal\jaraba_legal_billing\Form\QuoteForm",
 *       "edit" = "Drupal\jaraba_legal_billing\Form\QuoteForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_legal_billing\Access\QuoteAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "quote",
 *   admin_permission = "administer billing",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/quotes",
 *     "add-form" = "/admin/content/quotes/add",
 *     "canonical" = "/admin/content/quotes/{quote}",
 *     "edit-form" = "/admin/content/quotes/{quote}/edit",
 *     "delete-form" = "/admin/content/quotes/{quote}/delete",
 *   },
 *   field_ui_base_route = "jaraba_legal_billing.quote.settings",
 * )
 */
class Quote extends ContentEntityBase implements EntityOwnerInterface {

  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

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

    $fields['provider_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Profesional'))
      ->setDescription(new TranslatableMarkup('Autor del presupuesto.'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['inquiry_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Consulta Origen'))
      ->setDescription(new TranslatableMarkup('Consulta del cliente que origino este presupuesto.'))
      ->setSetting('target_type', 'client_inquiry')
      ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['converted_to_case_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Expediente Generado'))
      ->setDescription(new TranslatableMarkup('Expediente creado al aceptar el presupuesto.'))
      ->setSetting('target_type', 'client_case')
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: NUMERACION E IDENTIFICACION
    // =========================================================================

    $fields['quote_number'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Numero de Presupuesto'))
      ->setDescription(new TranslatableMarkup('Auto-generado: PRES-YYYY-NNNN.'))
      ->setSetting('max_length', 32)
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Titulo'))
      ->setSetting('max_length', 255)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['access_token'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Token de Acceso'))
      ->setDescription(new TranslatableMarkup('Token publico para acceso del cliente.'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: DATOS DEL CLIENTE
    // =========================================================================

    $fields['client_name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Nombre del Cliente'))
      ->setSetting('max_length', 255)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['client_email'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Email del Cliente'))
      ->setSetting('max_length', 255)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['client_phone'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Telefono'))
      ->setSetting('max_length', 24)
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['client_company'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Empresa'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 13])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['client_nif'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('NIF/CIF'))
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 14])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 4: CONTENIDO DEL PRESUPUESTO
    // =========================================================================

    $fields['introduction'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Texto Introductorio'))
      ->setDisplayOptions('form', ['type' => 'text_textarea', 'weight' => 15])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['payment_terms'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Condiciones de Pago'))
      ->setDisplayOptions('form', ['type' => 'text_textarea', 'weight' => 16])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Notas Adicionales'))
      ->setDisplayOptions('form', ['type' => 'text_textarea', 'weight' => 17])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 5: IMPORTES
    // =========================================================================

    $fields['subtotal'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Subtotal'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['discount_percent'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Descuento (%)'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDefaultValue('0.00')
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 20])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['discount_amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Descuento (EUR)'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDefaultValue('0.00')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tax_rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Tipo IVA (%)'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDefaultValue('21.00')
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 21])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tax_amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('IVA'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Total'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['currency'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Moneda'))
      ->setSetting('max_length', 3)
      ->setDefaultValue('EUR')
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 6: CICLO DE VIDA
    // =========================================================================

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado'))
      ->setSetting('allowed_values', [
        'draft' => 'Borrador',
        'sent' => 'Enviado',
        'viewed' => 'Visto',
        'accepted' => 'Aceptado',
        'rejected' => 'Rechazado',
        'expired' => 'Expirado',
      ])
      ->setDefaultValue('draft')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => 25])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['valid_until'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Valido Hasta'))
      ->setSetting('datetime_type', 'date')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'datetime_default', 'weight' => 26])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sent_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fecha de Envio'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('view', TRUE);

    $fields['viewed_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Primera Visualizacion'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('view', TRUE);

    $fields['responded_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fecha de Respuesta'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('view', TRUE);

    $fields['rejection_reason'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Motivo de Rechazo'))
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 7: IA
    // =========================================================================

    $fields['ai_generated'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Generado por IA'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 8: TIMESTAMPS
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

    // Auto-generar numero de presupuesto: PRES-YYYY-NNNN.
    if ($this->isNew() && empty($this->get('quote_number')->value)) {
      $year = date('Y');
      $query = $storage->getQuery()
        ->condition('quote_number', "PRES-{$year}-", 'STARTS_WITH')
        ->accessCheck(FALSE)
        ->sort('id', 'DESC')
        ->range(0, 1);
      $ids = $query->execute();
      $next = 1;
      if (!empty($ids)) {
        $last = $storage->load(reset($ids));
        if ($last && preg_match('/PRES-\d{4}-(\d{4})/', $last->get('quote_number')->value, $m)) {
          $next = (int) $m[1] + 1;
        }
      }
      $this->set('quote_number', sprintf('PRES-%s-%04d', $year, $next));
    }

    // Auto-generar access_token.
    if ($this->isNew() && empty($this->get('access_token')->value)) {
      $this->set('access_token', bin2hex(random_bytes(32)));
    }
  }

}
