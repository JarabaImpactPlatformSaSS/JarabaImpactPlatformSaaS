<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * ENTIDAD WHISTLEBLOWER REPORT — Reporte del canal de denuncias.
 *
 * ESTRUCTURA:
 * Content Entity que almacena los reportes del canal de denuncias.
 * Los reportes pueden ser anónimos, con un código de seguimiento único
 * para que el denunciante pueda consultar el estado sin identificarse.
 *
 * LÓGICA DE NEGOCIO:
 * - Los reportes son de solo lectura una vez creados (protección de integridad).
 * - La descripción y el contacto del reportante se cifran en la BD.
 * - Cada reporte tiene un tracking_code único para seguimiento anónimo.
 * - Solo usuarios con permiso 'manage whistleblower reports' pueden ver los reportes.
 * - No se permite eliminar reportes (requisito legal Directiva EU 2019/1937).
 *
 * RELACIONES:
 * - assigned_to → User (usuario asignado a investigar)
 * - tenant_id → Group (referencia al tenant)
 *
 * Spec: Doc 184 §2.5. Plan: FASE 5, Stack Compliance Legal N1.
 *
 * @ContentEntityType(
 *   id = "whistleblower_report",
 *   label = @Translation("Whistleblower Report"),
 *   label_collection = @Translation("Whistleblower Reports"),
 *   label_singular = @Translation("whistleblower report"),
 *   label_plural = @Translation("whistleblower reports"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal\ListBuilder\WhistleblowerReportListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_legal\Form\WhistleblowerReportForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_legal\Access\WhistleblowerReportAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "whistleblower_report",
 *   admin_permission = "administer legal",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "tracking_code",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/whistleblower-report/{whistleblower_report}",
 *     "add-form" = "/admin/content/whistleblower-report/add",
 *     "edit-form" = "/admin/content/whistleblower-report/{whistleblower_report}/edit",
 *     "delete-form" = "/admin/content/whistleblower-report/{whistleblower_report}/delete",
 *     "collection" = "/admin/content/whistleblower-reports",
 *   },
 *   field_ui_base_route = "jaraba_legal.whistleblower_report.settings",
 * )
 */
class WhistleblowerReport extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- IDENTIFICACIÓN ---

    $fields['tracking_code'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Código de seguimiento'))
      ->setDescription(new TranslatableMarkup('Código único para seguimiento anónimo de la denuncia.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 32)
      ->setDisplayOptions('form', ['weight' => -10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- CLASIFICACIÓN ---

    $fields['category'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Categoría'))
      ->setDescription(new TranslatableMarkup('Categoría de la denuncia.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'fraud' => new TranslatableMarkup('Fraude'),
        'corruption' => new TranslatableMarkup('Corrupción'),
        'harassment' => new TranslatableMarkup('Acoso'),
        'safety' => new TranslatableMarkup('Seguridad'),
        'environment' => new TranslatableMarkup('Medio ambiente'),
        'data_protection' => new TranslatableMarkup('Protección de datos'),
        'other' => new TranslatableMarkup('Otro'),
      ])
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- CONTENIDO CIFRADO ---

    $fields['description_encrypted'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Descripción (cifrada)'))
      ->setDescription(new TranslatableMarkup('Descripción detallada de la denuncia, almacenada cifrada.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- SEVERIDAD Y ESTADO ---

    $fields['severity'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Severidad'))
      ->setDescription(new TranslatableMarkup('Nivel de severidad de la denuncia.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'low' => new TranslatableMarkup('Baja'),
        'medium' => new TranslatableMarkup('Media'),
        'high' => new TranslatableMarkup('Alta'),
        'critical' => new TranslatableMarkup('Crítica'),
      ])
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado'))
      ->setDescription(new TranslatableMarkup('Estado actual de la investigación.'))
      ->setRequired(TRUE)
      ->setDefaultValue('received')
      ->setSetting('allowed_values', [
        'received' => new TranslatableMarkup('Recibida'),
        'investigating' => new TranslatableMarkup('En investigación'),
        'resolved' => new TranslatableMarkup('Resuelta'),
        'dismissed' => new TranslatableMarkup('Desestimada'),
      ])
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- CONTACTO DEL DENUNCIANTE ---

    $fields['reporter_contact_encrypted'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Contacto del denunciante (cifrado)'))
      ->setDescription(new TranslatableMarkup('Datos de contacto cifrados del denunciante, si los proporcionó.'))
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_anonymous'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Anónimo'))
      ->setDescription(new TranslatableMarkup('Indica si la denuncia fue realizada de forma anónima.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- ASIGNACIÓN ---

    $fields['assigned_to'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Asignado a'))
      ->setDescription(new TranslatableMarkup('Usuario responsable de investigar la denuncia.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- RESOLUCIÓN ---

    $fields['resolution'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Resolución'))
      ->setDescription(new TranslatableMarkup('Descripción de la resolución o resultado de la investigación.'))
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['resolved_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Fecha de resolución'))
      ->setDescription(new TranslatableMarkup('Timestamp UTC de resolución de la denuncia.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- METADATA ---

    $fields['ip_address'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Dirección IP'))
      ->setDescription(new TranslatableMarkup('IP desde la que se realizó la denuncia.'))
      ->setSetting('max_length', 45)
      ->setDisplayConfigurable('view', TRUE);

    // --- TENANT ---

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant relacionado con la denuncia.'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- TIMESTAMPS ---

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'))
      ->setDescription(new TranslatableMarkup('Fecha de creación del registro.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Modificado'))
      ->setDescription(new TranslatableMarkup('Fecha de última modificación.'));

    return $fields;
  }

  /**
   * Comprueba si la denuncia ha sido resuelta.
   */
  public function isResolved(): bool {
    return $this->get('status')->value === 'resolved';
  }

  /**
   * Comprueba si la denuncia ha sido desestimada.
   */
  public function isDismissed(): bool {
    return $this->get('status')->value === 'dismissed';
  }

  /**
   * Comprueba si la denuncia fue anónima.
   */
  public function isAnonymous(): bool {
    return (bool) $this->get('is_anonymous')->value;
  }

  /**
   * Comprueba si la denuncia está en investigación.
   */
  public function isUnderInvestigation(): bool {
    return $this->get('status')->value === 'investigating';
  }

  /**
   * Comprueba si la denuncia es de severidad crítica.
   */
  public function isCritical(): bool {
    return $this->get('severity')->value === 'critical';
  }

  /**
   * Comprueba si la denuncia tiene un investigador asignado.
   */
  public function hasAssignee(): bool {
    return !empty($this->get('assigned_to')->target_id);
  }

}
