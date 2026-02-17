<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_lexnet\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Envio LexNET (LexnetSubmission).
 *
 * ESTRUCTURA:
 * Entidad que representa un escrito o documento presentado a traves del
 * sistema LexNET. Almacena datos del organo judicial destino, tipo de escrito,
 * documentos adjuntos, confirmacion de presentacion y estado del envio.
 *
 * LOGICA:
 * Los envios siguen el ciclo: draft -> submitting -> submitted -> confirmed.
 * En caso de error: draft -> submitting -> error (reintentable).
 * En caso de rechazo judicial: submitted -> rejected.
 * El confirmation_id se recibe de LexNET tras presentacion exitosa.
 *
 * RELACIONES:
 * - LexnetSubmission -> TaxonomyTerm (tenant_id): tenant multi-tenant.
 * - LexnetSubmission -> ClientCase (case_id): expediente vinculado.
 * - LexnetSubmission -> User (uid): usuario que creo el envio.
 *
 * @ContentEntityType(
 *   id = "lexnet_submission",
 *   label = @Translation("Envio LexNET"),
 *   label_collection = @Translation("Envios LexNET"),
 *   label_singular = @Translation("envio LexNET"),
 *   label_plural = @Translation("envios LexNET"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal_lexnet\ListBuilder\LexnetSubmissionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_legal_lexnet\Form\LexnetSubmissionForm",
 *       "add" = "Drupal\jaraba_legal_lexnet\Form\LexnetSubmissionForm",
 *       "edit" = "Drupal\jaraba_legal_lexnet\Form\LexnetSubmissionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_legal_lexnet\Access\LexnetSubmissionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "lexnet_submission",
 *   admin_permission = "administer lexnet",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "subject",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/lexnet-submissions",
 *     "add-form" = "/admin/content/lexnet-submissions/add",
 *     "canonical" = "/admin/content/lexnet-submissions/{lexnet_submission}",
 *     "edit-form" = "/admin/content/lexnet-submissions/{lexnet_submission}/edit",
 *     "delete-form" = "/admin/content/lexnet-submissions/{lexnet_submission}/delete",
 *   },
 *   field_ui_base_route = "jaraba_legal_lexnet.lexnet_submission.settings",
 * )
 */
class LexnetSubmission extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOQUE 1: MULTI-TENANT Y VINCULACION
    // =========================================================================

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant al que pertenece el envio.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['case_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Expediente Vinculado'))
      ->setDescription(new TranslatableMarkup('Expediente juridico vinculado a este envio.'))
      ->setSetting('target_type', 'client_case')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: TIPO DE ESCRITO
    // =========================================================================

    $fields['submission_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Tipo de Escrito'))
      ->setRequired(TRUE)
      ->setDefaultValue('escrito')
      ->setSetting('allowed_values', [
        'demanda' => new TranslatableMarkup('Demanda'),
        'contestacion' => new TranslatableMarkup('Contestacion'),
        'recurso' => new TranslatableMarkup('Recurso'),
        'escrito' => new TranslatableMarkup('Escrito'),
        'solicitud' => new TranslatableMarkup('Solicitud'),
        'subsanacion' => new TranslatableMarkup('Subsanacion'),
      ])
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: DATOS JUDICIALES
    // =========================================================================

    $fields['court'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Organo Judicial'))
      ->setDescription(new TranslatableMarkup('Juzgado o tribunal destino del escrito.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['procedure_number'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Numero de Procedimiento'))
      ->setDescription(new TranslatableMarkup('Numero de autos o procedimiento judicial.'))
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['subject'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Asunto'))
      ->setDescription(new TranslatableMarkup('Asunto o titulo del escrito.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 500)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 4: DOCUMENTOS Y ENVIO
    // =========================================================================

    $fields['document_ids'] = BaseFieldDefinition::create('map')
      ->setLabel(new TranslatableMarkup('Documentos'))
      ->setDescription(new TranslatableMarkup('Mapa de documentos adjuntos al envio (filename => fid).'));

    $fields['submitted_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fecha de Presentacion'))
      ->setDescription(new TranslatableMarkup('Fecha y hora en que se presento el escrito.'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['confirmation_id'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('ID de Confirmacion'))
      ->setDescription(new TranslatableMarkup('Identificador de confirmacion de LexNET tras presentacion exitosa.'))
      ->setSetting('max_length', 128)
      ->setDisplayOptions('view', ['weight' => 7])
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 5: ESTADO Y ERRORES
    // =========================================================================

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado'))
      ->setRequired(TRUE)
      ->setDefaultValue('draft')
      ->setSetting('allowed_values', [
        'draft' => new TranslatableMarkup('Borrador'),
        'submitting' => new TranslatableMarkup('Enviando'),
        'submitted' => new TranslatableMarkup('Presentado'),
        'confirmed' => new TranslatableMarkup('Confirmado'),
        'rejected' => new TranslatableMarkup('Rechazado'),
        'error' => new TranslatableMarkup('Error'),
      ])
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['error_message'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Mensaje de Error'))
      ->setDescription(new TranslatableMarkup('Detalle del error en caso de fallo de envio.'))
      ->setDisplayOptions('form', ['weight' => 9])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['raw_response'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Respuesta Cruda'))
      ->setDescription(new TranslatableMarkup('Respuesta cruda de LexNET para auditoria.'))
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 6: TIMESTAMPS
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Modificado'));

    return $fields;
  }

}
