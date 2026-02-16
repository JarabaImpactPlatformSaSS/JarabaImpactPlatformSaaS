
GUÍA DE IMPLEMENTACIÓN
Legal Intelligence Hub
Código PHP + Python + YAML + SQL + Twig + Tests + Docker
Ready for Claude Code Implementation
Vertical JarabaLex (antes ServiciosConecta) — JARABA IMPACT PLATFORM

Código:	178B_JarabaLex_Legal_Intelligence_Hub_Implementation
Documentos base:	178_v1 (especificación) + 178A_v1 (fuentes UE)
Fecha:	Febrero 2026
Estado:	Ready for Implementation
Prioridad:	CRÍTICA — Sin esto, 178/178A no son implementables
 
 
1. Estructura del Módulo Drupal
El Legal Intelligence Hub se implementa como un módulo Drupal custom jaraba_legal_intelligence que sigue las convenciones establecidas en el Developer Onboarding Kit (doc 142).
1.1 Árbol de Directorios
modules/custom/jaraba_legal_intelligence/
├── jaraba_legal_intelligence.info.yml
├── jaraba_legal_intelligence.module
├── jaraba_legal_intelligence.install
├── jaraba_legal_intelligence.permissions.yml
├── jaraba_legal_intelligence.routing.yml
├── jaraba_legal_intelligence.services.yml
├── jaraba_legal_intelligence.links.menu.yml
├── config/
│   ├── install/
│   │   ├── jaraba_legal_intelligence.settings.yml
│   │   ├── jaraba_legal_intelligence.sources.yml
│   │   └── system.action.legal_reindex.yml
│   └── schema/
│       └── jaraba_legal_intelligence.schema.yml
├── src/
│   ├── Entity/
│   │   ├── LegalResolution.php
│   │   ├── LegalSource.php
│   │   ├── LegalAlert.php
│   │   ├── LegalBookmark.php
│   │   └── LegalCitation.php
│   ├── Service/
│   │   ├── LegalSearchService.php
│   │   ├── LegalIngestionService.php
│   │   ├── LegalNlpPipelineService.php
│   │   ├── LegalAlertService.php
│   │   ├── LegalCitationService.php
│   │   ├── LegalDigestService.php
│   │   └── Spider/
│   │       ├── SpiderInterface.php
│   │       ├── CendojSpider.php
│   │       ├── BoeSpider.php
│   │       ├── DgtSpider.php
│   │       ├── TeacSpider.php
│   │       ├── EurLexSpider.php
│   │       ├── CuriaSpider.php
│   │       ├── HudocSpider.php
│   │       └── EdpbSpider.php
│   ├── Controller/
│   │   ├── LegalSearchController.php
│   │   ├── LegalResolutionController.php
│   │   └── LegalAdminController.php
│   ├── Form/
│   │   ├── LegalSearchForm.php
│   │   ├── LegalAlertForm.php
│   │   └── LegalSettingsForm.php
│   ├── Plugin/
│   │   ├── Block/
│   │   │   ├── LegalSearchBlock.php
│   │   │   └── LegalRecentBlock.php
│   │   ├── QueueWorker/
│   │   │   ├── LegalIngestionWorker.php
│   │   │   └── LegalNlpWorker.php
│   │   └── Action/
│   │       └── ReindexResolutionAction.php
│   └── EventSubscriber/
│       └── LegalEventSubscriber.php
├── templates/
│   ├── legal-resolution-detail.html.twig
│   ├── legal-search-results.html.twig
│   ├── legal-citation-insert.html.twig
│   └── legal-digest-email.html.twig
├── scripts/
│   ├── nlp/
│   │   ├── requirements.txt
│   │   ├── pipeline.py
│   │   ├── legal_ner.py
│   │   ├── embeddings.py
│   │   └── qdrant_client.py
│   └── spiders/
│       ├── base_spider.py
│       ├── cendoj_spider.py
│       ├── eurlex_spider.py
│       └── hudoc_spider.py
└── tests/
    └── src/
        ├── Unit/
        │   ├── LegalSearchServiceTest.php
        │   └── LegalNlpPipelineServiceTest.php
        └── Kernel/
            ├── LegalResolutionEntityTest.php
            └── LegalIngestionTest.php
 
1.2 jaraba_legal_intelligence.info.yml
name: 'Jaraba Legal Intelligence Hub'
type: module
description: 'AI-powered legal research with semantic search across
  national (ES) and European (EU/CEDH) sources.'
package: Jaraba ServiciosConecta
core_version_requirement: ^11
php: 8.3
dependencies:
  - drupal:user
  - drupal:file
  - drupal:jsonapi
  - drupal:taxonomy
  - eca:eca
  - group:group
  - jaraba_core:jaraba_core
  - jaraba_tenant:jaraba_tenant
  - jaraba_ai:jaraba_ai
  - jaraba_services:jaraba_services
configure: jaraba_legal_intelligence.settings
1.3 jaraba_legal_intelligence.permissions.yml
administer legal intelligence:
  title: 'Administer Legal Intelligence Hub'
  description: 'Configure sources, reindex, manage settings.'
  restrict access: true

search legal resolutions:
  title: 'Search legal resolutions'
  description: 'Perform semantic and faceted searches.'

view legal resolutions:
  title: 'View legal resolution detail'
  description: 'View full text and metadata of resolutions.'

bookmark legal resolutions:
  title: 'Bookmark resolutions'
  description: 'Save resolutions as favorites.'

insert legal citations:
  title: 'Insert citations into case files'
  description: 'One-click citation insertion in Buzon expedientes.'

manage legal alerts:
  title: 'Manage legal alerts'
  description: 'Create and manage alert subscriptions.'

access legal api:
  title: 'Access Legal Intelligence REST API'
  description: 'Programmatic access to search and citations.'
 
1.4 jaraba_legal_intelligence.services.yml
services:
  # === SEARCH ===
  jaraba_legal.search:
    class: Drupal\jaraba_legal_intelligence\Service\LegalSearchService
    arguments:
      - '@jaraba_ai.rag'
      - '@jaraba_tenant.context'
      - '@entity_type.manager'
      - '@http_client'
      - '@config.factory'
      - '@logger.channel.jaraba_legal'

  # === INGESTION ===
  jaraba_legal.ingestion:
    class: Drupal\jaraba_legal_intelligence\Service\LegalIngestionService
    arguments:
      - '@entity_type.manager'
      - '@queue'
      - '@http_client'
      - '@config.factory'
      - '@logger.channel.jaraba_legal'

  # === NLP PIPELINE ===
  jaraba_legal.nlp_pipeline:
    class: Drupal\jaraba_legal_intelligence\Service\LegalNlpPipelineService
    arguments:
      - '@http_client'
      - '@jaraba_ai.rag'
      - '@config.factory'
      - '@logger.channel.jaraba_legal'

  # === ALERTS ===
  jaraba_legal.alerts:
    class: Drupal\jaraba_legal_intelligence\Service\LegalAlertService
    arguments:
      - '@entity_type.manager'
      - '@jaraba_tenant.context'
      - '@plugin.manager.mail'
      - '@logger.channel.jaraba_legal'

  # === CITATIONS ===
  jaraba_legal.citations:
    class: Drupal\jaraba_legal_intelligence\Service\LegalCitationService
    arguments:
      - '@entity_type.manager'
      - '@jaraba_tenant.context'

  # === DIGEST ===
  jaraba_legal.digest:
    class: Drupal\jaraba_legal_intelligence\Service\LegalDigestService
    arguments:
      - '@jaraba_legal.search'
      - '@jaraba_legal.nlp_pipeline'
      - '@plugin.manager.mail'
      - '@entity_type.manager'
      - '@logger.channel.jaraba_legal'

  # === LOGGER CHANNEL ===
  logger.channel.jaraba_legal:
    parent: logger.channel_base
    arguments: ['jaraba_legal']
 
1.5 jaraba_legal_intelligence.routing.yml
# === SEARCH ===
jaraba_legal.search:
  path: '/legal/search'
  defaults:
    _controller: '\Drupal\jaraba_legal_intelligence\Controller\LegalSearchController::search'
    _title: 'Legal Intelligence Search'
  requirements:
    _permission: 'search legal resolutions'

# === RESOLUTION DETAIL ===
jaraba_legal.resolution:
  path: '/legal/{source_id}/{external_ref}'
  defaults:
    _controller: '\Drupal\jaraba_legal_intelligence\Controller\LegalResolutionController::view'
    _title: 'Resolución Legal'
  requirements:
    _permission: 'view legal resolutions'

# === CITATION INSERT ===
jaraba_legal.cite:
  path: '/legal/cite/{resolution_id}/{format}'
  defaults:
    _controller: '\Drupal\jaraba_legal_intelligence\Controller\LegalResolutionController::cite'
    format: 'formal'
  requirements:
    _permission: 'insert legal citations'
    format: 'formal|summary|biblio|footnote'

# === SIMILAR RESOLUTIONS ===
jaraba_legal.similar:
  path: '/legal/{resolution_id}/similar'
  defaults:
    _controller: '\Drupal\jaraba_legal_intelligence\Controller\LegalResolutionController::similar'
  requirements:
    _permission: 'view legal resolutions'

# === ADMIN SETTINGS ===
jaraba_legal.settings:
  path: '/admin/config/jaraba/legal-intelligence'
  defaults:
    _form: '\Drupal\jaraba_legal_intelligence\Form\LegalSettingsForm'
    _title: 'Legal Intelligence Hub Settings'
  requirements:
    _permission: 'administer legal intelligence'

# === ADMIN: FORCE SYNC ===
jaraba_legal.admin.sync:
  path: '/admin/config/jaraba/legal-intelligence/sync/{source_id}'
  defaults:
    _controller: '\Drupal\jaraba_legal_intelligence\Controller\LegalAdminController::syncSource'
  requirements:
    _permission: 'administer legal intelligence'

# === REST API ===
jaraba_legal.api.search:
  path: '/api/v1/legal/search'
  defaults:
    _controller: '\Drupal\jaraba_legal_intelligence\Controller\LegalSearchController::apiSearch'
  methods: [GET]
  requirements:
    _permission: 'access legal api'
    _format: 'json'

jaraba_legal.api.resolution:
  path: '/api/v1/legal/resolutions/{id}'
  defaults:
    _controller: '\Drupal\jaraba_legal_intelligence\Controller\LegalResolutionController::apiGet'
  methods: [GET]
  requirements:
    _permission: 'access legal api'
    _format: 'json'

jaraba_legal.api.bookmark:
  path: '/api/v1/legal/bookmark'
  defaults:
    _controller: '\Drupal\jaraba_legal_intelligence\Controller\LegalResolutionController::apiBookmark'
  methods: [POST, DELETE]
  requirements:
    _permission: 'bookmark legal resolutions'
    _format: 'json'

jaraba_legal.api.alerts:
  path: '/api/v1/legal/alerts'
  defaults:
    _controller: '\Drupal\jaraba_legal_intelligence\Controller\LegalSearchController::apiAlerts'
  methods: [GET, POST, PATCH, DELETE]
  requirements:
    _permission: 'manage legal alerts'
    _format: 'json'

# === SEO: PUBLIC SUMMARY ===
jaraba_legal.public_summary:
  path: '/legal/{source_slug}/{seo_slug}'
  defaults:
    _controller: '\Drupal\jaraba_legal_intelligence\Controller\LegalResolutionController::publicSummary'
    _title: 'Resolución'
  requirements:
    _access: 'TRUE'
 
2. Entidades Drupal: Código PHP Completo
2.1 LegalResolution Entity (Entidad Principal)
<?php

namespace Drupal\jaraba_legal_intelligence\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * @ContentEntityType(
 *   id = "legal_resolution",
 *   label = @Translation("Legal Resolution"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_legal_intelligence\LegalResolutionListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_legal_intelligence\Form\LegalResolutionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_legal_intelligence\LegalResolutionAccessControlHandler",
 *   },
 *   base_table = "legal_resolution",
 *   data_table = "legal_resolution_field_data",
 *   translatable = FALSE,
 *   admin_permission = "administer legal intelligence",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   links = {
 *     "canonical" = "/legal/resolution/{legal_resolution}",
 *   },
 * )
 */
class LegalResolution extends ContentEntityBase {

  use EntityChangedTrait;

  public static function baseFieldDefinitions(
    EntityTypeInterface $entity_type
  ) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // === IDENTIFICATION ===
    $fields['source_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Source ID'))
      ->setDescription(t('Source identifier: cendoj, boe, dgt, teac,'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 32])
      ->addPropertyConstraints('value', [
        'AllowedValues' => [
          'choices' => [
            'cendoj', 'boe', 'dgt', 'teac', 'igae', 'boicac',
            'boja', 'dgrn', 'aepd', 'tc',
            'tjue', 'eurlex', 'tedh', 'edpb',
            'eba', 'esma', 'ag_tjue',
          ],
        ],
      ]);

    $fields['external_ref'] = BaseFieldDefinition::create('string')
      ->setLabel(t('External Reference'))
      ->setDescription(t('Official reference: V0123-24, STS 1234/2024,'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 128])
      ->addConstraint('UniqueField');

    $fields['content_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Content SHA-256 Hash'))
      ->setDescription(t('SHA-256 hash for deduplication'))
      ->setSettings(['max_length' => 64]);

    // === CORE METADATA ===
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 512]);

    $fields['resolution_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Resolution Type'))
      ->setDescription(t('sentencia, auto, consulta_vinculante,'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 64]);

    $fields['issuing_body'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Issuing Body'))
      ->setDescription(t('TS, TSJ Andalucia, DGT, TEAC, TJUE,'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 128]);

    $fields['jurisdiction'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Jurisdiction'))
      ->setDescription(t('civil, penal, contencioso, laboral,'))
      ->setSettings(['max_length' => 64]);

    $fields['date_issued'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Date Issued'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'date');

    $fields['date_published'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Date Published'))
      ->setSetting('datetime_type', 'date');

    $fields['status_legal'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Legal Status'))
      ->setSettings(['max_length' => 32])
      ->setDefaultValue('vigente')
      ->addPropertyConstraints('value', [
        'AllowedValues' => [
          'choices' => [
            'vigente', 'derogada', 'anulada', 'superada',
            'parcialmente_derogada',
          ],
        ],
      ]);

    // === FULL TEXT ===
    $fields['full_text'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Full Text'))
      ->setDescription(t('Complete text of the resolution'));

    $fields['original_url'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Original URL'))
      ->setDescription(t('URL to the official source'));

    // === AI-GENERATED FIELDS ===
    $fields['abstract_ai'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('AI Abstract'))
      ->setDescription(t('3-5 line summary generated by Gemini'));

    $fields['key_holdings'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Key Holdings / Ratio Decidendi'))
      ->setDescription(t('AI-extracted ratio decidendi'));

    $fields['topics'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Topics (JSON array)'))
      ->setDescription(t('AI-classified topics as JSON'))
      ->setSettings(['max_length' => 2048]);

    $fields['cited_legislation'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Cited Legislation (JSON)'))
      ->setDescription(t('Laws, articles, regulations cited'))
      ->setSettings(['max_length' => 4096]);

    // === EU-SPECIFIC FIELDS (Annex 178A) ===
    $fields['celex_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('CELEX Number'))
      ->setDescription(t('EUR-Lex CELEX identifier'))
      ->setSettings(['max_length' => 32]);

    $fields['ecli'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ECLI'))
      ->setDescription(t('European Case Law Identifier'))
      ->setSettings(['max_length' => 64]);

    $fields['case_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Case Number'))
      ->setDescription(t('C-415/11, 8675/15, etc.'))
      ->setSettings(['max_length' => 64]);

    $fields['procedure_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Procedure Type'))
      ->setDescription(t('prejudicial, infraccion, anulacion,'))
      ->setSettings(['max_length' => 64]);

    $fields['respondent_state'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Respondent State'))
      ->setDescription(t('ISO 3166-1 alpha-3 code'))
      ->setSettings(['max_length' => 3]);

    $fields['cedh_articles'] = BaseFieldDefinition::create('string')
      ->setLabel(t('CEDH Articles (JSON)'))
      ->setSettings(['max_length' => 512]);

    $fields['eu_legal_basis'] = BaseFieldDefinition::create('string')
      ->setLabel(t('EU Legal Basis (JSON)'))
      ->setSettings(['max_length' => 2048]);

    $fields['advocate_general'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Advocate General'))
      ->setSettings(['max_length' => 128]);

    $fields['importance_level'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Importance Level'))
      ->setDescription(t('1=key case, 2=medium, 3=low'))
      ->setDefaultValue(3);

    $fields['language_original'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Original Language'))
      ->setSettings(['max_length' => 3])
      ->setDefaultValue('es');

    $fields['impact_spain'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Impact on Spanish Law'))
      ->setDescription(t('AI-generated: how this affects ES law'));

    // === QDRANT VECTOR REFERENCES ===
    $fields['vector_ids'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Qdrant Vector IDs (JSON)'))
      ->setDescription(t('Array of Qdrant point UUIDs'))
      ->setSettings(['max_length' => 4096]);

    $fields['qdrant_collection'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Qdrant Collection'))
      ->setDefaultValue('legal_intelligence')
      ->setSettings(['max_length' => 64]);

    // === SEO ===
    $fields['seo_slug'] = BaseFieldDefinition::create('string')
      ->setLabel(t('SEO Slug'))
      ->setDescription(t('URL-friendly slug for public pages'))
      ->setSettings(['max_length' => 255]);

    // === TIMESTAMPS ===
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    $fields['last_nlp_processed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last NLP Processed'));

    return $fields;
  }

  /**
   * Check if this is an EU source resolution.
   */
  public function isEuSource(): bool {
    $euSources = ['tjue','eurlex','tedh','edpb','eba','esma','ag_tjue'];
    return in_array($this->get('source_id')->value, $euSources);
  }

  /**
   * Get decoded topics as array.
   */
  public function getTopics(): array {
    $raw = $this->get('topics')->value;
    return $raw ? json_decode($raw, TRUE) ?? [] : [];
  }

  /**
   * Get decoded cited legislation.
   */
  public function getCitedLegislation(): array {
    $raw = $this->get('cited_legislation')->value;
    return $raw ? json_decode($raw, TRUE) ?? [] : [];
  }

  /**
   * Generate formatted citation in requested format.
   */
  public function formatCitation(string $format = 'formal'): string {
    $body = $this->get('issuing_body')->value;
    $ref = $this->get('external_ref')->value;
    $date = $this->get('date_issued')->value;
    $ratio = $this->get('key_holdings')->value ?? '';

    return match ($format) {
      'formal' => sprintf(
        'Según establece %s %s de %s, de fecha %s: «%s».',
        $this->getResolutionTypeLabel(),
        $ref, $body, $date, mb_substr($ratio, 0, 500)
      ),
      'summary' => sprintf('%s (%s, %s): %s',
        $ref, $body, $date, mb_substr($ratio, 0, 200)
      ),
      'biblio' => sprintf(
        '%s. %s. %s, %s.',
        $body, $this->get('title')->value, $ref, $date
      ),
      'footnote' => sprintf(
        'Vid. %s %s, %s (%s).',
        $this->getResolutionTypeLabel(), $ref, $body, $date
      ),
      default => $ref,
    };
  }

  private function getResolutionTypeLabel(): string {
    return match ($this->get('resolution_type')->value) {
      'sentencia' => 'la Sentencia',
      'auto' => 'el Auto',
      'consulta_vinculante' => 'la Consulta Vinculante',
      'resolucion' => 'la Resolución',
      'directiva' => 'la Directiva',
      'reglamento' => 'el Reglamento',
      default => 'la Resolución',
    };
  }
}
 
2.2 LegalAlert Entity
<?php

namespace Drupal\jaraba_legal_intelligence\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @ContentEntityType(
 *   id = "legal_alert",
 *   label = @Translation("Legal Alert"),
 *   base_table = "legal_alert",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *   },
 * )
 */
class LegalAlert extends ContentEntityBase {

  public static function baseFieldDefinitions(
    EntityTypeInterface $entity_type
  ) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Alert Name'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255]);

    $fields['provider_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Provider'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE);

    $fields['group_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant Group'))
      ->setSetting('target_type', 'group');

    $fields['alert_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Alert Type'))
      ->setSettings(['max_length' => 64])
      ->addPropertyConstraints('value', [
        'AllowedValues' => [
          'choices' => [
            'resolution_annulled', 'criteria_change',
            'new_relevant_doctrine', 'legislation_modified',
            'procedural_deadline', 'tjue_spain_impact',
            'tedh_spain', 'edpb_guideline',
            'transposition_deadline', 'ag_conclusions',
          ],
        ],
      ]);

    $fields['severity'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Severity'))
      ->setDefaultValue('medium')
      ->addPropertyConstraints('value', [
        'AllowedValues' => [
          'choices' => ['critical','high','medium','low'],
        ],
      ]);

    $fields['filter_sources'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Source filter (JSON array)'))
      ->setSettings(['max_length' => 512]);

    $fields['filter_topics'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Topic filter (JSON array)'))
      ->setSettings(['max_length' => 1024]);

    $fields['filter_jurisdictions'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Jurisdiction filter (JSON array)'))
      ->setSettings(['max_length' => 512]);

    $fields['channels'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Notification channels (JSON)'))
      ->setDescription(t('["email","push","in_app"]'))
      ->setSettings(['max_length' => 256])
      ->setDefaultValue('["in_app"]');

    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDefaultValue(TRUE);

    $fields['last_triggered'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last Triggered'));

    $fields['trigger_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Trigger Count'))
      ->setDefaultValue(0);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    return $fields;
  }
}
 
2.3 LegalBookmark Entity
<?php

namespace Drupal\jaraba_legal_intelligence\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @ContentEntityType(
 *   id = "legal_bookmark",
 *   label = @Translation("Legal Bookmark"),
 *   base_table = "legal_bookmark",
 *   entity_keys = {
 *     "id" = "id", "uuid" = "uuid",
 *   },
 * )
 */
class LegalBookmark extends ContentEntityBase {

  public static function baseFieldDefinitions(
    EntityTypeInterface $entity_type
  ) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))->setSetting('target_type', 'user')
      ->setRequired(TRUE);

    $fields['resolution_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Resolution'))->setSetting('target_type', 'legal_resolution')
      ->setRequired(TRUE);

    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Personal Notes'));

    $fields['folder'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Folder/Tag'))->setSettings(['max_length' => 128]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    return $fields;
  }
}
2.4 LegalCitation Entity (Citas insertadas en expedientes)
<?php

namespace Drupal\jaraba_legal_intelligence\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @ContentEntityType(
 *   id = "legal_citation",
 *   label = @Translation("Legal Citation"),
 *   base_table = "legal_citation",
 *   entity_keys = {
 *     "id" = "id", "uuid" = "uuid",
 *   },
 * )
 */
class LegalCitation extends ContentEntityBase {

  public static function baseFieldDefinitions(
    EntityTypeInterface $entity_type
  ) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['resolution_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Resolution'))
      ->setSetting('target_type', 'legal_resolution')
      ->setRequired(TRUE);

    $fields['expediente_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Expediente (Buzon Confianza)'))
      ->setDescription(t('FK to buzon_expediente entity from doc 88'))
      ->setRequired(TRUE);

    $fields['group_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant Group'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE);

    $fields['citation_format'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Citation Format'))
      ->setSettings(['max_length' => 32])
      ->setDefaultValue('formal');

    $fields['citation_text'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Generated Citation Text'))->setRequired(TRUE);

    $fields['inserted_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Inserted By'))->setSetting('target_type', 'user');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    return $fields;
  }
}
 
3. SQL CREATE TABLE (Referencia)
Drupal genera estas tablas automáticamente desde baseFieldDefinitions(). Se incluyen como referencia para validación y para los índices adicionales que requieren creación manual en hook_install().
3.1 Tabla legal_resolution
CREATE TABLE legal_resolution (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uuid VARCHAR(128) NOT NULL UNIQUE,
  source_id VARCHAR(32) NOT NULL,
  external_ref VARCHAR(128) NOT NULL UNIQUE,
  content_hash VARCHAR(64),
  title VARCHAR(512) NOT NULL,
  resolution_type VARCHAR(64) NOT NULL,
  issuing_body VARCHAR(128) NOT NULL,
  jurisdiction VARCHAR(64),
  date_issued DATE NOT NULL,
  date_published DATE,
  status_legal VARCHAR(32) NOT NULL DEFAULT 'vigente',
  full_text LONGTEXT,
  original_url VARCHAR(2048),
  abstract_ai LONGTEXT,
  key_holdings LONGTEXT,
  topics VARCHAR(2048),
  cited_legislation VARCHAR(4096),
  -- EU fields (Annex 178A)
  celex_number VARCHAR(32),
  ecli VARCHAR(64),
  case_number VARCHAR(64),
  procedure_type VARCHAR(64),
  respondent_state VARCHAR(3),
  cedh_articles VARCHAR(512),
  eu_legal_basis VARCHAR(2048),
  advocate_general VARCHAR(128),
  importance_level INT DEFAULT 3,
  language_original VARCHAR(3) DEFAULT 'es',
  impact_spain LONGTEXT,
  -- Vector refs
  vector_ids VARCHAR(4096),
  qdrant_collection VARCHAR(64) DEFAULT 'legal_intelligence',
  -- SEO
  seo_slug VARCHAR(255),
  -- Timestamps
  created INT NOT NULL,
  changed INT NOT NULL,
  last_nlp_processed INT,
  -- Indexes
  INDEX idx_source (source_id),
  INDEX idx_date_issued (date_issued),
  INDEX idx_issuing_body (issuing_body),
  INDEX idx_resolution_type (resolution_type),
  INDEX idx_jurisdiction (jurisdiction),
  INDEX idx_status (status_legal),
  INDEX idx_celex (celex_number),
  INDEX idx_ecli (ecli),
  INDEX idx_case_number (case_number),
  INDEX idx_respondent (respondent_state),
  INDEX idx_importance (importance_level),
  INDEX idx_seo_slug (seo_slug),
  FULLTEXT idx_fulltext (title, abstract_ai, key_holdings)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
3.2 hook_install() para índices adicionales
<?php

/**
 * Implements hook_install().
 */
function jaraba_legal_intelligence_install() {
  $connection = \Drupal::database();

  // Composite indexes for common query patterns
  $connection->query(
    'CREATE INDEX idx_source_date ON {legal_resolution}
      (source_id, date_issued DESC)'
  );

  $connection->query(
    'CREATE INDEX idx_source_type_date ON {legal_resolution}
      (source_id, resolution_type, date_issued DESC)'
  );

  $connection->query(
    'CREATE INDEX idx_eu_respondent_date ON {legal_resolution}
      (respondent_state, date_issued DESC)
      WHERE respondent_state IS NOT NULL'
  );

  // Citation graph table (many-to-many self-ref)
  $connection->schema()->createTable('legal_citation_graph', [
    'fields' => [
      'citing_id' => [
        'type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE,
      ],
      'cited_id' => [
        'type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE,
      ],
      'citation_context' => [
        'type' => 'varchar', 'length' => 512,
      ],
      'created' => [
        'type' => 'int', 'not null' => TRUE,
      ],
    ],
    'primary key' => ['citing_id', 'cited_id'],
    'indexes' => [
      'idx_cited' => ['cited_id'],
    ],
    'foreign keys' => [
      'fk_citing' => [
        'table' => 'legal_resolution', 'columns' => ['citing_id' => 'id'],
      ],
      'fk_cited' => [
        'table' => 'legal_resolution', 'columns' => ['cited_id' => 'id'],
      ],
    ],
  ]);
}
 
4. Servicios PHP: Implementación Completa
4.1 LegalSearchService
<?php

namespace Drupal\jaraba_legal_intelligence\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\jaraba_ai\Service\RagService;
use Drupal\jaraba_tenant\Service\TenantContextService;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

class LegalSearchService {

  private const COLLECTION_NATIONAL = 'legal_intelligence';
  private const COLLECTION_EU = 'legal_intelligence_eu';
  private const DEFAULT_LIMIT = 10;
  private const MAX_LIMIT = 50;

  public function __construct(
    protected RagService $ragService,
    protected TenantContextService $tenantContext,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ClientInterface $httpClient,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Semantic search across national and/or EU sources.
   *
   * @param string $query Natural language query
   * @param array $filters Optional facet filters:
   *   - scope: 'national'|'eu'|'all' (default: 'all')
   *   - source_id: string|string[] filter by source
   *   - jurisdiction: string filter by jurisdiction
   *   - resolution_type: string filter by type
   *   - date_from: string (Y-m-d) date range start
   *   - date_to: string (Y-m-d) date range end
   *   - issuing_body: string filter by organ
   *   - respondent_state: string (EU only)
   *   - importance_level: int (1-3)
   * @param int $limit Max results (default 10)
   * @param int $offset Pagination offset
   * @return array{results: array, total: int, facets: array}
   */
  public function search(
    string $query,
    array $filters = [],
    int $limit = self::DEFAULT_LIMIT,
    int $offset = 0
  ): array {
    $limit = min($limit, self::MAX_LIMIT);
    $scope = $filters['scope'] ?? 'all';

    // Plan limits check
    $this->checkPlanLimits();

    // Build Qdrant filter payload
    $qdrantFilter = $this->buildQdrantFilter($filters);

    // Generate embedding for query
    $embedding = $this->ragService->generateEmbedding($query);

    $results = [];

    // Search national collection
    if (in_array($scope, ['national', 'all'])) {
      $national = $this->queryQdrant(
        self::COLLECTION_NATIONAL,
        $embedding,
        $qdrantFilter,
        $limit,
        $offset
      );
      $results = array_merge($results, $national);
    }

    // Search EU collection
    if (in_array($scope, ['eu', 'all'])) {
      $eu = $this->queryQdrant(
        self::COLLECTION_EU,
        $embedding,
        $qdrantFilter,
        $limit,
        $offset
      );
      $results = array_merge($results, $eu);
    }

    // Merge, rank, and deduplicate
    $ranked = $this->mergeAndRank($results, $scope);

    // Hydrate with Drupal entity data
    $hydrated = $this->hydrateResults(
      array_slice($ranked, 0, $limit)
    );

    // Track search for usage billing
    $this->trackSearch($query, $scope);

    return [
      'results' => $hydrated,
      'total' => count($ranked),
      'facets' => $this->computeFacets($ranked),
    ];
  }

  /**
   * Find similar resolutions to a given one.
   */
  public function findSimilar(
    int $resolutionId,
    int $limit = 5
  ): array {
    $resolution = $this->entityTypeManager
      ->getStorage('legal_resolution')
      ->load($resolutionId);

    if (!$resolution) return [];

    $vectorIds = json_decode(
      $resolution->get('vector_ids')->value ?? '[]', TRUE
    );

    if (empty($vectorIds)) return [];

    // Use first vector (title+abstract chunk) for similarity
    $collection = $resolution->get('qdrant_collection')->value;
    $point = $this->getQdrantPoint($collection, $vectorIds[0]);

    if (!$point) return [];

    return $this->queryQdrant(
      $collection,
      $point['vector'],
      ['must_not' => [
        ['key' => 'resolution_id', 'match' => ['value' => $resolutionId]]
      ]],
      $limit
    );
  }

  /**
   * Exact reference lookup.
   */
  public function findByReference(string $ref): ?array {
    $entities = $this->entityTypeManager
      ->getStorage('legal_resolution')
      ->loadByProperties(['external_ref' => $ref]);

    if (empty($entities)) return NULL;
    return $this->entityToArray(reset($entities));
  }

  // --- Private helper methods ---

  private function queryQdrant(
    string $collection,
    array $vector,
    array $filter,
    int $limit,
    int $offset = 0
  ): array {
    $config = $this->configFactory
      ->get('jaraba_legal_intelligence.settings');
    $qdrantUrl = $config->get('qdrant_url') ?? 'http://localhost:6333';

    try {
      $response = $this->httpClient->request('POST',
        "{$qdrantUrl}/collections/{$collection}/points/search",
        ['json' => [
          'vector' => $vector,
          'filter' => $filter ?: new \stdClass(),
          'limit' => $limit,
          'offset' => $offset,
          'with_payload' => TRUE,
          'score_threshold' => 0.65,
        ]]
      );

      $data = json_decode(
        $response->getBody()->getContents(), TRUE
      );
      return $data['result'] ?? [];
    }
    catch (\Exception $e) {
      $this->logger->error(
        'Qdrant search failed: @error',
        ['@error' => $e->getMessage()]
      );
      return [];
    }
  }

  private function buildQdrantFilter(array $filters): array {
    $must = [];

    if (!empty($filters['source_id'])) {
      $sources = (array) $filters['source_id'];
      $must[] = ['key' => 'source_id',
        'match' => ['any' => $sources]];
    }
    if (!empty($filters['jurisdiction'])) {
      $must[] = ['key' => 'jurisdiction',
        'match' => ['value' => $filters['jurisdiction']]];
    }
    if (!empty($filters['date_from'])) {
      $must[] = ['key' => 'date_issued',
        'range' => ['gte' => $filters['date_from']]];
    }
    if (!empty($filters['date_to'])) {
      $must[] = ['key' => 'date_issued',
        'range' => ['lte' => $filters['date_to']]];
    }
    if (!empty($filters['respondent_state'])) {
      $must[] = ['key' => 'respondent_state',
        'match' => ['value' => $filters['respondent_state']]];
    }
    if (!empty($filters['importance_level'])) {
      $must[] = ['key' => 'importance_level',
        'range' => ['lte' => (int) $filters['importance_level']]];
    }

    return $must ? ['must' => $must] : [];
  }

  private function mergeAndRank(
    array $results, string $scope
  ): array {
    // Score boosting: TJUE/TEDH get +0.05 for EU primacy
    foreach ($results as &$r) {
      $source = $r['payload']['source_id'] ?? '';
      if (in_array($source, ['tjue','tedh']) && $scope === 'all') {
        $r['score'] += 0.05;
      }
      // Recency boost: +0.02 for docs < 1 year old
      $date = $r['payload']['date_issued'] ?? '';
      if ($date && strtotime($date) > strtotime('-1 year')) {
        $r['score'] += 0.02;
      }
    }
    unset($r);

    // Sort by score descending
    usort($results, fn($a, $b) =>
      ($b['score'] ?? 0) <=> ($a['score'] ?? 0)
    );

    // Deduplicate by resolution_id
    $seen = [];
    return array_filter($results, function($r) use (&$seen) {
      $id = $r['payload']['resolution_id'] ?? $r['id'];
      if (isset($seen[$id])) return FALSE;
      $seen[$id] = TRUE;
      return TRUE;
    });
  }

  private function checkPlanLimits(): void {
    // Check monthly search quota based on tenant plan
    // Starter: 50/month, Pro: unlimited, Enterprise: unlimited
    $plan = $this->tenantContext->getCurrentPlan();
    if ($plan === 'starter') {
      $count = $this->getMonthlySearchCount();
      if ($count >= 50) {
        throw new \Drupal\Core\Access\AccessException(
          'Monthly search limit reached. Upgrade to Pro.'
        );
      }
    }
  }
}
 
4.2 LegalNlpPipelineService
<?php

namespace Drupal\jaraba_legal_intelligence\Service;

use GuzzleHttp\ClientInterface;
use Drupal\jaraba_ai\Service\RagService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

class LegalNlpPipelineService {

  public function __construct(
    protected ClientInterface $httpClient,
    protected RagService $ragService,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Process a resolution through the full 9-stage NLP pipeline.
   *
   * Stage 1: Extract text (Apache Tika)
   * Stage 2: Normalize (encoding, headers)
   * Stage 3: Segment (sections)
   * Stage 4: Legal NER (spaCy)
   * Stage 5: Classify (Gemini)
   * Stage 6: Summarize (Gemini)
   * Stage 7: Generate embeddings
   * Stage 8: Index in Qdrant
   * Stage 9: Build citation graph
   *
   * @return array Pipeline result with all generated data
   */
  public function process(int $resolutionId): array {
    $storage = \Drupal::entityTypeManager()
      ->getStorage('legal_resolution');
    $resolution = $storage->load($resolutionId);
    if (!$resolution) {
      throw new \InvalidArgumentException(
        "Resolution {$resolutionId} not found"
      );
    }

    $result = [];
    $text = $resolution->get('full_text')->value;

    // Stage 1: Extract (if text is empty, call Tika)
    if (empty($text)) {
      $text = $this->extractWithTika(
        $resolution->get('original_url')->value
      );
      $resolution->set('full_text', $text);
    }

    // Stage 2: Normalize
    $text = $this->normalize($text);

    // Stage 3: Segment into sections
    $segments = $this->callPythonNlp('segment', [
      'text' => $text,
      'source_id' => $resolution->get('source_id')->value,
    ]);

    // Stage 4: Legal NER
    $ner = $this->callPythonNlp('ner', ['text' => $text]);
    $result['cited_legislation'] = $ner['entities'] ?? [];

    // Stage 5: Classify with Gemini
    $classification = $this->classifyWithGemini(
      $text, $resolution
    );
    $resolution->set('topics', json_encode(
      $classification['topics'] ?? []
    ));

    // Stage 6: Summarize with Gemini
    $summary = $this->summarizeWithGemini(
      $text, $resolution
    );
    $resolution->set('abstract_ai', $summary['abstract']);
    $resolution->set('key_holdings', $summary['ratio']);
    if ($resolution->isEuSource()) {
      $resolution->set('impact_spain',
        $summary['impact_spain'] ?? '');
    }

    // Stage 7: Generate embeddings
    $chunks = $this->chunkText($text, $segments);
    $embeddings = [];
    foreach ($chunks as $chunk) {
      $embeddings[] = [
        'text' => $chunk['text'],
        'vector' => $this->ragService->generateEmbedding(
          $chunk['text']
        ),
        'metadata' => array_merge($chunk['metadata'], [
          'resolution_id' => $resolutionId,
          'source_id' => $resolution->get('source_id')->value,
          'date_issued' => $resolution->get('date_issued')->value,
          'jurisdiction' => $resolution->get('jurisdiction')->value,
          'issuing_body' => $resolution->get('issuing_body')->value,
          'importance_level' => $resolution->get('importance_level')->value,
          'respondent_state' => $resolution->get('respondent_state')->value,
        ]),
      ];
    }

    // Stage 8: Index in Qdrant
    $collection = $resolution->isEuSource()
      ? 'legal_intelligence_eu'
      : 'legal_intelligence';
    $vectorIds = $this->indexInQdrant(
      $collection, $embeddings
    );
    $resolution->set('vector_ids', json_encode($vectorIds));
    $resolution->set('qdrant_collection', $collection);

    // Stage 9: Build citation graph
    $this->buildCitationGraph(
      $resolutionId, $ner['entities'] ?? []
    );

    // Update timestamps
    $resolution->set('last_nlp_processed', time());
    $resolution->set('cited_legislation',
      json_encode($result['cited_legislation']));
    $resolution->save();

    $this->logger->info(
      'NLP pipeline completed for @ref (@source)',
      [
        '@ref' => $resolution->get('external_ref')->value,
        '@source' => $resolution->get('source_id')->value,
      ]
    );

    return $result;
  }

  private function extractWithTika(string $url): string {
    $config = $this->configFactory->get(
      'jaraba_legal_intelligence.settings'
    );
    $tikaUrl = $config->get('tika_url') ?? 'http://tika:9998';

    $response = $this->httpClient->request('PUT',
      $tikaUrl . '/tika',
      [
        'headers' => [
          'Content-Type' => 'application/pdf',
          'Accept' => 'text/plain',
        ],
        'body' => file_get_contents($url),
      ]
    );
    return $response->getBody()->getContents();
  }

  private function normalize(string $text): string {
    // Remove BOM, normalize line endings, fix encoding
    $text = preg_replace('/\x{FEFF}/u', '', $text);
    $text = str_replace(['\r\n', '\r'], '\n', $text);
    $text = mb_convert_encoding($text, 'UTF-8', 'auto');
    // Normalize multiple spaces/newlines
    $text = preg_replace('/\n{3,}/', '\n\n', $text);
    return trim($text);
  }

  private function callPythonNlp(
    string $task, array $data
  ): array {
    $config = $this->configFactory->get(
      'jaraba_legal_intelligence.settings'
    );
    $nlpUrl = $config->get('nlp_service_url')
      ?? 'http://localhost:8001';

    $response = $this->httpClient->request('POST',
      "{$nlpUrl}/api/{$task}",
      ['json' => $data, 'timeout' => 120]
    );
    return json_decode(
      $response->getBody()->getContents(), TRUE
    );
  }

  private function classifyWithGemini(
    string $text, $resolution
  ): array {
    $isEu = $resolution->isEuSource();
    $prompt = $isEu
      ? $this->getEuClassificationPrompt()
      : $this->getNationalClassificationPrompt();

    return $this->ragService->callGemini(
      $prompt . '\n\nTEXTO:\n' . mb_substr($text, 0, 8000),
      ['response_format' => 'json']
    );
  }

  private function summarizeWithGemini(
    string $text, $resolution
  ): array {
    $isEu = $resolution->isEuSource();
    $prompt = $isEu
      ? $this->getEuSummarizationPrompt()
      : $this->getNationalSummarizationPrompt();

    return $this->ragService->callGemini(
      $prompt . '\n\nTEXTO:\n' . mb_substr($text, 0, 12000),
      ['response_format' => 'json']
    );
  }

  private function chunkText(
    string $text, array $segments
  ): array {
    $chunks = [];
    // Chunk 1: title + abstract (always)
    // Then: chunk by segments with 200-token overlap
    $maxTokens = 512;
    $overlap = 50;

    foreach ($segments as $seg) {
      $segText = $seg['text'];
      $words = explode(' ', $segText);
      $pos = 0;
      while ($pos < count($words)) {
        $chunkWords = array_slice(
          $words, $pos, $maxTokens
        );
        $chunks[] = [
          'text' => implode(' ', $chunkWords),
          'metadata' => [
            'section' => $seg['section'] ?? 'body',
            'chunk_index' => count($chunks),
          ],
        ];
        $pos += $maxTokens - $overlap;
      }
    }
    return $chunks;
  }

  private function indexInQdrant(
    string $collection, array $embeddings
  ): array {
    $config = $this->configFactory->get(
      'jaraba_legal_intelligence.settings'
    );
    $qdrantUrl = $config->get('qdrant_url')
      ?? 'http://localhost:6333';

    $points = [];
    $ids = [];
    foreach ($embeddings as $emb) {
      $id = \Drupal\Component\Uuid\Php::generate();
      $points[] = [
        'id' => $id,
        'vector' => $emb['vector'],
        'payload' => $emb['metadata'],
      ];
      $ids[] = $id;
    }

    $this->httpClient->request('PUT',
      "{$qdrantUrl}/collections/{$collection}/points",
      ['json' => ['points' => $points]]
    );

    return $ids;
  }

  private function buildCitationGraph(
    int $resolutionId, array $entities
  ): void {
    $connection = \Drupal::database();
    $storage = \Drupal::entityTypeManager()
      ->getStorage('legal_resolution');

    foreach ($entities as $entity) {
      if ($entity['type'] !== 'legislation_ref') continue;

      // Try to find cited resolution in DB
      $cited = $storage->loadByProperties([
        'external_ref' => $entity['reference'],
      ]);

      if (!empty($cited)) {
        $citedId = reset($cited)->id();
        $connection->merge('legal_citation_graph')
          ->keys([
            'citing_id' => $resolutionId,
            'cited_id' => $citedId,
          ])
          ->fields([
            'citation_context' => mb_substr(
              $entity['context'] ?? '', 0, 512
            ),
            'created' => time(),
          ])
          ->execute();
      }
    }
  }
}
 
5. Scripts Python: Pipeline NLP
Los scripts Python se ejecutan como microservicio independiente (FastAPI) que el módulo Drupal invoca vía HTTP.
5.1 requirements.txt
fastapi==0.109.0
uvicorn==0.27.0
spacy==3.7.4
# python -m spacy download es_core_news_lg
pydantic==2.5.3
httpx==0.26.0
qdrant-client==1.7.3
openai==1.12.0
google-generativeai==0.4.0
5.2 pipeline.py (FastAPI server)
from fastapi import FastAPI
from pydantic import BaseModel
import spacy
from legal_ner import LegalNER

app = FastAPI(title='Jaraba Legal NLP Service')
nlp = spacy.load('es_core_news_lg')
legal_ner = LegalNER(nlp)

class SegmentRequest(BaseModel):
    text: str
    source_id: str

class NerRequest(BaseModel):
    text: str

@app.post('/api/segment')
async def segment(req: SegmentRequest):
    doc = nlp(req.text[:50000])
    sections = []
    current = {'section': 'header', 'text': ''}

    SECTION_MARKERS = {
        'ANTECEDENTES': 'antecedentes',
        'HECHOS': 'hechos',
        'FUNDAMENTOS': 'fundamentos',
        'FALLO': 'fallo',
        'RESUELVE': 'fallo',
        'VOTO PARTICULAR': 'voto_particular',
    }

    for sent in doc.sents:
        text_upper = sent.text.strip().upper()
        matched = False
        for marker, section_name in SECTION_MARKERS.items():
            if marker in text_upper:
                if current['text'].strip():
                    sections.append(current.copy())
                current = {
                    'section': section_name,
                    'text': sent.text
                }
                matched = True
                break
        if not matched:
            current['text'] += ' ' + sent.text

    if current['text'].strip():
        sections.append(current)

    return sections

@app.post('/api/ner')
async def ner(req: NerRequest):
    entities = legal_ner.extract(req.text[:50000])
    return {'entities': entities}

if __name__ == '__main__':
    import uvicorn
    uvicorn.run(app, host='0.0.0.0', port=8001)
5.3 legal_ner.py (NER Legal Personalizado)
import re
import spacy
from spacy.tokens import Span

class LegalNER:
    PATTERNS = {
        'ley': r'(?:Ley\s+(?:Org[a-z]+\.?\s+)?\d+/\d{4})
                r'(?:,?\s+de\s+\d+\s+de\s+\w+)?',
        'rd': r'(?:Real\s+Decreto(?:-[Ll]ey)?\s+\d+/\d{4})
              r'(?:,?\s+de\s+\d+\s+de\s+\w+)?',
        'articulo': r'(?:[Aa]rt(?:[i\u00ed]culo|\.?)\s+\d+
                     r'(?:\.\d+)?(?:\s+(?:bis|ter|quater))?)',
        'sentencia': r'(?:S(?:T|entencia)\s*(?:del?\s+)?
                      r'(?:TS|TSJ|TC|TJUE|TEDH)\s+
                      r'\d+/\d{4})',
        'consulta_dgt': r'(?:V\d{4}-\d{2})',
        'directiva_ue': r'(?:Directiva\s+(?:\(UE\)\s+)?
                         r'\d{2,4}/\d+/(?:CEE|CE|UE))',
        'reglamento_ue': r'(?:Reglamento\s+(?:\(UE\)\s+)?
                          r'(?:n[\u00ba]\s*)?\d+/\d{4})',
        'ecli': r'ECLI:[A-Z]{2}:[A-Z0-9]+:\d{4}:\d+',
        'celex': r'[0-9]{5}[A-Z]{1,2}[0-9]{4}',
    }

    def __init__(self, nlp):
        self.nlp = nlp
        self.compiled = {
            k: re.compile(v, re.IGNORECASE)
            for k, v in self.PATTERNS.items()
        }

    def extract(self, text: str) -> list[dict]:
        entities = []
        for ent_type, pattern in self.compiled.items():
            for match in pattern.finditer(text):
                start = max(0, match.start() - 100)
                end = min(len(text), match.end() + 100)
                entities.append({
                    'type': 'legislation_ref',
                    'subtype': ent_type,
                    'reference': match.group().strip(),
                    'start': match.start(),
                    'end': match.end(),
                    'context': text[start:end],
                })
        return entities
 
6. Spiders de Ingesta: Implementación
6.1 SpiderInterface (PHP)
<?php

namespace Drupal\jaraba_legal_intelligence\Service\Spider;

interface SpiderInterface {

  /** Source machine name. */
  public function getSourceId(): string;

  /** Human-readable label. */
  public function getLabel(): string;

  /**
   * Fetch new/updated resolutions since last sync.
   *
   * @param \DateTimeInterface|null $since
   * @return iterable<array> Raw resolution data arrays
   */
  public function fetch(?\DateTimeInterface $since = NULL): iterable;

  /**
   * Download full text for a single resolution.
   */
  public function fetchFullText(string $externalRef): ?string;

  /** Get source URL for an external reference. */
  public function getSourceUrl(string $externalRef): string;
}
6.2 EurLexSpider (EUR-Lex SPARQL)
<?php

namespace Drupal\jaraba_legal_intelligence\Service\Spider;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

class EurLexSpider implements SpiderInterface {

  private const SPARQL_ENDPOINT =
    'https://publications.europa.eu/webapi/rdf/sparql';

  public function __construct(
    protected ClientInterface $httpClient,
    protected LoggerInterface $logger,
  ) {}

  public function getSourceId(): string { return 'eurlex'; }
  public function getLabel(): string { return 'EUR-Lex / Cellar'; }

  public function fetch(
    ?\DateTimeInterface $since = NULL
  ): iterable {
    $dateFilter = $since
      ? sprintf(
          'FILTER(?date >= "%s"^^xsd:date)',
          $since->format('Y-m-d')
        )
      : '';

    $sparql = <<<SPARQL
    PREFIX cdm: <http://publications.europa.eu/ontology/cdm#>
    PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
    SELECT DISTINCT ?work ?celex ?title ?date ?type ?force
    WHERE {
      ?work cdm:work_has_resource-type ?rtype.
      FILTER(?rtype IN (
        <http://pub.../resource-type/DIR>,
        <http://pub.../resource-type/REG>,
        <http://pub.../resource-type/DEC>
      ))
      ?work cdm:resource_legal_id_celex ?celex.
      ?work cdm:work_date_document ?date.
      OPTIONAL{?work cdm:resource_legal_in-force ?force.}
      ?expr cdm:expression_belongs_to_work ?work.
      ?expr cdm:expression_uses_language
        <http://pub.../language/SPA>.
      ?expr cdm:expression_title ?title.
      {$dateFilter}
    }
    ORDER BY DESC(?date)
    LIMIT 500
    SPARQL;

    $response = $this->httpClient->request('GET',
      self::SPARQL_ENDPOINT, [
        'query' => [
          'query' => $sparql,
          'format' => 'application/json',
        ],
        'timeout' => 60,
      ]
    );

    $data = json_decode(
      $response->getBody()->getContents(), TRUE
    );

    foreach ($data['results']['bindings'] ?? [] as $row) {
      yield [
        'source_id' => 'eurlex',
        'external_ref' => $row['celex']['value'],
        'title' => $row['title']['value'],
        'date_issued' => $row['date']['value'],
        'celex_number' => $row['celex']['value'],
        'status_legal' =>
          ($row['force']['value'] ?? 'true') === 'true'
            ? 'vigente' : 'derogada',
        'resolution_type' => $this->mapResourceType(
          $row['rtype']['value'] ?? ''
        ),
        'issuing_body' => 'Unión Europea',
        'original_url' => sprintf(
          'https://eur-lex.europa.eu/legal-content/ES/TXT/?uri=CELEX:%s',
          $row['celex']['value']
        ),
        'language_original' => 'es',
      ];
    }
  }

  public function fetchFullText(string $externalRef): ?string {
    $url = sprintf(
      'https://eur-lex.europa.eu/legal-content/ES/TXT/HTML/?uri=CELEX:%s',
      $externalRef
    );
    try {
      $response = $this->httpClient->request('GET', $url,
        ['timeout' => 30]
      );
      $html = $response->getBody()->getContents();
      return strip_tags($html); // Simplified; use Tika for PDF
    }
    catch (\Exception $e) {
      $this->logger->warning(
        'Failed to fetch EUR-Lex full text for @ref: @err',
        ['@ref' => $externalRef, '@err' => $e->getMessage()]
      );
      return NULL;
    }
  }

  public function getSourceUrl(string $ref): string {
    return 'https://eur-lex.europa.eu/legal-content/ES/TXT/?uri=CELEX:'
      . $ref;
  }

  private function mapResourceType(string $uri): string {
    return match (TRUE) {
      str_contains($uri, 'DIR') => 'directiva',
      str_contains($uri, 'REG') => 'reglamento',
      str_contains($uri, 'DEC') => 'decision',
      default => 'otro',
    };
  }
}
 
6.3 HudocSpider (TEDH)
<?php

namespace Drupal\jaraba_legal_intelligence\Service\Spider;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

class HudocSpider implements SpiderInterface {

  private const HUDOC_API = 'https://hudoc.echr.coe.int/app/query/results';

  public function __construct(
    protected ClientInterface $httpClient,
    protected LoggerInterface $logger,
  ) {}

  public function getSourceId(): string { return 'tedh'; }
  public function getLabel(): string { return 'TEDH (HUDOC)'; }

  public function fetch(
    ?\DateTimeInterface $since = NULL
  ): iterable {
    $query = [
      'query' => 'respondent:ESP',
      'select' => 'itemid,docname,appno,importance,respondent,',
      'select' => $this->buildSelect(),
      'sort' => 'kpdate Descending',
      'start' => 0,
      'length' => 100,
    ];

    if ($since) {
      $query['query'] .= sprintf(
        ' AND kpdate:["%s" TO "%s"]',
        $since->format('Y-m-d\TH:i:s\Z'),
        date('Y-m-d\TH:i:s\Z')
      );
    }

    $response = $this->httpClient->request('GET',
      self::HUDOC_API,
      ['query' => $query, 'timeout' => 30]
    );

    $data = json_decode(
      $response->getBody()->getContents(), TRUE
    );

    foreach ($data['results'] ?? [] as $item) {
      $cols = $item['columns'] ?? [];
      yield [
        'source_id' => 'tedh',
        'external_ref' => $cols['appno'] ?? $cols['itemid'],
        'title' => $cols['docname'] ?? 'Sin título',
        'date_issued' => $cols['kpdate'] ?? '',
        'ecli' => $cols['ecli'] ?? '',
        'case_number' => $cols['appno'] ?? '',
        'resolution_type' => $this->mapDocType(
          $cols['doctype'] ?? ''
        ),
        'issuing_body' => 'TEDH',
        'respondent_state' => 'ESP',
        'cedh_articles' => json_encode(
          $cols['article'] ?? []
        ),
        'importance_level' =>
          (int)($cols['importance'] ?? 3),
        'original_url' =>
          'https://hudoc.echr.coe.int/spa?i='
          . ($cols['itemid'] ?? ''),
        'language_original' =>
          str_contains($cols['docname'] ?? '', 'c. Espa')
            ? 'es' : 'en',
      ];
    }
  }

  public function fetchFullText(string $ref): ?string {
    // HUDOC returns HTML in the API response
    $url = 'https://hudoc.echr.coe.int/app/conversion/docx/html/body/'
      . urlencode($ref);
    try {
      $response = $this->httpClient->request('GET', $url);
      return strip_tags(
        $response->getBody()->getContents()
      );
    } catch (\Exception $e) {
      return NULL;
    }
  }

  public function getSourceUrl(string $ref): string {
    return 'https://hudoc.echr.coe.int/spa?i=' . $ref;
  }

  private function mapDocType(string $type): string {
    return match ($type) {
      'HEJUD' => 'sentencia',
      'HEDEC' => 'decision',
      'HEADV' => 'opinion_consultiva',
      default => 'resolucion',
    };
  }
}
 
7. Queue Workers
7.1 LegalIngestionWorker
<?php

namespace Drupal\jaraba_legal_intelligence\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @QueueWorker(
 *   id = "legal_ingestion",
 *   title = @Translation("Legal Resolution Ingestion"),
 *   cron = {"time" = 300}
 * )
 */
class LegalIngestionWorker extends QueueWorkerBase
  implements ContainerFactoryPluginInterface {

  protected $ingestionService;
  protected $nlpPipeline;

  public static function create(
    ContainerInterface $container, array $config,
    $plugin_id, $plugin_definition
  ) {
    $instance = new static($config, $plugin_id, $plugin_definition);
    $instance->ingestionService = $container->get(
      'jaraba_legal.ingestion'
    );
    $instance->nlpPipeline = $container->get(
      'jaraba_legal.nlp_pipeline'
    );
    return $instance;
  }

  public function processItem($data) {
    // $data = ['source_id' => 'cendoj', 'raw' => [...]]
    $resolutionId = $this->ingestionService->upsert(
      $data['raw']
    );

    if ($resolutionId) {
      // Enqueue NLP processing
      \Drupal::queue('legal_nlp_processing')->createItem([
        'resolution_id' => $resolutionId,
      ]);
    }
  }
}
7.2 LegalNlpWorker
<?php

namespace Drupal\jaraba_legal_intelligence\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @QueueWorker(
 *   id = "legal_nlp_processing",
 *   title = @Translation("Legal NLP Processing"),
 *   cron = {"time" = 600}
 * )
 */
class LegalNlpWorker extends QueueWorkerBase
  implements ContainerFactoryPluginInterface {

  protected $nlpPipeline;

  public static function create(
    ContainerInterface $container, array $config,
    $plugin_id, $plugin_definition
  ) {
    $instance = new static($config, $plugin_id, $plugin_definition);
    $instance->nlpPipeline = $container->get(
      'jaraba_legal.nlp_pipeline'
    );
    return $instance;
  }

  public function processItem($data) {
    $this->nlpPipeline->process($data['resolution_id']);
  }
}
 
8. Flujos ECA: Configuración YAML
8.1 ECA-LIH-001: Ingesta Programada
# config/install/eca.model.legal_ingestion_cron.yml
id: legal_ingestion_cron
label: 'Legal Intelligence - Scheduled Ingestion'
status: true
events:
  - plugin: 'eca_cron'
    configuration:
      frequency: '0 2 * * *'  # Daily at 02:00 UTC
conditions: []
actions:
  - plugin: 'eca_execute_php'
    configuration:
      code: |
        $ingestion = \Drupal::service('jaraba_legal.ingestion');
        $sources = ['cendoj','boe','dgt','teac','tjue','eurlex',
                    'tedh','edpb'];
        foreach ($sources as $sourceId) {
          $ingestion->syncSource($sourceId);
        }
8.2 ECA-LIH-003: Detector de Cambios Normativos
# config/install/eca.model.legal_change_detector.yml
id: legal_change_detector
label: 'Legal Intelligence - Regulatory Change Detector'
status: true
events:
  - plugin: 'entity_presave'
    configuration:
      entity_type: 'legal_resolution'
conditions:
  - plugin: 'eca_entity_field_value_changed'
    configuration:
      field_name: 'status_legal'
actions:
  - plugin: 'eca_execute_php'
    configuration:
      code: |
        $alertService = \Drupal::service('jaraba_legal.alerts');
        $resolution = $event->getEntity();
        $oldStatus = $resolution->original
          ? $resolution->original->get('status_legal')->value
          : NULL;
        $newStatus = $resolution->get('status_legal')->value;
        if ($oldStatus === 'vigente'
            && in_array($newStatus, ['anulada','derogada'])) {
          $alertService->triggerResolutionAnnulled(
            $resolution->id()
          );
        }
8.3 ECA-LIH-004: Digest Semanal Personalizado
# config/install/eca.model.legal_weekly_digest.yml
id: legal_weekly_digest
label: 'Legal Intelligence - Weekly Digest'
status: true
events:
  - plugin: 'eca_cron'
    configuration:
      frequency: '0 7 * * 1'  # Monday 07:00 UTC
conditions: []
actions:
  - plugin: 'eca_execute_php'
    configuration:
      code: |
        $digest = \Drupal::service('jaraba_legal.digest');
        $digest->generateAndSendAll();
 
9. Qdrant: Scripts de Creación de Colecciones
#!/bin/bash
# scripts/qdrant_setup.sh
# Run once to create collections

QDRANT_URL=${QDRANT_URL:-http://localhost:6333}

# Collection: National sources (ES)
curl -X PUT "${QDRANT_URL}/collections/legal_intelligence" \
  -H 'Content-Type: application/json' \
  -d '{
    "vectors": {
      "size": 3072,
      "distance": "Cosine",
      "on_disk": true
    },
    "hnsw_config": {
      "m": 32,
      "ef_construct": 200
    },
    "optimizers_config": {
      "memmap_threshold": 20000
    }
  }'

# Collection: EU sources
curl -X PUT "${QDRANT_URL}/collections/legal_intelligence_eu" \
  -H 'Content-Type: application/json' \
  -d '{
    "vectors": {
      "size": 3072,
      "distance": "Cosine",
      "on_disk": true
    },
    "hnsw_config": {
      "m": 32,
      "ef_construct": 200
    }
  }'

# Payload indexes for both collections
for COLL in legal_intelligence legal_intelligence_eu; do
  for FIELD in source_id jurisdiction resolution_type \
    issuing_body respondent_state importance_level; do
    curl -X PUT \
      "${QDRANT_URL}/collections/${COLL}/index" \
      -H 'Content-Type: application/json' \
      -d "{
        \"field_name\": \"${FIELD}\",
        \"field_schema\": \"keyword\"
      }"
  done

  # Date index (integer for range queries)
  curl -X PUT \
    "${QDRANT_URL}/collections/${COLL}/index" \
    -H 'Content-Type: application/json' \
    -d '{
      "field_name": "date_issued",
      "field_schema": "keyword"
    }'

  # Resolution ID for dedup/exclusion
  curl -X PUT \
    "${QDRANT_URL}/collections/${COLL}/index" \
    -H 'Content-Type: application/json' \
    -d '{
      "field_name": "resolution_id",
      "field_schema": "integer"
    }'
done

echo 'Qdrant collections created successfully.'
 
10. Configuración de Instalación
10.1 jaraba_legal_intelligence.settings.yml
# config/install/jaraba_legal_intelligence.settings.yml
qdrant_url: 'http://qdrant:6333'
tika_url: 'http://tika:9998'
nlp_service_url: 'http://legal-nlp:8001'
gemini_model: 'gemini-2.0-flash'
embedding_model: 'text-embedding-3-large'
embedding_dimensions: 3072
search_score_threshold: 0.65
max_chunk_tokens: 512
chunk_overlap_tokens: 50
digest_enabled: true
digest_day: 'monday'
digest_hour: 7
plan_limits:
  starter:
    monthly_searches: 50
    sources: ['cendoj', 'boe']
    max_alerts: 3
    citation_insert: false
  pro:
    monthly_searches: -1  # unlimited
    sources: 'all'
    max_alerts: 20
    citation_insert: true
    digest: true
  enterprise:
    monthly_searches: -1
    sources: 'all'
    max_alerts: -1
    citation_insert: true
    digest: true
    api_access: true
10.2 jaraba_legal_intelligence.sources.yml
# config/install/jaraba_legal_intelligence.sources.yml
sources:
  cendoj:
    label: 'CENDOJ (Jurisprudencia ES)'
    url: 'https://www.poderjudicial.es/cendoj'
    spider: 'cendoj'
    frequency: 'daily'
    priority: 'critical'
    enabled: true
  boe:
    label: 'BOE (Legislación ES)'
    url: 'https://www.boe.es'
    spider: 'boe'
    frequency: 'daily'
    priority: 'critical'
    enabled: true
  dgt:
    label: 'DGT (Consultas Tributarias)'
    url: 'https://petete.tributos.hacienda.gob.es'
    spider: 'dgt'
    frequency: 'weekly'
    priority: 'high'
    enabled: true
  teac:
    label: 'TEAC (Económico-Administrativas)'
    url: 'https://serviciostelematicosext.hacienda.gob.es'
    spider: 'teac'
    frequency: 'weekly'
    priority: 'high'
    enabled: true
  tc:
    label: 'Tribunal Constitucional'
    url: 'https://hj.tribunalconstitucional.es'
    spider: 'tc'
    frequency: 'weekly'
    priority: 'high'
    enabled: true
  tjue:
    label: 'TJUE (CURIA + EUR-Lex sector 6)'
    url: 'https://curia.europa.eu'
    spider: 'curia'
    frequency: 'weekly'
    priority: 'critical'
    enabled: true
  eurlex:
    label: 'EUR-Lex (Legislación UE)'
    url: 'https://eur-lex.europa.eu'
    spider: 'eurlex'
    frequency: 'weekly'
    priority: 'critical'
    enabled: true
  tedh:
    label: 'TEDH (HUDOC)'
    url: 'https://hudoc.echr.coe.int'
    spider: 'hudoc'
    frequency: 'weekly'
    priority: 'high'
    enabled: true
  edpb:
    label: 'EDPB (Directrices RGPD)'
    url: 'https://edpb.europa.eu'
    spider: 'edpb'
    frequency: 'monthly'
    priority: 'medium'
    enabled: true
 
11. Templates Twig
11.1 legal-search-results.html.twig
{# templates/legal-search-results.html.twig #}
{% if results is not empty %}
<div class="legal-search-results">
  <p class="results-count">
    {{ total }} resultados para
    &laquo;{{ query }}&raquo;
  </p>

  {% for result in results %}
  <article class="legal-result
    legal-result--{{ result.source_id }}
    {% if result.is_eu %}legal-result--eu{% endif %}">

    <div class="legal-result__header">
      <span class="legal-result__source-badge
        badge--{{ result.source_id }}">
        {% if result.is_eu %}🇪🇺{% else %}🇪🇸{% endif %}
        {{ result.source_label }}
      </span>

      {% if result.primacy_badge %}
      <span class="legal-result__primacy
        primacy--{{ result.primacy_badge }}">
        {{ result.primacy_label }}
      </span>
      {% endif %}

      <span class="legal-result__score">
        {{ (result.score * 100)|round }}% relevancia
      </span>
    </div>

    <h3 class="legal-result__title">
      <a href="{{ result.url }}">
        {{ result.external_ref }} — {{ result.title }}
      </a>
    </h3>

    <p class="legal-result__meta">
      {{ result.issuing_body }} &middot;
      {{ result.date_issued|date('d/m/Y') }} &middot;
      {{ result.resolution_type_label }}
    </p>

    <p class="legal-result__abstract">
      {{ result.abstract_ai|striptags|truncate(300) }}
    </p>

    <div class="legal-result__actions">
      <a href="{{ result.url }}"
         class="btn btn--sm">Ver detalle</a>
      <button class="btn btn--sm btn--outline
        js-legal-bookmark"
        data-id="{{ result.id }}">
        ☆ Guardar
      </button>
      {% if can_insert_citation %}
      <button class="btn btn--sm btn--accent
        js-legal-cite"
        data-id="{{ result.id }}">
        ⊕ Insertar en expediente
      </button>
      {% endif %}
    </div>
  </article>
  {% endfor %}

  {{ pager }}
</div>
{% else %}
<p class="legal-search-empty">
  No se encontraron resultados.
</p>
{% endif %}
 
12. Tests PHPUnit
12.1 LegalResolutionEntityTest (Kernel)
<?php

namespace Drupal\Tests\jaraba_legal_intelligence\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\jaraba_legal_intelligence\Entity\LegalResolution;

class LegalResolutionEntityTest extends KernelTestBase {

  protected static $modules = [
    'system', 'user', 'file', 'group', 'taxonomy',
    'jaraba_core', 'jaraba_tenant', 'jaraba_ai',
    'jaraba_services', 'jaraba_legal_intelligence',
  ];

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('legal_resolution');
    $this->installEntitySchema('legal_alert');
    $this->installEntitySchema('legal_bookmark');
    $this->installEntitySchema('legal_citation');
  }

  public function testCreateNationalResolution(): void {
    $entity = LegalResolution::create([
      'source_id' => 'dgt',
      'external_ref' => 'V0123-24',
      'title' => 'Tributación criptomonedas IRPF',
      'resolution_type' => 'consulta_vinculante',
      'issuing_body' => 'DGT',
      'date_issued' => '2024-03-15',
      'status_legal' => 'vigente',
    ]);
    $entity->save();

    $this->assertNotNull($entity->id());
    $this->assertEquals('dgt', $entity->get('source_id')->value);
    $this->assertFalse($entity->isEuSource());
  }

  public function testCreateEuResolution(): void {
    $entity = LegalResolution::create([
      'source_id' => 'tjue',
      'external_ref' => 'ECLI:EU:C:2013:164',
      'title' => 'Aziz v Catalunyacaixa',
      'resolution_type' => 'sentencia',
      'issuing_body' => 'TJUE (Gran Sala)',
      'date_issued' => '2013-03-14',
      'case_number' => 'C-415/11',
      'ecli' => 'ECLI:EU:C:2013:164',
      'procedure_type' => 'prejudicial',
      'respondent_state' => 'ESP',
    ]);
    $entity->save();

    $this->assertTrue($entity->isEuSource());
    $this->assertEquals('C-415/11',
      $entity->get('case_number')->value);
  }

  public function testFormatCitation(): void {
    $entity = LegalResolution::create([
      'source_id' => 'dgt',
      'external_ref' => 'V0123-24',
      'title' => 'Test',
      'resolution_type' => 'consulta_vinculante',
      'issuing_body' => 'DGT',
      'date_issued' => '2024-03-15',
      'key_holdings' => 'Las criptomonedas tributan...',
    ]);

    $formal = $entity->formatCitation('formal');
    $this->assertStringContainsString('Consulta Vinculante', $formal);
    $this->assertStringContainsString('V0123-24', $formal);
    $this->assertStringContainsString('DGT', $formal);
  }

  public function testDeduplication(): void {
    LegalResolution::create([
      'source_id' => 'boe',
      'external_ref' => 'BOE-A-2024-1234',
      'title' => 'Ley X',
      'resolution_type' => 'ley',
      'issuing_body' => 'Jefatura del Estado',
      'date_issued' => '2024-01-01',
    ])->save();

    $this->expectException(
      \Drupal\Core\Entity\EntityStorageException::class
    );
    LegalResolution::create([
      'source_id' => 'boe',
      'external_ref' => 'BOE-A-2024-1234',
      'title' => 'Ley X duplicada',
      'resolution_type' => 'ley',
      'issuing_body' => 'Jefatura del Estado',
      'date_issued' => '2024-01-01',
    ])->save();
  }
}
 
13. Docker: Servicio NLP
# docker-compose.legal-nlp.yml
# Add to main docker-compose.yml

services:
  legal-nlp:
    build:
      context: ./web/modules/custom/jaraba_legal_intelligence/scripts
      dockerfile: Dockerfile.nlp
    ports:
      - '8001:8001'
    environment:
      - SPACY_MODEL=es_core_news_lg
    volumes:
      - legal_nlp_cache:/app/cache
    restart: unless-stopped
    mem_limit: 2g

volumes:
  legal_nlp_cache:
# scripts/Dockerfile.nlp
FROM python:3.12-slim
WORKDIR /app
COPY nlp/requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt
RUN python -m spacy download es_core_news_lg
COPY nlp/ .
EXPOSE 8001
CMD ["uvicorn", "pipeline:app",
     "--host", "0.0.0.0", "--port", "8001"]
 
Control de Versiones
Versión	Fecha	Autor	Cambios
1.0	Febrero 2026	Claude (Anthropic) / Pepe Jaraba	Guía de implementación completa: entidades, servicios, spiders, NLP, Qdrant, ECA, tests, Docker
——— Fin del Documento ———
