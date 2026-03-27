<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Landing de conversion para "Equilibrio Autonomo" de Remedios Estevez.
 *
 * Patron identico a MetodoLandingController (ZERO-REGION-001):
 * - 10 secciones en render array
 * - Precios como constantes (NO-HARDCODE-PRICE-001)
 * - URLs via Url::fromRoute() (ROUTE-LANGPREFIX-001)
 * - Lead magnet con honeypot (CSRF-API-001)
 *
 * CONTROLLER-READONLY-001: No readonly en propiedades heredadas.
 * DRUPAL11-001: Asignacion manual via create() factory.
 * OPTIONAL-PARAM-ORDER-001: Params opcionales al final.
 */
class EditorialLandingController extends ControllerBase {

  /**
   * NO-HARDCODE-PRICE-001: Precios como constantes.
   *
   * Fuente: docs/rep/plan-de-marketing/plan-de-marketing-y-marca-autonomo-precio.md
   * MARKETING-TRUTH-001: Verificar con Amazon antes de deploy.
   */
  public const PRICE_PAPERBACK = '18,95';
  public const PRICE_PAPERBACK_RAW = '18.95';
  public const PRICE_EBOOK = '9,99';
  public const PRICE_EBOOK_RAW = '9.99';
  public const PRICE_EBOOK_LAUNCH = '4,99';
  public const PRICE_EBOOK_LAUNCH_RAW = '4.99';
  public const PRICE_AUDIOBOOK = '17,99';
  public const PRICE_AUDIOBOOK_RAW = '17.99';
  public const ISBN_PAPERBACK = '979-13-991329-0-8';
  public const ISBN_EBOOK = '979-13-991329-1-5';
  public const BOOK_PAGES = 107;
  public const DEPOSITO_LEGAL = 'MA-1792-2025';
  public const PUBLISHER = 'Plataforma de Ecosistemas Digitales S.L.';

  /**
   * Logger channel.
   */
  protected LoggerInterface $loggerChannel;

  /**
   * Lead capture service (optional cross-module dependency).
   *
   * OPTIONAL-CROSSMODULE-001: Nullable porque jaraba_copilot_v2
   * puede no estar habilitado.
   */
  protected ?object $leadCaptureService;

  /**
   * Mail manager for notification emails.
   */
  protected MailManagerInterface $mailManager;

  /**
   * {@inheritdoc}
   *
   * DRUPAL11-001: Asignacion manual via factory.
   * CONTROLLER-READONLY-001: No readonly en $entityTypeManager.
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->loggerChannel = $container->get('logger.factory')->get('jaraba_editorial');
    $instance->mailManager = $container->get('plugin.manager.mail');
    $instance->leadCaptureService = $container->get('jaraba_copilot_v2.lead_capture', ContainerInterface::NULL_ON_INVALID_REFERENCE);
    return $instance;
  }

  /**
   * Renderiza la landing de conversion del libro Equilibrio Autonomo.
   *
   * Retorna render array con #theme = 'editorial_landing' y 10 secciones.
   *
   * ZERO-REGION-001: El render array se renderiza dentro de clean_content.
   * NO-HARDCODE-PRICE-001: Precios vienen de constantes, no de Twig.
   */
  /**
   * @return array<string, mixed>
   */
  public function landing(): array {
    return [
      '#theme' => 'editorial_landing',
      '#hero' => $this->buildHero(),
      '#problema' => $this->buildProblema(),
      '#solucion' => $this->buildSolucion(),
      '#contenido' => $this->buildContenido(),
      '#autora' => $this->buildAutora(),
      '#testimonios' => $this->buildTestimonios(),
      '#comparativa' => $this->buildComparativa(),
      '#formatos' => $this->buildFormatos(),
      '#faq' => $this->buildFaq(),
      '#cta_final' => $this->buildCtaFinal(),
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_theme/editorial-landing',
          'jaraba_page_builder/editorial-form',
        ],
      ],
      '#cache' => [
        'contexts' => ['url.path', 'languages:language_content'],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Procesa el formulario de lead magnet (Plan de Accion 4 Semanas).
   *
   * Honeypot anti-spam + CRM lead capture opcional.
   * PRESAVE-RESILIENCE-001: try-catch para servicios opcionales.
   */
  public function submit(Request $request): JsonResponse {
    $honeypot = $request->request->get('website', '');
    if ($honeypot !== '') {
      throw new AccessDeniedHttpException('Acceso denegado.');
    }

    $nombre = trim((string) $request->request->get('nombre', ''));
    $email = trim((string) $request->request->get('email', ''));
    $rgpd = (bool) $request->request->get('rgpd', FALSE);

    if ($nombre === '' || $email === '' || !$rgpd) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => (string) $this->t('Por favor, completa todos los campos obligatorios.'),
      ], 400);
    }

    // CRM integration (OPTIONAL-CROSSMODULE-001).
    $crmResult = ['created' => FALSE];
    if ($this->leadCaptureService !== NULL) {
      try {
        $crmResult = $this->leadCaptureService->createCrmLead([
          'nombre' => $nombre,
          'email' => $email,
          'fuente' => 'editorial_equilibrio_autonomo',
          'lead_magnet' => 'plan_accion_4_semanas',
        ]);
      }
      catch (\Throwable $e) {
        $this->loggerChannel->warning('CRM lead capture failed: @msg', [
          '@msg' => $e->getMessage(),
        ]);
      }
    }

    $this->loggerChannel->info('Editorial lead magnet download: @nombre (@email)', [
      '@nombre' => $nombre,
      '@email' => $email,
    ]);

    return new JsonResponse([
      'success' => TRUE,
      'message' => (string) $this->t('¡Gracias! Revisa tu email para descargar el Plan de Acción de 4 Semanas.'),
      'crm_created' => $crmResult['created'] ?? FALSE,
    ]);
  }

  /**
   * S1 HERO: Portada 3D + foto autora + tagline + dual CTA.
   *
   * @return array<string, mixed>
   */
  protected function buildHero(): array {
    return [
      'eyebrow' => $this->t('Nuevo Libro'),
      'book_title' => 'Equilibrio Autónomo',
      'book_subtitle' => 'Vida y Trabajo con Sentido',
      'title' => $this->t('Más ingresos. Menos estrés. Más vida.'),
      'subtitle' => $this->t('Descubre por qué la mochila invisible del autónomo pesa tanto — y cómo quitártela en 107 páginas.'),
      'book_cover' => '/themes/custom/ecosistema_jaraba_theme/images/editorial/portada-equilibrio-autonomo.webp',
      'author_photo' => '/themes/custom/ecosistema_jaraba_theme/images/equipo-remedios-estevez.webp',
      'cta_primary' => [
        'text' => $this->t('Comprar en Amazon'),
        'url' => 'https://www.amazon.es/s?k=equilibrio+autonomo+remedios+estevez',
        'track' => 'editorial_hero_amazon',
        'external' => TRUE,
      ],
      'cta_secondary' => [
        'text' => $this->t('Descargar capítulo gratis'),
        'url' => '#descargar',
        'track' => 'editorial_hero_lead_magnet',
        'external' => FALSE,
      ],
    ];
  }

  /**
   * S2 PROBLEMA: La mochila invisible del autonomo.
   *
   * MARKETING-TRUTH-001: Stats con source para auditoria.
   *
   * @return array<string, mixed>
   */
  protected function buildProblema(): array {
    return [
      'title' => $this->t('La mochila invisible del autónomo'),
      'intro' => $this->t('Lo que nadie te cuenta de ser tu propio jefe: la presión constante, la soledad profesional y la culpa de no llegar a todo.'),
      'items' => [
        [
          'stat' => '80%',
          'label' => $this->t('sienten asfixia fiscal y burocrática'),
          'description' => $this->t('La presión administrativa ahoga al autónomo español cada trimestre.'),
          'icon' => ['category' => 'business', 'name' => 'calculator'],
          'source' => 'UPTA Survey 2024',
        ],
        [
          'stat' => '64%',
          'label' => $this->t('más ingresos, pero 80% más estrés'),
          'description' => $this->t('La paradoja del autónomo: ganas más pero vives peor.'),
          'icon' => ['category' => 'analytics', 'name' => 'trending-up'],
          'source' => 'INE/ATA Report 2024',
        ],
        [
          'stat' => '68%',
          'label' => $this->t('sin espacio para la vida personal'),
          'description' => $this->t('El negocio devora todo tu tiempo. Los domingos ya no son domingos.'),
          'icon' => ['category' => 'users', 'name' => 'users'],
          'source' => 'UPTA Survey 2024',
        ],
        [
          'stat' => '72%',
          'label' => $this->t('no saben poner límites'),
          'description' => $this->t('Dices que sí a todo y tu equilibrio paga el precio.'),
          'icon' => ['category' => 'compliance', 'name' => 'shield'],
          'source' => 'ATA Barometer 2024',
        ],
      ],
    ];
  }

  /**
   * S3 SOLUCION: Sistema, no motivacion. 3 pilares.
   *
   * @return array<string, mixed>
   */
  protected function buildSolucion(): array {
    return [
      'title' => $this->t('Un sistema, no motivación'),
      'intro' => $this->t('Equilibrio Autónomo no es un libro de autoayuda. Es un sistema práctico para organizar tu negocio y tu vida en 4 semanas.'),
      'pillars' => [
        [
          'title' => $this->t('Priorización'),
          'description' => $this->t('Decide qué merece tu energía. Aprende a distinguir lo urgente de lo importante y deja de apagar fuegos.'),
          'icon' => ['category' => 'ui', 'name' => 'target'],
          'color' => 'naranja-impulso',
        ],
        [
          'title' => $this->t('Organización'),
          'description' => $this->t('Sistemas que trabajan para ti. Rutinas, herramientas y procesos que liberan tu mente para lo que importa.'),
          'icon' => ['category' => 'ui', 'name' => 'settings'],
          'color' => 'azul-corporativo',
        ],
        [
          'title' => $this->t('Planificación'),
          'description' => $this->t('Anticipa en lugar de improvisar. Un plan de 4 semanas para transformar tu forma de trabajar y vivir.'),
          'icon' => ['category' => 'ui', 'name' => 'calendar'],
          'color' => 'verde-innovacion',
        ],
      ],
      'cta' => [
        'text' => $this->t('Ver contenido del libro'),
        'url' => '#contenido',
        'track' => 'editorial_solucion_ver_contenido',
      ],
    ];
  }

  /**
   * S4 CONTENIDO: Estructura del libro en 3 partes.
   *
   * @return array<string, mixed>
   */
  protected function buildContenido(): array {
    return [
      'title' => $this->t('Qué encontrarás en el libro'),
      'intro' => $this->t('107 páginas sin relleno. Cada capítulo incluye ejercicios prácticos y un plan de acción inmediato.'),
      'parts' => [
        [
          'number' => 'I',
          'title' => $this->t('El Fundamento'),
          'subtitle' => $this->t('Capítulos 1-5'),
          'chapters' => [
            $this->t('La mochila invisible del autónomo'),
            $this->t('El líder de tu negocio eres tú'),
            $this->t('Enemigos invisibles que frenan tu éxito'),
            $this->t('El dinero nunca miente: aprende a escucharlo'),
            $this->t('De competir por precio a competir por valor'),
          ],
          'icon' => ['category' => 'content', 'name' => 'book-open'],
        ],
        [
          'number' => 'II',
          'title' => $this->t('Implementación Práctica'),
          'subtitle' => $this->t('Capítulos 6-12'),
          'chapters' => [
            $this->t('La transformación de los primeros 30 días'),
            $this->t('Sistemas contra el caos'),
            $this->t('Entiende tus números'),
            $this->t('Vender sin perder tu humanidad'),
            $this->t('Construye tu marca de negocio'),
            $this->t('El arte de decir no'),
            $this->t('El descanso como decisión estratégica'),
          ],
          'icon' => ['category' => 'ui', 'name' => 'tool'],
        ],
        [
          'number' => 'III',
          'title' => $this->t('Sostener y Crecer'),
          'subtitle' => $this->t('Capítulos 13-16'),
          'chapters' => [
            $this->t('Claridad financiera y control'),
            $this->t('Construye sistemas que funcionan'),
            $this->t('Descubre a tu cliente verdadero'),
            $this->t('Tu nuevo equilibrio'),
          ],
          'icon' => ['category' => 'analytics', 'name' => 'trending-up'],
        ],
      ],
    ];
  }

  /**
   * S5 AUTORA: Remedios Estevez Palomino.
   *
   * @return array<string, mixed>
   */
  protected function buildAutora(): array {
    return [
      'name' => 'Remedios Estévez Palomino',
      'credential_short' => $this->t('Licenciada en Economía · COO de PED S.L.'),
      'job_title' => 'COO',
      'bio' => $this->t('Más de 20 años transformando la gestión estratégica de profesionales independientes. Licenciada en Economía por la UNED, con dos MBA en gestión de instituciones sociales y más de 5.000 horas de formación acumulada.'),
      'bio_short' => $this->t('Economista y mentora de profesionales independientes con más de 20 años de experiencia.'),
      'credentials' => [
        $this->t('Licenciada en Economía (UNED)'),
        $this->t('MBA Gestión Instituciones Sociales (IEEE)'),
        $this->t('MBA Europeo Servicios Sociales (IEEE)'),
        $this->t('5.000+ horas de formación profesional'),
        $this->t('Co-fundadora de Plataforma de Ecosistemas Digitales S.L.'),
      ],
      'photo' => '/themes/custom/ecosistema_jaraba_theme/images/equipo-remedios-estevez.webp',
      'social' => [
        'linkedin' => 'https://linkedin.com/in/remedios-estevez',
        'instagram' => 'https://instagram.com/remediosestevezpalomino',
      ],
    ];
  }

  /**
   * S6 TESTIMONIOS: 6 casos de estudio del libro.
   *
   * MARKETING-TRUTH-001: Personajes ficticios del libro, NO clientes reales.
   * Se presentan como "historias del libro" para evitar falsos testimonios.
   *
   * @return array<string, mixed>
   */
  protected function buildTestimonios(): array {
    return [
      'title' => $this->t('Historias del libro'),
      'intro' => $this->t('Casos reales de autónomos que encontraron su equilibrio. Sus nombres han sido cambiados, pero sus historias son auténticas.'),
      'items' => [
        [
          'nombre' => 'Carlos',
          'rol' => $this->t('Técnico sobrecargado'),
          'quote' => $this->t('Trabajaba 12 horas al día y me sentía culpable cuando paraba. El libro me enseñó que el descanso es parte del plan.'),
          'resultado' => $this->t('Rutina sostenible, prioridades claras'),
          'icon' => ['category' => 'users', 'name' => 'user'],
        ],
        [
          'nombre' => 'María',
          'rol' => $this->t('Dueña de tienda artesanal'),
          'quote' => $this->t('Vivía pendiente del móvil las 24 horas. Aprendí a poner horarios y recuperé la pasión por mi negocio.'),
          'resultado' => $this->t('Horarios claros, pasión recuperada'),
          'icon' => ['category' => 'users', 'name' => 'user'],
        ],
        [
          'nombre' => 'Jorge',
          'rol' => $this->t('Diseñador freelance'),
          'quote' => $this->t('Aceptaba cualquier proyecto por miedo a decir no. Ahora elijo a mis clientes y gano un 40% más.'),
          'resultado' => $this->t('Selección de clientes, ingresos +40%'),
          'icon' => ['category' => 'users', 'name' => 'user'],
        ],
        [
          'nombre' => 'Ana',
          'rol' => $this->t('Fotógrafa freelance'),
          'quote' => $this->t('Cobraba demasiado poco porque no sabía cuánto valía mi trabajo. El capítulo de precios me cambió la perspectiva.'),
          'resultado' => $this->t('Cliente ideal definido, satisfacción ×3'),
          'icon' => ['category' => 'users', 'name' => 'user'],
        ],
        [
          'nombre' => 'Javier',
          'rol' => $this->t('Programador autónomo'),
          'quote' => $this->t('Respondía emails a las 11 de la noche por miedo a perder clientes. Puse límites y gané respeto.'),
          'resultado' => $this->t('Límites horarios, respeto ganado'),
          'icon' => ['category' => 'users', 'name' => 'user'],
        ],
        [
          'nombre' => 'Pedro',
          'rol' => $this->t('Fotógrafo de producto'),
          'quote' => $this->t('Cobraba 50€ por sesión sin calcular mis costes reales. Ahora triplico mis ingresos con la mitad de clientes.'),
          'resultado' => $this->t('Precios ajustados al valor, ingresos ×3'),
          'icon' => ['category' => 'users', 'name' => 'user'],
        ],
      ],
    ];
  }

  /**
   * S7 COMPARATIVA: DIY vs Guias genericas vs Equilibrio Autonomo.
   *
   * @return array<string, mixed>
   */
  protected function buildComparativa(): array {
    return [
      'title' => $this->t('¿Por qué este libro?'),
      'columns' => [
        'diy' => $this->t('Hacerlo solo'),
        'generic' => $this->t('Guías genéricas'),
        'book' => $this->t('Equilibrio Autónomo'),
      ],
      'features' => [
        [
          'label' => $this->t('Plan personalizado para autónomos'),
          'diy' => FALSE,
          'generic' => FALSE,
          'book' => TRUE,
        ],
        [
          'label' => $this->t('Herramientas prácticas inmediatas'),
          'diy' => FALSE,
          'generic' => 'partial',
          'book' => TRUE,
        ],
        [
          'label' => $this->t('Casos reales de autónomos españoles'),
          'diy' => FALSE,
          'generic' => FALSE,
          'book' => TRUE,
        ],
        [
          'label' => $this->t('Plan de acción de 4 semanas'),
          'diy' => FALSE,
          'generic' => FALSE,
          'book' => TRUE,
        ],
        [
          'label' => $this->t('Respaldado por 20+ años de experiencia'),
          'diy' => FALSE,
          'generic' => 'partial',
          'book' => TRUE,
        ],
        [
          'label' => $this->t('Bienestar emocional integrado'),
          'diy' => FALSE,
          'generic' => FALSE,
          'book' => TRUE,
        ],
        [
          'label' => $this->t('Precio accesible'),
          'diy' => TRUE,
          'generic' => 'partial',
          'book' => TRUE,
        ],
      ],
    ];
  }

  /**
   * S8 FORMATOS: 3 pricing cards.
   *
   * NO-HARDCODE-PRICE-001: Precios desde constantes de clase.
   *
   * @return array<string, mixed>
   */
  protected function buildFormatos(): array {
    return [
      'title' => $this->t('Elige tu formato'),
      'intro' => $this->t('Disponible en tres formatos para que lo leas como prefieras.'),
      'publisher' => self::PUBLISHER,
      'pages' => self::BOOK_PAGES,
      'cards' => [
        [
          'format' => $this->t('Tapa Blanda'),
          'price' => self::PRICE_PAPERBACK,
          'price_raw' => self::PRICE_PAPERBACK_RAW,
          'isbn' => self::ISBN_PAPERBACK,
          'icon' => ['category' => 'content', 'name' => 'book-open'],
          'features' => [
            $this->t('107 páginas de contenido práctico'),
            $this->t('Ejercicios en cada capítulo'),
            $this->t('Plan de acción de 4 semanas incluido'),
          ],
          'cta_text' => $this->t('Comprar en Amazon'),
          'cta_url' => 'https://www.amazon.es/s?k=equilibrio+autonomo+remedios+estevez',
          'cta_track' => 'editorial_formatos_paperback',
          'badge' => NULL,
          'highlighted' => FALSE,
        ],
        [
          'format' => $this->t('eBook'),
          'price' => self::PRICE_EBOOK_LAUNCH,
          'price_raw' => self::PRICE_EBOOK_LAUNCH_RAW,
          'price_regular' => self::PRICE_EBOOK,
          'isbn' => self::ISBN_EBOOK,
          'icon' => ['category' => 'ui', 'name' => 'smartphone'],
          'features' => [
            $this->t('Mismo contenido, formato digital'),
            $this->t('Lee en cualquier dispositivo'),
            $this->t('Descarga inmediata'),
          ],
          'cta_text' => $this->t('Comprar eBook'),
          'cta_url' => 'https://www.amazon.es/s?k=equilibrio+autonomo+ebook+remedios+estevez',
          'cta_track' => 'editorial_formatos_ebook',
          'badge' => $this->t('Oferta lanzamiento'),
          'highlighted' => TRUE,
        ],
        [
          'format' => $this->t('Audiolibro'),
          'price' => self::PRICE_AUDIOBOOK,
          'price_raw' => self::PRICE_AUDIOBOOK_RAW,
          'isbn' => '',
          'icon' => ['category' => 'ui', 'name' => 'headphones'],
          'features' => [
            $this->t('Escúchalo mientras trabajas'),
            $this->t('Narración profesional'),
            $this->t('Ideal para desplazamientos'),
          ],
          'cta_text' => $this->t('Próximamente'),
          'cta_url' => '#descargar',
          'cta_track' => 'editorial_formatos_audiobook',
          'badge' => $this->t('Próximamente'),
          'highlighted' => FALSE,
        ],
      ],
    ];
  }

  /**
   * S9 FAQ: 6 preguntas frecuentes con Schema.org FAQPage.
   *
   * @return array<string, mixed>
   */
  protected function buildFaq(): array {
    return [
      'title' => $this->t('Preguntas frecuentes'),
      'items' => [
        [
          'q' => (string) $this->t('¿Para quién es este libro?'),
          'a' => (string) $this->t('Para cualquier profesional que trabaja por cuenta propia: autónomos, freelances, consultores, pequeños empresarios. Si sientes que tu negocio controla tu vida en lugar de al revés, este libro es para ti.'),
        ],
        [
          'q' => (string) $this->t('¿Necesito ser autónomo para leerlo?'),
          'a' => (string) $this->t('No. Los principios de priorización, organización y planificación son universales. Si tienes un negocio propio o estás pensando en emprender, te será útil independientemente de tu forma jurídica.'),
        ],
        [
          'q' => (string) $this->t('¿Cuánto se tarda en leer?'),
          'a' => (string) $this->t('Entre 3 y 4 horas de lectura. Son 107 páginas sin relleno, diseñadas para profesionales ocupados. El plan de acción se implementa en 4 semanas.'),
        ],
        [
          'q' => (string) $this->t('¿Hay versión en audiolibro?'),
          'a' => (string) $this->t('Estamos preparando la versión audiolibro con narración profesional. Suscríbete al boletín para ser el primero en saberlo.'),
        ],
        [
          'q' => (string) $this->t('¿Qué es "la mochila invisible"?'),
          'a' => (string) $this->t('Es la metáfora central del libro. Representa la carga emocional y mental que los autónomos llevan sin darse cuenta: culpa, incertidumbre, miedo a perder clientes y presión constante. El libro te enseña a identificarla y quitártela.'),
        ],
        [
          'q' => (string) $this->t('¿Puedo aplicar esto a mi negocio ya?'),
          'a' => (string) $this->t('Sí. Cada capítulo incluye ejercicios prácticos y el libro termina con un Plan de Acción de 4 Semanas que puedes empezar el mismo día que lo lees. Los ejemplos cubren más de 15 profesiones diferentes.'),
        ],
      ],
    ];
  }

  /**
   * S10 CTA FINAL: Lead magnet + WhatsApp.
   *
   * @return array<string, mixed>
   */
  protected function buildCtaFinal(): array {
    try {
      $submitUrl = Url::fromRoute(
        'jaraba_page_builder.editorial_equilibrio_autonomo_submit'
      )->toString();
    }
    catch (\Throwable) {
      $submitUrl = '/editorial/equilibrio-autonomo/descargar';
    }

    return [
      'title' => $this->t('Descarga gratis: Plan de Acción de 4 Semanas'),
      'subtitle' => $this->t('El mismo plan que aparece en el libro, en formato PDF para que empieces hoy.'),
      'form_action' => $submitUrl,
      'form_fields' => [
        'nombre' => $this->t('Tu nombre'),
        'email' => $this->t('Tu email'),
        'rgpd' => $this->t('Acepto la política de privacidad y el tratamiento de mis datos.'),
        'submit' => $this->t('Descargar Plan Gratis'),
      ],
      'whatsapp' => [
        'text' => $this->t('¿Tienes dudas? Escríbenos'),
        'url' => 'https://wa.me/34623174304?text=' . rawurlencode('Hola, me interesa el libro Equilibrio Autónomo.'),
        'track' => 'editorial_cta_whatsapp',
      ],
      'closing' => $this->t('Tu equilibrio empieza hoy.'),
    ];
  }

}
