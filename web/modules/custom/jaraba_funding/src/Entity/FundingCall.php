<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Funding Call (Convocatoria de Subvencion).
 *
 * ESTRUCTURA:
 * Entidad que almacena convocatorias de subvenciones sincronizadas desde
 * BDNS, BOJA y otras fuentes. Cada convocatoria incluye requisitos,
 * importes, plazos y se procesa con embeddings para matching semantico.
 *
 * LOGICA:
 * El campo status controla el ciclo de vida de la convocatoria:
 * open, closed, pending_resolution, resolved, draft. Las convocatorias
 * se sincronizan periodicamente desde fuentes oficiales y se enriquecen
 * con embeddings para el motor de matching IA.
 *
 * RELACIONES:
 * - FundingCall -> Tenant (tenant_id): tenant propietario
 * - FundingCall <- FundingMatch (call_id): matches con suscripciones
 * - FundingCall <- FundingAlert (call_id): alertas asociadas
 *
 * @ContentEntityType(
 *   id = "funding_call",
 *   label = @Translation("Funding Call"),
 *   label_collection = @Translation("Funding Calls"),
 *   label_singular = @Translation("funding call"),
 *   label_plural = @Translation("funding calls"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_funding\ListBuilder\FundingCallListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "storage_schema" = "Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema",
 *     "access" = "Drupal\jaraba_funding\Access\FundingCallAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "funding_call",
 *   admin_permission = "administer jaraba funding",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/funding-calls/{funding_call}",
 *     "collection" = "/admin/content/funding-calls",
 *     "add-form" = "/admin/content/funding-calls/add",
 *     "edit-form" = "/admin/content/funding-calls/{funding_call}/edit",
 *     "delete-form" = "/admin/content/funding-calls/{funding_call}/delete",
 *   },
 * )
 */
class FundingCall extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // --- Title ---
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Titulo'))
      ->setDescription(t('Titulo de la convocatoria.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 512)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- BDNS ID ---
    $fields['bdns_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('BDNS ID'))
      ->setDescription(t('Identificador unico en la BDNS.'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- BOJA ID (nullable) ---
    $fields['boja_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('BOJA ID'))
      ->setDescription(t('Identificador unico en el BOJA, si aplica.'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Source ---
    $fields['source'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Fuente'))
      ->setDescription(t('Origen de la convocatoria.'))
      ->setRequired(TRUE)
      ->setDefaultValue('bdns')
      ->setSetting('allowed_values', [
        'bdns' => 'BDNS',
        'boja' => 'BOJA',
        'manual' => 'Manual',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Source URL ---
    $fields['source_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL Fuente'))
      ->setDescription(t('URL del documento original.'))
      ->setSetting('max_length', 2048)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Call Type ---
    $fields['call_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Convocatoria'))
      ->setDescription(t('Tipo de ayuda o subvencion.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'subvencion' => 'Subvencion',
        'ayuda' => 'Ayuda',
        'prestamo' => 'Prestamo',
        'incentivo' => 'Incentivo',
        'premio' => 'Premio',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Department ---
    $fields['department'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Departamento'))
      ->setDescription(t('Departamento emisor de la convocatoria.'))
      ->setSetting('max_length', 512)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Region ---
    $fields['region'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Region'))
      ->setDescription(t('Ambito territorial: nacional, andalucia, cataluna, etc.'))
      ->setSetting('max_length', 128)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Beneficiary Types (JSON array) ---
    $fields['beneficiary_types'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Tipos de Beneficiario'))
      ->setDescription(t('JSON array de tipos de beneficiario.'));

    // --- Sectors (JSON array) ---
    $fields['sectors'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Sectores'))
      ->setDescription(t('JSON array de sectores.'));

    // --- Min Employees (nullable) ---
    $fields['min_employees'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Minimo de Empleados'))
      ->setDescription(t('Numero minimo de empleados requerido.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Max Employees (nullable) ---
    $fields['max_employees'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Maximo de Empleados'))
      ->setDescription(t('Numero maximo de empleados permitido.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Min Revenue (nullable) ---
    $fields['min_revenue'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Facturacion Minima'))
      ->setDescription(t('Facturacion minima requerida.'))
      ->setSetting('precision', 14)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Max Revenue (nullable) ---
    $fields['max_revenue'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Facturacion Maxima'))
      ->setDescription(t('Facturacion maxima permitida.'))
      ->setSetting('precision', 14)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Budget Total ---
    $fields['budget_total'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Presupuesto Total'))
      ->setDescription(t('Presupuesto total de la convocatoria.'))
      ->setSetting('precision', 14)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Amount Min ---
    $fields['amount_min'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Importe Minimo'))
      ->setDescription(t('Importe minimo por beneficiario.'))
      ->setSetting('precision', 14)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Amount Max ---
    $fields['amount_max'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Importe Maximo'))
      ->setDescription(t('Importe maximo por beneficiario.'))
      ->setSetting('precision', 14)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Intensity Percentage ---
    $fields['intensity_pct'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Intensidad de Ayuda (%)'))
      ->setDescription(t('Porcentaje de intensidad de la ayuda.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Publication Date ---
    $fields['publication_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fecha de Publicacion'))
      ->setDescription(t('Fecha de publicacion oficial.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Deadline ---
    $fields['deadline'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fecha Limite'))
      ->setDescription(t('Fecha limite de solicitud.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Resolution Date (nullable) ---
    $fields['resolution_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fecha de Resolucion'))
      ->setDescription(t('Fecha estimada de resolucion.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Summary ---
    $fields['summary'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Resumen'))
      ->setDescription(t('Resumen de la convocatoria.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- Full Text ---
    $fields['full_text'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Texto Completo'))
      ->setDescription(t('Texto completo de la convocatoria.'));

    // --- Requirements (JSON array) ---
    $fields['requirements'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Requisitos'))
      ->setDescription(t('JSON array de requisitos.'));

    // --- Documentation Required (JSON array) ---
    $fields['documentation_required'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Documentacion Requerida'))
      ->setDescription(t('JSON array de documentacion requerida.'));

    // --- Embedding ID ---
    $fields['embedding_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Embedding ID'))
      ->setDescription(t('Qdrant point ID para la convocatoria.'))
      ->setSetting('max_length', 128)
      ->setDisplayConfigurable('view', TRUE);

    // --- Status ---
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado de la convocatoria.'))
      ->setRequired(TRUE)
      ->setDefaultValue('open')
      ->setSetting('allowed_values', [
        'open' => 'Abierta',
        'closed' => 'Cerrada',
        'pending_resolution' => 'Pendiente de Resolucion',
        'resolved' => 'Resuelta',
        'draft' => 'Borrador',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Tenant ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de este registro.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Metadatos ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creacion'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificacion'));

    return $fields;
  }

}
