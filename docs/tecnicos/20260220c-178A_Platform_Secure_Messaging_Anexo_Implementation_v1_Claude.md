
ANEXO A: ARTEFACTOS DE IMPLEMENTACIÓN
Claude Code Ready — Ficheros YAML, PHP Annotations, Interfaces, Templates y Tests
Complementa: 178_Platform_Secure_Messaging_v1.docx
Código:	178A_Platform_Secure_Messaging_Anexo_Implementation_v1
Versión:	1.0
Fecha:	Febrero 2026
Propósito:	Cerrar los gaps entre doc 178 (especificación técnica) y los artefactos que Claude Code necesita para generar código sin ambigüedad
Patrón de referencia:	doc 106 (SEPE), doc 172 (Credentials), doc 02 (Core Módulos), doc 01 (Core Entidades)

Gaps Identificados vs. Documentos de Referencia
#	Gap	Referencia que lo tiene	Impacto si no se cierra
G1	Falta jaraba_messaging.info.yml completo	doc 106 (jaraba_sepe_teleformacion.info.yml)	Claude Code no sabe dependencias del módulo ni versión mínima Drupal
G2	Falta jaraba_messaging.services.yml con DI completa	doc 02 (jaraba_core.services.yml con todos los arguments)	Claude Code no puede registrar servicios en el contenedor Symfony/Drupal
G3	Falta jaraba_messaging.routing.yml	doc 106 (rutas SOAP), doc 172 (rutas REST)	Sin routing.yml no hay endpoints funcionales
G4	Falta jaraba_messaging.permissions.yml + matriz de roles	doc 172 (permissions.yml + matriz completa)	Claude Code genera permisos genéricos; sin granularidad por vertical
G5	Entidades definidas como SQL, no como Drupal Entity PHP	doc 106 (SepeCentro con @ContentEntityType + baseFieldDefinitions)	Claude Code debe adivinar los tipos de campo Drupal y las annotations
G6	Faltan interfaces PHP formales de todos los servicios	doc 02 (métodos con firma completa), doc 172 (interfaces explícitas)	Sin interfaces no hay contrato; tests no pueden usar mocks
G7	Falta config/schema/jaraba_messaging.schema.yml	Estándar Drupal para configuración tipada	Drupal lanza warnings sin schema de configuración
G8	Falta config/install/jaraba_messaging.settings.yml	doc 128 (parameters en config/services.yml)	Sin defaults el módulo no arranca correctamente
G9	Falta hook_install() con migraciones de BD	doc 106 (esquema SQL explícito)	Las tablas no se crean automáticamente al instalar
G10	Twig templates sin código concreto	Estándar Drupal theming	Sin plantillas el renderizado es genérico/feo
G11	Componentes React sin props/state/types	Estándar frontend moderno	Claude Code genera React incompleto o con tipos incorrectos
G12	Tests sin escenarios específicos (solo categorías)	doc 172 (ECA tests con escenarios concretos)	Tests sin assertions concretas no verifican nada
 
A1. jaraba_messaging.info.yml [G1]
name: 'Jaraba Secure Messaging'
type: module
description: 'Sistema de mensajería segura bidireccional cifrada en tiempo real para la Jaraba Impact Platform. WebSocket + AES-256-GCM + Redis Pub/Sub + Audit Trail Inmutable.'
package: 'Jaraba Platform'
core_version_requirement: ^10.3 || ^11
php: 8.3

dependencies:
  - drupal:user
  - drupal:system
  - drupal:options
  - group:group
  - eca:eca
  - jaraba_core:jaraba_core
  - jaraba_tenant:jaraba_tenant
  - jaraba_notifications:jaraba_notifications

# Dependencias opcionales (funcionalidad degradada sin ellas)
# - jaraba_buzon_confianza (adjuntos cifrados)
# - jaraba_copilot (RAG sobre mensajes)
# - jaraba_ai_skills (redacción asistida)

configure: jaraba_messaging.settings

test_dependencies:
  - drupal:views
 
A2. jaraba_messaging.services.yml [G2]
services:

  # === ORQUESTADOR CENTRAL ===
  jaraba_messaging.messaging:
    class: Drupal\jaraba_messaging\Service\MessagingService
    arguments:
      - '@jaraba_messaging.conversation'
      - '@jaraba_messaging.message'
      - '@jaraba_messaging.encryption'
      - '@jaraba_messaging.audit'
      - '@jaraba_messaging.notification_bridge'
      - '@jaraba_messaging.attachment_bridge'
      - '@jaraba_messaging.presence'
      - '@jaraba_messaging.search'
      - '@database'
      - '@current_user'
      - '@request_stack'

  # === SERVICIOS CORE ===
  jaraba_messaging.conversation:
    class: Drupal\jaraba_messaging\Service\ConversationService
    arguments:
      - '@entity_type.manager'
      - '@jaraba_tenant.context'
      - '@current_user'
      - '@database'

  jaraba_messaging.message:
    class: Drupal\jaraba_messaging\Service\MessageService
    arguments:
      - '@entity_type.manager'
      - '@jaraba_messaging.encryption'
      - '@database'

  jaraba_messaging.encryption:
    class: Drupal\jaraba_messaging\Service\MessageEncryptionService
    arguments:
      - '@jaraba_messaging.tenant_key'
      - '@logger.channel.jaraba_messaging'

  jaraba_messaging.tenant_key:
    class: Drupal\jaraba_messaging\Service\TenantKeyService
    arguments:
      - '@jaraba_tenant.context'
      - '@config.factory'
    # Reutiliza patrón de doc 88 Buzón de Confianza

  jaraba_messaging.audit:
    class: Drupal\jaraba_messaging\Service\MessageAuditService
    arguments:
      - '@entity_type.manager'
      - '@current_user'
      - '@request_stack'
      - '@database'
      - '@logger.channel.jaraba_messaging'

  # === BRIDGES A ECOSISTEMA ===
  jaraba_messaging.notification_bridge:
    class: Drupal\jaraba_messaging\Service\NotificationBridgeService
    arguments:
      - '@jaraba_messaging.conversation'
      - '@jaraba_messaging.presence'
      - '@jaraba_notifications.notification'   # doc 98
      - '@queue'
      - '@entity_type.manager'

  jaraba_messaging.attachment_bridge:
    class: Drupal\jaraba_messaging\Service\AttachmentBridgeService
    arguments:
      - '@jaraba_buzon_confianza.vault'         # doc 88 (opcional)
      - '@jaraba_messaging.audit'
      - '@logger.channel.jaraba_messaging'
    calls:
      - [setVaultService, ['@?jaraba_buzon_confianza.vault']]  # Inyección opcional

  # === PRESENCIA Y WEBSOCKET ===
  jaraba_messaging.presence:
    class: Drupal\jaraba_messaging\Service\PresenceService
    arguments:
      - '@jaraba_core.redis'                   # Redis client (doc 02)
      - '@jaraba_tenant.context'

  jaraba_messaging.websocket_server:
    class: Drupal\jaraba_messaging\WebSocket\MessagingWebSocketServer
    arguments:
      - '@jaraba_messaging.messaging'
      - '@jaraba_messaging.presence'
      - '@jaraba_messaging.connection_manager'
      - '@logger.channel.jaraba_messaging'

  jaraba_messaging.connection_manager:
    class: Drupal\jaraba_messaging\WebSocket\ConnectionManager
    arguments:
      - '@jaraba_core.redis'

  # === BÚSQUEDA ===
  jaraba_messaging.search:
    class: Drupal\jaraba_messaging\Service\SearchService
    arguments:
      - '@jaraba_messaging.encryption'
      - '@jaraba_ai.rag'                       # doc 93/129 (Qdrant)
      - '@database'
      - '@queue'
      - '@jaraba_tenant.context'

  # === RETENCIÓN RGPD ===
  jaraba_messaging.retention:
    class: Drupal\jaraba_messaging\Service\RetentionService
    arguments:
      - '@entity_type.manager'
      - '@jaraba_messaging.audit'
      - '@database'
      - '@config.factory'
      - '@logger.channel.jaraba_messaging'

  # === QUEUE WORKERS ===
  jaraba_messaging.queue.message_index:
    class: Drupal\jaraba_messaging\Queue\MessageIndexWorker
    arguments:
      - '@jaraba_messaging.encryption'
      - '@jaraba_messaging.search'
    tags:
      - { name: drush.command }

  jaraba_messaging.queue.retention_cleanup:
    class: Drupal\jaraba_messaging\Queue\RetentionCleanupWorker
    arguments:
      - '@jaraba_messaging.retention'
    tags:
      - { name: drush.command }

  # === LOGGER ===
  logger.channel.jaraba_messaging:
    parent: logger.channel_base
    arguments: ['jaraba_messaging']
 
A3. jaraba_messaging.routing.yml [G3]
# ============================================
# CONVERSATIONS
# ============================================

jaraba_messaging.conversations.list:
  path: '/api/v1/messaging/conversations'
  defaults:
    _controller: '\Drupal\jaraba_messaging\Controller\ConversationController::list'
  methods: [GET]
  requirements:
    _permission: 'use jaraba messaging'
  options:
    _auth: ['jwt_auth', 'cookie']
    no_cache: TRUE

jaraba_messaging.conversations.create:
  path: '/api/v1/messaging/conversations'
  defaults:
    _controller: '\Drupal\jaraba_messaging\Controller\ConversationController::create'
  methods: [POST]
  requirements:
    _permission: 'create messaging conversations'
  options:
    _auth: ['jwt_auth', 'cookie']

jaraba_messaging.conversations.view:
  path: '/api/v1/messaging/conversations/{uuid}'
  defaults:
    _controller: '\Drupal\jaraba_messaging\Controller\ConversationController::view'
  methods: [GET]
  requirements:
    _custom_access: '\Drupal\jaraba_messaging\Access\ConversationAccessCheck::access'
    uuid: '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'
  options:
    _auth: ['jwt_auth', 'cookie']

jaraba_messaging.conversations.update:
  path: '/api/v1/messaging/conversations/{uuid}'
  defaults:
    _controller: '\Drupal\jaraba_messaging\Controller\ConversationController::update'
  methods: [PATCH]
  requirements:
    _custom_access: '\Drupal\jaraba_messaging\Access\ConversationAccessCheck::access'
  options:
    _auth: ['jwt_auth', 'cookie']

jaraba_messaging.conversations.close:
  path: '/api/v1/messaging/conversations/{uuid}'
  defaults:
    _controller: '\Drupal\jaraba_messaging\Controller\ConversationController::close'
  methods: [DELETE]
  requirements:
    _custom_access: '\Drupal\jaraba_messaging\Access\ConversationOwnerAccessCheck::access'
  options:
    _auth: ['jwt_auth', 'cookie']

jaraba_messaging.conversations.participants.add:
  path: '/api/v1/messaging/conversations/{uuid}/participants'
  defaults:
    _controller: '\Drupal\jaraba_messaging\Controller\ConversationController::addParticipant'
  methods: [POST]
  requirements:
    _custom_access: '\Drupal\jaraba_messaging\Access\ConversationOwnerAccessCheck::access'
  options:
    _auth: ['jwt_auth', 'cookie']

jaraba_messaging.conversations.participants.remove:
  path: '/api/v1/messaging/conversations/{uuid}/participants/{uid}'
  defaults:
    _controller: '\Drupal\jaraba_messaging\Controller\ConversationController::removeParticipant'
  methods: [DELETE]
  requirements:
    _custom_access: '\Drupal\jaraba_messaging\Access\ConversationOwnerAccessCheck::access'
    uid: '\d+'
  options:
    _auth: ['jwt_auth', 'cookie']

# ============================================
# MESSAGES
# ============================================

jaraba_messaging.messages.list:
  path: '/api/v1/messaging/conversations/{uuid}/messages'
  defaults:
    _controller: '\Drupal\jaraba_messaging\Controller\MessageController::list'
  methods: [GET]
  requirements:
    _custom_access: '\Drupal\jaraba_messaging\Access\ConversationAccessCheck::access'
  options:
    _auth: ['jwt_auth', 'cookie']

jaraba_messaging.messages.send:
  path: '/api/v1/messaging/conversations/{uuid}/messages'
  defaults:
    _controller: '\Drupal\jaraba_messaging\Controller\MessageController::send'
  methods: [POST]
  requirements:
    _custom_access: '\Drupal\jaraba_messaging\Access\MessageSendAccessCheck::access'
  options:
    _auth: ['jwt_auth', 'cookie']

jaraba_messaging.messages.edit:
  path: '/api/v1/messaging/messages/{uuid}'
  defaults:
    _controller: '\Drupal\jaraba_messaging\Controller\MessageController::edit'
  methods: [PATCH]
  requirements:
    _custom_access: '\Drupal\jaraba_messaging\Access\MessageOwnerAccessCheck::access'
  options:
    _auth: ['jwt_auth', 'cookie']

jaraba_messaging.messages.delete:
  path: '/api/v1/messaging/messages/{uuid}'
  defaults:
    _controller: '\Drupal\jaraba_messaging\Controller\MessageController::delete'
  methods: [DELETE]
  requirements:
    _custom_access: '\Drupal\jaraba_messaging\Access\MessageOwnerAccessCheck::access'
  options:
    _auth: ['jwt_auth', 'cookie']

jaraba_messaging.messages.reactions:
  path: '/api/v1/messaging/messages/{uuid}/reactions'
  defaults:
    _controller: '\Drupal\jaraba_messaging\Controller\MessageController::addReaction'
  methods: [POST]
  requirements:
    _custom_access: '\Drupal\jaraba_messaging\Access\ConversationAccessCheck::access'
  options:
    _auth: ['jwt_auth', 'cookie']

jaraba_messaging.messages.read:
  path: '/api/v1/messaging/conversations/{uuid}/read'
  defaults:
    _controller: '\Drupal\jaraba_messaging\Controller\MessageController::markRead'
  methods: [POST]
  requirements:
    _custom_access: '\Drupal\jaraba_messaging\Access\ConversationAccessCheck::access'
  options:
    _auth: ['jwt_auth', 'cookie']

jaraba_messaging.messages.attachments:
  path: '/api/v1/messaging/conversations/{uuid}/attachments'
  defaults:
    _controller: '\Drupal\jaraba_messaging\Controller\MessageController::uploadAttachment'
  methods: [POST]
  requirements:
    _custom_access: '\Drupal\jaraba_messaging\Access\AttachmentAccessCheck::access'
  options:
    _auth: ['jwt_auth', 'cookie']

# ============================================
# SEARCH & UTILITIES
# ============================================

jaraba_messaging.search:
  path: '/api/v1/messaging/search'
  defaults:
    _controller: '\Drupal\jaraba_messaging\Controller\SearchController::search'
  methods: [GET]
  requirements:
    _permission: 'use jaraba messaging'
  options:
    _auth: ['jwt_auth', 'cookie']

jaraba_messaging.unread_count:
  path: '/api/v1/messaging/unread-count'
  defaults:
    _controller: '\Drupal\jaraba_messaging\Controller\ConversationController::unreadCount'
  methods: [GET]
  requirements:
    _permission: 'use jaraba messaging'
  options:
    _auth: ['jwt_auth', 'cookie']

jaraba_messaging.presence:
  path: '/api/v1/messaging/presence/{uid}'
  defaults:
    _controller: '\Drupal\jaraba_messaging\Controller\PresenceController::status'
  methods: [GET]
  requirements:
    _permission: 'use jaraba messaging'
    uid: '\d+'
  options:
    _auth: ['jwt_auth', 'cookie']

jaraba_messaging.export:
  path: '/api/v1/messaging/export/{uuid}'
  defaults:
    _controller: '\Drupal\jaraba_messaging\Controller\ExportController::export'
  methods: [POST]
  requirements:
    _custom_access: '\Drupal\jaraba_messaging\Access\ConversationOwnerAccessCheck::access'
  options:
    _auth: ['jwt_auth', 'cookie']

jaraba_messaging.audit:
  path: '/api/v1/messaging/conversations/{uuid}/audit'
  defaults:
    _controller: '\Drupal\jaraba_messaging\Controller\AuditController::list'
  methods: [GET]
  requirements:
    _custom_access: '\Drupal\jaraba_messaging\Access\AuditAccessCheck::access'
  options:
    _auth: ['jwt_auth', 'cookie']

# ============================================
# ADMIN SETTINGS
# ============================================

jaraba_messaging.settings:
  path: '/admin/config/jaraba/messaging'
  defaults:
    _form: '\Drupal\jaraba_messaging\Form\MessagingSettingsForm'
    _title: 'Jaraba Secure Messaging Settings'
  requirements:
    _permission: 'administer jaraba messaging'
 
A4. jaraba_messaging.permissions.yml [G4]
# --- PERMISOS BÁSICOS ---
use jaraba messaging:
  title: 'Usar mensajería segura'
  description: 'Permite enviar y recibir mensajes dentro de la plataforma'

create messaging conversations:
  title: 'Crear conversaciones'
  description: 'Permite iniciar nuevas conversaciones con otros usuarios'

# --- PERMISOS DE CONTENIDO ---
send messaging attachments:
  title: 'Enviar adjuntos'
  description: 'Permite adjuntar archivos a mensajes (enrutados a Buzón de Confianza)'

edit own messages:
  title: 'Editar mensajes propios'
  description: 'Permite editar mensajes enviados (ventana de 15 minutos)'

delete own messages:
  title: 'Eliminar mensajes propios'
  description: 'Soft-delete de mensajes propios'

# --- PERMISOS DE GESTIÓN ---
manage conversation participants:
  title: 'Gestionar participantes'
  description: 'Añadir/eliminar participantes de conversaciones propias'

view conversation audit:
  title: 'Ver audit log de conversaciones'
  description: 'Acceder al registro inmutable de una conversación'

export conversations:
  title: 'Exportar conversaciones'
  description: 'Exportar historial completo (RGPD art. 20)'

# --- PERMISOS DE MODERACIÓN ---
moderate messaging conversations:
  title: 'Moderar conversaciones'
  description: 'Silenciar, cerrar o archivar cualquier conversación del tenant'

delete any messages:
  title: 'Eliminar cualquier mensaje'
  description: 'Eliminar mensajes de otros usuarios (moderación)'
  restrict access: TRUE

# --- PERMISOS DE ADMINISTRACIÓN ---
administer jaraba messaging:
  title: 'Administrar mensajería'
  description: 'Configurar retención RGPD, rate limits, cifrado y parámetros globales'
  restrict access: TRUE

bypass messaging rate limit:
  title: 'Bypass rate limit'
  description: 'Enviar mensajes sin límite de frecuencia (solo sistema/admin)'
  restrict access: TRUE

view all tenant conversations:
  title: 'Ver todas las conversaciones del tenant'
  description: 'Acceso de auditoría a todas las conversaciones (compliance officer)'
  restrict access: TRUE

A4.1 Matriz de Roles por Vertical
Permiso	Cliente	Candidato	Emprendedor	Profesional	Orientador	Mentor	Admin Tenant	Super Admin
use jaraba messaging	✓	✓	✓	✓	✓	✓	✓	✓
create messaging conversations				✓	✓	✓	✓	✓
send messaging attachments	✓	✓	✓	✓	✓	✓	✓	✓
edit own messages	✓	✓	✓	✓	✓	✓	✓	✓
delete own messages	✓	✓	✓	✓	✓	✓	✓	✓
manage conversation participants				✓	✓	✓	✓	✓
view conversation audit				✓	✓	✓	✓	✓
export conversations				✓			✓	✓
moderate messaging conversations							✓	✓
delete any messages							✓	✓
administer jaraba messaging								✓
bypass messaging rate limit								✓
view all tenant conversations							✓ (solo lectura)	✓
Nota: Los clientes no pueden iniciar conversaciones directamente. Un profesional/orientador/mentor inicia la conversación y el cliente responde. Esto previene spam y mantiene control del flujo profesional.
 
A5. Entidades Drupal con Annotations PHP [G5]
A5.1 SecureConversation.php
<?php
namespace Drupal\jaraba_messaging\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * @ContentEntityType(
 *   id = "secure_conversation",
 *   label = @Translation("Secure Conversation"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_messaging\SecureConversationListBuilder",
 *     "access" = "Drupal\jaraba_messaging\Access\SecureConversationAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\jaraba_messaging\Form\SecureConversationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *   },
 *   base_table = "secure_conversation",
 *   admin_permission = "administer jaraba messaging",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 * )
 */
class SecureConversation extends ContentEntityBase {

  use EntityChangedTrait;

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setSettings(['max_length' => 255]);

    $fields['conversation_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Conversation Type'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'direct' => 'Direct (1:1)',
        'case_linked' => 'Case Linked',
        'booking_linked' => 'Booking Linked',
        'group_dm' => 'Group DM',
        'support' => 'Support',
        'ai_assisted' => 'AI Assisted',
      ]);

    $fields['context_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Context Entity Type'))
      ->setSettings(['max_length' => 64]);

    $fields['context_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Context Entity ID'));

    $fields['initiated_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Initiated By'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE);

    $fields['encryption_key_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Encryption Key ID'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 64]);

    $fields['is_confidential'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Confidential Mode'))
      ->setDefaultValue(FALSE)
      ->setDescription(t('When TRUE, messages are excluded from AI indexing'));

    $fields['max_participants'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Max Participants'))
      ->setDefaultValue(10);

    $fields['is_archived'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Archived'))->setDefaultValue(FALSE);

    $fields['is_muted_by_system'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('System Muted'))->setDefaultValue(FALSE);

    $fields['last_message_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last Message At'));

    $fields['last_message_preview'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Last Message Preview'))
      ->setSettings(['max_length' => 255]);

    $fields['last_message_sender_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Last Message Sender'))
      ->setSetting('target_type', 'user');

    $fields['message_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Message Count'))->setDefaultValue(0);

    $fields['participant_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Participant Count'))->setDefaultValue(0);

    $fields['metadata'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Metadata'));

    $fields['retention_days'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Retention Days (RGPD)'));

    $fields['auto_close_days'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Auto-close Days'));

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setRequired(TRUE)
      ->setDefaultValue('active')
      ->setSetting('allowed_values', [
        'active' => 'Active',
        'archived' => 'Archived',
        'closed' => 'Closed',
        'deleted' => 'Deleted',
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }
}

A5.2 SecureMessage.php (Campos críticos — custom table)
SecureMessage usa custom table en lugar de ContentEntityBase porque necesita BIGSERIAL para alto volumen y campos BLOB para cifrado. Se gestiona via custom SQL en hook_install().
// NOTA: SecureMessage NO extiende ContentEntityBase.
// Usa tabla custom gestionada por hook_schema() + service layer.
// Razón: MEDIUMBLOB no es soportado por BaseFieldDefinition,
//          y BIGSERIAL requiere definición manual de schema.

// Ver A9 (hook_install) para el SQL de creación de tabla.
// Los métodos CRUD están en MessageService.php (doc 178 sec 5).

// Clase DTO para transferir datos del mensaje:
<?php
namespace Drupal\jaraba_messaging\Model;

final class SecureMessageDTO {
  public function __construct(
    public readonly ?int $id,
    public readonly string $uuid,
    public readonly int $conversationId,
    public readonly int $senderId,
    public readonly string $messageType,
    public readonly string $bodyEncrypted,
    public readonly string $bodyIv,
    public readonly string $bodyTag,
    public readonly string $bodyPlaintextHash,
    public readonly int $bodyLength,
    public readonly ?string $bodyPreviewEncrypted,
    public readonly ?int $replyToId,
    public readonly ?int $forwardFromId,
    public readonly ?array $attachmentIds,
    public readonly int $attachmentCount,
    public readonly ?array $mentions,
    public readonly ?array $reactions,
    public readonly ?array $metadata,
    public readonly bool $isEdited,
    public readonly ?string $editedAt,
    public readonly ?string $originalBodyEncrypted,
    public readonly bool $isDeleted,
    public readonly ?string $deletedAt,
    public readonly ?int $deletedBy,
    public readonly ?string $deliveredAt,
    public readonly string $status,
    public readonly string $created,
  ) {}
}
 
A6. Interfaces PHP de Servicios [G6]
<?php
namespace Drupal\jaraba_messaging\Service;

interface MessagingServiceInterface {

  /**
   * Envía un mensaje en una conversación existente.
   *
   * @param int $conversationId
   * @param string $body Texto plano del mensaje
   * @param string $messageType text|file|image|voice_note|system|ai_suggestion
   * @param int|null $replyToId ID del mensaje al que responde
   * @param string[] $attachmentUuids UUIDs de docs en Buzón de Confianza
   * @param array $mentions [{uid: int, name: string, offset: int}]
   * @param array $metadata Datos extensibles
   *
   * @return SecureMessageDTO Mensaje creado
   * @throws \Drupal\jaraba_messaging\Exception\RateLimitException
   * @throws \Drupal\jaraba_messaging\Exception\AccessDeniedException
   */
  public function sendMessage(
    int $conversationId,
    string $body,
    string $messageType = 'text',
    ?int $replyToId = NULL,
    array $attachmentUuids = [],
    array $mentions = [],
    array $metadata = [],
  ): SecureMessageDTO;

  /**
   * Crea nueva conversación.
   *
   * @param int[] $participantIds UIDs de participantes (sin incluir initiator)
   * @param string $type direct|case_linked|booking_linked|group_dm|support
   * @param string|null $contextType Entity type del contexto
   * @param int|null $contextId Entity ID del contexto
   * @param string|null $firstMessage Mensaje inicial opcional
   *
   * @return SecureConversation Conversación creada
   */
  public function startConversation(
    array $participantIds,
    string $type = 'direct',
    ?string $contextType = NULL,
    ?int $contextId = NULL,
    ?string $firstMessage = NULL,
  ): SecureConversation;

  /**
   * Marca mensajes como leídos hasta un punto.
   */
  public function markAsRead(int $conversationId, int $upToMessageId): void;

  /**
   * Edita un mensaje propio (ventana de 15 min).
   *
   * @throws \Drupal\jaraba_messaging\Exception\EditWindowExpiredException
   */
  public function editMessage(int $messageId, string $newBody): SecureMessageDTO;

  /**
   * Soft-delete de un mensaje.
   */
  public function deleteMessage(int $messageId): void;
}


interface MessageEncryptionServiceInterface {

  /**
   * Cifra texto plano con clave del tenant.
   * Algoritmo: AES-256-GCM con IV aleatorio de 12 bytes.
   */
  public function encrypt(string $plaintext, int $tenantId): EncryptedPayload;

  /**
   * Descifra payload cifrado.
   * @throws \Drupal\jaraba_messaging\Exception\DecryptionException
   * @throws \Drupal\jaraba_messaging\Exception\IntegrityException
   */
  public function decrypt(EncryptedPayload $payload, int $tenantId): string;

  /**
   * Cifra preview (primeros 100 chars) para listados.
   */
  public function encryptPreview(string $plaintext, int $tenantId): EncryptedPayload;
}


interface MessageAuditServiceInterface {

  /**
   * Registra evento en audit log inmutable con hash chain.
   * hash_chain = SHA-256(previous_hash + json(entry))
   */
  public function log(
    int $conversationId,
    ?int $messageId,
    string $action,
    array $details = [],
  ): void;

  /**
   * Verifica integridad de la cadena de hashes.
   * @return IntegrityReport {valid: bool, total: int, brokenAt: ?int}
   */
  public function verifyIntegrity(int $conversationId): IntegrityReport;
}


interface PresenceServiceInterface {
  public function setOnline(int $userId): void;
  public function setOffline(int $userId): void;
  public function isOnline(int $userId): bool;
  public function setTyping(int $userId, int $conversationId): void;
  public function broadcastMessage(SecureConversation $conv, SecureMessageDTO $msg, int $senderId): void;
  public function broadcastReadReceipt(int $conversationId, int $userId, int $upToMessageId): void;
}


interface SearchServiceInterface {
  /** Encola mensaje para indexación en Qdrant (asíncrono). */
  public function enqueueForIndexing(SecureMessageDTO $message, string $plaintext): void;

  /** Búsqueda combinada full-text + semántica. */
  public function search(int $tenantId, string $query, ?int $conversationId = NULL, int $limit = 20): array;
}
 
A7. Configuración: Schema + Settings [G7, G8]
A7.1 config/schema/jaraba_messaging.schema.yml
jaraba_messaging.settings:
  type: config_object
  label: 'Jaraba Messaging Settings'
  mapping:
    encryption:
      type: mapping
      label: 'Encryption settings'
      mapping:
        algorithm:
          type: string
          label: 'Encryption algorithm'
        argon2id_memory:
          type: integer
          label: 'Argon2id memory cost (KB)'
        argon2id_iterations:
          type: integer
          label: 'Argon2id iterations'
    rate_limiting:
      type: mapping
      label: 'Rate limiting'
      mapping:
        messages_per_minute_per_user:
          type: integer
          label: 'Max messages per minute per user per conversation'
        messages_per_minute_per_conversation:
          type: integer
          label: 'Max messages per minute per conversation'
    retention:
      type: mapping
      label: 'RGPD Retention'
      mapping:
        default_message_retention_days:
          type: integer
          label: 'Default message retention (days)'
        audit_log_retention_days:
          type: integer
          label: 'Audit log retention (days)'
        auto_close_inactive_days:
          type: integer
          label: 'Auto-close inactive conversations (days)'
    websocket:
      type: mapping
      label: 'WebSocket settings'
      mapping:
        host:
          type: string
          label: 'WebSocket server host'
        port:
          type: integer
          label: 'WebSocket server port'
        ping_interval:
          type: integer
          label: 'Ping interval (seconds)'
        online_ttl:
          type: integer
          label: 'Online presence TTL (seconds)'
    notifications:
      type: mapping
      label: 'Notification settings'
      mapping:
        offline_delay_seconds:
          type: integer
          label: 'Delay before sending offline notification'
        digest_interval_hours:
          type: integer
          label: 'Unread digest interval (hours)'

A7.2 config/install/jaraba_messaging.settings.yml
encryption:
  algorithm: 'aes-256-gcm'
  argon2id_memory: 65536       # 64MB
  argon2id_iterations: 3
rate_limiting:
  messages_per_minute_per_user: 30
  messages_per_minute_per_conversation: 100
retention:
  default_message_retention_days: 730    # 2 años
  audit_log_retention_days: 2555         # 7 años
  auto_close_inactive_days: 90
websocket:
  host: '0.0.0.0'
  port: 8090
  ping_interval: 30
  online_ttl: 120
notifications:
  offline_delay_seconds: 30
  digest_interval_hours: 4
 
A8. hook_schema() + hook_install() [G9]
Las tablas secure_message, message_audit_log y message_read_receipt usan hook_schema() porque requieren tipos de columna no soportados por BaseFieldDefinition (MEDIUMBLOB, BIGSERIAL, VARBINARY, DATETIME(6)). La tabla secure_conversation y conversation_participant usan ContentEntityBase con BaseFieldDefinition (secciones A5.1 y similar).
<?php
/**
 * Implements hook_schema().
 */
function jaraba_messaging_schema() {
  $schema = [];

  $schema['secure_message'] = [
    'description' => 'Mensajes cifrados con AES-256-GCM',
    'fields' => [
      'id' => ['type' => 'serial', 'size' => 'big', 'not null' => TRUE],
      'uuid' => ['type' => 'varchar_ascii', 'length' => 36, 'not null' => TRUE],
      'conversation_id' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
      'sender_id' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
      'message_type' => ['type' => 'varchar', 'length' => 24, 'not null' => TRUE, 'default' => 'text'],
      'body_encrypted' => ['type' => 'blob', 'size' => 'medium', 'not null' => TRUE],
      'body_iv' => ['type' => 'blob', 'size' => 'normal', 'not null' => TRUE],       // 12 bytes
      'body_tag' => ['type' => 'blob', 'size' => 'normal', 'not null' => TRUE],      // 16 bytes
      'body_plaintext_hash' => ['type' => 'char', 'length' => 64, 'not null' => TRUE],
      'body_length' => ['type' => 'int', 'not null' => TRUE, 'default' => 0],
      'body_preview_encrypted' => ['type' => 'blob', 'size' => 'normal'],
      'reply_to_id' => ['type' => 'int', 'size' => 'big'],
      'forward_from_id' => ['type' => 'int', 'size' => 'big'],
      'attachment_ids' => ['type' => 'text', 'size' => 'normal'],                    // JSON
      'attachment_count' => ['type' => 'int', 'size' => 'tiny', 'not null' => TRUE, 'default' => 0],
      'mentions' => ['type' => 'text', 'size' => 'normal'],                          // JSON
      'reactions' => ['type' => 'text', 'size' => 'normal'],                         // JSON
      'metadata' => ['type' => 'text', 'size' => 'normal'],                          // JSON
      'is_edited' => ['type' => 'int', 'size' => 'tiny', 'not null' => TRUE, 'default' => 0],
      'edited_at' => ['type' => 'varchar', 'length' => 26],                          // DATETIME(3) as string
      'original_body_encrypted' => ['type' => 'blob', 'size' => 'medium'],
      'is_deleted' => ['type' => 'int', 'size' => 'tiny', 'not null' => TRUE, 'default' => 0],
      'deleted_at' => ['type' => 'varchar', 'length' => 26],
      'deleted_by' => ['type' => 'int', 'unsigned' => TRUE],
      'delivered_at' => ['type' => 'varchar', 'length' => 26],
      'status' => ['type' => 'varchar', 'length' => 16, 'not null' => TRUE, 'default' => 'sending'],
      'created' => ['type' => 'varchar', 'length' => 26, 'not null' => TRUE],
    ],
    'primary key' => ['id'],
    'unique keys' => ['uuid' => ['uuid']],
    'indexes' => [
      'idx_msg_conv_created' => ['conversation_id', ['created', 'DESC']],
      'idx_msg_sender' => ['sender_id'],
      'idx_msg_reply' => ['reply_to_id'],
      'idx_msg_status' => ['conversation_id', 'status'],
    ],
  ];

  $schema['message_audit_log'] = [
    'description' => 'Audit log inmutable con hash chain SHA-256',
    'fields' => [
      'id' => ['type' => 'serial', 'size' => 'big', 'not null' => TRUE],
      'tenant_id' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
      'conversation_id' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
      'message_id' => ['type' => 'int', 'size' => 'big'],
      'action' => ['type' => 'varchar', 'length' => 40, 'not null' => TRUE],
      'actor_id' => ['type' => 'int', 'unsigned' => TRUE],
      'actor_ip' => ['type' => 'varchar', 'length' => 45, 'not null' => TRUE],
      'actor_user_agent' => ['type' => 'varchar', 'length' => 512],
      'details' => ['type' => 'text', 'size' => 'normal'],          // JSON
      'created' => ['type' => 'varchar', 'length' => 32, 'not null' => TRUE],
      'hash_chain' => ['type' => 'char', 'length' => 64, 'not null' => TRUE],
    ],
    'primary key' => ['id'],
    'indexes' => [
      'idx_audit_tenant' => ['tenant_id', 'created'],
      'idx_audit_conv' => ['conversation_id', 'created'],
      'idx_audit_action' => ['action'],
    ],
  ];

  $schema['message_read_receipt'] = [
    'description' => 'Read receipts para conversaciones de 3+ participantes',
    'fields' => [
      'id' => ['type' => 'serial', 'size' => 'big', 'not null' => TRUE],
      'message_id' => ['type' => 'int', 'size' => 'big', 'not null' => TRUE],
      'user_id' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
      'read_at' => ['type' => 'varchar', 'length' => 26, 'not null' => TRUE],
    ],
    'primary key' => ['id'],
    'unique keys' => ['msg_user' => ['message_id', 'user_id']],
    'indexes' => ['idx_read_msg' => ['message_id']],
  ];

  return $schema;
}
 
A9. Templates Twig + TypeScript Types [G10, G11]
A9.1 templates/chat-panel.html.twig
{#
 # @file
 # Container principal del panel de mensajería segura.
 # Renderiza el punto de montaje React y pasa datos de Drupal.
 #}
{% set classes = [
  'jaraba-messaging-panel',
  is_open ? 'jaraba-messaging-panel--open',
  is_mobile ? 'jaraba-messaging-panel--mobile',
] %}

<aside{{ attributes.addClass(classes) }}
  data-drupal-selector="messaging-panel"
  data-ws-url="{{ websocket_url }}"
  data-user-id="{{ current_user_id }}"
  data-tenant-id="{{ tenant_id }}"
  data-api-base="/api/v1/messaging"
  data-jwt-token="{{ jwt_token }}"
  role="complementary"
  aria-label="{{ 'Secure Messaging'|t }}"
>
  <div id="jaraba-messaging-root">
    {# React monta aquí #}
    <div class="jaraba-messaging-loading" aria-live="polite">
      {{ 'Cargando mensajes...'|t }}
    </div>
  </div>
</aside>

A9.2 TypeScript Types para React Components
// types/messaging.ts

export interface Conversation {
  uuid: string;
  type: 'direct' | 'case_linked' | 'booking_linked' | 'group_dm' | 'support' | 'ai_assisted';
  title: string;
  context?: { type: string; id: number; label: string };
  participants: Participant[];
  lastMessage?: MessagePreview;
  unreadCount: number;
  isPinned: boolean;
  isMuted: boolean;
  status: 'active' | 'archived' | 'closed';
  created: string; // ISO 8601
}

export interface Participant {
  uid: number;
  name: string;
  role: 'owner' | 'participant' | 'observer' | 'system';
  online: boolean;
  avatarUrl?: string;
}

export interface Message {
  id: number;
  uuid: string;
  conversationId: number;
  senderId: number;
  senderName: string;
  messageType: 'text' | 'file' | 'image' | 'voice_note' | 'system' | 'ai_suggestion';
  body: string; // Descifrado en cliente
  replyTo?: { id: number; preview: string; senderName: string };
  attachments: Attachment[];
  mentions: Mention[];
  reactions: Record<string, number[]>; // emoji -> uid[]
  isEdited: boolean;
  isDeleted: boolean;
  status: 'sending' | 'sent' | 'delivered' | 'read' | 'failed';
  created: string; // ISO 8601
}

export interface Attachment {
  uuid: string;
  filename: string;
  mimeType: string;
  size: number;
  thumbnailUrl?: string;
}

export interface Mention { uid: number; name: string; offset: number; }
export interface MessagePreview { preview: string; senderName: string; created: string; }

// Props de componentes React principales

export interface ChatPanelProps {
  wsUrl: string;
  userId: number;
  tenantId: number;
  apiBase: string;
  jwtToken: string;
  initialConversationUuid?: string; // Abrir directamente en una conv
  onClose: () => void;
}

export interface ConversationListProps {
  conversations: Conversation[];
  activeConversationUuid: string | null;
  onSelect: (uuid: string) => void;
  onSearch: (query: string) => void;
  isLoading: boolean;
}

export interface MessageThreadProps {
  messages: Message[];
  currentUserId: number;
  onLoadMore: () => Promise<void>; // Scroll up = load older
  onReply: (messageId: number) => void;
  onReact: (messageId: number, emoji: string) => void;
  hasMore: boolean;
  isLoading: boolean;
}

export interface MessageComposerProps {
  conversationUuid: string;
  replyTo: Message | null;
  onSend: (body: string, attachments: File[], replyToId?: number) => Promise<void>;
  onCancelReply: () => void;
  onTyping: () => void;
  canAttach: boolean;
  disabled: boolean;
}

// WebSocket message types (cliente <-> servidor)

export type WSClientMessage =
  | { type: 'message'; conversation_id: number; body: string; message_type: string; reply_to_id?: number; attachment_uuids: string[]; client_id: string }
  | { type: 'typing'; conversation_id: number; is_typing: boolean }
  | { type: 'read_receipt'; conversation_id: number; up_to_message_id: number }
  | { type: 'ping' };

export type WSServerMessage =
  | { type: 'message.new'; conversation_id: number; message: Message }
  | { type: 'message.ack'; client_id: string; message_id: number; status: string }
  | { type: 'typing'; conversation_id: number; user_id: number; user_name: string; is_typing: boolean }
  | { type: 'read_receipt'; conversation_id: number; user_id: number; up_to_message_id: number }
  | { type: 'presence'; user_id: number; status: 'online' | 'offline' | 'away' }
  | { type: 'pong' };
 
A10. Especificaciones de Test [G12]
ID	Test	Tipo	Escenario	Assertions
T01	Cifrado round-trip	Unit	Cifrar 'Hola mundo' con tenant_id=1, descifrar resultado	assertEqual(descifrado, 'Hola mundo'); assertNotEquals(cifrado, 'Hola mundo'); assertLength(iv, 12); assertLength(tag, 16)
T02	Cifrado con clave incorrecta	Unit	Cifrar con tenant 1, intentar descifrar con tenant 2	assertThrows(DecryptionException)
T03	Hash de integridad	Unit	Cifrar, alterar 1 byte del ciphertext, descifrar	assertThrows(IntegrityException) — GCM detecta tamper
T04	Crear conversación 1:1	Kernel	User A crea conv con User B en tenant 1	assertCount(2, participants); assertEquals('active', status); assertNotNull(uuid)
T05	Evitar conv duplicada	Kernel	User A crea conv con User B dos veces	Retorna la existente en lugar de crear duplicado; assertEquals(conv1.id, conv2.id)
T06	Enviar mensaje	Kernel	User A envía 'Hola' en conv con User B	assertCount(1, messages); assertEquals(1, conv.message_count); assertEquals(1, participantB.unread_count)
T07	Rate limit	Kernel	User A envía 31 mensajes en 1 minuto	31º mensaje lanza RateLimitException; 30 primeros succeeded
T08	Audit log hash chain	Kernel	Crear conv + enviar 3 mensajes + verificar integridad	verifyIntegrity() retorna {valid: true, total: 4 entries}
T09	Audit log tamper	Kernel	Crear 3 audit entries, modificar details de entry #2, verificar	verifyIntegrity() retorna {valid: false, brokenAt: 2}
T10	Permisos: cliente no inicia conv	Functional	User con rol 'cliente' hace POST /conversations	assertResponse(403)
T11	Permisos: cross-tenant	Functional	User de tenant 1 accede a conv de tenant 2	assertResponse(403) — aislamiento multi-tenant
T12	API: listar conversaciones	Functional	GET /conversations con 3 convs activas y 1 archivada	assertCount(3, data); assert all status=active; assertExists(meta.cursor)
T13	API: paginación mensajes	Functional	GET /conversations/{uuid}/messages?limit=25 con 60 msgs	assertCount(25, data); assertExists(meta.cursor.next); mensajes en orden DESC
T14	Editar dentro de ventana	Functional	Enviar mensaje, esperar 1s, editar	assertResponse(200); is_edited=true; original_body_encrypted ≠ null
T15	Editar fuera de ventana	Functional	Enviar mensaje, simular 16 min, editar	assertResponse(422) — EditWindowExpiredException
T16	Soft-delete	Functional	Enviar mensaje, deletarlo, listar conversación	Mensaje aparece con is_deleted=true, body='Mensaje eliminado'
T17	RGPD export	Functional	POST /export/{uuid} como owner	assertResponse(200); JSON con todos los mensajes descifrados
T18	WebSocket auth	Integration	Conectar WS sin JWT	Conexión rechazada con error 4001 (unauthorized)
T19	WebSocket mensaje E2E	Integration	A envía msg por WS, B conectado recibe	B recibe message.new con body descifrado en <100ms
T20	Typing indicator	Integration	A envía typing event, B lo recibe	B recibe typing con is_typing=true; desaparece en 5s auto

——— Fin del Anexo A ———
178A_Platform_Secure_Messaging_Anexo_Implementation_v1.docx | Jaraba Impact Platform | Febrero 2026
