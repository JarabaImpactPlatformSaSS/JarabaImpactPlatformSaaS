# 🎓 JARABA INTERACTIVE AI-POWERED
## Arquitectura Maestra del Sistema de Contenido Interactivo

> **Estado**: 📋 Especificación Aprobada  
> **Versión**: 1.0  
> **Fecha**: Febrero 2026  
> **Código**: JARABA_INTERACTIVE_AI  
> **Dependencias**: jaraba_lms, jaraba_training, jaraba_credentials, jaraba_ai_agents

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Visión del Producto](#2-visión-del-producto)
3. [Arquitectura del Sistema](#3-arquitectura-del-sistema)
4. [Modelo de Datos](#4-modelo-de-datos)
5. [Sistema de Plugins InteractiveType](#5-sistema-de-plugins-interactivetype)
6. [Content Types Prioritarios](#6-content-types-prioritarios)
7. [Player JavaScript World-Class](#7-player-javascript-world-class)
8. [Motor IA Generativa](#8-motor-ia-generativa)
9. [Sistema xAPI y Tracking](#9-sistema-xapi-y-tracking)
10. [Integración con Ecosistema Jaraba](#10-integración-con-ecosistema-jaraba)
11. [API REST y Endpoints](#11-api-rest-y-endpoints)
12. [Frontend y UX Premium](#12-frontend-y-ux-premium)
13. [Multitenancy y Branding](#13-multitenancy-y-branding)
14. [Directivas del Proyecto Aplicables](#14-directivas-del-proyecto-aplicables)
15. [Especificaciones Técnicas de Referencia](#15-especificaciones-técnicas-de-referencia)
16. [Roadmap de Implementación](#16-roadmap-de-implementación)
17. [Análisis de Riesgos](#17-análisis-de-riesgos)
18. [Métricas de Éxito](#18-métricas-de-éxito)
19. [Referencias](#19-referencias)

---

## 1. Resumen Ejecutivo

### 1.1 Propósito

El módulo **jaraba_interactive** es un sistema de contenido interactivo de clase mundial diseñado para competir con plataformas como Coursera, LinkedIn Learning y Duolingo. A diferencia de soluciones basadas en H5P u otros módulos contrib, jaraba_interactive es **100% propio**, ofreciendo control total sobre la experiencia de usuario, integración nativa con el ecosistema Jaraba, y capacidades de IA generativa para creación automática de contenido.

### 1.2 Alcance

| Aspecto | Descripción |
|---------|-------------|
| **Verticales** | Empleabilidad, Emprendimiento, Capacitación/Certificación |
| **Usuarios** | Estudiantes, Instructores, Administradores, Tenants |
| **Contenido** | 6 tipos interactivos prioritarios + extensible |
| **IA** | Generación automática con Claude API |
| **Tracking** | xAPI completo integrado con jaraba_lms |

### 1.3 Decisión Estratégica

**¿Por qué no usar H5P?**

| Criterio | H5P Contrib | jaraba_interactive |
|----------|-------------|-------------------|
| Estado módulo Drupal | Alpha perpetua | Producción estable |
| Control de código | Externo | 100% propio |
| Multi-tenant | Requiere parches | Nativo |
| IA generativa | No disponible | Claude integrado |
| Branding por tenant | Limitado | CSS inyectable |
| xAPI | Bridge externo | Nativo |
| Dependencias | h5p_package, h5p_analytics | Ninguna externa |

### 1.4 Inversión y ROI

| Métrica | Valor |
|---------|-------|
| **Esfuerzo total** | ~500 horas |
| **Timeline** | 18 semanas (6 sprints) |
| **Coste estimado** | €40,000-50,000 |
| **ROI proyectado** | 400-600% primer año |
| **Diferenciación** | Única plataforma con IA generativa nativa |

---

## 2. Visión del Producto

### 2.1 Benchmarking Competitivo

El diseño de jaraba_interactive se basa en el análisis de las mejores plataformas educativas del mundo:

| Plataforma | Fortaleza Clave | Adopción en Jaraba |
|------------|-----------------|-------------------|
| **Coursera** | Video + Quiz integrado, flujo seamless | ✅ InteractiveVideo con checkpoints |
| **Duolingo** | Gamificación, feedback instantáneo | ✅ Micro-interacciones premium, XP |
| **Articulate Rise** | Editor WYSIWYG intuitivo | ✅ ContentEditor visual |
| **LinkedIn Learning** | Tracking granular, completions | ✅ xAPI completo |
| **H5P** | Diversidad de tipos, portabilidad | ✅ 6 tipos core + import .h5p |

### 2.2 Diferenciadores Únicos de Jaraba

1. **Multi-tenant Nativo**: Contenido aislado por tenant sin parches
2. **IA Generativa Integrada**: Claude API para Smart Import propio
3. **xAPI sin LRS Externo**: Integración directa con `progress_record`
4. **Branding Inyectable**: CSS variables por tenant
5. **Mobile-First Premium**: Gestos táctiles, swipe navigation
6. **Certificación Automática**: Conexión con `jaraba_credentials` (Open Badge 3.0)

### 2.3 Casos de Uso por Vertical

#### Empleabilidad (Impulso Empleo)
- Módulos teóricos con Course Presentation
- Simulaciones de entrevista con Branching Scenario
- Evaluaciones de competencias con Question Set
- Videos de formación con checkpoints interactivos

#### Emprendimiento (Impulso Negocio)
- Masterclasses interactivas
- Simulaciones de toma de decisiones empresariales
- Guías de creación de empresa (Interactive Book)
- Roadmaps de madurez digital (Timeline)

#### Capacitación/Certificación (SEPE/Andalucía)
- Exámenes de certificación con scoring
- Contenido de teleformación oficial
- Ejercicios de memorización de normativa
- Tracking xAPI homologable

---

## 3. Arquitectura del Sistema

### 3.1 Visión General

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           JARABA INTERACTIVE                                │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐         │
│  │  CONTENT LAYER  │    │   PLAYER LAYER  │    │    AI LAYER     │         │
│  │                 │    │                 │    │                 │         │
│  │ InteractiveType │◄──►│ PlayerCore.js   │◄──►│ ContentGenerator│         │
│  │ Plugins (6+)    │    │ TypeRenderers   │    │ Claude API      │         │
│  │ ContentRenderer │    │ XapiTracker     │    │ ImportPipeline  │         │
│  └────────┬────────┘    └────────┬────────┘    └────────┬────────┘         │
│           │                      │                      │                   │
│           ▼                      ▼                      ▼                   │
│  ┌─────────────────────────────────────────────────────────────────┐       │
│  │                        DATA LAYER                                │       │
│  │  InteractiveContent (Entity) │ InteractiveResult (Entity)       │       │
│  │  progress_record (jaraba_lms) │ xAPI Statements                 │       │
│  └─────────────────────────────────────────────────────────────────┘       │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
                                     │
                    ┌────────────────┼────────────────┐
                    ▼                ▼                ▼
           ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
           │  jaraba_lms  │ │jaraba_training│ │jaraba_creds │
           │   Courses    │ │   Programs   │ │   Badges     │
           │  Enrollments │ │Certifications│ │  Issuance    │
           └──────────────┘ └──────────────┘ └──────────────┘
```

### 3.2 Estructura del Módulo

```
jaraba_interactive/
│
├── jaraba_interactive.info.yml        # Metadata del módulo
├── jaraba_interactive.module          # Hooks de Drupal
├── jaraba_interactive.services.yml    # Definición de servicios
├── jaraba_interactive.routing.yml     # Rutas y endpoints
├── jaraba_interactive.permissions.yml # Permisos granulares
├── jaraba_interactive.libraries.yml   # Assets JS/CSS
│
├── config/
│   ├── install/
│   │   └── jaraba_interactive.settings.yml
│   └── schema/
│       └── jaraba_interactive.schema.yml
│
├── src/
│   ├── Entity/
│   │   ├── InteractiveContent.php     # Entidad principal
│   │   ├── InteractiveResult.php      # Resultados/respuestas
│   │   └── InteractiveContentType.php # Bundle config entity
│   │
│   ├── Service/
│   │   ├── ContentRenderer.php        # Renderizado de contenido
│   │   ├── XapiEmitter.php            # Emisión de xAPI statements
│   │   ├── ScoreCalculator.php        # Cálculo de puntuaciones
│   │   ├── ContentGenerator.php       # IA generativa
│   │   ├── H5PImporter.php            # Import .h5p (opcional)
│   │   └── CompletionService.php      # Lógica de completitud
│   │
│   ├── Plugin/
│   │   └── InteractiveType/
│   │       ├── InteractiveTypeInterface.php
│   │       ├── InteractiveTypeBase.php
│   │       ├── InteractiveVideo.php
│   │       ├── QuestionSet.php
│   │       ├── CoursePresentation.php
│   │       ├── BranchingScenario.php
│   │       ├── DragAndDrop.php
│   │       └── Essay.php
│   │
│   ├── Annotation/
│   │   └── InteractiveType.php        # Anotación para plugins
│   │
│   ├── Controller/
│   │   ├── PlayerController.php       # Renderiza player frontend
│   │   ├── EditorController.php       # Editor WYSIWYG
│   │   ├── XapiController.php         # Endpoint xAPI
│   │   └── ApiController.php          # REST API
│   │
│   ├── Form/
│   │   ├── InteractiveContentForm.php
│   │   ├── ContentTypeSettingsForm.php
│   │   └── GlobalSettingsForm.php
│   │
│   └── EventSubscriber/
│       ├── CompletionSubscriber.php   # Dispara certificaciones
│       └── XapiSubscriber.php         # Procesa statements
│
├── js/
│   ├── player/
│   │   ├── core.js                    # Player principal
│   │   ├── state-manager.js           # Gestión de estado
│   │   ├── xapi-tracker.js            # Tracking client-side
│   │   ├── feedback-engine.js         # Feedback visual
│   │   └── types/
│   │       ├── interactive-video.js
│   │       ├── question-set.js
│   │       ├── course-presentation.js
│   │       ├── branching-scenario.js
│   │       ├── drag-and-drop.js
│   │       └── essay.js
│   │
│   └── editor/
│       ├── content-editor.js          # Editor principal
│       ├── preview-engine.js          # Preview en tiempo real
│       └── type-editors/
│           └── [editor por tipo]
│
├── scss/
│   ├── _player.scss                   # Estilos del player
│   ├── _editor.scss                   # Estilos del editor
│   ├── _types.scss                    # Estilos por tipo
│   ├── _feedback.scss                 # Animaciones feedback
│   └── _responsive.scss               # Mobile-first
│
└── templates/
    ├── interactive-player.html.twig
    ├── interactive-editor.html.twig
    ├── interactive-content.html.twig
    └── types/
        └── [template por tipo]
```

### 3.3 Flujo de Datos Principal

```
┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐
│ CREAR    │────►│ ALMACENAR│────►│ RENDERIZAR────►│ TRACKEAR │
│ Contenido│     │ Entity   │     │ Player   │     │ xAPI     │
└──────────┘     └──────────┘     └──────────┘     └──────────┘
     │                │                │                │
     ▼                ▼                ▼                ▼
 ContentEditor  InteractiveContent  PlayerController  XapiEmitter
 ContentGenerator     ↓                  ↓                ↓
                 content_data      JS Player        progress_record
                 (JSON blob)       (client-side)    (jaraba_lms)
```

---

## 4. Modelo de Datos

### 4.1 Entidad Principal: InteractiveContent

```php
/**
 * @ContentEntityType(
 *   id = "interactive_content",
 *   label = @Translation("Contenido Interactivo"),
 *   label_collection = @Translation("Contenidos Interactivos"),
 *   label_singular = @Translation("contenido interactivo"),
 *   label_plural = @Translation("contenidos interactivos"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_interactive\InteractiveContentListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_interactive\Form\InteractiveContentForm",
 *       "add" = "Drupal\jaraba_interactive\Form\InteractiveContentForm",
 *       "edit" = "Drupal\jaraba_interactive\Form\InteractiveContentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_interactive\InteractiveContentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "interactive_content",
 *   data_table = "interactive_content_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer interactive content",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "langcode" = "langcode",
 *     "owner" = "uid",
 *   },
 * )
 */
class InteractiveContent extends ContentEntityBase implements EntityOwnerInterface {
    // Ver sección 4.2 para campos
}
```

### 4.2 Campos de InteractiveContent

| Campo | Tipo | Descripción | Requerido |
|-------|------|-------------|-----------|
| `id` | integer | ID único | Auto |
| `uuid` | uuid | UUID para referencias | Auto |
| `title` | string(255) | Título del contenido | ✅ |
| `uid` | entity_reference(user) | Autor/propietario | ✅ |
| `tenant_id` | entity_reference(group) | Tenant propietario | ✅ |
| `content_type` | string(64) | Plugin ID (interactive_video, etc.) | ✅ |
| `content_data` | string_long | JSON con estructura del contenido | ✅ |
| `settings` | string_long | JSON: passing_score, show_feedback, etc. | ❌ |
| `thumbnail` | entity_reference(file) | Imagen de preview | ❌ |
| `duration_minutes` | integer | Duración estimada | ❌ |
| `difficulty` | list_string | beginner, intermediate, advanced | ❌ |
| `status` | list_string | draft, published, archived | ✅ |
| `created` | created | Fecha creación | Auto |
| `changed` | changed | Fecha modificación | Auto |

### 4.3 Entidad de Resultados: InteractiveResult

```php
/**
 * @ContentEntityType(
 *   id = "interactive_result",
 *   label = @Translation("Resultado Interactivo"),
 *   base_table = "interactive_result",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class InteractiveResult extends ContentEntityBase {
    // Almacena respuestas individuales del usuario
}
```

### 4.4 Campos de InteractiveResult

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | integer | ID único |
| `uuid` | uuid | UUID |
| `user_id` | entity_reference(user) | Usuario que respondió |
| `content_id` | entity_reference(interactive_content) | Contenido relacionado |
| `enrollment_id` | entity_reference(enrollment) | Matrícula si aplica |
| `response_data` | string_long | JSON con respuestas detalladas |
| `score` | decimal(5,2) | Puntuación obtenida (0-100) |
| `max_score` | decimal(5,2) | Puntuación máxima posible |
| `passed` | boolean | Si aprobó según passing_score |
| `attempts` | integer | Número de intentos |
| `time_spent` | integer | Segundos dedicados |
| `completed` | boolean | Si completó el contenido |
| `completed_at` | datetime | Fecha de completitud |
| `created` | created | Fecha creación |

### 4.5 Esquema JSON: content_data

Cada tipo de contenido tiene su propio esquema JSON. Ejemplo para QuestionSet:

```json
{
  "version": "1.0",
  "type": "question_set",
  "title": "Evaluación de Competencias Digitales",
  "settings": {
    "passing_score": 70,
    "randomize_questions": true,
    "show_feedback": "immediate",
    "allow_retry": true,
    "max_attempts": 3
  },
  "questions": [
    {
      "id": "q1",
      "type": "multiple_choice",
      "question": "¿Qué es una hoja de cálculo?",
      "options": [
        {"id": "a", "text": "Un editor de texto", "correct": false},
        {"id": "b", "text": "Una herramienta para datos tabulares", "correct": true},
        {"id": "c", "text": "Un navegador web", "correct": false}
      ],
      "feedback": {
        "correct": "¡Correcto! Las hojas de cálculo organizan datos en filas y columnas.",
        "incorrect": "No exactamente. Una hoja de cálculo es para datos tabulares."
      },
      "points": 10
    }
  ],
  "metadata": {
    "estimated_time": 15,
    "difficulty": "beginner"
  }
}
```

---

## 5. Sistema de Plugins InteractiveType

### 5.1 Arquitectura del Plugin System

El sistema utiliza el patrón Plugin API de Drupal para permitir extensibilidad. Cada tipo de contenido interactivo es un plugin independiente.

```php
namespace Drupal\jaraba_interactive\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Define la anotación InteractiveType.
 *
 * @Annotation
 */
class InteractiveType extends Plugin {
  
  public $id;
  public $label;
  public $description;
  public $icon;
  public $category;
  public $weight = 0;
}
```

### 5.2 Interface del Plugin

```php
namespace Drupal\jaraba_interactive\Plugin\InteractiveType;

interface InteractiveTypeInterface {
  
  /**
   * Retorna el esquema JSON del contenido.
   */
  public function getSchema(): array;
  
  /**
   * Renderiza el contenido para el player.
   */
  public function render(array $data): array;
  
  /**
   * Calcula la puntuación basada en respuestas.
   */
  public function calculateScore(array $responses): float;
  
  /**
   * Retorna los verbos xAPI soportados.
   */
  public function getXapiVerbs(): array;
  
  /**
   * Valida la estructura del contenido.
   */
  public function validate(array $data): array;
  
  /**
   * Retorna configuración del editor.
   */
  public function getEditorConfig(): array;
}
```

### 5.3 Clase Base

```php
namespace Drupal\jaraba_interactive\Plugin\InteractiveType;

abstract class InteractiveTypeBase implements InteractiveTypeInterface {
  
  protected $pluginId;
  protected $pluginDefinition;
  
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
  }
  
  public function getLabel(): string {
    return $this->pluginDefinition['label'];
  }
  
  public function getIcon(): string {
    return $this->pluginDefinition['icon'] ?? 'play-circle';
  }
  
  public function getCategory(): string {
    return $this->pluginDefinition['category'] ?? 'general';
  }
  
  // Implementaciones por defecto...
}
```

---

## 6. Content Types Prioritarios

### 6.1 Resumen de Tipos

| ID | Nombre | Descripción | Prioridad |
|----|--------|-------------|-----------|
| `interactive_video` | Video Interactivo | Video con checkpoints, quizzes y overlays | CRÍTICA |
| `question_set` | Set de Preguntas | Evaluaciones con MC, TF, fill-in | CRÍTICA |
| `course_presentation` | Presentación | Slides interactivas tipo PowerPoint | ALTA |
| `branching_scenario` | Escenario Ramificado | Simulaciones con decisiones | ALTA |
| `drag_and_drop` | Arrastrar y Soltar | Matching y ordenamiento | MEDIA |
| `essay` | Ensayo | Respuesta de texto libre | MEDIA |

### 6.2 Interactive Video

```php
/**
 * @InteractiveType(
 *   id = "interactive_video",
 *   label = @Translation("Video Interactivo"),
 *   description = @Translation("Video con checkpoints, quizzes y overlays interactivos"),
 *   icon = "play-circle",
 *   category = "video",
 *   weight = -10,
 * )
 */
class InteractiveVideo extends InteractiveTypeBase {
  
  public function getSchema(): array {
    return [
      'video_source' => [
        'type' => 'object',
        'properties' => [
          'type' => ['enum' => ['youtube', 'vimeo', 'bunny', 'file']],
          'url' => ['type' => 'string'],
          'poster' => ['type' => 'string'],
        ],
      ],
      'checkpoints' => [
        'type' => 'array',
        'items' => [
          'time' => 'integer',      // Segundos
          'type' => ['enum' => ['question', 'info', 'branch', 'hotspot']],
          'pause_video' => 'boolean',
          'required' => 'boolean',
          'content' => 'object',
        ],
      ],
      'settings' => [
        'completion_threshold' => ['type' => 'integer', 'default' => 90],
        'allow_skip' => ['type' => 'boolean', 'default' => false],
        'show_progress_bar' => ['type' => 'boolean', 'default' => true],
      ],
    ];
  }
  
  public function getXapiVerbs(): array {
    return ['played', 'paused', 'seeked', 'answered', 'completed'];
  }
}
```

**Ejemplo content_data:**
```json
{
  "video_source": {
    "type": "youtube",
    "url": "https://youtube.com/watch?v=xyz",
    "poster": "/files/thumbnails/video1.jpg"
  },
  "checkpoints": [
    {
      "time": 120,
      "type": "question",
      "pause_video": true,
      "required": true,
      "content": {
        "question": "¿Qué técnica se acaba de explicar?",
        "type": "multiple_choice",
        "options": ["Opción A", "Opción B", "Opción C"],
        "correct": 1
      }
    }
  ]
}
```

### 6.3 Question Set

```php
/**
 * @InteractiveType(
 *   id = "question_set",
 *   label = @Translation("Set de Preguntas"),
 *   description = @Translation("Evaluación con múltiples tipos de preguntas"),
 *   icon = "help-circle",
 *   category = "assessment",
 *   weight = -9,
 * )
 */
class QuestionSet extends InteractiveTypeBase {
  
  // Tipos de pregunta soportados
  const QUESTION_TYPES = [
    'multiple_choice',   // Una respuesta correcta
    'multiple_response', // Múltiples correctas
    'true_false',        // Verdadero/Falso
    'fill_in_blank',     // Rellenar huecos
    'matching',          // Emparejar
    'ordering',          // Ordenar
    'short_answer',      // Respuesta corta
  ];
  
  public function calculateScore(array $responses): float {
    $totalPoints = 0;
    $earnedPoints = 0;
    
    foreach ($this->questions as $question) {
      $totalPoints += $question['points'];
      if ($this->isCorrect($question, $responses[$question['id']] ?? null)) {
        $earnedPoints += $question['points'];
      }
    }
    
    return $totalPoints > 0 ? ($earnedPoints / $totalPoints) * 100 : 0;
  }
}
```

### 6.4 Branching Scenario

```php
/**
 * @InteractiveType(
 *   id = "branching_scenario",
 *   label = @Translation("Escenario Ramificado"),
 *   description = @Translation("Simulación interactiva con decisiones"),
 *   icon = "git-branch",
 *   category = "simulation",
 *   weight = -7,
 * )
 */
class BranchingScenario extends InteractiveTypeBase {
  
  public function getSchema(): array {
    return [
      'start_node' => 'string',
      'nodes' => [
        'type' => 'object',
        'additionalProperties' => [
          'id' => 'string',
          'type' => ['enum' => ['content', 'question', 'ending']],
          'content' => 'object',
          'choices' => [
            'type' => 'array',
            'items' => [
              'text' => 'string',
              'next_node' => 'string',
              'feedback' => 'string',
              'score_impact' => 'integer',
            ],
          ],
        ],
      ],
      'endings' => [
        'type' => 'array',
        'items' => [
          'id' => 'string',
          'title' => 'string',
          'description' => 'string',
          'score_range' => 'object',
        ],
      ],
    ];
  }
}
```

---

## 7. Player JavaScript World-Class

### 7.1 Arquitectura del Player

```javascript
// js/player/core.js

/**
 * Jaraba Interactive Player
 * Player de contenido interactivo world-class con tracking xAPI.
 */
class JarabaInteractivePlayer {
  
  constructor(container, config) {
    this.container = container;
    this.config = {
      contentId: config.contentId,
      contentType: config.contentType,
      xapiEndpoint: config.xapiEndpoint || '/api/v1/interactive/xapi',
      enrollmentId: config.enrollmentId,
      userId: config.userId,
      tenantId: config.tenantId,
      lang: config.lang || 'es',
      ...config
    };
    
    // Sub-sistemas
    this.tracker = new XapiTracker(this);
    this.stateManager = new StateManager(this);
    this.feedbackEngine = new FeedbackEngine(this);
    this.typeRenderer = null;
    
    // Estado
    this.contentData = null;
    this.responses = {};
    this.startTime = null;
    this.completed = false;
  }
  
  /**
   * Inicializa el player.
   */
  async init() {
    try {
      // Cargar contenido
      await this.loadContent();
      
      // Restaurar estado previo si existe
      await this.stateManager.restore();
      
      // Inicializar renderer específico del tipo
      this.typeRenderer = this.createTypeRenderer();
      
      // Emitir evento de inicio
      this.tracker.emit('initialized');
      this.startTime = Date.now();
      
      // Renderizar
      this.render();
      
      // Configurar event listeners
      this.bindEvents();
      
    } catch (error) {
      console.error('Error initializing player:', error);
      this.showError(error.message);
    }
  }
  
  /**
   * Carga el contenido desde la API.
   */
  async loadContent() {
    const response = await fetch(
      `/api/v1/interactive/content/${this.config.contentId}`
    );
    
    if (!response.ok) {
      throw new Error('No se pudo cargar el contenido');
    }
    
    this.contentData = await response.json();
  }
  
  /**
   * Maneja interacciones del usuario.
   */
  onInteraction(type, data) {
    // Guardar respuesta
    if (data.questionId) {
      this.responses[data.questionId] = data.response;
    }
    
    // Emitir xAPI
    this.tracker.emit(type, data);
    
    // Guardar estado
    this.stateManager.save();
    
    // Mostrar feedback si aplica
    if (data.showFeedback !== false) {
      this.showFeedback(data);
    }
  }
  
  /**
   * Completa el contenido.
   */
  async complete() {
    const timeSpent = Math.round((Date.now() - this.startTime) / 1000);
    const score = this.typeRenderer.calculateScore(this.responses);
    const passed = score >= (this.contentData.settings?.passing_score || 70);
    
    // Emitir completed
    await this.tracker.emit('completed', {
      score,
      passed,
      timeSpent,
      responses: this.responses
    });
    
    // Marcar como completado
    this.completed = true;
    
    // Mostrar pantalla de resultados
    this.showResults({ score, passed, timeSpent });
    
    // Disparar evento custom
    this.container.dispatchEvent(new CustomEvent('interactive:completed', {
      detail: { score, passed, timeSpent }
    }));
  }
}

// Exportar para uso global
window.JarabaInteractivePlayer = JarabaInteractivePlayer;
```

### 7.2 Feedback Engine (Micro-interacciones Premium)

```javascript
// js/player/feedback-engine.js

class FeedbackEngine {
  
  constructor(player) {
    this.player = player;
    this.container = player.container;
  }
  
  /**
   * Muestra feedback visual premium.
   */
  show(options) {
    const { type, message, points, correct } = options;
    
    // Crear elemento de feedback
    const feedback = document.createElement('div');
    feedback.className = `jaraba-feedback jaraba-feedback--${type}`;
    
    // Contenido según tipo
    switch (type) {
      case 'correct':
        this.showCorrectFeedback(feedback, message, points);
        break;
      case 'incorrect':
        this.showIncorrectFeedback(feedback, message);
        break;
      case 'info':
        this.showInfoFeedback(feedback, message);
        break;
      case 'progress':
        this.showProgressFeedback(feedback, options.progress);
        break;
    }
    
    // Animar entrada
    this.container.appendChild(feedback);
    requestAnimationFrame(() => feedback.classList.add('is-visible'));
    
    // Auto-ocultar
    setTimeout(() => this.hide(feedback), options.duration || 3000);
  }
  
  /**
   * Feedback de respuesta correcta con confetti.
   */
  showCorrectFeedback(element, message, points) {
    element.innerHTML = `
      <div class="feedback__icon">
        <svg class="checkmark">...</svg>
      </div>
      <div class="feedback__content">
        <p class="feedback__message">${message || '¡Correcto!'}</p>
        ${points ? `<span class="feedback__points">+${points} XP</span>` : ''}
      </div>
    `;
    
    // Lanzar confetti
    this.launchConfetti();
    
    // Vibración háptica (móvil)
    if (navigator.vibrate) {
      navigator.vibrate([50, 30, 50]);
    }
  }
  
  /**
   * Feedback de respuesta incorrecta con shake.
   */
  showIncorrectFeedback(element, message) {
    element.innerHTML = `
      <div class="feedback__icon feedback__icon--error">
        <svg class="x-mark">...</svg>
      </div>
      <div class="feedback__content">
        <p class="feedback__message">${message || 'Inténtalo de nuevo'}</p>
      </div>
    `;
    
    // Shake animation
    this.container.classList.add('shake');
    setTimeout(() => this.container.classList.remove('shake'), 500);
  }
  
  /**
   * Lanza confetti animado.
   */
  launchConfetti() {
    const colors = ['#4F46E5', '#10B981', '#F59E0B', '#EF4444'];
    const confettiCount = 50;
    
    for (let i = 0; i < confettiCount; i++) {
      const confetti = document.createElement('div');
      confetti.className = 'confetti';
      confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
      confetti.style.left = Math.random() * 100 + '%';
      confetti.style.animationDelay = Math.random() * 0.5 + 's';
      
      this.container.appendChild(confetti);
      
      setTimeout(() => confetti.remove(), 2000);
    }
  }
}
```

---

## 8. Motor IA Generativa

### 8.1 ContentGenerator Service

```php
namespace Drupal\jaraba_interactive\Service;

use Drupal\jaraba_ai_agents\Service\ClaudeApiService;

/**
 * Servicio de generación de contenido interactivo con IA.
 *
 * Implementa Smart Import propio usando Claude API para generar
 * contenido interactivo desde texto, URLs, videos y documentos.
 */
class ContentGenerator {
  
  protected ClaudeApiService $claude;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;
  
  public function __construct(
    ClaudeApiService $claude,
    EntityTypeManagerInterface $entityTypeManager,
    LoggerChannelInterface $logger
  ) {
    $this->claude = $claude;
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
  }
  
  /**
   * Genera contenido interactivo desde fuente.
   *
   * @param string $source
   *   Fuente: texto, URL, o path de archivo.
   * @param string $sourceType
   *   Tipo: 'text', 'url', 'youtube', 'document'.
   * @param string $targetType
   *   Tipo de contenido a generar: 'question_set', 'interactive_video', etc.
   * @param array $options
   *   Opciones adicionales: num_questions, difficulty, language.
   *
   * @return InteractiveContent
   *   Entidad de contenido generada.
   */
  public function generate(
    string $source,
    string $sourceType,
    string $targetType,
    array $options = []
  ): InteractiveContent {
    
    // 1. Extraer contenido de la fuente
    $extractedContent = $this->extractContent($source, $sourceType);
    
    // 2. Construir prompt para Claude
    $prompt = $this->buildPrompt($extractedContent, $targetType, $options);
    
    // 3. Llamar a Claude API
    $response = $this->claude->complete([
      'model' => 'claude-sonnet-4-20250514',
      'max_tokens' => 8000,
      'messages' => [
        ['role' => 'user', 'content' => $prompt]
      ],
      'response_format' => ['type' => 'json_object'],
    ]);
    
    // 4. Parsear y crear entidad
    $contentData = json_decode($response['content'][0]['text'], TRUE);
    
    // 5. Validar estructura
    $this->validateGeneratedContent($contentData, $targetType);
    
    // 6. Crear entidad
    return $this->createContentEntity($contentData, $targetType, $options);
  }
  
  /**
   * Genera preguntas desde texto.
   */
  public function generateQuestionSet(
    string $text,
    int $numQuestions = 5,
    string $difficulty = 'intermediate'
  ): array {
    
    $prompt = <<<PROMPT
Eres un experto en diseño instruccional. Genera un set de {$numQuestions} preguntas
de evaluación basadas en el siguiente contenido educativo.

CONTENIDO:
{$text}

REQUISITOS:
- Nivel de dificultad: {$difficulty}
- Tipos de pregunta variados (multiple_choice, true_false, fill_in_blank)
- Feedback educativo para cada opción
- Puntuación por pregunta (5-15 puntos)
- Idioma: Español

FORMATO JSON REQUERIDO:
{
  "title": "Título descriptivo",
  "questions": [
    {
      "id": "q1",
      "type": "multiple_choice",
      "question": "...",
      "options": [{"id": "a", "text": "...", "correct": false}, ...],
      "feedback": {"correct": "...", "incorrect": "..."},
      "points": 10
    }
  ]
}
PROMPT;

    $response = $this->claude->complete([
      'model' => 'claude-sonnet-4-20250514',
      'max_tokens' => 4000,
      'messages' => [['role' => 'user', 'content' => $prompt]],
    ]);
    
    return json_decode($response['content'][0]['text'], TRUE);
  }
  
  /**
   * Genera checkpoints para video desde transcript.
   */
  public function generateVideoCheckpoints(
    string $transcript,
    string $videoUrl,
    int $maxCheckpoints = 5
  ): array {
    
    $prompt = <<<PROMPT
Eres un experto en diseño de videos educativos interactivos. Analiza el transcript
y genera {$maxCheckpoints} checkpoints interactivos en los momentos más relevantes.

TRANSCRIPT:
{$transcript}

GENERA:
- Checkpoints en puntos clave del contenido
- Preguntas de comprensión
- Información complementaria tipo popup
- Momentos para pausar y reflexionar

FORMATO JSON:
{
  "checkpoints": [
    {
      "time": 120,
      "type": "question",
      "pause_video": true,
      "content": {
        "question": "...",
        "options": [...],
        "correct": 0
      }
    }
  ]
}
PROMPT;

    $response = $this->claude->complete([
      'model' => 'claude-sonnet-4-20250514',
      'max_tokens' => 3000,
      'messages' => [['role' => 'user', 'content' => $prompt]],
    ]);
    
    return json_decode($response['content'][0]['text'], TRUE);
  }
}
```

### 8.2 Pipeline de Importación

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   FUENTE     │────►│   EXTRACTOR  │────►│   GENERADOR  │────►│   VALIDADOR  │
│              │     │              │     │    (Claude)  │     │              │
│ - Texto      │     │ - TextExtract│     │ - Prompt     │     │ - Schema     │
│ - URL        │     │ - URLScraper │     │ - JSON Parse │     │ - Sanitize   │
│ - YouTube    │     │ - YouTubeAPI │     │              │     │              │
│ - Documento  │     │ - DocParser  │     │              │     │              │
└──────────────┘     └──────────────┘     └──────────────┘     └──────────────┘
                                                                       │
                                                                       ▼
                                                               ┌──────────────┐
                                                               │ INTERACTIVE  │
                                                               │   CONTENT    │
                                                               │   (Entity)   │
                                                               └──────────────┘
```

---

## 9. Sistema xAPI y Tracking

### 9.1 XapiEmitter Service

```php
namespace Drupal\jaraba_interactive\Service;

/**
 * Servicio de emisión de xAPI statements.
 *
 * Integra directamente con jaraba_lms.progress_tracking
 * sin necesidad de LRS externo.
 */
class XapiEmitter {
  
  protected ProgressTrackingService $progressService;
  protected TenantResolverInterface $tenantResolver;
  protected AccountProxyInterface $currentUser;
  
  /**
   * Emite un xAPI statement.
   */
  public function emit(
    string $verb,
    InteractiveContent $content,
    array $result = [],
    array $context = []
  ): void {
    
    // Construir statement xAPI completo
    $statement = [
      'id' => \Drupal::service('uuid')->generate(),
      'timestamp' => (new \DateTime())->format(\DateTime::ATOM),
      
      'actor' => $this->buildActor(),
      
      'verb' => [
        'id' => $this->getVerbIri($verb),
        'display' => ['es' => $this->getVerbLabel($verb)],
      ],
      
      'object' => [
        'id' => $content->toUrl()->setAbsolute()->toString(),
        'objectType' => 'Activity',
        'definition' => [
          'type' => 'http://adlnet.gov/expapi/activities/assessment',
          'name' => ['es' => $content->label()],
          'description' => ['es' => $content->get('description')->value ?? ''],
        ],
      ],
      
      'result' => $this->buildResult($result),
      
      'context' => [
        'registration' => $context['enrollment_id'] ?? NULL,
        'extensions' => [
          'https://jaraba.io/xapi/tenant_id' => $this->tenantResolver->getCurrentTenantId(),
          'https://jaraba.io/xapi/content_type' => $content->get('content_type')->value,
          'https://jaraba.io/xapi/course_id' => $context['course_id'] ?? NULL,
          'https://jaraba.io/xapi/lesson_id' => $context['lesson_id'] ?? NULL,
        ],
      ],
    ];
    
    // Almacenar en progress_record de jaraba_lms
    $this->progressService->recordFromXapi($statement);
    
    // Log para debugging
    $this->logger->debug('xAPI statement emitted: @verb for content @id', [
      '@verb' => $verb,
      '@id' => $content->id(),
    ]);
  }
  
  /**
   * Mapea verbos a IRIs xAPI estándar.
   */
  protected function getVerbIri(string $verb): string {
    $verbs = [
      'initialized' => 'http://adlnet.gov/expapi/verbs/initialized',
      'played' => 'https://w3id.org/xapi/video/verbs/played',
      'paused' => 'https://w3id.org/xapi/video/verbs/paused',
      'seeked' => 'https://w3id.org/xapi/video/verbs/seeked',
      'answered' => 'http://adlnet.gov/expapi/verbs/answered',
      'completed' => 'http://adlnet.gov/expapi/verbs/completed',
      'passed' => 'http://adlnet.gov/expapi/verbs/passed',
      'failed' => 'http://adlnet.gov/expapi/verbs/failed',
      'interacted' => 'http://adlnet.gov/expapi/verbs/interacted',
      'progressed' => 'http://adlnet.gov/expapi/verbs/progressed',
    ];
    
    return $verbs[$verb] ?? "https://jaraba.io/xapi/verbs/{$verb}";
  }
}
```

### 9.2 Integración con progress_record

```php
// En ProgressTrackingService de jaraba_lms

public function recordFromXapi(array $statement): void {
  
  // Extraer datos del statement
  $verb = basename($statement['verb']['id']);
  $contentId = $this->extractContentId($statement['object']['id']);
  $userId = $this->extractUserId($statement['actor']);
  $enrollmentId = $statement['context']['registration'] ?? NULL;
  
  // Mapear a progress_record
  $status = $this->mapVerbToStatus($verb);
  $score = $statement['result']['score']['scaled'] ?? NULL;
  
  // Crear o actualizar registro
  $this->recordProgress(
    enrollment_id: $enrollmentId,
    activity_id: $contentId,
    activity_type: 'interactive_content',
    status: $status,
    score: $score,
    time_spent: $statement['result']['duration'] ?? 0,
    response_data: $statement
  );
}
```

---

## 10. Integración con Ecosistema Jaraba

### 10.1 Diagrama de Integraciones

```
                        ┌─────────────────────────────────────┐
                        │        jaraba_interactive           │
                        │                                     │
                        │  InteractiveContent                 │
                        │  InteractiveResult                  │
                        │  ContentGenerator (IA)              │
                        └──────────────┬──────────────────────┘
                                       │
           ┌───────────────────────────┼───────────────────────────┐
           │                           │                           │
           ▼                           ▼                           ▼
┌─────────────────────┐    ┌─────────────────────┐    ┌─────────────────────┐
│     jaraba_lms      │    │   jaraba_training   │    │  jaraba_credentials │
│                     │    │                     │    │                     │
│ - Courses           │    │ - CertificationProg │    │ - CredentialTemplate│
│ - Lessons           │    │ - UserCertification │    │ - IssuedCredential  │
│ - Activities        │    │ - TrainingProduct   │    │ - OpenBadge 3.0     │
│ - Enrollments       │    │                     │    │                     │
│ - progress_record   │◄───┤ Completion triggers │◄───┤ Badge issuance      │
└─────────────────────┘    └─────────────────────┘    └─────────────────────┘
           │                           │                           │
           │                           │                           │
           ▼                           ▼                           ▼
┌─────────────────────┐    ┌─────────────────────┐    ┌─────────────────────┐
│   jaraba_ai_agents  │    │    jaraba_gamify    │    │    Group Module     │
│                     │    │                     │    │                     │
│ - Claude API        │    │ - XP System         │    │ - Multi-tenant      │
│ - Smart Import      │    │ - Achievements      │    │ - Content isolation │
│ - Content analysis  │    │ - Leaderboards      │    │ - Permissions       │
└─────────────────────┘    └─────────────────────┘    └─────────────────────┘
```

### 10.2 Eventos y Suscriptores

```php
// CompletionSubscriber.php

namespace Drupal\jaraba_interactive\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CompletionSubscriber implements EventSubscriberInterface {
  
  public static function getSubscribedEvents(): array {
    return [
      'jaraba_interactive.content_completed' => 'onContentCompleted',
    ];
  }
  
  public function onContentCompleted(ContentCompletedEvent $event): void {
    $content = $event->getContent();
    $result = $event->getResult();
    $user = $event->getUser();
    
    // 1. Actualizar progreso en jaraba_lms
    $this->updateLmsProgress($content, $result, $user);
    
    // 2. Verificar si dispara certificación
    if ($result->passed && $this->shouldTriggerCertification($content)) {
      $this->triggerCertification($content, $user, $result);
    }
    
    // 3. Otorgar XP si gamificación está habilitada
    if ($this->gamificationEnabled()) {
      $this->awardXp($user, 'complete_interactive', $result->score);
    }
    
    // 4. Emitir badge si corresponde
    if ($this->shouldIssueBadge($content, $result)) {
      $this->issueBadge($content, $user);
    }
  }
  
  protected function triggerCertification($content, $user, $result): void {
    // Buscar programa de certificación asociado
    $program = $this->findCertificationProgram($content);
    if (!$program) {
      return;
    }
    
    // Verificar requisitos del programa
    $orchestrator = \Drupal::service('jaraba_training.certification_orchestrator');
    if ($orchestrator->checkProgramRequirements($user->id(), $program->id())) {
      $orchestrator->certifyUser($user->id(), $program->id(), $result->score);
    }
  }
}
```

### 10.3 Servicios Inyectados

```yaml
# jaraba_interactive.services.yml

services:
  jaraba_interactive.content_renderer:
    class: Drupal\jaraba_interactive\Service\ContentRenderer
    arguments:
      - '@plugin.manager.interactive_type'
      - '@entity_type.manager'
      
  jaraba_interactive.xapi_emitter:
    class: Drupal\jaraba_interactive\Service\XapiEmitter
    arguments:
      - '@jaraba_lms.progress_tracking'      # Integración LMS
      - '@jaraba_page_builder.tenant_resolver'
      - '@current_user'
      - '@logger.channel.jaraba_interactive'
      
  jaraba_interactive.content_generator:
    class: Drupal\jaraba_interactive\Service\ContentGenerator
    arguments:
      - '@jaraba_ai_agents.claude_api'       # Integración IA
      - '@entity_type.manager'
      - '@logger.channel.jaraba_interactive'
      
  jaraba_interactive.completion_service:
    class: Drupal\jaraba_interactive\Service\CompletionService
    arguments:
      - '@entity_type.manager'
      - '@jaraba_training.certification_orchestrator'  # Integración Training
      - '@jaraba_credentials.issuer'                   # Integración Credentials
      - '@event_dispatcher'
```

---

## 11. API REST y Endpoints

### 11.1 Resumen de Endpoints

| Método | Endpoint | Descripción | Permisos |
|--------|----------|-------------|----------|
| `GET` | `/api/v1/interactive/content/{id}` | Obtener contenido | view interactive content |
| `POST` | `/api/v1/interactive/content` | Crear contenido | create interactive content |
| `PATCH` | `/api/v1/interactive/content/{id}` | Actualizar | edit interactive content |
| `DELETE` | `/api/v1/interactive/content/{id}` | Eliminar | delete interactive content |
| `GET` | `/api/v1/interactive/types` | Listar tipos disponibles | view interactive content |
| `POST` | `/api/v1/interactive/xapi` | Recibir xAPI statements | authenticated |
| `POST` | `/api/v1/interactive/generate` | Generar con IA | use ai generator |
| `GET` | `/api/v1/interactive/results/{content_id}` | Resultados de estudiantes | view results |

### 11.2 Ejemplo de Respuesta API

```json
// GET /api/v1/interactive/content/123
{
  "id": 123,
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "title": "Evaluación de Competencias Digitales",
  "content_type": "question_set",
  "status": "published",
  "difficulty": "intermediate",
  "duration_minutes": 15,
  "settings": {
    "passing_score": 70,
    "max_attempts": 3,
    "randomize_questions": true,
    "show_feedback": "immediate"
  },
  "content_data": {
    "questions": [/* ... */]
  },
  "metadata": {
    "created": "2026-02-04T10:00:00Z",
    "changed": "2026-02-04T14:00:00Z",
    "author": {
      "id": 1,
      "name": "Admin"
    },
    "tenant": {
      "id": 5,
      "name": "Andalucía +ei"
    }
  },
  "_links": {
    "self": "/api/v1/interactive/content/123",
    "play": "/interactive/play/123",
    "edit": "/admin/interactive/123/edit"
  }
}
```

---

## 12. Frontend y UX Premium

### 12.1 Principios de Diseño

1. **Mobile-First**: Diseño responsive desde 320px
2. **Progresivo**: Degradación elegante sin JavaScript
3. **Accesible**: WCAG 2.1 AA compliant
4. **Inmersivo**: Micro-interacciones que mantienen engagement
5. **Rápido**: Lazy loading, code splitting, < 3s LCP

### 12.2 Componentes SCSS

```scss
// scss/_player.scss

.jaraba-player {
  // Variables del tema (inyectables por tenant)
  --player-primary: var(--jaraba-primary, #4F46E5);
  --player-success: var(--jaraba-success, #10B981);
  --player-error: var(--jaraba-error, #EF4444);
  --player-bg: var(--jaraba-surface, #1F2937);
  --player-text: var(--jaraba-text, #F9FAFB);
  --player-radius: var(--jaraba-radius, 12px);
  
  position: relative;
  background: var(--player-bg);
  border-radius: var(--player-radius);
  overflow: hidden;
  
  // Modo fullscreen
  &.is-fullscreen {
    position: fixed;
    inset: 0;
    z-index: 9999;
    border-radius: 0;
  }
  
  // Header del player
  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--spacing-md);
    background: linear-gradient(180deg, rgba(0,0,0,0.5) 0%, transparent 100%);
  }
  
  // Zona de contenido
  &__content {
    padding: var(--spacing-lg);
    min-height: 400px;
  }
  
  // Footer con progreso
  &__footer {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-md);
    background: rgba(0,0,0,0.3);
  }
}

// Animaciones premium
@keyframes confetti-fall {
  0% {
    transform: translateY(-100vh) rotate(0deg);
    opacity: 1;
  }
  100% {
    transform: translateY(100vh) rotate(720deg);
    opacity: 0;
  }
}

.confetti {
  position: absolute;
  width: 10px;
  height: 10px;
  top: 0;
  animation: confetti-fall 2s ease-out forwards;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-5px); }
  75% { transform: translateX(5px); }
}

.shake {
  animation: shake 0.5s ease-in-out;
}
```

### 12.3 Templates Twig

```twig
{# templates/interactive-player.html.twig #}

<div 
  id="jaraba-player-{{ content.id }}"
  class="jaraba-player jaraba-player--{{ content.content_type }}"
  data-content-id="{{ content.id }}"
  data-content-type="{{ content.content_type }}"
  data-xapi-endpoint="{{ xapi_endpoint }}"
  data-enrollment-id="{{ enrollment_id }}"
>
  {# Header #}
  <header class="jaraba-player__header">
    <h2 class="jaraba-player__title">{{ content.title }}</h2>
    <div class="jaraba-player__controls">
      <button class="btn-icon" data-action="fullscreen" aria-label="{{ 'Pantalla completa'|t }}">
        {% include '@jaraba_icons/maximize.svg.twig' %}
      </button>
      <button class="btn-icon" data-action="close" aria-label="{{ 'Cerrar'|t }}">
        {% include '@jaraba_icons/x.svg.twig' %}
      </button>
    </div>
  </header>
  
  {# Contenido dinámico (renderizado por JS) #}
  <main class="jaraba-player__content" role="main">
    <div class="jaraba-player__loader">
      <div class="spinner"></div>
      <p>{{ 'Cargando contenido...'|t }}</p>
    </div>
  </main>
  
  {# Footer con progreso #}
  <footer class="jaraba-player__footer">
    <div class="jaraba-player__progress">
      <div class="progress-bar">
        <div class="progress-bar__fill" style="width: 0%"></div>
      </div>
      <span class="progress-text">0%</span>
    </div>
    <div class="jaraba-player__score" hidden>
      <span class="score-label">{{ 'Puntuación:'|t }}</span>
      <span class="score-value">0</span>
    </div>
  </footer>
</div>

{{ attach_library('jaraba_interactive/player') }}
```

---

## 13. Multitenancy y Branding

### 13.1 Aislamiento de Contenido

```php
// InteractiveContentAccessControlHandler.php

class InteractiveContentAccessControlHandler extends EntityAccessControlHandler {
  
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    
    // Verificar pertenencia al tenant
    $tenantId = $entity->get('tenant_id')->target_id;
    $userTenants = $this->tenantResolver->getUserTenants($account);
    
    if (!in_array($tenantId, $userTenants)) {
      return AccessResult::forbidden('El contenido no pertenece a su organización.');
    }
    
    // Permisos normales
    return parent::checkAccess($entity, $operation, $account);
  }
}
```

### 13.2 Inyección de Branding

```php
// PlayerController.php

public function render(InteractiveContent $content) {
  
  // Obtener configuración del tenant
  $tenantConfig = $this->tenantResolver->getCurrentTenantConfig();
  
  // Generar CSS custom del tenant
  $customCss = $this->generateTenantCss($tenantConfig);
  
  return [
    '#theme' => 'interactive_player',
    '#content' => $content,
    '#attached' => [
      'library' => ['jaraba_interactive/player'],
      'html_head' => [
        [
          [
            '#type' => 'html_tag',
            '#tag' => 'style',
            '#value' => $customCss,
          ],
          'tenant-player-styles',
        ],
      ],
      'drupalSettings' => [
        'jarabaInteractive' => [
          'tenantId' => $tenantConfig['id'],
          'branding' => [
            'primaryColor' => $tenantConfig['primary_color'],
            'logo' => $tenantConfig['logo_url'],
          ],
        ],
      ],
    ],
  ];
}

protected function generateTenantCss(array $config): string {
  return <<<CSS
  .jaraba-player {
    --player-primary: {$config['primary_color']};
    --player-secondary: {$config['secondary_color']};
    --player-font: {$config['font_family']};
  }
  CSS;
}
```

---

## 14. Directivas del Proyecto Aplicables

### 14.1 Directivas Nucleares

| # | Directiva | Aplicación en jaraba_interactive |
|---|-----------|----------------------------------|
| **1** | Frontend Page Pattern | Player usa `page--interactive-player.html.twig` sin regiones Drupal |
| **2** | SCSS Token System | Todas las variables heredan de `ecosistema_jaraba_theme` |
| **3** | i18n via Twig/PHP | Strings UI traducibles: `{{ 'Siguiente'|t }}` |
| **4** | Multi-tenant Isolation | `tenant_id` en entidad + AccessControlHandler |
| **5** | Service-Layer Architecture | Lógica en Services, Controllers delegadores |
| **14** | SaaS Purification | Sin código hardcodeado, todo configurable |
| **20** | Zero-Code Configurability | Settings via `config/install/*.yml` |

### 14.2 Patrones de Implementación Obligatorios

#### 14.2.1 Operational Tower Pattern
El dashboard de administración de contenido interactivo sigue el patrón Operational Tower:
- Header premium con gradiente y partículas
- Iconos blancos para contraste
- Cards con efectos glassmorphism
- Stats en tiempo real

#### 14.2.2 Slide Panel Pattern
Editor de contenido usa slide panels para:
- Configuración de preguntas
- Settings de checkpoints
- Ajustes de scoring

#### 14.2.3 Premium Card Pattern
Listados de contenido usan Premium Cards con:
- Thumbnail con overlay hover
- Metadata visible (tipo, duración, dificultad)
- Acciones contextuales

### 14.3 Estándares de Código

```php
// ✅ CORRECTO: Documentación en español, tipos estrictos
/**
 * Calcula la puntuación del usuario.
 *
 * @param array $responses
 *   Respuestas del usuario indexadas por question_id.
 *
 * @return float
 *   Puntuación de 0 a 100.
 */
public function calculateScore(array $responses): float {
  // Implementación...
}

// ❌ INCORRECTO: Sin documentación, sin tipos
public function calculateScore($responses) {
  // ...
}
```

---

## 15. Especificaciones Técnicas de Referencia

### 15.1 Requisitos del Sistema

| Componente | Mínimo | Recomendado |
|------------|--------|-------------|
| PHP | 8.2 | 8.3 |
| Drupal | 11.0 | 11.1 |
| Node.js | 18.x | 20.x |
| PostgreSQL | 15 | 16 |
| Redis | 7.x | 7.x |
| Navegadores | Chrome 90+, Firefox 88+, Safari 14+ | Últimas versiones |

### 15.2 Dependencias de Drupal

```yaml
# jaraba_interactive.info.yml

name: 'Jaraba Interactive AI-Powered'
type: module
description: 'Sistema de contenido interactivo world-class con IA generativa.'
core_version_requirement: ^11
php: 8.2

dependencies:
  - drupal:user
  - drupal:options
  - drupal:file
  - jaraba_page_builder:jaraba_page_builder   # Tenant resolver
  - jaraba_lms:jaraba_lms                     # Progress tracking
  - jaraba_ai_agents:jaraba_ai_agents         # Claude API
  - group:group                               # Multi-tenant

configure: admin/config/jaraba/interactive
```

### 15.3 Librerías JavaScript

| Librería | Versión | Propósito | CDN/Bundle |
|----------|---------|-----------|------------|
| Core Player | Propio | Motor principal | Bundle |
| Video.js | 8.x | Reproductor de video | CDN |
| Sortable.js | 1.15 | Drag & drop | Bundle |
| Canvas Confetti | 1.9 | Animaciones celebración | Bundle |
| Tippy.js | 6.x | Tooltips | Bundle |

### 15.4 Esquema de Base de Datos

```sql
-- interactive_content
CREATE TABLE interactive_content (
  id SERIAL PRIMARY KEY,
  uuid UUID NOT NULL UNIQUE,
  tenant_id INTEGER REFERENCES groups_field_data(id),
  uid INTEGER REFERENCES users_field_data(uid),
  title VARCHAR(255) NOT NULL,
  content_type VARCHAR(64) NOT NULL,
  content_data JSONB NOT NULL,
  settings JSONB,
  status VARCHAR(32) DEFAULT 'draft',
  difficulty VARCHAR(32),
  duration_minutes INTEGER,
  created TIMESTAMP DEFAULT NOW(),
  changed TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_interactive_tenant ON interactive_content(tenant_id);
CREATE INDEX idx_interactive_type ON interactive_content(content_type);
CREATE INDEX idx_interactive_status ON interactive_content(status);

-- interactive_result  
CREATE TABLE interactive_result (
  id SERIAL PRIMARY KEY,
  uuid UUID NOT NULL UNIQUE,
  user_id INTEGER REFERENCES users_field_data(uid),
  content_id INTEGER REFERENCES interactive_content(id) ON DELETE CASCADE,
  enrollment_id INTEGER,
  response_data JSONB,
  score DECIMAL(5,2),
  max_score DECIMAL(5,2),
  passed BOOLEAN,
  attempts INTEGER DEFAULT 1,
  time_spent INTEGER,
  completed BOOLEAN DEFAULT FALSE,
  completed_at TIMESTAMP,
  created TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_result_user ON interactive_result(user_id);
CREATE INDEX idx_result_content ON interactive_result(content_id);
CREATE INDEX idx_result_enrollment ON interactive_result(enrollment_id);
```

---

## 16. Roadmap de Implementación

### 16.1 Timeline Detallado

```
          Feb 2026         Mar 2026         Abr 2026         May 2026         Jun 2026
     ─────────────────────────────────────────────────────────────────────────────────►
     
     S1 ████████          S2 ████████          S3 ████████
     Entidades +          Player JS +          InteractiveVideo +
     Plugin System        xAPI Tracker         Editor Visual
     QuestionSet          Feedback Engine      
     
                                               S4 ████████          S5 ████████
                                               CoursePresent +      ContentGenerator +
                                               Branching           DragDrop + Essay
     
                                                                    S6 ████████
                                                                    Multi-tenant +
                                                                    Polish + QA
```

### 16.2 Sprints Detallados

| Sprint | Semanas | Entregables | Criterios de Aceptación | Horas |
|--------|---------|-------------|------------------------|-------|
| **1** | 1-3 | Entidades, Plugin system, QuestionSet básico | CRUD funcional, tests unitarios | 80h |
| **2** | 4-6 | Player JS core, xAPI tracker, Feedback engine | Player renderiza QuestionSet, xAPI emite | 100h |
| **3** | 7-9 | InteractiveVideo, Editor visual básico | Video con checkpoints, editor funcional | 100h |
| **4** | 10-12 | CoursePresentation, BranchingScenario | Slides navegables, branching completo | 80h |
| **5** | 13-15 | ContentGenerator IA, DragAndDrop, Essay | Generación desde texto, tipos adicionales | 80h |
| **6** | 16-18 | Multi-tenant, Branding, Premium polish, QA | Tests E2E, documentación completa | 60h |
| **Total** | **18** | **Sistema completo world-class** | | **~500h** |

### 16.3 Milestones

| Milestone | Fecha | Entregable |
|-----------|-------|------------|
| **M1: MVP** | Semana 6 | QuestionSet funcional con xAPI |
| **M2: Video** | Semana 9 | InteractiveVideo completo |
| **M3: IA** | Semana 15 | Generación automática operativa |
| **M4: Release** | Semana 18 | Sistema completo en producción |

---

## 17. Análisis de Riesgos

### 17.1 Matriz de Riesgos

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|--------------|---------|------------|
| **Complejidad del editor visual** | Alta | Alto | Usar GrapesJS como base, no reinventar |
| **Integración Claude API** | Media | Medio | Fallback a generación manual |
| **Performance del player** | Media | Alto | Lazy loading, code splitting, CDN |
| **Compatibilidad navegadores** | Baja | Medio | Polyfills, feature detection |
| **Scope creep** | Alta | Alto | Backlog priorizado, MVP primero |
| **Dependencias de módulos** | Media | Medio | Tests de integración, mocking |

### 17.2 Plan de Contingencia

| Escenario | Acción |
|-----------|--------|
| Claude API no disponible | Usar OpenAI GPT-4 como backup |
| Retraso en sprints | Reducir scope de Content Types (4 en lugar de 6) |
| Issues de performance | Implementar SSR para player |
| Problemas multi-tenant | Consultar con equipo Group module |

---

## 18. Métricas de Éxito

### 18.1 KPIs Técnicos

| Métrica | Baseline | Target 3 meses | Target 6 meses |
|---------|----------|----------------|----------------|
| Tiempo carga player | N/A | < 2s | < 1.5s |
| Cobertura tests | 0% | 60% | 80% |
| Score Lighthouse | N/A | > 80 | > 90 |
| Uptime | N/A | 99.5% | 99.9% |

### 18.2 KPIs de Producto

| Métrica | Baseline | Target 3 meses | Target 6 meses |
|---------|----------|----------------|----------------|
| Contenidos creados/mes | 0 | 50 | 200 |
| Contenidos IA generados | 0 | 20% | 40% |
| Tiempo creación contenido | N/A | 30min | 15min |
| Completion rate usuarios | N/A | 60% | 75% |
| NPS funcionalidad | N/A | +30 | +50 |

### 18.3 KPIs de Negocio

| Métrica | Impacto |
|---------|---------|
| Reducción tiempo formación | -40% vs método tradicional |
| Mejora en retención contenido | +25% vs texto plano |
| Ahorro en herramientas externas | €2,000/año por tenant |
| Diferenciación competitiva | Feature única en mercado |

---

## 19. Referencias

### 19.1 Documentación Interna

| Documento | Ubicación |
|-----------|-----------|
| Estándares de Desarrollo | `docs/standards/desarrollo.md` |
| Arquitectura General | KI `jaraba_saas_platform_architecture_roadmap` |
| Sistema LMS | KI `jaraba_lms_system` |
| Sistema Training | KI `jaraba_training_system` |
| Sistema Credentials | KI `jaraba_saas_credentials_system` |
| Agentes IA | KI `jaraba_ai_agents_architecture` |

### 19.2 Referencias Externas

| Recurso | URL |
|---------|-----|
| xAPI Specification | https://github.com/adlnet/xAPI-Spec |
| xAPI Video Profile | https://lxhub.gitbook.io/xapi-video-profile |
| Drupal Plugin API | https://www.drupal.org/docs/drupal-apis/plugin-api |
| GrapesJS (referencia editor) | https://grapesjs.com |
| Video.js | https://videojs.com |

### 19.3 Competidores Analizados

| Plataforma | Análisis |
|------------|----------|
| H5P | https://h5p.org/documentation |
| Articulate Rise | https://articulate.com/360/rise |
| Coursera | https://www.coursera.org |
| LinkedIn Learning | https://learning.linkedin.com |

---

> **Documento creado**: 2026-02-04  
> **Autor**: Jaraba Technical Team + Claude AI  
> **Versión**: 1.0  
> **Estado**: Especificación Aprobada  
> **Próxima revisión**: Post-Sprint 3
