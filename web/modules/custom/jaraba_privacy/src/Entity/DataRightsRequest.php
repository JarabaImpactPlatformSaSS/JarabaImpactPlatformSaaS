<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * ENTIDAD DATA RIGHTS REQUEST — Solicitudes ARCO-POL.
 *
 * ESTRUCTURA:
 * Content Entity que gestiona las solicitudes de derechos de los interesados
 * según RGPD Art. 15-22: Acceso, Rectificación, Supresión (Cancelación),
 * Oposición, Portabilidad, Olvido y Limitación (ARCO-POL).
 *
 * LÓGICA DE NEGOCIO:
 * - El plazo máximo de respuesta es de 30 días naturales (configurable).
 * - La identidad del solicitante debe verificarse antes de procesar.
 * - Se notifica al DPO por email al crear la solicitud.
 * - El sistema alerta cuando quedan 5 y 2 días para el vencimiento.
 * - El historial completo de la solicitud se mantiene como audit trail.
 *
 * RELACIONES:
 * - tenant_id → Group (referencia al tenant)
 * - handler_id → User (responsable de procesar la solicitud)
 *
 * Spec: Doc 183 §6.1. Plan: FASE 1, Stack Compliance Legal N1.
 *
 * @ContentEntityType(
 *   id = "data_rights_request",
 *   label = @Translation("Data Rights Request"),
 *   label_collection = @Translation("Data Rights Requests"),
 *   label_singular = @Translation("data rights request"),
 *   label_plural = @Translation("data rights requests"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_privacy\ListBuilder\DataRightsRequestListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_privacy\Form\DataRightsRequestForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_privacy\Access\DataRightsRequestAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "data_rights_request",
 *   admin_permission = "administer privacy",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "requester_name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/data-rights-request/{data_rights_request}",
 *     "add-form" = "/admin/content/data-rights-request/add",
 *     "edit-form" = "/admin/content/data-rights-request/{data_rights_request}/edit",
 *     "delete-form" = "/admin/content/data-rights-request/{data_rights_request}/delete",
 *     "collection" = "/admin/content/data-rights-requests",
 *   },
 *   field_ui_base_route = "jaraba_privacy.dpa_agreement.settings",
 * )
 */
class DataRightsRequest extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- TENANT ---

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant al que se dirige la solicitud.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => -10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- SOLICITANTE ---

    $fields['requester_email'] = BaseFieldDefinition::create('email')
      ->setLabel(new TranslatableMarkup('Email del solicitante'))
      ->setDescription(new TranslatableMarkup('Dirección de correo electrónico del interesado.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['requester_name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Nombre del solicitante'))
      ->setDescription(new TranslatableMarkup('Nombre completo del interesado.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- TIPO DE DERECHO ---

    $fields['right_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Tipo de derecho'))
      ->setDescription(new TranslatableMarkup('Derecho ARCO-POL que se ejerce.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'access' => new TranslatableMarkup('Acceso (Art. 15)'),
        'rectification' => new TranslatableMarkup('Rectificación (Art. 16)'),
        'erasure' => new TranslatableMarkup('Supresión / Olvido (Art. 17)'),
        'restriction' => new TranslatableMarkup('Limitación (Art. 18)'),
        'portability' => new TranslatableMarkup('Portabilidad (Art. 20)'),
        'objection' => new TranslatableMarkup('Oposición (Art. 21)'),
      ])
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- DESCRIPCIÓN ---

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Descripción'))
      ->setDescription(new TranslatableMarkup('Descripción detallada de la solicitud del interesado.'))
      ->setDisplayOptions('form', ['weight' => 3, 'type' => 'text_textarea'])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- VERIFICACIÓN DE IDENTIDAD ---

    $fields['identity_verified'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Identidad verificada'))
      ->setDescription(new TranslatableMarkup('Indica si la identidad del solicitante ha sido verificada.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['verification_method'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Método de verificación'))
      ->setDescription(new TranslatableMarkup('Método utilizado para verificar la identidad.'))
      ->setSetting('allowed_values', [
        'session' => new TranslatableMarkup('Sesión autenticada'),
        'otp' => new TranslatableMarkup('Código OTP por email'),
        'document' => new TranslatableMarkup('Documento de identidad'),
        'other' => new TranslatableMarkup('Otro método'),
      ])
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- PLAZOS ---

    $fields['received_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Fecha de recepción'))
      ->setDescription(new TranslatableMarkup('Timestamp de cuándo se recibió la solicitud.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['deadline'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Fecha límite'))
      ->setDescription(new TranslatableMarkup('Fecha límite para responder (30 días desde recepción).'))
      ->setDisplayConfigurable('view', TRUE);

    // --- ESTADO ---

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado'))
      ->setDescription(new TranslatableMarkup('Estado actual de la solicitud.'))
      ->setRequired(TRUE)
      ->setDefaultValue('received')
      ->setSetting('allowed_values', [
        'received' => new TranslatableMarkup('Recibida'),
        'pending_verification' => new TranslatableMarkup('Pendiente de verificación'),
        'in_progress' => new TranslatableMarkup('En proceso'),
        'completed' => new TranslatableMarkup('Completada'),
        'rejected' => new TranslatableMarkup('Rechazada'),
        'expired' => new TranslatableMarkup('Vencida'),
      ])
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- RESPUESTA ---

    $fields['response'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Respuesta'))
      ->setDescription(new TranslatableMarkup('Respuesta oficial a la solicitud del interesado.'))
      ->setDisplayOptions('form', ['weight' => 7, 'type' => 'text_textarea'])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['completed_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Fecha de resolución'))
      ->setDescription(new TranslatableMarkup('Timestamp de cuándo se completó la solicitud.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- RESPONSABLE ---

    $fields['handler_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Responsable'))
      ->setDescription(new TranslatableMarkup('Usuario responsable de procesar esta solicitud (DPO).'))
      ->setSetting('target_type', 'user')
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
   * Comprueba si la solicitud está completada.
   */
  public function isCompleted(): bool {
    return $this->get('status')->value === 'completed';
  }

  /**
   * Comprueba si la solicitud ha vencido.
   */
  public function isExpired(): bool {
    return $this->get('status')->value === 'expired';
  }

  /**
   * Comprueba si la identidad del solicitante está verificada.
   */
  public function isIdentityVerified(): bool {
    return (bool) $this->get('identity_verified')->value;
  }

  /**
   * Calcula los días restantes hasta el vencimiento.
   */
  public function getDaysRemaining(): int {
    $deadline = (int) $this->get('deadline')->value;
    if ($deadline === 0) {
      return 0;
    }
    $remaining = $deadline - \Drupal::time()->getRequestTime();
    return max(0, (int) ceil($remaining / 86400));
  }

}
