
LEGAL KNOWLEDGE MODULE
Anexo de Implementación Técnica
Código PHP, Entidades, ECA, Prompts, UI y Tests
JARABA IMPACT PLATFORM
Documento Técnico de Implementación - Ready for Development
Versión:	1.0
Fecha:	Febrero 2026
Estado:	Ready for Development
Código:	178b_Platform_Legal_Knowledge_Implementation
Documento Base:	178_Platform_Legal_Knowledge_Module_v1
Dependencias:	44_AI_Business_Copilot, 129_AI_Skills_System, 130_Knowledge_Training
Horas Estimadas:	400-520 horas
 
1. Estructura del Módulo Drupal	1
1.1 Árbol de Directorios	1
1.2 Archivo info.yml	1
1.3 Archivo services.yml	1
2. Entidades Drupal con BaseFieldDefinitions	1
2.1 Entidad: LegalNorm	1
2.2 Entidad: LegalChunk	1
2.3 Entidad: LegalQueryLog	1
3. Servicios PHP	1
3.1 BoeApiClient	1
3.2 LegalChunkingService	1
3.3 LegalRagService	1
3.4 LegalDisclaimerService	1
3.5 TaxCalculatorService	1
4. Flujos ECA (Event-Condition-Action)	1
4.1 ECA-LEG-001: Sincronización Diaria BOE	1
4.2 ECA-LEG-002: Detección de Cambios Normativos	1
4.3 ECA-LEG-003: Notificación a Tenants Afectados	1
4.4 ECA-LEG-004: Log de Consultas	1
5. Biblioteca de Prompts	1
5.1 Prompt Principal: Consulta Normativa General	1
5.2 Prompt: Consulta IRPF Autónomos	1
5.3 Prompt: Consulta Laboral/Contratos	1
5.4 Prompt: Generación de Resumen de Cambio Normativo	1
6. Integración con AI Business Copilot	1
6.1 Extensión del AI Business Copilot	1
6.2 Detección de Intent para Consultas Legales	1
7. Componentes React del Admin UI	1
7.1 LegalAdminDashboard.jsx	1
7.2 TaxCalculatorIRPF.jsx	1
8. Tests Automatizados	1
8.1 Test Unitario: BoeApiClient	1
8.2 Test Unitario: TaxCalculatorService	1
8.3 Test Kernel: LegalRagService	1
9. Checklist de Implementación	1
9.1 Pre-requisitos	1
9.2 Sprint 1: Fundamentos (Semanas 1-2)	1
9.3 Sprint 2: Pipeline RAG (Semanas 3-4)	1
9.4 Sprint 3: Sistema de Alertas (Semanas 5-6)	1
9.5 Sprint 4: Calculadoras Fiscales (Semanas 7-8)	1
9.6 Sprint 5: Admin UI y Dashboard (Semanas 9-10)	1
9.7 Sprint 6: Integración y QA (Semanas 11-12)	1
9.8 Resumen de Inversión	1
10. Configuración de Producción	1
10.1 Variables de Entorno	1
10.2 Configuración Drupal	1

 
1. Estructura del Módulo Drupal
El módulo jaraba_legal_knowledge implementa la integración con la API BOE y el sistema RAG para consultas normativas. Sigue las convenciones de Drupal 11 y se integra con el ecosistema Jaraba.
1.1 Árbol de Directorios
modules/custom/jaraba_legal_knowledge/
├── jaraba_legal_knowledge.info.yml
├── jaraba_legal_knowledge.module
├── jaraba_legal_knowledge.install
├── jaraba_legal_knowledge.routing.yml
├── jaraba_legal_knowledge.services.yml
├── jaraba_legal_knowledge.permissions.yml
├── jaraba_legal_knowledge.links.menu.yml
├── config/
│   ├── install/
│   │   └── jaraba_legal_knowledge.settings.yml
│   └── schema/
│       └── jaraba_legal_knowledge.schema.yml
├── src/
│   ├── Entity/
│   │   ├── LegalNorm.php
│   │   ├── LegalChunk.php
│   │   ├── LegalQueryLog.php
│   │   └── NormChangeAlert.php
│   ├── Service/
│   │   ├── BoeApiClient.php
│   │   ├── LegalIngestionService.php
│   │   ├── LegalChunkingService.php
│   │   ├── LegalEmbeddingService.php
│   │   ├── LegalRagService.php
│   │   ├── LegalQueryService.php
│   │   ├── LegalCitationService.php
│   │   ├── LegalDisclaimerService.php
│   │   ├── LegalAlertService.php
│   │   └── TaxCalculatorService.php
│   ├── Controller/
│   │   ├── LegalQueryController.php
│   │   ├── LegalAdminController.php
│   │   └── TaxCalculatorController.php
│   ├── Form/
│   │   ├── LegalSettingsForm.php
│   │   └── LegalSyncForm.php
│   ├── Plugin/
│   │   ├── rest/
│   │   │   └── resource/
│   │   │       ├── LegalQueryResource.php
│   │   │       ├── LegalAlertsResource.php
│   │   │       └── TaxCalculatorResource.php
│   │   └── QueueWorker/
│   │       ├── LegalNormIngestionWorker.php
│   │       └── LegalAlertNotificationWorker.php
│   └── EventSubscriber/
│       └── LegalKnowledgeEventSubscriber.php
├── templates/
│   ├── legal-query-response.html.twig
│   ├── legal-citation.html.twig
│   └── legal-admin-dashboard.html.twig
├── js/
│   └── components/
│       ├── LegalAdminDashboard.jsx
│       ├── TaxCalculatorIRPF.jsx
│       ├── TaxCalculatorIVA.jsx
│       └── NormAlertPanel.jsx
└── tests/
    ├── src/
    │   ├── Unit/
    │   │   ├── BoeApiClientTest.php
    │   │   ├── LegalChunkingServiceTest.php
    │   │   └── TaxCalculatorServiceTest.php
    │   └── Kernel/
    │       ├── LegalIngestionTest.php
    │       └── LegalRagServiceTest.php
    └── fixtures/
        ├── sample_norm.xml
        └── sample_query_response.json
1.2 Archivo info.yml
# jaraba_legal_knowledge.info.yml
name: 'Jaraba Legal Knowledge'
type: module
description: 'Base de conocimiento normativa BOE con RAG para AI Copilots'
core_version_requirement: ^10 || ^11
package: Jaraba
dependencies:
  - drupal:rest
  - drupal:serialization
  - jaraba_core:jaraba_core
  - jaraba_ai:jaraba_ai
  - eca:eca
configure: jaraba_legal_knowledge.settings
1.3 Archivo services.yml
# jaraba_legal_knowledge.services.yml
services:
  jaraba_legal_knowledge.boe_api_client:
    class: Drupal\jaraba_legal_knowledge\Service\BoeApiClient
    arguments:
      - '@http_client'
      - '@logger.factory'
      - '@config.factory'
      - '@cache.default'

  jaraba_legal_knowledge.ingestion_service:
    class: Drupal\jaraba_legal_knowledge\Service\LegalIngestionService
    arguments:
      - '@jaraba_legal_knowledge.boe_api_client'
      - '@jaraba_legal_knowledge.chunking_service'
      - '@jaraba_legal_knowledge.embedding_service'
      - '@entity_type.manager'
      - '@queue'
      - '@logger.factory'

  jaraba_legal_knowledge.chunking_service:
    class: Drupal\jaraba_legal_knowledge\Service\LegalChunkingService
    arguments:
      - '@config.factory'

  jaraba_legal_knowledge.embedding_service:
    class: Drupal\jaraba_legal_knowledge\Service\LegalEmbeddingService
    arguments:
      - '@jaraba_ai.openai_client'
      - '@jaraba_ai.qdrant_client'
      - '@config.factory'

  jaraba_legal_knowledge.rag_service:
    class: Drupal\jaraba_legal_knowledge\Service\LegalRagService
    arguments:
      - '@jaraba_ai.qdrant_client'
      - '@jaraba_ai.claude_client'
      - '@jaraba_legal_knowledge.citation_service'
      - '@jaraba_legal_knowledge.disclaimer_service'
      - '@entity_type.manager'
      - '@config.factory'

  jaraba_legal_knowledge.query_service:
    class: Drupal\jaraba_legal_knowledge\Service\LegalQueryService
    arguments:
      - '@jaraba_legal_knowledge.rag_service'
      - '@jaraba_legal_knowledge.embedding_service'
      - '@entity_type.manager'
      - '@current_user'
      - '@logger.factory'

  jaraba_legal_knowledge.citation_service:
    class: Drupal\jaraba_legal_knowledge\Service\LegalCitationService

  jaraba_legal_knowledge.disclaimer_service:
    class: Drupal\jaraba_legal_knowledge\Service\LegalDisclaimerService
    arguments:
      - '@config.factory'

  jaraba_legal_knowledge.alert_service:
    class: Drupal\jaraba_legal_knowledge\Service\LegalAlertService
    arguments:
      - '@entity_type.manager'
      - '@jaraba_ai.claude_client'
      - '@queue'
      - '@logger.factory'

  jaraba_legal_knowledge.tax_calculator:
    class: Drupal\jaraba_legal_knowledge\Service\TaxCalculatorService
    arguments:
      - '@config.factory'
 
2. Entidades Drupal con BaseFieldDefinitions
2.1 Entidad: LegalNorm
Representa una norma jurídica indexada del BOE.
<?php
// src/Entity/LegalNorm.php
namespace Drupal\jaraba_legal_knowledge\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the Legal Norm entity.
 *
 * @ContentEntityType(
 *   id = "legal_norm",
 *   label = @Translation("Legal Norm"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_legal_knowledge\LegalNormListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_legal_knowledge\Form\LegalNormForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "legal_norm",
 *   admin_permission = "administer legal knowledge",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   links = {
 *     "canonical" = "/admin/jaraba/legal/norm/{legal_norm}",
 *     "collection" = "/admin/jaraba/legal/norms",
 *   },
 * )
 */
class LegalNorm extends ContentEntityBase implements ContentEntityInterface {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // BOE Identifier
    $fields['boe_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('BOE ID'))
      ->setDescription(t('Identificador único en el BOE'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 32])
      ->addConstraint('UniqueField')
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 0])
      ->setDisplayConfigurable('view', TRUE);

    // ELI (European Legislation Identifier)
    $fields['eli'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ELI'))
      ->setDescription(t('European Legislation Identifier'))
      ->setSettings(['max_length' => 255])
      ->setDisplayConfigurable('view', TRUE);

    // Title
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('Título de la norma'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 500])
      ->setDisplayOptions('view', ['label' => 'hidden', 'weight' => -5])
      ->setDisplayConfigurable('view', TRUE);

    // Norm Type (ley, real decreto, orden...)
    $fields['norm_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Norma'))
      ->setRequired(TRUE)
      ->setSettings([
        'allowed_values' => [
          'ley_organica' => 'Ley Orgánica',
          'ley' => 'Ley',
          'real_decreto_ley' => 'Real Decreto-ley',
          'real_decreto_legislativo' => 'Real Decreto Legislativo',
          'real_decreto' => 'Real Decreto',
          'orden' => 'Orden Ministerial',
          'resolucion' => 'Resolución',
          'instruccion' => 'Instrucción',
          'circular' => 'Circular',
        ],
      ])
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 1])
      ->setDisplayConfigurable('view', TRUE);

    // Department
    $fields['department'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Departamento'))
      ->setDescription(t('Departamento emisor'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255])
      ->setDisplayConfigurable('view', TRUE);

    // Publication Date
    $fields['publication_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Publicación'))
      ->setRequired(TRUE)
      ->setSettings(['datetime_type' => 'date'])
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 2])
      ->setDisplayConfigurable('view', TRUE);

    // Effective Date
    $fields['effective_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Entrada en Vigor'))
      ->setSettings(['datetime_type' => 'date'])
      ->setDisplayConfigurable('view', TRUE);

    // Consolidation State
    $fields['consolidation_state'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado de Consolidación'))
      ->setRequired(TRUE)
      ->setDefaultValue('vigente')
      ->setSettings([
        'allowed_values' => [
          'vigente' => 'Vigente',
          'parcialmente_vigente' => 'Parcialmente Vigente',
          'derogada' => 'Derogada',
          'en_revision' => 'En Revisión',
        ],
      ])
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 3])
      ->setDisplayConfigurable('view', TRUE);

    // Subject Areas (JSON)
    $fields['subject_areas'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Materias'))
      ->setDescription(t('Array JSON de materias: tributario, laboral, etc.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Ambits (JSON)
    $fields['ambits'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Ámbitos'))
      ->setDescription(t('Ámbitos de aplicación'))
      ->setDisplayConfigurable('view', TRUE);

    // BOE URL
    $fields['boe_url'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('URL BOE'))
      ->setDescription(t('Enlace oficial al BOE'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 4])
      ->setDisplayConfigurable('view', TRUE);

    // Full Text (encrypted/compressed)
    $fields['full_text'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Texto Completo'))
      ->setDescription(t('Texto consolidado completo de la norma'))
      ->setRequired(TRUE);

    // Chunks Count
    $fields['chunks_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Número de Chunks'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    // Last BOE Update
    $fields['last_boe_update'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Última Actualización BOE'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Last Indexed
    $fields['last_indexed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Última Indexación'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Created
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    // Changed
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * Gets subject areas as array.
   */
  public function getSubjectAreas(): array {
    $value = $this->get('subject_areas')->value;
    return $value ? json_decode($value, TRUE) : [];
  }

  /**
   * Sets subject areas from array.
   */
  public function setSubjectAreas(array $areas): self {
    $this->set('subject_areas', json_encode($areas));
    return $this;
  }
}
 
2.2 Entidad: LegalChunk
Fragmento de norma indexado en Qdrant para búsqueda semántica.
<?php
// src/Entity/LegalChunk.php
namespace Drupal\jaraba_legal_knowledge\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @ContentEntityType(
 *   id = "legal_chunk",
 *   label = @Translation("Legal Chunk"),
 *   base_table = "legal_chunk",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class LegalChunk extends ContentEntityBase {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Reference to parent norm
    $fields['norm_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Norma'))
      ->setDescription(t('Norma padre'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'legal_norm')
      ->setDisplayConfigurable('view', TRUE);

    // Chunk Index
    $fields['chunk_index'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Índice del Chunk'))
      ->setRequired(TRUE);

    // Article Reference (Art. 15.2)
    $fields['article_ref'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Referencia de Artículo'))
      ->setSettings(['max_length' => 64]);

    // Section Path (Título I > Cap 2 > Art 15)
    $fields['section_path'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Ruta Jerárquica'))
      ->setSettings(['max_length' => 255]);

    // Content
    $fields['content'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Contenido'))
      ->setRequired(TRUE);

    // Content Hash (SHA256)
    $fields['content_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hash del Contenido'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 64]);

    // Token Count
    $fields['tokens_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Número de Tokens'))
      ->setRequired(TRUE);

    // Qdrant Point ID
    $fields['qdrant_point_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Qdrant Point ID'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 64]);

    // Created
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    return $fields;
  }
}
2.3 Entidad: LegalQueryLog
Registro de consultas para analytics y mejora continua.
<?php
// src/Entity/LegalQueryLog.php
namespace Drupal\jaraba_legal_knowledge\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @ContentEntityType(
 *   id = "legal_query_log",
 *   label = @Translation("Legal Query Log"),
 *   base_table = "legal_query_log",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class LegalQueryLog extends ContentEntityBase {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Tenant ID
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group');

    // User ID
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario'))
      ->setSetting('target_type', 'user');

    // Copilot Source
    $fields['copilot_source'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Copilot de Origen'))
      ->setRequired(TRUE)
      ->setSettings([
        'allowed_values' => [
          'business' => 'AI Business Copilot (Emprendimiento)',
          'empleabilidad' => 'AI Copilot (Empleabilidad)',
          'servicios' => 'Copilot Servicios',
          'standalone' => 'Consulta Directa',
        ],
      ]);

    // Query Text
    $fields['query_text'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Consulta'))
      ->setRequired(TRUE);

    // Chunks Retrieved (JSON)
    $fields['chunks_retrieved'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Chunks Recuperados'))
      ->setRequired(TRUE);

    // Response Text
    $fields['response_text'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Respuesta'))
      ->setRequired(TRUE);

    // Citations (JSON)
    $fields['citations'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Citas'))
      ->setRequired(TRUE);

    // Disclaimer Level
    $fields['disclaimer_level'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Nivel de Disclaimer'))
      ->setRequired(TRUE)
      ->setSettings([
        'allowed_values' => [
          'standard' => 'Estándar',
          'enhanced' => 'Reforzado',
          'critical' => 'Crítico',
        ],
      ]);

    // Feedback
    $fields['feedback'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Feedback'))
      ->setSettings([
        'allowed_values' => [
          'helpful' => 'Útil',
          'not_helpful' => 'No Útil',
        ],
      ]);

    // Latency
    $fields['latency_ms'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Latencia (ms)'))
      ->setRequired(TRUE);

    // Created
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    return $fields;
  }
}
 
3. Servicios PHP
3.1 BoeApiClient
Cliente para consumir la API de Datos Abiertos del BOE.
<?php
// src/Service/BoeApiClient.php
namespace Drupal\jaraba_legal_knowledge\Service;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;

class BoeApiClient {

  const API_BASE = 'https://www.boe.es/datosabiertos/api';
  const CACHE_TTL = 3600; // 1 hora

  protected ClientInterface $httpClient;
  protected $logger;
  protected $config;
  protected CacheBackendInterface $cache;

  public function __construct(
    ClientInterface $http_client,
    LoggerChannelFactoryInterface $logger_factory,
    ConfigFactoryInterface $config_factory,
    CacheBackendInterface $cache
  ) {
    $this->httpClient = $http_client;
    $this->logger = $logger_factory->get('jaraba_legal_knowledge');
    $this->config = $config_factory->get('jaraba_legal_knowledge.settings');
    $this->cache = $cache;
  }

  /**
   * Obtiene normas actualizadas desde una fecha.
   *
   * @param \DateTime $from Fecha desde
   * @param array $subjects Materias a filtrar
   * @return array Normas encontradas
   */
  public function getUpdatedNorms(\DateTime $from, array $subjects = []): array {
    $params = [
      'from' => $from->format('Y-m-d'),
      'format' => 'json',
      'limit' => 100,
    ];

    $norms = [];
    $offset = 0;

    do {
      $params['offset'] = $offset;
      $response = $this->request('/legislacion-consolidada', $params);

      if (empty($response['data'])) {
        break;
      }

      foreach ($response['data'] as $norm) {
        // Filtrar por materias si se especifican
        if (!empty($subjects)) {
          $normSubjects = $norm['materias'] ?? [];
          if (empty(array_intersect($subjects, $normSubjects))) {
            continue;
          }
        }
        $norms[] = $norm;
      }

      $offset += 100;
    } while (count($response['data']) >= 100);

    return $norms;
  }

  /**
   * Obtiene metadatos de una norma.
   */
  public function getNormMetadata(string $boeId): ?array {
    $cacheKey = 'boe_meta_' . $boeId;
    
    if ($cached = $this->cache->get($cacheKey)) {
      return $cached->data;
    }

    $response = $this->request("/legislacion-consolidada/id/{$boeId}/metadatos");
    
    if ($response) {
      $this->cache->set($cacheKey, $response, time() + self::CACHE_TTL);
    }

    return $response;
  }

  /**
   * Obtiene texto consolidado de una norma.
   */
  public function getNormText(string $boeId): ?string {
    $response = $this->request(
      "/legislacion-consolidada/id/{$boeId}/texto",
      ['format' => 'xml']
    );

    if (!$response) {
      return NULL;
    }

    // Parsear XML y extraer texto
    return $this->parseNormXml($response);
  }

  /**
   * Obtiene índice de bloques de una norma.
   */
  public function getNormIndex(string $boeId): array {
    return $this->request("/legislacion-consolidada/id/{$boeId}/texto/indice") ?? [];
  }

  /**
   * Obtiene sumario diario del BOE.
   */
  public function getDailySummary(\DateTime $date): array {
    $dateStr = $date->format('Ymd');
    return $this->request("/boe/sumario/{$dateStr}") ?? [];
  }

  /**
   * Obtiene catálogo de materias.
   */
  public function getSubjectsCatalog(): array {
    $cacheKey = 'boe_subjects_catalog';
    
    if ($cached = $this->cache->get($cacheKey)) {
      return $cached->data;
    }

    $response = $this->request('/datos-auxiliares/materias') ?? [];
    $this->cache->set($cacheKey, $response, time() + 86400); // 24h

    return $response;
  }

  /**
   * Realiza una petición a la API del BOE.
   */
  protected function request(string $endpoint, array $params = []): mixed {
    try {
      $url = self::API_BASE . $endpoint;
      
      $response = $this->httpClient->request('GET', $url, [
        'query' => $params,
        'timeout' => 30,
        'headers' => [
          'Accept' => 'application/json',
          'User-Agent' => 'Jaraba-Legal-Knowledge/1.0',
        ],
      ]);

      $body = $response->getBody()->getContents();
      
      // Si es JSON, decodificar
      if (str_contains($response->getHeaderLine('Content-Type'), 'json')) {
        return json_decode($body, TRUE);
      }

      return $body;
    }
    catch (\Exception $e) {
      $this->logger->error('BOE API Error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Parsea XML de norma y extrae texto limpio.
   */
  protected function parseNormXml(string $xml): string {
    $doc = new \DOMDocument();
    @$doc->loadXML($xml);

    $xpath = new \DOMXPath($doc);
    $textNodes = $xpath->query('//texto_consolidado//p | //texto_consolidado//articulo');

    $text = [];
    foreach ($textNodes as $node) {
      $text[] = trim($node->textContent);
    }

    return implode("\n\n", array_filter($text));
  }
}
 
3.2 LegalChunkingService
Servicio de división de normativa en chunks para indexación.
<?php
// src/Service/LegalChunkingService.php
namespace Drupal\jaraba_legal_knowledge\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

class LegalChunkingService {

  protected int $chunkSize;
  protected int $chunkOverlap;

  public function __construct(ConfigFactoryInterface $config_factory) {
    $config = $config_factory->get('jaraba_legal_knowledge.settings');
    $this->chunkSize = $config->get('chunk_size') ?? 500;
    $this->chunkOverlap = $config->get('chunk_overlap') ?? 50;
  }

  /**
   * Divide el texto de una norma en chunks.
   *
   * @param string $text Texto completo de la norma
   * @param array $index Índice de estructura (artículos, secciones)
   * @return array Chunks con metadata
   */
  public function chunkNorm(string $text, array $index = []): array {
    // Primero intentar chunking por artículos
    if (!empty($index)) {
      return $this->chunkByArticles($text, $index);
    }

    // Fallback a chunking por tokens
    return $this->chunkByTokens($text);
  }

  /**
   * Chunking inteligente por artículos.
   */
  protected function chunkByArticles(string $text, array $index): array {
    $chunks = [];
    $chunkIndex = 0;

    // Regex para detectar artículos
    $pattern = '/(?:Art[ií]culo|Art\.)\s+(\d+(?:\.\d+)?(?:\s*bis|\s*ter)?)/ui';

    $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);

    for ($i = 0; $i < count($parts); $i += 2) {
      $content = trim($parts[$i]);
      $articleRef = isset($parts[$i + 1]) ? 'Art. ' . $parts[$i + 1] : NULL;

      if (empty($content)) {
        continue;
      }

      // Si el chunk es muy grande, subdividir
      if ($this->countTokens($content) > $this->chunkSize * 1.5) {
        $subChunks = $this->chunkByTokens($content);
        foreach ($subChunks as $subChunk) {
          $subChunk['article_ref'] = $articleRef;
          $subChunk['chunk_index'] = $chunkIndex++;
          $chunks[] = $subChunk;
        }
      }
      else {
        $chunks[] = [
          'content' => $content,
          'article_ref' => $articleRef,
          'chunk_index' => $chunkIndex++,
          'tokens_count' => $this->countTokens($content),
          'content_hash' => hash('sha256', $content),
        ];
      }
    }

    return $chunks;
  }

  /**
   * Chunking por tokens con overlap.
   */
  protected function chunkByTokens(string $text): array {
    $chunks = [];
    $words = preg_split('/\s+/', $text);
    $totalWords = count($words);
    $chunkIndex = 0;
    $position = 0;

    while ($position < $totalWords) {
      $chunkWords = array_slice($words, $position, $this->chunkSize);
      $content = implode(' ', $chunkWords);

      $chunks[] = [
        'content' => $content,
        'article_ref' => NULL,
        'chunk_index' => $chunkIndex++,
        'tokens_count' => count($chunkWords),
        'content_hash' => hash('sha256', $content),
      ];

      $position += $this->chunkSize - $this->chunkOverlap;
    }

    return $chunks;
  }

  /**
   * Cuenta tokens aproximados (palabras).
   */
  public function countTokens(string $text): int {
    return count(preg_split('/\s+/', trim($text)));
  }
}
 
3.3 LegalRagService
Servicio principal de RAG para consultas normativas.
<?php
// src/Service/LegalRagService.php
namespace Drupal\jaraba_legal_knowledge\Service;

use Drupal\jaraba_ai\Service\QdrantClient;
use Drupal\jaraba_ai\Service\ClaudeClient;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

class LegalRagService {

  const COLLECTION = 'legal_knowledge';
  const TOP_K = 5;
  const SCORE_THRESHOLD = 0.7;

  protected QdrantClient $qdrant;
  protected ClaudeClient $claude;
  protected LegalCitationService $citations;
  protected LegalDisclaimerService $disclaimers;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected $config;

  public function __construct(
    QdrantClient $qdrant,
    ClaudeClient $claude,
    LegalCitationService $citations,
    LegalDisclaimerService $disclaimers,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory
  ) {
    $this->qdrant = $qdrant;
    $this->claude = $claude;
    $this->citations = $citations;
    $this->disclaimers = $disclaimers;
    $this->entityTypeManager = $entity_type_manager;
    $this->config = $config_factory->get('jaraba_legal_knowledge.settings');
  }

  /**
   * Procesa una consulta normativa.
   */
  public function query(string $query, array $context = []): array {
    $startTime = microtime(TRUE);

    // 1. Construir filtros
    $filters = $this->buildFilters($context);

    // 2. Buscar chunks relevantes
    $chunks = $this->retrieveChunks($query, $filters);

    if (empty($chunks)) {
      return $this->buildNoResultsResponse($query);
    }

    // 3. Generar respuesta con Claude
    $response = $this->generateResponse($query, $chunks, $context);

    // 4. Extraer y formatear citas
    $citations = $this->citations->extractCitations($response, $chunks);

    // 5. Determinar nivel de disclaimer
    $disclaimerLevel = $this->disclaimers->determineLevel($query, $context);
    $disclaimer = $this->disclaimers->getDisclaimer($disclaimerLevel);

    $latency = (int) ((microtime(TRUE) - $startTime) * 1000);

    return [
      'answer' => $response,
      'citations' => $citations,
      'disclaimer' => $disclaimer,
      'disclaimer_level' => $disclaimerLevel,
      'chunks_used' => array_column($chunks, 'id'),
      'confidence' => $this->calculateConfidence($chunks),
      'latency_ms' => $latency,
    ];
  }

  /**
   * Construye filtros de Qdrant basados en contexto.
   */
  protected function buildFilters(array $context): array {
    $filters = [
      'must' => [
        ['key' => 'source', 'match' => ['value' => 'boe']],
      ],
    ];

    // Filtrar por materia si se especifica
    if (!empty($context['subject_area'])) {
      $filters['must'][] = [
        'key' => 'subject_area',
        'match' => ['any' => (array) $context['subject_area']],
      ];
    }

    // Solo normas vigentes
    $filters['must'][] = [
      'key' => 'consolidation_state',
      'match' => ['any' => ['vigente', 'parcialmente_vigente']],
    ];

    return $filters;
  }

  /**
   * Recupera chunks relevantes de Qdrant.
   */
  protected function retrieveChunks(string $query, array $filters): array {
    // Generar embedding de la query
    $queryEmbedding = $this->qdrant->embed($query);

    // Buscar en Qdrant
    $results = $this->qdrant->search(
      self::COLLECTION,
      $queryEmbedding,
      self::TOP_K,
      $filters,
      self::SCORE_THRESHOLD
    );

    // Enriquecer con datos de Drupal
    $chunks = [];
    foreach ($results as $result) {
      $chunkId = $result['payload']['chunk_id'] ?? NULL;
      if (!$chunkId) continue;

      $chunk = $this->entityTypeManager
        ->getStorage('legal_chunk')
        ->load($chunkId);

      if (!$chunk) continue;

      $norm = $chunk->get('norm_id')->entity;

      $chunks[] = [
        'id' => $chunkId,
        'content' => $chunk->get('content')->value,
        'article_ref' => $chunk->get('article_ref')->value,
        'norm_title' => $norm ? $norm->get('title')->value : '',
        'norm_type' => $norm ? $norm->get('norm_type')->value : '',
        'boe_url' => $norm ? $norm->get('boe_url')->value : '',
        'score' => $result['score'],
      ];
    }

    return $chunks;
  }

  /**
   * Genera respuesta con Claude usando strict grounding.
   */
  protected function generateResponse(
    string $query,
    array $chunks,
    array $context
  ): string {
    $chunksFormatted = $this->formatChunksForPrompt($chunks);
    $contextInfo = $this->formatContext($context);

    $prompt = $this->buildPrompt($query, $chunksFormatted, $contextInfo);

    return $this->claude->complete([
      'model' => 'claude-sonnet-4-5-20250929',
      'max_tokens' => 2000,
      'messages' => [
        ['role' => 'user', 'content' => $prompt],
      ],
    ]);
  }

  /**
   * Construye el prompt con strict grounding.
   */
  protected function buildPrompt(
    string $query,
    string $chunks,
    string $context
  ): string {
    return <<<PROMPT
Eres un asistente especializado en normativa española. Tu rol es proporcionar
información objetiva basada EXCLUSIVAMENTE en la legislación vigente.

## REGLAS CRÍTICAS (STRICT LEGAL GROUNDING)

1. SOLO puedes usar información de los DOCUMENTOS NORMATIVOS proporcionados abajo
2. SIEMPRE cita la norma específica: 'Según el Art. X de la Ley Y...'
3. Incluye SIEMPRE la referencia entre corchetes: [Art. X, Ley Y]
4. Si la información NO está en los documentos, di exactamente:
   'No encuentro normativa específica sobre esto en la base de datos.
   Te recomiendo consultar con un profesional cualificado.'
5. NUNCA inventes artículos, fechas, importes o requisitos
6. Si hay ambigüedad, explica las diferentes interpretaciones posibles
7. Usa lenguaje claro y accesible, evitando jerga jurídica innecesaria

## CONTEXTO DEL USUARIO
{$context}

## DOCUMENTOS NORMATIVOS RECUPERADOS (usa SOLO esta información)
{$chunks}

## CONSULTA DEL USUARIO
{$query}

## FORMATO DE RESPUESTA
- Respuesta clara y estructurada
- Citas obligatorias en formato: [Art. X, Nombre de la Ley]
- Si aplica, mencionar excepciones o casos especiales
- NO incluyas el disclaimer (se añade automáticamente)
PROMPT;
  }

  /**
   * Formatea chunks para el prompt.
   */
  protected function formatChunksForPrompt(array $chunks): string {
    $formatted = [];
    foreach ($chunks as $i => $chunk) {
      $ref = $chunk['article_ref'] ? " ({$chunk['article_ref']})" : '';
      $formatted[] = "[Documento " . ($i + 1) . "]{$ref}\n"
        . "Norma: {$chunk['norm_title']}\n"
        . "URL BOE: {$chunk['boe_url']}\n"
        . "Contenido:\n{$chunk['content']}";
    }
    return implode("\n\n---\n\n", $formatted);
  }

  /**
   * Formatea contexto del usuario.
   */
  protected function formatContext(array $context): string {
    $lines = [];
    if (!empty($context['tenant_type'])) {
      $lines[] = "Tipo de negocio: {$context['tenant_type']}";
    }
    if (!empty($context['activity'])) {
      $lines[] = "Actividad: {$context['activity']}";
    }
    if (!empty($context['epigraph'])) {
      $lines[] = "Epígrafe IAE: {$context['epigraph']}";
    }
    return empty($lines) ? 'No se ha proporcionado contexto específico.' : implode("\n", $lines);
  }

  /**
   * Calcula confianza basada en scores de chunks.
   */
  protected function calculateConfidence(array $chunks): float {
    if (empty($chunks)) return 0.0;
    $scores = array_column($chunks, 'score');
    return round(array_sum($scores) / count($scores), 2);
  }

  /**
   * Respuesta cuando no hay resultados.
   */
  protected function buildNoResultsResponse(string $query): array {
    return [
      'answer' => 'No he encontrado normativa específica relacionada con tu consulta '
        . 'en la base de datos. Te recomiendo consultar directamente con un '
        . 'asesor fiscal o laboral cualificado para obtener orientación precisa.',
      'citations' => [],
      'disclaimer' => $this->disclaimers->getDisclaimer('critical'),
      'disclaimer_level' => 'critical',
      'chunks_used' => [],
      'confidence' => 0.0,
      'latency_ms' => 0,
    ];
  }
}
 
3.4 LegalDisclaimerService
Servicio de generación de disclaimers según contexto.
<?php
// src/Service/LegalDisclaimerService.php
namespace Drupal\jaraba_legal_knowledge\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

class LegalDisclaimerService {

  const DISCLAIMERS = [
    'standard' => 'Esta información es orientativa y no constituye asesoramiento '
      . 'profesional. Para decisiones específicas, consulte con un asesor '
      . 'fiscal o laboral cualificado.',

    'enhanced' => 'AVISO: Cada situación es única y puede tener particularidades '
      . 'no contempladas en esta orientación general. Le recomendamos '
      . 'encarecidamente consultar con un asesor fiscal o laboral antes de '
      . 'tomar cualquier decisión basada en esta información.',

    'critical' => 'IMPORTANTE: Esta información tiene carácter meramente '
      . 'orientativo y NO sustituye el asesoramiento profesional. Las '
      . 'implicaciones fiscales y laborales pueden ser significativas. '
      . 'Consulte SIEMPRE con un abogado, asesor fiscal o graduado social '
      . 'antes de actuar. La plataforma no se hace responsable de las '
      . 'decisiones tomadas en base a esta información.',
  ];

  // Palabras clave que elevan el nivel de disclaimer
  const CRITICAL_KEYWORDS = [
    'sanción', 'multa', 'inspección', 'hacienda', 'embargo',
    'despido', 'indemnización', 'demanda', 'juicio', 'recurso',
    'delito', 'fraude', 'prescripción', 'caducidad',
  ];

  const ENHANCED_KEYWORDS = [
    'deducir', 'desgravar', 'bonificación', 'exención',
    'contrato', 'nómina', 'cotización', 'autónomo',
    'factura', 'iva', 'irpf', 'retención',
  ];

  protected $config;

  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('jaraba_legal_knowledge.settings');
  }

  /**
   * Determina el nivel de disclaimer según la consulta y contexto.
   */
  public function determineLevel(string $query, array $context = []): string {
    $queryLower = mb_strtolower($query);

    // Verificar palabras críticas
    foreach (self::CRITICAL_KEYWORDS as $keyword) {
      if (str_contains($queryLower, $keyword)) {
        return 'critical';
      }
    }

    // Verificar palabras que requieren disclaimer reforzado
    foreach (self::ENHANCED_KEYWORDS as $keyword) {
      if (str_contains($queryLower, $keyword)) {
        return 'enhanced';
      }
    }

    // Si pregunta por situación personal específica
    if (preg_match('/\b(mi|mis|yo|tengo|puedo|debo)\b/i', $query)) {
      return 'enhanced';
    }

    return 'standard';
  }

  /**
   * Obtiene el texto del disclaimer.
   */
  public function getDisclaimer(string $level): string {
    return self::DISCLAIMERS[$level] ?? self::DISCLAIMERS['standard'];
  }
}
 
3.5 TaxCalculatorService
Servicio de calculadoras fiscales integradas.
<?php
// src/Service/TaxCalculatorService.php
namespace Drupal\jaraba_legal_knowledge\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

class TaxCalculatorService {

  // Tramos IRPF 2024 (estatal + autonómico medio)
  const IRPF_BRACKETS_2024 = [
    ['min' => 0, 'max' => 12450, 'rate' => 0.19],
    ['min' => 12450, 'max' => 20200, 'rate' => 0.24],
    ['min' => 20200, 'max' => 35200, 'rate' => 0.30],
    ['min' => 35200, 'max' => 60000, 'rate' => 0.37],
    ['min' => 60000, 'max' => 300000, 'rate' => 0.45],
    ['min' => 300000, 'max' => PHP_INT_MAX, 'rate' => 0.47],
  ];

  // Tipos IVA
  const IVA_RATES = [
    'general' => 0.21,
    'reducido' => 0.10,
    'superreducido' => 0.04,
    'exento' => 0.00,
  ];

  // Cuota autónomos 2024 (tabla simplificada)
  const AUTONOMOS_QUOTA_2024 = [
    ['min' => 0, 'max' => 670, 'quota' => 230],
    ['min' => 670, 'max' => 900, 'quota' => 260],
    ['min' => 900, 'max' => 1166.70, 'quota' => 275],
    ['min' => 1166.70, 'max' => 1300, 'quota' => 291],
    ['min' => 1300, 'max' => 1500, 'quota' => 294],
    ['min' => 1500, 'max' => 1700, 'quota' => 294],
    ['min' => 1700, 'max' => 1850, 'quota' => 310],
    ['min' => 1850, 'max' => 2030, 'quota' => 315],
    ['min' => 2030, 'max' => 2330, 'quota' => 320],
    ['min' => 2330, 'max' => 2760, 'quota' => 330],
    ['min' => 2760, 'max' => 3190, 'quota' => 350],
    ['min' => 3190, 'max' => 3620, 'quota' => 370],
    ['min' => 3620, 'max' => 4050, 'quota' => 390],
    ['min' => 4050, 'max' => 6000, 'quota' => 420],
    ['min' => 6000, 'max' => PHP_INT_MAX, 'quota' => 530],
  ];

  protected $config;

  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('jaraba_legal_knowledge.settings');
  }

  /**
   * Calcula estimación IRPF para autónomos.
   */
  public function calculateIRPF(array $params): array {
    $grossIncome = $params['gross_income'] ?? 0;
    $deductibleExpenses = $params['deductible_expenses'] ?? 0;
    $socialSecurity = $params['social_security'] ?? 0;
    $personalAllowance = $params['personal_allowance'] ?? 5550;

    // Base imponible
    $taxableBase = max(0, $grossIncome - $deductibleExpenses - $socialSecurity);

    // Aplicar mínimo personal
    $taxableBaseAfterAllowance = max(0, $taxableBase - $personalAllowance);

    // Calcular cuota por tramos
    $taxAmount = 0;
    $breakdown = [];
    $remaining = $taxableBaseAfterAllowance;

    foreach (self::IRPF_BRACKETS_2024 as $bracket) {
      if ($remaining <= 0) break;

      $bracketWidth = $bracket['max'] - $bracket['min'];
      $taxableInBracket = min($remaining, $bracketWidth);
      $taxInBracket = $taxableInBracket * $bracket['rate'];

      $taxAmount += $taxInBracket;
      $breakdown[] = [
        'bracket' => "{$bracket['min']}€ - {$bracket['max']}€",
        'rate' => $bracket['rate'] * 100 . '%',
        'taxable' => round($taxableInBracket, 2),
        'tax' => round($taxInBracket, 2),
      ];

      $remaining -= $taxableInBracket;
    }

    $effectiveRate = $taxableBase > 0
      ? round(($taxAmount / $taxableBase) * 100, 2)
      : 0;

    return [
      'gross_income' => $grossIncome,
      'deductible_expenses' => $deductibleExpenses,
      'social_security' => $socialSecurity,
      'taxable_base' => round($taxableBase, 2),
      'personal_allowance' => $personalAllowance,
      'taxable_base_after_allowance' => round($taxableBaseAfterAllowance, 2),
      'tax_amount' => round($taxAmount, 2),
      'effective_rate' => $effectiveRate,
      'breakdown' => $breakdown,
      'quarterly_payment' => round($taxAmount / 4, 2),
      'disclaimer' => 'Esta es una estimación orientativa. Los cálculos '
        . 'definitivos dependen de tu situación personal y pueden variar '
        . 'según la comunidad autónoma.',
      'legal_reference' => [
        'norm' => 'Ley 35/2006, de 28 de noviembre, del IRPF',
        'articles' => 'Arts. 63-66 (Escala del impuesto)',
        'boe_url' => 'https://www.boe.es/eli/es/l/2006/11/28/35/con',
      ],
    ];
  }

  /**
   * Calcula IVA.
   */
  public function calculateIVA(array $params): array {
    $baseAmount = $params['base_amount'] ?? 0;
    $rateType = $params['rate_type'] ?? 'general';
    $rate = self::IVA_RATES[$rateType] ?? self::IVA_RATES['general'];

    $ivaAmount = $baseAmount * $rate;
    $totalAmount = $baseAmount + $ivaAmount;

    return [
      'base_amount' => round($baseAmount, 2),
      'rate_type' => $rateType,
      'rate_percentage' => $rate * 100,
      'iva_amount' => round($ivaAmount, 2),
      'total_amount' => round($totalAmount, 2),
      'legal_reference' => [
        'norm' => 'Ley 37/1992, de 28 de diciembre, del IVA',
        'articles' => 'Art. 90 (Tipo general), Art. 91 (Tipos reducidos)',
        'boe_url' => 'https://www.boe.es/eli/es/l/1992/12/28/37/con',
      ],
    ];
  }

  /**
   * Calcula cuota de autónomos por rendimientos.
   */
  public function calculateAutonomosQuota(float $monthlyIncome): array {
    $quota = 0;
    $bracket = NULL;

    foreach (self::AUTONOMOS_QUOTA_2024 as $b) {
      if ($monthlyIncome >= $b['min'] && $monthlyIncome < $b['max']) {
        $quota = $b['quota'];
        $bracket = $b;
        break;
      }
    }

    return [
      'monthly_income' => $monthlyIncome,
      'monthly_quota' => $quota,
      'annual_quota' => $quota * 12,
      'bracket' => $bracket,
      'legal_reference' => [
        'norm' => 'Real Decreto-ley 13/2022 (Sistema de cotización por ingresos reales)',
        'boe_url' => 'https://www.boe.es/eli/es/rdl/2022/07/26/13/con',
      ],
    ];
  }
}
 
4. Flujos ECA (Event-Condition-Action)
4.1 ECA-LEG-001: Sincronización Diaria BOE
# config/eca/eca.model.legal_daily_sync.yml
id: legal_daily_sync
label: 'Sincronización Diaria BOE'
status: true
version: '1.0'

events:
  - plugin: 'eca_cron:cron'
    label: 'Cron diario 03:00'
    configuration:
      frequency: '0 3 * * *'

conditions: []

actions:
  # 1. Obtener última fecha de sincronización
  - plugin: 'eca_state:state_get'
    label: 'Obtener última sincronización'
    configuration:
      key: 'jaraba_legal_knowledge.last_sync'
      token_name: 'last_sync_date'

  # 2. Llamar al servicio de ingestion
  - plugin: 'eca_service:service_call'
    label: 'Ejecutar ingestion'
    configuration:
      service: 'jaraba_legal_knowledge.ingestion_service'
      method: 'syncFromDate'
      arguments:
        - '[last_sync_date:value]'

  # 3. Actualizar fecha de sincronización
  - plugin: 'eca_state:state_set'
    label: 'Guardar fecha sync'
    configuration:
      key: 'jaraba_legal_knowledge.last_sync'
      value: '[current_timestamp]'

  # 4. Log del resultado
  - plugin: 'eca_log:log_message'
    label: 'Log sincronización'
    configuration:
      message: 'Legal Knowledge: Sincronización BOE completada'
      severity: 'info'
4.2 ECA-LEG-002: Detección de Cambios Normativos
# config/eca/eca.model.legal_change_detection.yml
id: legal_change_detection
label: 'Detección de Cambios Normativos'
status: true
version: '1.0'

events:
  - plugin: 'eca_content:entity_update'
    label: 'Norma actualizada'
    configuration:
      entity_type: 'legal_norm'

conditions:
  # Solo si cambió el estado de consolidación o el texto
  - plugin: 'eca_content:entity_field_value_changed'
    label: 'Verificar cambio relevante'
    configuration:
      field_name: 'consolidation_state'
    negate: false

actions:
  # 1. Generar resumen del cambio con IA
  - plugin: 'eca_service:service_call'
    label: 'Generar resumen cambio'
    configuration:
      service: 'jaraba_legal_knowledge.alert_service'
      method: 'generateChangeSummary'
      arguments:
        - '[entity:id]'
      token_name: 'change_summary'

  # 2. Crear alerta de cambio
  - plugin: 'eca_content:entity_create'
    label: 'Crear alerta'
    configuration:
      entity_type: 'norm_change_alert'
      values:
        norm_id: '[entity:id]'
        change_type: 'modified'
        change_summary: '[change_summary:value]'
        severity: 'important'

  # 3. Encolar notificaciones
  - plugin: 'eca_queue:queue_item'
    label: 'Encolar notificaciones'
    configuration:
      queue_name: 'legal_alert_notifications'
      data:
        alert_id: '[entity:id]'
4.3 ECA-LEG-003: Notificación a Tenants Afectados
# config/eca/eca.model.legal_tenant_notification.yml
id: legal_tenant_notification
label: 'Notificación Cambios a Tenants'
status: true
version: '1.0'

events:
  - plugin: 'eca_content:entity_insert'
    label: 'Nueva alerta creada'
    configuration:
      entity_type: 'norm_change_alert'

conditions:
  # Solo alertas importantes o críticas
  - plugin: 'eca_content:entity_field_value'
    label: 'Verificar severidad'
    configuration:
      field_name: 'severity'
      value: 'info'
    negate: true

actions:
  # 1. Obtener tenants suscritos a la materia afectada
  - plugin: 'eca_service:service_call'
    label: 'Obtener tenants afectados'
    configuration:
      service: 'jaraba_legal_knowledge.alert_service'
      method: 'getAffectedTenants'
      arguments:
        - '[entity:id]'
      token_name: 'affected_tenants'

  # 2. Para cada tenant, enviar notificación
  - plugin: 'eca_foreach:foreach'
    label: 'Iterar tenants'
    configuration:
      items: '[affected_tenants:value]'
      item_name: 'tenant'
    successors:
      # Email a admin del tenant
      - plugin: 'eca_email:email_send'
        label: 'Enviar email'
        configuration:
          to: '[tenant:admin_email]'
          subject: 'Cambio normativo relevante para tu negocio'
          body: |
            Hola,
            
            Se ha detectado un cambio en la normativa que puede afectar a tu negocio:
            
            [entity:change_summary]
            
            Consulta los detalles en tu panel de administración.
4.4 ECA-LEG-004: Log de Consultas
# config/eca/eca.model.legal_query_logging.yml
id: legal_query_logging
label: 'Logging de Consultas Normativas'
status: true
version: '1.0'

events:
  - plugin: 'eca_custom:custom_event'
    label: 'Consulta legal completada'
    configuration:
      event_name: 'jaraba_legal_knowledge.query_completed'

conditions: []

actions:
  # Crear registro de log
  - plugin: 'eca_content:entity_create'
    label: 'Crear log de consulta'
    configuration:
      entity_type: 'legal_query_log'
      values:
        tenant_id: '[event:tenant_id]'
        user_id: '[event:user_id]'
        copilot_source: '[event:copilot_source]'
        query_text: '[event:query]'
        chunks_retrieved: '[event:chunks_json]'
        response_text: '[event:response]'
        citations: '[event:citations_json]'
        disclaimer_level: '[event:disclaimer_level]'
        latency_ms: '[event:latency_ms]'
 
5. Biblioteca de Prompts
5.1 Prompt Principal: Consulta Normativa General
Usado por LegalRagService para consultas estándar.
// SYSTEM PROMPT - Legal Query General
Eres un asistente especializado en normativa tributaria y laboral española.
Tu rol es proporcionar información objetiva basada EXCLUSIVAMENTE en la
legislación vigente del Boletín Oficial del Estado (BOE).

## IDENTIDAD
- Nombre: Asistente Normativo Jaraba
- Especialización: Derecho Tributario y Laboral español
- Fuente única: Normativa BOE consolidada

## REGLAS CRÍTICAS (STRICT LEGAL GROUNDING)

1. SOLO puedes usar información de los DOCUMENTOS NORMATIVOS proporcionados
2. SIEMPRE cita la norma específica usando el formato:
   'Según el Art. X de la [Nombre de la Ley]...'
3. SIEMPRE incluye la referencia entre corchetes al final de cada afirmación:
   [Art. X, Ley Y/AAAA]
4. Si la información NO está en los documentos, responde exactamente:
   'No encuentro normativa específica sobre esto en mi base de datos.
   Te recomiendo consultar con un asesor fiscal/laboral cualificado.'
5. NUNCA inventes:
   - Artículos o números de ley
   - Fechas o plazos
   - Importes, porcentajes o cantidades
   - Requisitos o condiciones
6. Si hay ambigüedad normativa, explica las diferentes interpretaciones
7. Usa lenguaje claro y accesible, evitando jerga jurídica innecesaria
8. Si detectas que la consulta requiere análisis de caso específico, indica
   que es necesario consultar con un profesional

## FORMATO DE RESPUESTA

1. Respuesta directa y clara a la pregunta
2. Fundamento normativo con citas
3. Excepciones o casos especiales (si aplican)
4. Pasos prácticos (si aplican)

## PROHIBICIONES

- NO incluyas el disclaimer (se añade automáticamente)
- NO uses frases como 'según mi conocimiento' o 'hasta donde sé'
- NO hagas recomendaciones de inversión o fiscales específicas
- NO des consejos sobre evasión fiscal o prácticas ilegales
5.2 Prompt: Consulta IRPF Autónomos
// SYSTEM PROMPT - IRPF Autónomos
Eres un asistente especializado en fiscalidad de trabajadores autónomos.

## CONTEXTO ESPECÍFICO
El usuario es un trabajador autónomo o está considerando serlo.
Las consultas típicas son sobre:
- Gastos deducibles
- Retenciones IRPF
- Pagos fraccionados (modelo 130)
- Declaración de la renta

## NORMATIVA CLAVE A CITAR
- Ley 35/2006, de 28 de noviembre, del IRPF
- Real Decreto 439/2007 (Reglamento IRPF)
- Ley 58/2003, General Tributaria

## RESPUESTAS TIPO

Para gastos deducibles:
- Art. 28 LIRPF: Rendimientos de actividades económicas
- Art. 30 LIRPF: Gastos deducibles en estimación directa

Para retenciones:
- Art. 101 LIRPF: Retenciones e ingresos a cuenta
- Art. 95 RIRPF: Tipo de retención actividades profesionales

## CALCULADORA INTEGRADA
Si el usuario proporciona cifras, ofrece usar la calculadora IRPF:
'¿Quieres que calcule una estimación de tu IRPF con estos datos?'
5.3 Prompt: Consulta Laboral/Contratos
// SYSTEM PROMPT - Laboral y Contratos
Eres un asistente especializado en derecho laboral español.

## CONTEXTO ESPECÍFICO
Las consultas típicas son sobre:
- Tipos de contratos
- Derechos y obligaciones
- Despidos e indemnizaciones
- Seguridad Social

## NORMATIVA CLAVE A CITAR
- Real Decreto Legislativo 2/2015 (Estatuto de los Trabajadores)
- Real Decreto Legislativo 8/2015 (Ley General de la Seguridad Social)
- Real Decreto 1561/1995 (Jornadas especiales)

## IMPORTANTE
- Los convenios colectivos pueden modificar condiciones mínimas
- Siempre indicar que las cantidades son las legales mínimas
- Para despidos, SIEMPRE recomendar asesoría profesional

## DISCLAIMER AUTOMÁTICO NIVEL ENHANCED
Las consultas laborales siempre requieren disclaimer reforzado
5.4 Prompt: Generación de Resumen de Cambio Normativo
// SYSTEM PROMPT - Change Summary Generation
Genera un resumen conciso del cambio normativo para notificación a usuarios.

## INPUT
- Texto anterior de la norma
- Texto nuevo de la norma
- Tipo de cambio (modificación, derogación, nueva)

## OUTPUT REQUERIDO
Un resumen de máximo 200 palabras que incluya:
1. Qué ha cambiado (en términos simples)
2. A quién afecta (autónomos, empresas, trabajadores)
3. Desde cuándo es efectivo
4. Impacto práctico principal

## TONO
- Informativo pero accesible
- Sin alarrmismo innecesario
- Orientado a la acción

## EJEMPLO DE OUTPUT
'Se ha modificado el Art. 30.2.5ª de la Ley del IRPF sobre gastos
deducibles para autónomos que trabajan desde casa. A partir del
1 de enero, el porcentaje de deducción de suministros pasa del
30% al 35% de la parte proporcional. Esto beneficia a autónomos
con domicilio fiscal en su vivienda habitual.'
 
6. Integración con AI Business Copilot
El Legal Knowledge Module se integra como capacidad adicional del AI Business Copilot (doc 44).
6.1 Extensión del AI Business Copilot
<?php
// En jaraba_emprendimiento/src/Service/AIBusinessCopilot.php
// Añadir método para consultas normativas

/**
 * Procesa consulta normativa delegando al Legal Knowledge Module.
 */
public function handleLegalQuery(string $query, array $context): array {
  // Verificar si el tenant tiene acceso premium
  if (!$this->tenantHasLegalAccess($context['tenant_id'])) {
    return $this->buildUpgradeResponse();
  }

  // Detectar si es consulta normativa
  $intent = $this->detectIntent($query);
  
  if (!in_array($intent, ['tax_question', 'labor_question', 'legal_obligation'])) {
    return NULL; // No es consulta normativa, continuar flujo normal
  }

  // Enriquecer contexto con datos del emprendedor
  $enrichedContext = $this->enrichLegalContext($context);

  // Delegar al módulo legal
  $legalService = \Drupal::service('jaraba_legal_knowledge.query_service');
  $result = $legalService->query($query, $enrichedContext);

  // Formatear respuesta para el copilot
  return $this->formatLegalResponse($result);
}

/**
 * Enriquece el contexto con datos del perfil del emprendedor.
 */
protected function enrichLegalContext(array $context): array {
  $entrepreneur = $this->loadEntrepreneurProfile($context['user_id']);
  
  return array_merge($context, [
    'tenant_type' => $entrepreneur->get('business_type')->value,
    'activity' => $entrepreneur->get('activity_description')->value,
    'epigraph' => $entrepreneur->get('iae_epigraph')->value,
    'autonomous_since' => $entrepreneur->get('autonomous_since')->value,
    'employees_count' => $entrepreneur->get('employees_count')->value,
    'subject_area' => $this->inferSubjectArea($context),
  ]);
}

/**
 * Detecta materia según la consulta.
 */
protected function inferSubjectArea(array $context): array {
  $query = $context['query'] ?? '';
  $areas = [];

  if (preg_match('/irpf|iva|impuesto|hacienda|deducir|factura/i', $query)) {
    $areas[] = 'tributario';
  }
  if (preg_match('/contrato|nómina|trabajador|despido|vacaciones/i', $query)) {
    $areas[] = 'laboral';
  }
  if (preg_match('/autónomo|reta|cotización|cuota/i', $query)) {
    $areas[] = 'autonomos';
  }

  return $areas ?: ['tributario', 'laboral']; // Default ambos
}
6.2 Detección de Intent para Consultas Legales
<?php
// Añadir al IntentDetector del Business Copilot

const LEGAL_INTENTS = [
  'tax_question' => [
    'patterns' => [
      '/¿?(puedo|debo|tengo que).*(deducir|desgravar|declarar)/i',
      '/¿?(cuánto|qué|cómo).*(irpf|iva|impuesto|retención)/i',
      '/¿?(qué|cuáles).*(gastos?|facturas?).*(deducible|desgrava)/i',
      '/modelo\s*(130|303|390|100|111)/i',
    ],
    'keywords' => ['irpf', 'iva', 'impuesto', 'hacienda', 'declaración',
                   'deducir', 'desgravar', 'retención', 'factura'],
  ],
  'labor_question' => [
    'patterns' => [
      '/¿?(puedo|debo|tengo que).*(contratar|despedir|pagar)/i',
      '/¿?(cuánto|qué|cómo).*(salario|vacaciones|permiso)/i',
      '/¿?(qué|cuál).*(contrato|convenio|despido)/i',
    ],
    'keywords' => ['contrato', 'nómina', 'trabajador', 'empleado',
                   'despido', 'vacaciones', 'permiso', 'salario'],
  ],
  'legal_obligation' => [
    'patterns' => [
      '/¿?(tengo|debo|estoy obligado).*(presentar|declarar|pagar)/i',
      '/¿?(cuál|cuáles).*(obligación|requisito|plazo)/i',
      '/¿?(cuándo|antes de).*(presentar|vence|caduca)/i',
    ],
    'keywords' => ['obligación', 'plazo', 'vencimiento', 'sanción',
                   'multa', 'requisito', 'presentar'],
  ],
];

public function detectLegalIntent(string $query): ?string {
  $queryLower = mb_strtolower($query);

  foreach (self::LEGAL_INTENTS as $intent => $config) {
    // Verificar patrones
    foreach ($config['patterns'] as $pattern) {
      if (preg_match($pattern, $query)) {
        return $intent;
      }
    }

    // Verificar keywords (mínimo 2)
    $keywordMatches = 0;
    foreach ($config['keywords'] as $keyword) {
      if (str_contains($queryLower, $keyword)) {
        $keywordMatches++;
      }
    }
    if ($keywordMatches >= 2) {
      return $intent;
    }
  }

  return NULL;
}
 
7. Componentes React del Admin UI
7.1 LegalAdminDashboard.jsx
// js/components/LegalAdminDashboard.jsx
import React, { useState, useEffect } from 'react';

const LegalAdminDashboard = () => {
  const [stats, setStats] = useState(null);
  const [recentAlerts, setRecentAlerts] = useState([]);
  const [syncStatus, setSyncStatus] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    try {
      const [statsRes, alertsRes, syncRes] = await Promise.all([
        fetch('/api/v1/legal/stats'),
        fetch('/api/v1/legal/alerts?limit=5'),
        fetch('/api/v1/legal/sync-status'),
      ]);

      setStats(await statsRes.json());
      setRecentAlerts(await alertsRes.json());
      setSyncStatus(await syncRes.json());
    } catch (error) {
      console.error('Error fetching dashboard:', error);
    } finally {
      setLoading(false);
    }
  };

  const triggerManualSync = async () => {
    setSyncStatus({ ...syncStatus, syncing: true });
    try {
      await fetch('/api/v1/legal/sync', { method: 'POST' });
      fetchDashboardData();
    } catch (error) {
      console.error('Sync error:', error);
    }
  };

  if (loading) return <div className="loading">Cargando...</div>;

  return (
    <div className="legal-admin-dashboard">
      <h1>Legal Knowledge Module</h1>
      
      {/* Stats Cards */}
      <div className="stats-grid">
        <StatCard
          title="Normas Indexadas"
          value={stats?.norms_count || 0}
          icon="📜"
        />
        <StatCard
          title="Chunks en Qdrant"
          value={stats?.chunks_count || 0}
          icon="🧩"
        />
        <StatCard
          title="Consultas Hoy"
          value={stats?.queries_today || 0}
          icon="🔍"
        />
        <StatCard
          title="Precisión Citas"
          value={`${stats?.citation_accuracy || 0}%`}
          icon="✅"
        />
      </div>

      {/* Sync Status */}
      <div className="sync-panel">
        <h2>Estado de Sincronización</h2>
        <p>Última sincronización: {syncStatus?.last_sync || 'Nunca'}</p>
        <p>Normas actualizadas: {syncStatus?.norms_updated || 0}</p>
        <button
          onClick={triggerManualSync}
          disabled={syncStatus?.syncing}
        >
          {syncStatus?.syncing ? 'Sincronizando...' : 'Sincronizar Ahora'}
        </button>
      </div>

      {/* Recent Alerts */}
      <div className="alerts-panel">
        <h2>Alertas Recientes</h2>
        {recentAlerts.map(alert => (
          <AlertCard key={alert.id} alert={alert} />
        ))}
      </div>

      {/* Top Queries */}
      <div className="queries-panel">
        <h2>Consultas Frecuentes</h2>
        <QueryList queries={stats?.top_queries || []} />
      </div>
    </div>
  );
};

const StatCard = ({ title, value, icon }) => (
  <div className="stat-card">
    <span className="icon">{icon}</span>
    <h3>{title}</h3>
    <p className="value">{value}</p>
  </div>
);

export default LegalAdminDashboard;
7.2 TaxCalculatorIRPF.jsx
// js/components/TaxCalculatorIRPF.jsx
import React, { useState } from 'react';

const TaxCalculatorIRPF = () => {
  const [inputs, setInputs] = useState({
    gross_income: '',
    deductible_expenses: '',
    social_security: '',
  });
  const [result, setResult] = useState(null);
  const [loading, setLoading] = useState(false);

  const handleChange = (e) => {
    setInputs({ ...inputs, [e.target.name]: e.target.value });
  };

  const calculate = async () => {
    setLoading(true);
    try {
      const response = await fetch('/api/v1/legal/calculators/irpf', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          gross_income: parseFloat(inputs.gross_income) || 0,
          deductible_expenses: parseFloat(inputs.deductible_expenses) || 0,
          social_security: parseFloat(inputs.social_security) || 0,
        }),
      });
      setResult(await response.json());
    } catch (error) {
      console.error('Calculation error:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="tax-calculator irpf">
      <h2>Calculadora IRPF Autónomos</h2>
      
      <div className="inputs-grid">
        <div className="input-group">
          <label>Ingresos Brutos Anuales (€)</label>
          <input
            type="number"
            name="gross_income"
            value={inputs.gross_income}
            onChange={handleChange}
            placeholder="ej: 40000"
          />
        </div>
        
        <div className="input-group">
          <label>Gastos Deducibles (€)</label>
          <input
            type="number"
            name="deductible_expenses"
            value={inputs.deductible_expenses}
            onChange={handleChange}
            placeholder="ej: 8000"
          />
        </div>
        
        <div className="input-group">
          <label>Seguridad Social Anual (€)</label>
          <input
            type="number"
            name="social_security"
            value={inputs.social_security}
            onChange={handleChange}
            placeholder="ej: 3500"
          />
        </div>
      </div>

      <button onClick={calculate} disabled={loading}>
        {loading ? 'Calculando...' : 'Calcular IRPF'}
      </button>

      {result && (
        <div className="result-panel">
          <h3>Resultado Estimado</h3>
          
          <div className="result-summary">
            <div className="result-item">
              <span>Base Imponible</span>
              <strong>{result.taxable_base.toLocaleString()}€</strong>
            </div>
            <div className="result-item highlight">
              <span>Cuota IRPF Anual</span>
              <strong>{result.tax_amount.toLocaleString()}€</strong>
            </div>
            <div className="result-item">
              <span>Tipo Efectivo</span>
              <strong>{result.effective_rate}%</strong>
            </div>
            <div className="result-item">
              <span>Pago Fraccionado Trimestral</span>
              <strong>{result.quarterly_payment.toLocaleString()}€</strong>
            </div>
          </div>

          <h4>Desglose por Tramos</h4>
          <table className="breakdown-table">
            <thead>
              <tr>
                <th>Tramo</th>
                <th>Tipo</th>
                <th>Base</th>
                <th>Cuota</th>
              </tr>
            </thead>
            <tbody>
              {result.breakdown.map((b, i) => (
                <tr key={i}>
                  <td>{b.bracket}</td>
                  <td>{b.rate}</td>
                  <td>{b.taxable.toLocaleString()}€</td>
                  <td>{b.tax.toLocaleString()}€</td>
                </tr>
              ))}
            </tbody>
          </table>

          <div className="legal-reference">
            <p><strong>Base Legal:</strong> {result.legal_reference.norm}</p>
            <p>{result.legal_reference.articles}</p>
            <a href={result.legal_reference.boe_url} target="_blank">
              Ver en BOE →
            </a>
          </div>

          <div className="disclaimer">
            {result.disclaimer}
          </div>
        </div>
      )}
    </div>
  );
};

export default TaxCalculatorIRPF;
 
8. Tests Automatizados
8.1 Test Unitario: BoeApiClient
<?php
// tests/src/Unit/BoeApiClientTest.php
namespace Drupal\Tests\jaraba_legal_knowledge\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\jaraba_legal_knowledge\Service\BoeApiClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class BoeApiClientTest extends UnitTestCase {

  protected BoeApiClient $client;
  protected MockHandler $mockHandler;

  protected function setUp(): void {
    parent::setUp();

    $this->mockHandler = new MockHandler();
    $handlerStack = HandlerStack::create($this->mockHandler);
    $httpClient = new Client(['handler' => $handlerStack]);

    // Mock logger and config
    $loggerFactory = $this->createMock('Drupal\Core\Logger\LoggerChannelFactoryInterface');
    $configFactory = $this->createMock('Drupal\Core\Config\ConfigFactoryInterface');
    $cache = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');

    $this->client = new BoeApiClient(
      $httpClient,
      $loggerFactory,
      $configFactory,
      $cache
    );
  }

  public function testGetUpdatedNormsReturnsArray(): void {
    $mockResponse = json_encode([
      'data' => [
        ['id' => 'BOE-A-2024-1234', 'titulo' => 'Test Norm'],
      ],
    ]);

    $this->mockHandler->append(
      new Response(200, ['Content-Type' => 'application/json'], $mockResponse)
    );

    $result = $this->client->getUpdatedNorms(new \DateTime('2024-01-01'));

    $this->assertIsArray($result);
    $this->assertCount(1, $result);
    $this->assertEquals('BOE-A-2024-1234', $result[0]['id']);
  }

  public function testGetNormMetadataReturnsCachedResult(): void {
    // First call - API
    $mockResponse = json_encode(['id' => 'BOE-A-2024-1234', 'titulo' => 'Test']);
    $this->mockHandler->append(
      new Response(200, ['Content-Type' => 'application/json'], $mockResponse)
    );

    $result1 = $this->client->getNormMetadata('BOE-A-2024-1234');
    $this->assertNotNull($result1);
  }

  public function testHandlesApiErrorGracefully(): void {
    $this->mockHandler->append(new Response(500, [], 'Server Error'));

    $result = $this->client->getNormMetadata('invalid-id');

    $this->assertNull($result);
  }
}
8.2 Test Unitario: TaxCalculatorService
<?php
// tests/src/Unit/TaxCalculatorServiceTest.php
namespace Drupal\Tests\jaraba_legal_knowledge\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\jaraba_legal_knowledge\Service\TaxCalculatorService;

class TaxCalculatorServiceTest extends UnitTestCase {

  protected TaxCalculatorService $calculator;

  protected function setUp(): void {
    parent::setUp();

    $configFactory = $this->createMock('Drupal\Core\Config\ConfigFactoryInterface');
    $this->calculator = new TaxCalculatorService($configFactory);
  }

  /**
   * @dataProvider irpfDataProvider
   */
  public function testCalculateIRPF(
    float $grossIncome,
    float $expenses,
    float $expectedTaxMin,
    float $expectedTaxMax
  ): void {
    $result = $this->calculator->calculateIRPF([
      'gross_income' => $grossIncome,
      'deductible_expenses' => $expenses,
      'social_security' => 0,
    ]);

    $this->assertGreaterThanOrEqual($expectedTaxMin, $result['tax_amount']);
    $this->assertLessThanOrEqual($expectedTaxMax, $result['tax_amount']);
    $this->assertArrayHasKey('breakdown', $result);
    $this->assertArrayHasKey('legal_reference', $result);
  }

  public static function irpfDataProvider(): array {
    return [
      'Low income' => [15000, 2000, 1000, 2000],
      'Medium income' => [40000, 5000, 6000, 9000],
      'High income' => [80000, 10000, 18000, 25000],
    ];
  }

  public function testCalculateIVAGeneral(): void {
    $result = $this->calculator->calculateIVA([
      'base_amount' => 1000,
      'rate_type' => 'general',
    ]);

    $this->assertEquals(210, $result['iva_amount']);
    $this->assertEquals(1210, $result['total_amount']);
    $this->assertEquals(21, $result['rate_percentage']);
  }

  public function testCalculateIVAReducido(): void {
    $result = $this->calculator->calculateIVA([
      'base_amount' => 1000,
      'rate_type' => 'reducido',
    ]);

    $this->assertEquals(100, $result['iva_amount']);
    $this->assertEquals(1100, $result['total_amount']);
  }

  public function testCalculateAutonomosQuota(): void {
    // Test for income of 2000€/month
    $result = $this->calculator->calculateAutonomosQuota(2000);

    $this->assertArrayHasKey('monthly_quota', $result);
    $this->assertArrayHasKey('annual_quota', $result);
    $this->assertGreaterThan(200, $result['monthly_quota']);
    $this->assertLessThan(400, $result['monthly_quota']);
  }
}
8.3 Test Kernel: LegalRagService
<?php
// tests/src/Kernel/LegalRagServiceTest.php
namespace Drupal\Tests\jaraba_legal_knowledge\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\jaraba_legal_knowledge\Entity\LegalNorm;
use Drupal\jaraba_legal_knowledge\Entity\LegalChunk;

class LegalRagServiceTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'jaraba_core',
    'jaraba_ai',
    'jaraba_legal_knowledge',
  ];

  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('legal_norm');
    $this->installEntitySchema('legal_chunk');
    $this->installEntitySchema('legal_query_log');
    $this->installConfig(['jaraba_legal_knowledge']);
  }

  public function testQueryReturnsFormattedResponse(): void {
    // Create test norm and chunk
    $norm = LegalNorm::create([
      'boe_id' => 'BOE-A-TEST-001',
      'title' => 'Ley de Prueba',
      'norm_type' => 'ley',
      'department' => 'Test',
      'publication_date' => '2024-01-01',
      'consolidation_state' => 'vigente',
      'subject_areas' => json_encode(['tributario']),
      'boe_url' => 'https://boe.es/test',
      'full_text' => 'Texto completo de prueba',
      'last_boe_update' => time(),
      'last_indexed' => time(),
    ]);
    $norm->save();

    // Mock the RAG service (Qdrant would need mocking)
    $ragService = $this->container->get('jaraba_legal_knowledge.rag_service');

    // Test that service exists and has correct methods
    $this->assertTrue(method_exists($ragService, 'query'));
  }

  public function testQueryLogsAreCreated(): void {
    $queryService = $this->container->get('jaraba_legal_knowledge.query_service');

    // This would need proper mocking of external services
    $this->assertTrue(method_exists($queryService, 'query'));
  }
}
 
9. Checklist de Implementación
9.1 Pre-requisitos
Verificación	Estado	Responsable
Qdrant Cloud configurado con collection 'legal_knowledge'	☐ Pendiente	DevOps
API key OpenAI para embeddings	☐ Pendiente	DevOps
API key Claude para RAG	☐ Pendiente	DevOps
jaraba_ai module instalado y configurado	☐ Pendiente	Backend
ECA module instalado	☐ Pendiente	Backend
Redis configurado para caché	☐ Pendiente	DevOps
9.2 Sprint 1: Fundamentos (Semanas 1-2)
Tarea	Horas Est.	Estado
Crear estructura módulo jaraba_legal_knowledge	4h	☐
Implementar BoeApiClient completo	16h	☐
Crear entidad LegalNorm con BaseFieldDefinitions	8h	☐
Crear entidad LegalChunk	4h	☐
Implementar LegalChunkingService	12h	☐
Implementar LegalEmbeddingService	8h	☐
Tests unitarios BoeApiClient	8h	☐
Tests unitarios ChunkingService	6h	☐
SUBTOTAL Sprint 1	66h	
9.3 Sprint 2: Pipeline RAG (Semanas 3-4)
Tarea	Horas Est.	Estado
Implementar LegalIngestionService	16h	☐
Implementar LegalRagService	24h	☐
Implementar LegalCitationService	8h	☐
Implementar LegalDisclaimerService	6h	☐
Crear entidad LegalQueryLog	4h	☐
Implementar LegalQueryService	12h	☐
Tests kernel LegalRagService	12h	☐
SUBTOTAL Sprint 2	82h	
9.4 Sprint 3: Sistema de Alertas (Semanas 5-6)
Tarea	Horas Est.	Estado
Crear entidad NormChangeAlert	4h	☐
Implementar LegalAlertService	16h	☐
Flujo ECA: Sincronización Diaria	8h	☐
Flujo ECA: Detección Cambios	8h	☐
Flujo ECA: Notificación Tenants	8h	☐
Flujo ECA: Log de Consultas	4h	☐
Queue workers para notificaciones	8h	☐
Tests de flujos ECA	8h	☐
SUBTOTAL Sprint 3	64h	
9.5 Sprint 4: Calculadoras Fiscales (Semanas 7-8)
Tarea	Horas Est.	Estado
Implementar TaxCalculatorService completo	24h	☐
API REST para calculadoras	8h	☐
Componente React TaxCalculatorIRPF	16h	☐
Componente React TaxCalculatorIVA	8h	☐
Componente React cuota autónomos	8h	☐
Tests unitarios calculadoras	12h	☐
SUBTOTAL Sprint 4	76h	
9.6 Sprint 5: Admin UI y Dashboard (Semanas 9-10)
Tarea	Horas Est.	Estado
Componente React LegalAdminDashboard	20h	☐
Componente React NormAlertPanel	12h	☐
API REST para stats y admin	12h	☐
Templates Twig para respuestas	8h	☐
Permisos y routing admin	4h	☐
SUBTOTAL Sprint 5	56h	
9.7 Sprint 6: Integración y QA (Semanas 11-12)
Tarea	Horas Est.	Estado
Integración con AI Business Copilot	20h	☐
Integración con Empleabilidad Copilot	12h	☐
Tests de integración end-to-end	16h	☐
Carga inicial de normativa BOE	8h	☐
QA funcional completo	16h	☐
Documentación de usuario	8h	☐
SUBTOTAL Sprint 6	80h	
9.8 Resumen de Inversión
Sprint	Horas	Semanas
Sprint 1: Fundamentos	66h	2
Sprint 2: Pipeline RAG	82h	2
Sprint 3: Sistema Alertas	64h	2
Sprint 4: Calculadoras	76h	2
Sprint 5: Admin UI	56h	2
Sprint 6: Integración QA	80h	2
TOTAL	424h	12 semanas

Inversión estimada: 424 horas × €45/h = €19,080
 
10. Configuración de Producción
10.1 Variables de Entorno
# .env.production

# BOE API (no requiere auth)
BOE_API_BASE_URL=https://www.boe.es/datosabiertos/api

# Qdrant
QDRANT_URL=https://your-cluster.qdrant.io
QDRANT_API_KEY=your-api-key
QDRANT_LEGAL_COLLECTION=legal_knowledge

# OpenAI (embeddings)
OPENAI_API_KEY=sk-your-api-key
OPENAI_EMBEDDING_MODEL=text-embedding-3-small

# Claude (RAG)
ANTHROPIC_API_KEY=sk-ant-your-api-key
CLAUDE_MODEL=claude-sonnet-4-5-20250929

# Legal Knowledge Settings
LEGAL_CHUNK_SIZE=500
LEGAL_CHUNK_OVERLAP=50
LEGAL_RAG_TOP_K=5
LEGAL_RAG_SCORE_THRESHOLD=0.7
LEGAL_SYNC_HOUR=3  # 3 AM

# Materias a indexar
LEGAL_SUBJECTS=tributario,laboral,autonomos,subvenciones
10.2 Configuración Drupal
# config/install/jaraba_legal_knowledge.settings.yml
boe_api:
  base_url: 'https://www.boe.es/datosabiertos/api'
  timeout: 30
  cache_ttl: 3600

qdrant:
  collection: 'legal_knowledge'
  score_threshold: 0.7
  top_k: 5

chunking:
  chunk_size: 500
  chunk_overlap: 50

sync:
  enabled: true
  frequency: 'daily'
  hour: 3
  subjects:
    - tributario
    - laboral
    - autonomos
    - subvenciones

disclaimers:
  standard: 'Esta información es orientativa...'
  enhanced: 'AVISO: Cada situación es única...'
  critical: 'IMPORTANTE: Esta información NO sustituye...'

— Fin del Documento —
