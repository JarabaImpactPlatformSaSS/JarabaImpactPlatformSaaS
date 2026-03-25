<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ecosistema_jaraba_core\Entity\QuizResult;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Quiz de Recomendación de Vertical — Conversación adaptativa con IA.
 *
 * El quiz tiene 3 preguntas: Q1 siempre igual, Q2 y Q3 se adaptan según
 * la respuesta de Q1 (perfil del usuario). Cada paso incluye una reacción
 * contextual del "asesor IA" que personaliza la experiencia.
 *
 * Flujo:
 * 1. Q1: ¿Cuál es tu perfil? (6 opciones) → determina el camino
 * 2. Reacción IA + Q2 adaptativa (según perfil)
 * 3. Reacción IA + Q3 adaptativa (según perfil + Q2)
 * 4. Submit → scoring → resultado con IA explanation
 */
class VerticalQuizService {

  use StringTranslationTrait;

  /**
   * Prioridad por ARPU para desempate.
   */
  private const ARPU_PRIORITY = [
    'jarabalex' => 9,
    'comercioconecta' => 8,
    'emprendimiento' => 7,
    'serviciosconecta' => 6,
    'agroconecta' => 5,
    'formacion' => 4,
    'andalucia_ei' => 3,
    'jaraba_content_hub' => 2,
    'empleabilidad' => 1,
  ];

  /**
   * Mapeo urgencia → BANT timeline.
   */
  private const URGENCY_TO_BANT = [
    'ahora' => 'immediate',
    'semanas' => '3mo',
    'explorando' => '6mo',
  ];

  /**
   * Mapeo necesidad → BANT need (genérico, refinado por perfil).
   */
  private const NEED_TO_BANT = [
    'encontrar_empleo' => 'urgent',
    'mejorar_cv' => 'identified',
    'formacion_continua' => 'identified',
    'cambio_carrera' => 'urgent',
    'validar_idea' => 'identified',
    'lanzar_negocio' => 'urgent',
    'escalar' => 'critical',
    'financiacion' => 'urgent',
    'ventas_online' => 'urgent',
    'gestion_clientes' => 'identified',
    'automatizar' => 'identified',
    'presencia_digital' => 'identified',
    'busqueda_legal' => 'urgent',
    'gestion_expedientes' => 'identified',
    'copiloto_juridico' => 'identified',
    'todo_legal' => 'critical',
    'gestion_programas' => 'critical',
    'seguimiento_fse' => 'urgent',
    'formacion_participantes' => 'identified',
    'crear_contenido' => 'identified',
    'distribuir' => 'identified',
    'monetizar' => 'urgent',
  ];

  /**
   * Datos de presentación por vertical.
   */
  private const VERTICAL_DATA = [
    'empleabilidad' => [
      'title' => 'Empleabilidad',
      'path' => 'empleabilidad',
      'icon_cat' => 'verticals',
      'icon_name' => 'empleabilidad',
      'color' => 'verde-innovacion',
      'demo_profile' => 'jobseeker',
      'benefits' => [
        'CV inteligente con IA que se adapta a cada oferta',
        'Matching automático con ofertas de empleo',
        'Preparación de entrevistas con simulador IA',
      ],
      'price_from' => '0€',
    ],
    'emprendimiento' => [
      'title' => 'Emprendimiento',
      'path' => 'emprendimiento',
      'icon_cat' => 'verticals',
      'icon_name' => 'emprendimiento',
      'color' => 'naranja-impulso',
      'demo_profile' => 'startup',
      'benefits' => [
        'Lean Canvas y Business Model Canvas con IA',
        'Diagnóstico de viabilidad con métricas reales',
        'Copiloto IA especializado en startups',
      ],
      'price_from' => '0€',
    ],
    'comercioconecta' => [
      'title' => 'ComercioConecta',
      'path' => 'comercioconecta',
      'icon_cat' => 'verticals',
      'icon_name' => 'comercioconecta',
      'color' => 'naranja-impulso',
      'demo_profile' => 'gourmet',
      'benefits' => [
        'Tienda online profesional en minutos',
        'Gestión de pedidos, stock e inventario',
        'Pagos integrados con Stripe y Bizum',
      ],
      'price_from' => '0€',
    ],
    'agroconecta' => [
      'title' => 'AgroConecta',
      'path' => 'agroconecta',
      'icon_cat' => 'verticals',
      'icon_name' => 'agroconecta',
      'color' => 'verde-oliva',
      'demo_profile' => 'winery',
      'benefits' => [
        'Marketplace agroalimentario con trazabilidad',
        'Gestión de lotes y certificaciones',
        'Conexión directa con compradores',
      ],
      'price_from' => '0€',
    ],
    'jarabalex' => [
      'title' => 'JarabaLex',
      'path' => 'jarabalex',
      'icon_cat' => 'verticals',
      'icon_name' => 'jarabalex',
      'color' => 'azul-corporativo',
      'demo_profile' => 'lawfirm',
      'benefits' => [
        'Buscador legal inteligente con IA grounding',
        'Gestión de expedientes y clientes',
        'Copiloto jurídico especializado',
      ],
      'price_from' => '29€',
    ],
    'serviciosconecta' => [
      'title' => 'ServiciosConecta',
      'path' => 'serviciosconecta',
      'icon_cat' => 'verticals',
      'icon_name' => 'serviciosconecta',
      'color' => 'verde-innovacion',
      'demo_profile' => 'servicepro',
      'benefits' => [
        'Gestión de clientes y proyectos',
        'Presupuestos y facturación automática',
        'Calendario de disponibilidad compartido',
      ],
      'price_from' => '0€',
    ],
    'formacion' => [
      'title' => 'Formación',
      'path' => 'formacion',
      'icon_cat' => 'verticals',
      'icon_name' => 'formacion',
      'color' => 'verde-innovacion',
      'demo_profile' => 'academy',
      'benefits' => [
        'Cursos online con certificación',
        'Contenido adaptativo con IA',
        'Seguimiento de progreso detallado',
      ],
      'price_from' => '0€',
    ],
    'andalucia_ei' => [
      'title' => 'Andalucía +ei',
      'path' => 'andalucia-ei',
      'icon_cat' => 'verticals',
      'icon_name' => 'andalucia-ei',
      'color' => 'verde-innovacion',
      'demo_profile' => 'socialimpact',
      'benefits' => [
        'Gestión integral de programas de empleo FSE+',
        'Seguimiento de participantes y sesiones',
        'Informes automatizados para justificación',
      ],
      'price_from' => '149€',
    ],
    'jaraba_content_hub' => [
      'title' => 'Content Hub',
      'path' => 'content-hub',
      'icon_cat' => 'content',
      'icon_name' => 'edit',
      'color' => 'azul-corporativo',
      'demo_profile' => 'creator',
      'benefits' => [
        'Editor de contenido profesional con IA',
        'Publicación multi-canal automatizada',
        'SEO y analíticas integradas',
      ],
      'price_from' => '0€',
    ],
  ];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected RequestStack $requestStack,
    protected ?object $contactService,
    protected ?object $opportunityService,
    protected ?object $modelRouter,
    protected ?object $aiProvider,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtener la estructura completa del quiz adaptativo.
   *
   * Devuelve Q1 (estática) + mapa de Q2/Q3 por perfil + reacciones IA.
   */
  public function getQuizStructure(): array {
    return [
      // Q1: Siempre igual — determina el camino.
      'q1' => [
        'field' => 'perfil',
        'title' => (string) $this->t('¿Cuál es tu perfil?'),
        'subtitle' => (string) $this->t('Esto nos ayuda a personalizar toda la experiencia.'),
        'options' => [
          ['value' => 'persona', 'label' => (string) $this->t('Busco empleo o quiero crecer profesionalmente'), 'icon_cat' => 'verticals', 'icon_name' => 'empleabilidad', 'color' => 'verde-innovacion'],
          ['value' => 'emprendedor', 'label' => (string) $this->t('Tengo una idea de negocio o soy emprendedor'), 'icon_cat' => 'verticals', 'icon_name' => 'emprendimiento', 'color' => 'naranja-impulso'],
          ['value' => 'empresa', 'label' => (string) $this->t('Tengo un negocio o empresa en marcha'), 'icon_cat' => 'verticals', 'icon_name' => 'comercioconecta', 'color' => 'naranja-impulso'],
          ['value' => 'legal', 'label' => (string) $this->t('Soy profesional del derecho'), 'icon_cat' => 'verticals', 'icon_name' => 'jarabalex', 'color' => 'azul-corporativo'],
          ['value' => 'institucion', 'label' => (string) $this->t('Trabajo en una institución pública'), 'icon_cat' => 'verticals', 'icon_name' => 'andalucia-ei', 'color' => 'verde-innovacion'],
          ['value' => 'creador', 'label' => (string) $this->t('Creo contenido o tengo un medio digital'), 'icon_cat' => 'content', 'icon_name' => 'edit', 'color' => 'azul-corporativo'],
        ],
      ],

      // Reacciones IA por perfil (micro-copy del "asesor").
      'reactions' => [
        'persona' => [
          'after_q1' => (string) $this->t('Perfecto. Conozco bien el mercado laboral actual — la IA está cambiando las reglas del juego para los candidatos. Déjame hacerte un par de preguntas más.'),
          'after_q2' => (string) $this->t('Entendido. Con esa información ya tengo una imagen clara de lo que necesitas.'),
        ],
        'emprendedor' => [
          'after_q1' => (string) $this->t('Genial, me encanta trabajar con emprendedores. Cada proyecto es único y quiero entender bien el tuyo.'),
          'after_q2' => (string) $this->t('Muy bien. Según tu fase, hay herramientas específicas que pueden marcar la diferencia.'),
        ],
        'empresa' => [
          'after_q1' => (string) $this->t('Entendido. Trabajamos con más de 500 empresas y cada una tiene necesidades distintas. Vamos a encontrar la tuya.'),
          'after_q2' => (string) $this->t('Perfecto. Con esto ya puedo hacer una recomendación bastante precisa.'),
        ],
        'legal' => [
          'after_q1' => (string) $this->t('Excelente. JarabaLex ya es la herramienta de referencia para +150 despachos en España. Déjame afinar la recomendación.'),
          'after_q2' => (string) $this->t('Claro. El tamaño del despacho cambia completamente las necesidades. Última pregunta.'),
        ],
        'institucion' => [
          'after_q1' => (string) $this->t('Perfecto. Tenemos amplia experiencia con programas FSE+ y Andalucía Emprende. Voy a personalizar la recomendación.'),
          'after_q2' => (string) $this->t('Entendido. Según el tipo de programa, hay funcionalidades clave que no puedes pasar por alto.'),
        ],
        'creador' => [
          'after_q1' => (string) $this->t('¡Genial! El contenido con IA está revolucionando la creación digital. Quiero entender mejor tu proyecto.'),
          'after_q2' => (string) $this->t('Perfecto. Ya casi tengo tu recomendación personalizada.'),
        ],
      ],

      // Q2 adaptativa por perfil.
      'q2' => [
        'persona' => [
          'field' => 'situacion',
          'title' => (string) $this->t('¿En qué momento profesional estás?'),
          'subtitle' => (string) $this->t('Cada etapa requiere herramientas diferentes.'),
          'options' => [
            ['value' => 'busco_empleo', 'label' => (string) $this->t('Buscando empleo activamente'), 'icon_cat' => 'verticals', 'icon_name' => 'empleabilidad', 'color' => 'verde-innovacion'],
            ['value' => 'mejorar_perfil', 'label' => (string) $this->t('Quiero mejorar mi CV y perfil'), 'icon_cat' => 'users', 'icon_name' => 'user-verified', 'color' => 'azul-corporativo'],
            ['value' => 'formarme', 'label' => (string) $this->t('Necesito formarme o certificarme'), 'icon_cat' => 'verticals', 'icon_name' => 'formacion', 'color' => 'verde-innovacion'],
            ['value' => 'cambio_carrera', 'label' => (string) $this->t('Quiero cambiar de carrera o sector'), 'icon_cat' => 'verticals', 'icon_name' => 'emprendimiento', 'color' => 'naranja-impulso'],
          ],
          'scoring' => [
            'busco_empleo' => ['empleabilidad' => 5, 'formacion' => 2],
            'mejorar_perfil' => ['empleabilidad' => 4, 'formacion' => 2, 'jaraba_content_hub' => 1],
            'formarme' => ['formacion' => 5, 'empleabilidad' => 2],
            'cambio_carrera' => ['empleabilidad' => 3, 'formacion' => 3, 'emprendimiento' => 2],
          ],
        ],
        'emprendedor' => [
          'field' => 'fase',
          'title' => (string) $this->t('¿En qué fase está tu proyecto?'),
          'subtitle' => (string) $this->t('Las herramientas cambian según la madurez del negocio.'),
          'options' => [
            ['value' => 'idea', 'label' => (string) $this->t('Tengo una idea pero no la he validado'), 'icon_cat' => 'ai', 'icon_name' => 'brain', 'color' => 'naranja-impulso'],
            ['value' => 'validando', 'label' => (string) $this->t('Estoy validando con primeros clientes'), 'icon_cat' => 'verticals', 'icon_name' => 'emprendimiento', 'color' => 'naranja-impulso'],
            ['value' => 'lanzado', 'label' => (string) $this->t('Ya tengo ventas y quiero escalar'), 'icon_cat' => 'verticals', 'icon_name' => 'comercioconecta', 'color' => 'naranja-impulso'],
            ['value' => 'digital', 'label' => (string) $this->t('Quiero un negocio 100% digital'), 'icon_cat' => 'content', 'icon_name' => 'edit', 'color' => 'azul-corporativo'],
          ],
          'scoring' => [
            'idea' => ['emprendimiento' => 5],
            'validando' => ['emprendimiento' => 4, 'comercioconecta' => 2],
            'lanzado' => ['comercioconecta' => 4, 'emprendimiento' => 2, 'serviciosconecta' => 1],
            'digital' => ['jaraba_content_hub' => 3, 'comercioconecta' => 3],
          ],
        ],
        'empresa' => [
          'field' => 'sector',
          'title' => (string) $this->t('¿En qué sector opera tu negocio?'),
          'subtitle' => (string) $this->t('Tenemos soluciones especializadas por industria.'),
          'options' => [
            ['value' => 'agro', 'label' => (string) $this->t('Agroalimentario o campo'), 'icon_cat' => 'verticals', 'icon_name' => 'agroconecta', 'color' => 'verde-oliva'],
            ['value' => 'comercio', 'label' => (string) $this->t('Comercio o retail'), 'icon_cat' => 'verticals', 'icon_name' => 'comercioconecta', 'color' => 'naranja-impulso'],
            ['value' => 'servicios', 'label' => (string) $this->t('Servicios profesionales'), 'icon_cat' => 'verticals', 'icon_name' => 'serviciosconecta', 'color' => 'verde-innovacion'],
            ['value' => 'otro_sector', 'label' => (string) $this->t('Otro sector'), 'icon_cat' => 'verticals', 'icon_name' => 'emprendimiento', 'color' => 'naranja-impulso'],
          ],
          'scoring' => [
            'agro' => ['agroconecta' => 5],
            'comercio' => ['comercioconecta' => 5],
            'servicios' => ['serviciosconecta' => 5],
            'otro_sector' => ['emprendimiento' => 3, 'comercioconecta' => 2],
          ],
        ],
        'legal' => [
          'field' => 'despacho',
          'title' => (string) $this->t('¿Cómo es tu práctica jurídica?'),
          'subtitle' => (string) $this->t('Cada tipo de despacho tiene necesidades distintas.'),
          'options' => [
            ['value' => 'autonomo', 'label' => (string) $this->t('Ejerzo en solitario'), 'icon_cat' => 'verticals', 'icon_name' => 'jarabalex', 'color' => 'azul-corporativo'],
            ['value' => 'pequeno', 'label' => (string) $this->t('Despacho pequeño (2-10 personas)'), 'icon_cat' => 'verticals', 'icon_name' => 'jarabalex', 'color' => 'azul-corporativo'],
            ['value' => 'mediano', 'label' => (string) $this->t('Despacho mediano o grande (10+)'), 'icon_cat' => 'verticals', 'icon_name' => 'jarabalex', 'color' => 'azul-corporativo'],
          ],
          'scoring' => [
            'autonomo' => ['jarabalex' => 5, 'emprendimiento' => 1],
            'pequeno' => ['jarabalex' => 5, 'serviciosconecta' => 2],
            'mediano' => ['jarabalex' => 5, 'serviciosconecta' => 2, 'formacion' => 1],
          ],
        ],
        'institucion' => [
          'field' => 'programa',
          'title' => (string) $this->t('¿Qué tipo de programa gestionas?'),
          'subtitle' => (string) $this->t('Cada programa tiene requisitos específicos de seguimiento y justificación.'),
          'options' => [
            ['value' => 'empleo_fse', 'label' => (string) $this->t('Programa de empleo / FSE+'), 'icon_cat' => 'verticals', 'icon_name' => 'andalucia-ei', 'color' => 'verde-innovacion'],
            ['value' => 'formacion_inst', 'label' => (string) $this->t('Formación institucional'), 'icon_cat' => 'verticals', 'icon_name' => 'formacion', 'color' => 'verde-innovacion'],
            ['value' => 'innovacion', 'label' => (string) $this->t('Innovación o emprendimiento social'), 'icon_cat' => 'verticals', 'icon_name' => 'emprendimiento', 'color' => 'naranja-impulso'],
          ],
          'scoring' => [
            'empleo_fse' => ['andalucia_ei' => 5, 'formacion' => 1],
            'formacion_inst' => ['andalucia_ei' => 4, 'formacion' => 2],
            'innovacion' => ['andalucia_ei' => 3, 'emprendimiento' => 2, 'formacion' => 1],
          ],
        ],
        'creador' => [
          'field' => 'tipo_contenido',
          'title' => (string) $this->t('¿Qué tipo de contenido creas?'),
          'subtitle' => (string) $this->t('Cada formato tiene herramientas optimizadas.'),
          'options' => [
            ['value' => 'blog_articulos', 'label' => (string) $this->t('Blog, artículos o newsletters'), 'icon_cat' => 'content', 'icon_name' => 'edit', 'color' => 'azul-corporativo'],
            ['value' => 'redes_sociales', 'label' => (string) $this->t('Redes sociales y comunidad'), 'icon_cat' => 'content', 'icon_name' => 'edit', 'color' => 'naranja-impulso'],
            ['value' => 'multicanal', 'label' => (string) $this->t('Multi-canal (blog + redes + email)'), 'icon_cat' => 'content', 'icon_name' => 'edit', 'color' => 'verde-innovacion'],
          ],
          'scoring' => [
            'blog_articulos' => ['jaraba_content_hub' => 5, 'formacion' => 1],
            'redes_sociales' => ['jaraba_content_hub' => 4, 'comercioconecta' => 2],
            'multicanal' => ['jaraba_content_hub' => 5, 'emprendimiento' => 2],
          ],
        ],
      ],

      // Q3 adaptativa por perfil (la última pregunta, orientada a urgencia/necesidad).
      'q3' => [
        'persona' => [
          'field' => 'urgencia',
          'title' => (string) $this->t('¿Cuándo necesitas resultados?'),
          'subtitle' => (string) $this->t('Última pregunta, prometido.'),
          'options' => [
            ['value' => 'ahora', 'label' => (string) $this->t('Lo antes posible — es urgente'), 'icon_cat' => 'actions', 'icon_name' => 'check-circle', 'color' => 'verde-innovacion'],
            ['value' => 'semanas', 'label' => (string) $this->t('En las próximas semanas'), 'icon_cat' => 'ui', 'icon_name' => 'clock', 'color' => 'naranja-impulso'],
            ['value' => 'explorando', 'label' => (string) $this->t('Estoy explorando opciones'), 'icon_cat' => 'general', 'icon_name' => 'globe', 'color' => 'azul-corporativo'],
          ],
          'scoring' => [],
        ],
        'emprendedor' => [
          'field' => 'urgencia',
          'title' => (string) $this->t('¿Cuándo quieres tener tu negocio operativo?'),
          'subtitle' => (string) $this->t('Esto me ayuda a priorizar las herramientas.'),
          'options' => [
            ['value' => 'ahora', 'label' => (string) $this->t('Ya — necesito avanzar rápido'), 'icon_cat' => 'actions', 'icon_name' => 'check-circle', 'color' => 'verde-innovacion'],
            ['value' => 'semanas', 'label' => (string) $this->t('En los próximos meses'), 'icon_cat' => 'ui', 'icon_name' => 'clock', 'color' => 'naranja-impulso'],
            ['value' => 'explorando', 'label' => (string) $this->t('Solo estoy valorando opciones'), 'icon_cat' => 'general', 'icon_name' => 'globe', 'color' => 'azul-corporativo'],
          ],
          'scoring' => [],
        ],
        'empresa' => [
          'field' => 'necesidad_empresa',
          'title' => (string) $this->t('¿Qué necesitas digitalizar primero?'),
          'subtitle' => (string) $this->t('Empezaremos por lo que más impacto tenga.'),
          'options' => [
            ['value' => 'ventas_online', 'label' => (string) $this->t('Vender online'), 'icon_cat' => 'verticals', 'icon_name' => 'comercioconecta', 'color' => 'naranja-impulso'],
            ['value' => 'gestion_clientes', 'label' => (string) $this->t('Gestionar clientes y proyectos'), 'icon_cat' => 'verticals', 'icon_name' => 'serviciosconecta', 'color' => 'verde-innovacion'],
            ['value' => 'automatizar', 'label' => (string) $this->t('Automatizar procesos con IA'), 'icon_cat' => 'ai', 'icon_name' => 'brain', 'color' => 'naranja-impulso'],
            ['value' => 'presencia_digital', 'label' => (string) $this->t('Mejorar presencia digital'), 'icon_cat' => 'content', 'icon_name' => 'edit', 'color' => 'azul-corporativo'],
          ],
          'scoring' => [
            'ventas_online' => ['comercioconecta' => 3],
            'gestion_clientes' => ['serviciosconecta' => 3],
            'automatizar' => ['emprendimiento' => 2, 'serviciosconecta' => 1],
            'presencia_digital' => ['jaraba_content_hub' => 3],
          ],
        ],
        'legal' => [
          'field' => 'necesidad_legal',
          'title' => (string) $this->t('¿Qué te quita más tiempo en el día a día?'),
          'subtitle' => (string) $this->t('La IA puede devolverte horas cada semana.'),
          'options' => [
            ['value' => 'busqueda_legal', 'label' => (string) $this->t('Buscar jurisprudencia y legislación'), 'icon_cat' => 'verticals', 'icon_name' => 'jarabalex', 'color' => 'azul-corporativo'],
            ['value' => 'gestion_expedientes', 'label' => (string) $this->t('Gestionar expedientes y plazos'), 'icon_cat' => 'verticals', 'icon_name' => 'jarabalex', 'color' => 'azul-corporativo'],
            ['value' => 'redaccion', 'label' => (string) $this->t('Redactar documentos legales'), 'icon_cat' => 'verticals', 'icon_name' => 'jarabalex', 'color' => 'azul-corporativo'],
          ],
          'scoring' => [
            'busqueda_legal' => ['jarabalex' => 3, 'formacion' => 1],
            'gestion_expedientes' => ['jarabalex' => 3, 'serviciosconecta' => 1],
            'redaccion' => ['jarabalex' => 3, 'jaraba_content_hub' => 1],
          ],
        ],
        'institucion' => [
          'field' => 'urgencia',
          'title' => (string) $this->t('¿Cuándo necesitas tener el sistema operativo?'),
          'subtitle' => (string) $this->t('Los plazos FSE no esperan.'),
          'options' => [
            ['value' => 'ahora', 'label' => (string) $this->t('Ya — tengo programa en curso'), 'icon_cat' => 'actions', 'icon_name' => 'check-circle', 'color' => 'verde-innovacion'],
            ['value' => 'semanas', 'label' => (string) $this->t('Próxima convocatoria'), 'icon_cat' => 'ui', 'icon_name' => 'clock', 'color' => 'naranja-impulso'],
            ['value' => 'explorando', 'label' => (string) $this->t('Estoy evaluando herramientas'), 'icon_cat' => 'general', 'icon_name' => 'globe', 'color' => 'azul-corporativo'],
          ],
          'scoring' => [],
        ],
        'creador' => [
          'field' => 'objetivo_contenido',
          'title' => (string) $this->t('¿Cuál es tu objetivo principal?'),
          'subtitle' => (string) $this->t('Esto determina las herramientas que necesitas.'),
          'options' => [
            ['value' => 'crear_mas', 'label' => (string) $this->t('Crear más contenido en menos tiempo'), 'icon_cat' => 'content', 'icon_name' => 'edit', 'color' => 'azul-corporativo'],
            ['value' => 'audiencia', 'label' => (string) $this->t('Hacer crecer mi audiencia'), 'icon_cat' => 'content', 'icon_name' => 'edit', 'color' => 'naranja-impulso'],
            ['value' => 'monetizar', 'label' => (string) $this->t('Monetizar mi contenido'), 'icon_cat' => 'verticals', 'icon_name' => 'comercioconecta', 'color' => 'naranja-impulso'],
          ],
          'scoring' => [
            'crear_mas' => ['jaraba_content_hub' => 3, 'emprendimiento' => 1],
            'audiencia' => ['jaraba_content_hub' => 3, 'comercioconecta' => 1],
            'monetizar' => ['jaraba_content_hub' => 2, 'comercioconecta' => 3],
          ],
        ],
      ],
    ];
  }

  /**
   * Calcular scores basado en respuestas adaptativas.
   */
  public function calculateScores(array $answers): array {
    $scores = array_fill_keys(array_keys(self::VERTICAL_DATA), 0);
    $structure = $this->getQuizStructure();
    $perfil = $answers['perfil'] ?? '';

    // Scoring de Q2.
    $q2 = $structure['q2'][$perfil] ?? NULL;
    if ($q2) {
      $q2Answer = $answers[$q2['field']] ?? '';
      $q2Scores = $q2['scoring'][$q2Answer] ?? [];
      foreach ($q2Scores as $vertical => $points) {
        if (isset($scores[$vertical])) {
          $scores[$vertical] += $points;
        }
      }
    }

    // Scoring de Q3.
    $q3 = $structure['q3'][$perfil] ?? NULL;
    if ($q3) {
      $q3Answer = $answers[$q3['field']] ?? '';
      $q3Scores = $q3['scoring'][$q3Answer] ?? [];
      foreach ($q3Scores as $vertical => $points) {
        if (isset($scores[$vertical])) {
          $scores[$vertical] += $points;
        }
      }
    }

    return $scores;
  }

  /**
   * Obtener recomendación (top 1 + alternativas).
   */
  public function getRecommendation(array $scores): array {
    arsort($scores);
    $sorted = [];
    foreach ($scores as $vertical => $score) {
      $sorted[] = [
        'vertical' => $vertical,
        'score' => $score,
        'arpu' => self::ARPU_PRIORITY[$vertical] ?? 0,
      ];
    }
    usort($sorted, function ($a, $b) {
      if ($a['score'] !== $b['score']) {
        return $b['score'] - $a['score'];
      }
      return $b['arpu'] - $a['arpu'];
    });

    $maxScore = $sorted[0]['score'] ?: 1;
    $recommended = $sorted[0]['vertical'];
    $alternatives = [];
    for ($i = 1; $i < min(3, count($sorted)); $i++) {
      if ($sorted[$i]['score'] > 0) {
        $data = self::VERTICAL_DATA[$sorted[$i]['vertical']] ?? [];
        $alternatives[] = [
          'id' => $sorted[$i]['vertical'],
          'title' => $data['title'] ?? $sorted[$i]['vertical'],
          'path' => $data['path'] ?? $sorted[$i]['vertical'],
          'icon_cat' => $data['icon_cat'] ?? 'verticals',
          'icon_name' => $data['icon_name'] ?? $sorted[$i]['vertical'],
          'color' => $data['color'] ?? 'azul-corporativo',
          'match_pct' => (int) round(($sorted[$i]['score'] / $maxScore) * 100),
        ];
      }
    }

    return ['recommended' => $recommended, 'alternatives' => $alternatives];
  }

  /**
   * Obtener datos de presentación para un vertical.
   */
  public function getVerticalPresentation(string $vertical): array {
    return self::VERTICAL_DATA[$vertical] ?? self::VERTICAL_DATA['empleabilidad'];
  }

  /**
   * Persistir resultado.
   */
  public function saveResult(array $answers, array $scores, string $vertical, array $alternatives, ?string $email = NULL): QuizResult {
    $request = $this->requestStack->getCurrentRequest();
    $ipRaw = $request?->getClientIp() ?? '0.0.0.0';
    $ipHash = hash('sha256', $ipRaw . date('Y-m-d') . 'jaraba_quiz_salt_2026');

    $values = [
      'answers' => $answers,
      'scores' => $scores,
      'recommended_vertical' => $vertical,
      'alternative_verticals' => $alternatives,
      'email' => $email,
      'source_url' => $request?->headers->get('referer', ''),
      'utm_source' => $request?->query->get('utm_source', ''),
      'utm_medium' => $request?->query->get('utm_medium', ''),
      'utm_campaign' => $request?->query->get('utm_campaign', ''),
      'ip_hash' => $ipHash,
      'converted' => FALSE,
    ];

    if ($this->currentUser->isAuthenticated()) {
      $values['uid'] = $this->currentUser->id();
    }

    $storage = $this->entityTypeManager->getStorage('quiz_result');
    $entity = $storage->create($values);
    $entity->save();

    return $entity;
  }

  /**
   * Vincular resultado a usuario post-registro.
   */
  public function linkResultToUser(string $uuid, int $uid, int $tenantId): void {
    $storage = $this->entityTypeManager->getStorage('quiz_result');
    $results = $storage->loadByProperties(['uuid' => $uuid]);
    $result = reset($results);
    if (!$result instanceof QuizResult) {
      return;
    }

    $result->set('uid', $uid);
    $result->set('tenant_id', $tenantId);
    $result->set('converted', TRUE);
    $result->save();

    $this->logger->notice('Quiz result @uuid linked to user @uid.', [
      '@uuid' => $uuid,
      '@uid' => $uid,
    ]);
  }

  /**
   * Crear lead en CRM.
   */
  public function createCrmLead(QuizResult $result): void {
    if ($this->contactService === NULL || $this->opportunityService === NULL) {
      return;
    }

    $email = $result->get('email')->value;
    if (empty($email)) {
      return;
    }

    try {
      $answers = $result->getAnswers();
      $vertical = $result->getRecommendedVertical();
      $verticalData = self::VERTICAL_DATA[$vertical] ?? [];

      $contact = $this->contactService->create([
        'first_name' => '',
        'last_name' => '',
        'email' => $email,
        'source' => 'quiz_vertical',
        'engagement_score' => 40,
        'notes' => (string) $this->t('Lead Quiz Vertical. Perfil: @p.', [
          '@p' => $answers['perfil'] ?? '?',
        ]),
      ]);

      $urgencia = $answers['urgencia'] ?? '';
      $this->opportunityService->create([
        'title' => (string) $this->t('Lead Quiz — @v', ['@v' => $verticalData['title'] ?? $vertical]),
        'contact_id' => $contact->id(),
        'stage' => 'mql',
        'probability' => 30,
        'bant_timeline' => self::URGENCY_TO_BANT[$urgencia] ?? 'none',
      ]);

      $result->set('crm_contact_id', $contact->id());
      $result->save();
    }
    catch (\Throwable $e) {
      $this->logger->warning('CRM lead failed for quiz @uuid: @e', [
        '@uuid' => $result->uuid(),
        '@e' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Obtener resultado por UUID.
   */
  public function getResultByUuid(string $uuid): ?QuizResult {
    $storage = $this->entityTypeManager->getStorage('quiz_result');
    $results = $storage->loadByProperties(['uuid' => $uuid]);
    $result = reset($results);
    return $result instanceof QuizResult ? $result : NULL;
  }

  /**
   * Generar explicación IA (fallback estático si no disponible).
   */
  public function generateAiExplanation(array $answers, string $vertical): string {
    $verticalData = self::VERTICAL_DATA[$vertical] ?? [];
    $verticalTitle = $verticalData['title'] ?? $vertical;

    // Fallbacks específicos por vertical — mucho más persuasivos que genérico.
    $fallbacks = [
      'empleabilidad' => (string) $this->t('Con tu perfil profesional, Empleabilidad te dará acceso a un CV inteligente que se adapta a cada oferta, matching automático con oportunidades y preparación de entrevistas con IA.'),
      'emprendimiento' => (string) $this->t('Para tu fase emprendedora, esta plataforma te ofrece Lean Canvas asistido, diagnóstico de viabilidad y un copiloto IA especializado que te acompaña desde la idea hasta la primera venta.'),
      'comercioconecta' => (string) $this->t('Tu negocio necesita presencia online. ComercioConecta te permite crear tu tienda, gestionar pedidos y cobrar con Stripe y Bizum desde el primer día, sin conocimientos técnicos.'),
      'agroconecta' => (string) $this->t('El sector agroalimentario tiene sus propias reglas. AgroConecta te ofrece marketplace especializado, trazabilidad de lotes y conexión directa con compradores — todo adaptado al campo.'),
      'jarabalex' => (string) $this->t('Para tu práctica jurídica, JarabaLex ofrece buscador legal con IA grounding sobre legislación española, gestión de expedientes y un copiloto que entiende de derecho.'),
      'serviciosconecta' => (string) $this->t('Gestionar servicios profesionales requiere herramientas específicas. ServiciosConecta te da CRM, presupuestos automáticos y un calendario compartido con tus clientes.'),
      'formacion' => (string) $this->t('La formación online con IA es el futuro. Con Formación puedes crear cursos certificados, contenido adaptativo y hacer seguimiento detallado del progreso de cada alumno.'),
      'andalucia_ei' => (string) $this->t('Para programas de empleo FSE+, Andalucía +ei te ofrece gestión integral: participantes, sesiones, acciones formativas e informes automatizados para justificación ante la administración.'),
      'jaraba_content_hub' => (string) $this->t('Como creador de contenido, Content Hub te da un editor profesional con IA, publicación multi-canal y analíticas integradas para hacer crecer tu audiencia.'),
    ];
    $fallback = $fallbacks[$vertical] ?? (string) $this->t('Basándonos en tu perfil, @vertical es la solución ideal. Podrás empezar a obtener resultados desde el primer día.', [
      '@vertical' => $verticalTitle,
    ]);

    if ($this->modelRouter === NULL || $this->aiProvider === NULL) {
      return $fallback;
    }

    try {
      // MODEL-ROUTING-CONFIG-001: tier fast (Haiku) para texto corto.
      $prompt = sprintf(
        'Eres un asesor de negocios experto de Jaraba Impact Platform. '
        . 'El usuario completó un quiz de recomendación y su perfil es: %s. '
        . 'El vertical recomendado es: %s. '
        . 'Genera EXACTAMENTE 2 frases cortas explicando POR QUÉ este vertical es perfecto para su perfil. '
        . 'Sé MUY concreto con funcionalidades específicas del vertical. '
        . 'Tono profesional pero cercano. Español de España. '
        . 'NO uses emojis. NO te presentes. Solo las 2 frases.',
        $answers['perfil'] ?? 'no especificado',
        $verticalTitle,
      );

      $routing = $this->modelRouter->route('fast', $prompt);
      $providerId = $routing['provider_id'] ?? '';
      $modelId = $routing['model_id'] ?? '';
      if ($providerId !== '' && $modelId !== '') {
        $provider = $this->aiProvider->createInstance($providerId);
        $chatInput = new ChatInput([
          new ChatMessage('user', $prompt),
        ]);
        $response = $provider->chat($chatInput, $modelId, [
          'temperature' => 0.7,
          'max_tokens' => 150,
        ]);
        $text = trim($response->getNormalized()->getText());
        if ($text !== '' && strlen($text) > 20) {
          return $text;
        }
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('AI explanation failed: @e', ['@e' => $e->getMessage()]);
    }

    return $fallback;
  }

}
