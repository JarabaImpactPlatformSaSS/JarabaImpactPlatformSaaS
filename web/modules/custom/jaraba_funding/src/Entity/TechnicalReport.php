<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Memoria Tecnica (TechnicalReport).
 *
 * ESTRUCTURA:
 * Entidad que almacena memorias tecnicas generadas para solicitudes de
 * fondos. Puede generarse manualmente o con asistencia de IA.
 * Las secciones se almacenan como JSON serializado.
 *
 * LOGICA:
 * El campo report_type clasifica la memoria: initial (memoria inicial),
 * progress (informe de progreso), final (memoria final), justification
 * (informe de justificacion). El campo ai_generated indica si la
 * memoria fue generada con asistencia de IA. El PDF exportado se
 * vincula via file_id.
 *
 * RELACIONES:
 * - TechnicalReport -> Group (tenant_id): tenant propietario.
 * - TechnicalReport -> User (uid): usuario generador.
 * - TechnicalReport -> FundingApplication (application_id): solicitud asociada.
 * - TechnicalReport -> File (file_id): PDF generado (nullable).
 *
 * @ContentEntityType(
 *   id = "technical_report",
 *   label = @Translation("Memoria Tecnica"),
 *   label_collection = @Translation("Memorias Tecnicas"),
 *   label_singular = @Translation("memoria tecnica"),
 *   label_plural = @Translation("memorias tecnicas"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_funding\ListBuilder\TechnicalReportListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_funding\Form\TechnicalReportForm",
 *       "add" = "Drupal\jaraba_funding\Form\TechnicalReportForm",
 *       "edit" = "Drupal\jaraba_funding\Form\TechnicalReportForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_funding\Access\TechnicalReportAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "technical_report",
 *   admin_permission = "administer funding",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/technical-reports",
 *     "add-form" = "/admin/content/technical-reports/add",
 *     "canonical" = "/admin/content/technical-reports/{technical_report}",
 *     "edit-form" = "/admin/content/technical-reports/{technical_report}/edit",
 *     "delete-form" = "/admin/content/technical-reports/{technical_report}/delete",
 *   },
 *   field_ui_base_route = "jaraba_funding.technical_report.settings",
 * )
 */
class TechnicalReport extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // --- BLOQUE 1: Identificacion ---

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant propietario de esta memoria tecnica.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['application_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Solicitud'))
      ->setDescription(new TranslatableMarkup('Solicitud de fondos asociada a esta memoria tecnica.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'funding_application')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Titulo'))
      ->setDescription(new TranslatableMarkup('Titulo de la memoria tecnica.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- BLOQUE 2: Tipo y estado ---

    $fields['report_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Tipo de memoria'))
      ->setDescription(new TranslatableMarkup('Tipo de memoria tecnica segun fase del proyecto.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'initial' => 'Memoria inicial',
        'progress' => 'Informe de progreso',
        'final' => 'Memoria final',
        'justification' => 'Informe de justificacion',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado'))
      ->setDescription(new TranslatableMarkup('Estado de la memoria tecnica.'))
      ->setRequired(TRUE)
      ->setDefaultValue('draft')
      ->setSetting('allowed_values', [
        'draft' => 'Borrador',
        'review' => 'En revision',
        'approved' => 'Aprobada',
        'submitted' => 'Presentada',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- BLOQUE 3: Contenido ---

    $fields['content_sections'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Secciones de contenido'))
      ->setDescription(new TranslatableMarkup('Secciones de la memoria en formato JSON (titulo + contenido por seccion).'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- BLOQUE 4: IA ---

    $fields['ai_generated'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Generada con IA'))
      ->setDescription(new TranslatableMarkup('Indica si la memoria fue generada con asistencia de inteligencia artificial.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ai_model_used'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Modelo IA usado'))
      ->setDescription(new TranslatableMarkup('Identificador del modelo de IA utilizado para la generacion.'))
      ->setSetting('max_length', 50)
      ->setDisplayConfigurable('view', TRUE);

    // --- BLOQUE 5: Archivo ---

    $fields['file_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Archivo PDF'))
      ->setDescription(new TranslatableMarkup('Archivo PDF generado de la memoria tecnica.'))
      ->setSetting('target_type', 'file')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- BLOQUE 6: Metadatos ---

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Fecha de creacion'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Fecha de modificacion'));

    return $fields;
  }

}
