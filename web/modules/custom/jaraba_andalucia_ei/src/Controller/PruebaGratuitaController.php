<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller para la landing page pública de prueba gratuita Andalucía +ei.
 *
 * Captura leads de negocios piloto interesados en el programa PIIL.
 * Ruta pública sin autenticación. Honeypot anti-spam en campo oculto.
 *
 * ZERO-REGION-001: landing() devuelve render array con #theme.
 * CONTROLLER-READONLY-001: No redeclara $entityTypeManager con readonly.
 * TENANT-001: Establece tenant_id al crear la entidad.
 * NO-HARDCODE-PRICE-001: Valores numéricos desde config.
 * FUNNEL-COMPLETENESS-001: Datos completos para tracking en template.
 */
class PruebaGratuitaController extends ControllerBase {

  /**
   * Servicio de contexto de tenant (opcional).
   */
  protected ?TenantContextService $tenantContext;

  /**
   * El logger del módulo.
   */
  protected LoggerInterface $logger;

  /**
   * Resuelve rutas de extensiones.
   */
  protected ExtensionPathResolver $pathResolver;

  /**
   * Construye el controller.
   *
   * CONTROLLER-READONLY-001: $entityTypeManager se asigna en el body.
   * OPTIONAL-PARAM-ORDER-001: $tenantContext (nullable) al final.
   */
  public function __construct(
    mixed $entityTypeManager,
    LoggerInterface $logger,
    ExtensionPathResolver $pathResolver,
    ConfigFactoryInterface $configFactory,
    ?TenantContextService $tenantContext = NULL,
  ) {
    // DRUPAL11-001: Asignar manualmente en constructor body.
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
    $this->pathResolver = $pathResolver;
    $this->configFactory = $configFactory;
    $this->tenantContext = $tenantContext;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('jaraba_andalucia_ei'),
      $container->get('extension.path.resolver'),
      $container->get('config.factory'),
      $container->get('ecosistema_jaraba_core.tenant_context', ContainerInterface::NULL_ON_INVALID_REFERENCE),
    );
  }

  /**
   * Renderiza la landing page pública de prueba gratuita.
   *
   * ZERO-REGION-001: Devuelve render array con #theme, sin lógica de region.
   * ROUTE-LANGPREFIX-001: URL de acción del formulario via Url::fromRoute().
   *
   * @return array<string, mixed>
   *   Render array con tema 'prueba_gratuita_landing'.
   */
  public function landing(): array {
    $config = $this->configFactory->get('jaraba_andalucia_ei.settings');
    $modulePath = $this->pathResolver->getPath('module', 'jaraba_andalucia_ei');
    $tasaInsercion = (int) ($config->get('tasa_insercion_objetivo') ?? 40);
    $showSuccess = \Drupal::request()->query->get('ok') === '1';

    $formAction = '#formulario';
    try {
      $formAction = Url::fromRoute('jaraba_andalucia_ei.prueba_gratuita_submit')->toString();
    }
    catch (\Exception $e) {
      $this->logger->warning('Route prueba_gratuita_submit not found: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }

    $services = [
      [
        'key' => 'redes',
        'icon_group' => 'social',
        'icon_name' => 'instagram',
        'color' => 'azul-corporativo',
        'title' => $this->t('Gestión de Redes Sociales'),
        'description' => $this->t('Posts profesionales en Instagram y Facebook para su negocio'),
      ],
      [
        'key' => 'web',
        'icon_group' => 'content',
        'icon_name' => 'globe',
        'color' => 'naranja-impulso',
        'title' => $this->t('Creación de Web'),
        'description' => $this->t('Página web profesional de 3-5 páginas con SEO local'),
      ],
      [
        'key' => 'resenas',
        'icon_group' => 'status',
        'icon_name' => 'star',
        'color' => 'verde-innovacion',
        'title' => $this->t('Gestión de Reseñas'),
        'description' => $this->t('Respuesta profesional a todas sus reseñas de Google'),
      ],
      [
        'key' => 'admin',
        'icon_group' => 'content',
        'icon_name' => 'document',
        'color' => 'naranja-impulso',
        'title' => $this->t('Asistencia Administrativa'),
        'description' => $this->t('Facturación, emails, agenda y organización documental'),
      ],
      [
        'key' => 'tienda',
        'icon_group' => 'commerce',
        'icon_name' => 'cart',
        'color' => 'azul-corporativo',
        'title' => $this->t('Tienda Online'),
        'description' => $this->t('Catálogo digital con fotos profesionales y pagos integrados'),
      ],
    ];

    $pain_points = [
      [
        'icon' => 'content/globe',
        'title' => $this->t('Sin web o web anticuada'),
        'description' => $this->t('Su negocio es invisible en Google. Los clientes buscan online y no le encuentran.'),
      ],
      [
        'icon' => 'status/star',
        'title' => $this->t('Reseñas sin responder'),
        'description' => $this->t('Tiene reseñas en Google que llevan meses sin contestar. Eso ahuyenta clientes.'),
      ],
      [
        'icon' => 'social/instagram',
        'title' => $this->t('Redes sociales abandonadas'),
        'description' => $this->t('Su última publicación fue hace meses. Parece que el negocio ya no existe.'),
      ],
      [
        'icon' => 'status/clock',
        'title' => $this->t('No tiene tiempo ni sabe cómo'),
        'description' => $this->t('Sabe que debería estar en internet pero no tiene tiempo, presupuesto ni conocimientos.'),
      ],
    ];

    $comparison = [
      [
        'feature' => $this->t('Coste'),
        'diy' => $this->t('Su tiempo'),
        'agency' => $this->t('300-2.000 €/mes'),
        'prueba' => $this->t('0 €'),
      ],
      [
        'feature' => $this->t('Duración'),
        'diy' => $this->t('Semanas aprendiendo'),
        'agency' => $this->t('Contrato anual'),
        'prueba' => $this->t('2-4 semanas'),
      ],
      [
        'feature' => $this->t('Supervisión experta'),
        'diy' => FALSE,
        'agency' => TRUE,
        'prueba' => TRUE,
      ],
      [
        'feature' => $this->t('Compromiso'),
        'diy' => FALSE,
        'agency' => $this->t('12 meses'),
        'prueba' => $this->t('0 — sin permanencia'),
      ],
      [
        'feature' => $this->t('Herramientas IA'),
        'diy' => FALSE,
        'agency' => FALSE,
        'prueba' => TRUE,
      ],
    ];

    $testimonials = [
      [
        'nombre' => 'Marcela Calabia',
        'rol' => $this->t('Coach de Comunicación'),
        'sector' => $this->t('Servicios profesionales'),
        'quote' => $this->t('Este curso es oro puro. Ninguno de los cursos de pago me ha dado lo que me dio este.'),
        'resultado' => $this->t('Libros en 4 idiomas, tarifa desde 75 €/h'),
        'foto' => 'testimonio-marcela.webp',
      ],
      [
        'nombre' => 'Cristina Martín',
        'rol' => $this->t('Fundadora De Cris Moda'),
        'sector' => $this->t('Comercio'),
        'quote' => $this->t('Hay un seguimiento real, que en muchos sitios se queda en el aire. Aquí funciona.'),
        'resultado' => $this->t('Tienda online con ventas en 6 países'),
        'foto' => 'testimonio-cristina.webp',
      ],
      [
        'nombre' => 'Adrián Capatina',
        'rol' => $this->t('Fundador NOVAVID Media'),
        'sector' => $this->t('Audiovisual'),
        'quote' => $this->t('La vida me ha cambiado. Todo lo que aprendí lo repartí a otros emprendedores.'),
        'resultado' => $this->t('Agencia audiovisual, clientes sector lujo'),
        'foto' => 'testimonio-adrian.webp',
      ],
    ];

    $stats = [
      ['value' => $tasaInsercion . '%', 'raw' => $tasaInsercion, 'suffix' => '%', 'label' => $this->t('inserción laboral')],
      ['value' => '30+', 'raw' => 30, 'suffix' => '+', 'label' => $this->t('años de experiencia')],
      ['value' => '0€', 'raw' => 0, 'suffix' => '€', 'label' => $this->t('coste para su negocio')],
      ['value' => '100', 'raw' => 100, 'suffix' => 'h', 'label' => $this->t('horas de programa')],
    ];

    $pricing_context = [
      ['service' => $this->t('Community manager'), 'market_price' => '300-600 €/mes', 'our_price' => '0 €'],
      ['service' => $this->t('Diseño web profesional'), 'market_price' => '1.500-5.000 €', 'our_price' => '0 €'],
      ['service' => $this->t('Gestión de reseñas'), 'market_price' => '150-400 €/mes', 'our_price' => '0 €'],
      ['service' => $this->t('Asistente administrativo'), 'market_price' => '800-1.200 €/mes', 'our_price' => '0 €'],
      ['service' => $this->t('Tienda online'), 'market_price' => '2.000-8.000 €', 'our_price' => '0 €'],
    ];

    $guarantees = [
      ['icon' => 'actions/check-circle', 'text' => $this->t('Sin permanencia')],
      ['icon' => 'actions/check-circle', 'text' => $this->t('Sin coste oculto')],
      ['icon' => 'actions/check-circle', 'text' => $this->t('Supervisado por expertos')],
      ['icon' => 'actions/check-circle', 'text' => $this->t('Datos protegidos (RGPD)')],
      ['icon' => 'actions/check-circle', 'text' => $this->t('+30 años de experiencia')],
    ];

    $faqs = [
      [
        'q' => $this->t('¿Es realmente gratis para mi negocio?'),
        'a' => $this->t('Sí, 100%. Es un programa público financiado por la Junta de Andalucía y la Unión Europea (FSE+). Su negocio recibe el servicio sin coste, sin letra pequeña.'),
      ],
      [
        'q' => $this->t('¿Qué pasa después de las 2-4 semanas?'),
        'a' => $this->t('Si queda satisfecho, puede continuar con un plan mensual asequible. Si no, no pasa nada. Sin compromiso ni permanencia. El contenido creado es suyo.'),
      ],
      [
        'q' => $this->t('¿Quién me atenderá exactamente?'),
        'a' => $this->t('Un profesional formado en servicios digitales e inteligencia artificial, supervisado por nuestro equipo de expertos con más de 30 años de experiencia.'),
      ],
      [
        'q' => $this->t('¿Es compatible con negocios muy pequeños?'),
        'a' => $this->t('Precisamente. Los negocios de 1-15 empleados son nuestro público ideal: suficientemente pequeños para no tener departamento de marketing, suficientemente grandes para beneficiarse del servicio.'),
      ],
      [
        'q' => $this->t('¿Cómo sé que el servicio será de calidad?'),
        'a' => $this->t('Cada proyecto está supervisado por formadores con experiencia real. Además, las herramientas de IA profesionales que usamos garantizan resultados de nivel agencia.'),
      ],
      [
        'q' => $this->t('¿En qué zonas de Andalucía dan servicio?'),
        'a' => $this->t('Actualmente en las provincias de Sevilla y Málaga. El servicio digital se puede prestar en remoto, pero priorizamos negocios accesibles para visitas presenciales.'),
      ],
    ];

    $urgency = [
      'mostrar_countdown' => (bool) ($config->get('mostrar_countdown') ?? TRUE),
      'fecha_limite' => $config->get('fecha_limite_solicitudes') ?? '2026-04-10',
    ];

    $program_info = [
      'entity' => $this->t('Plataforma de Ecosistemas Digitales S.L.'),
      'website' => 'plataformadeecosistemas.com',
      'funding' => $this->t('Programa cofinanciado por la Unión Europea · FSE+ Andalucía 2021-2027'),
    ];

    return [
      '#theme' => 'prueba_gratuita_landing',
      '#form_action' => $formAction,
      '#module_path' => $modulePath,
      '#stats' => $stats,
      '#services' => $services,
      '#pain_points' => $pain_points,
      '#comparison' => $comparison,
      '#testimonials' => $testimonials,
      '#faqs' => $faqs,
      '#urgency' => $urgency,
      '#program_info' => $program_info,
      '#pricing_context' => $pricing_context,
      '#guarantees' => $guarantees,
      '#show_success' => $showSuccess,
      '#attached' => [
        'library' => ['jaraba_andalucia_ei/prueba-gratuita'],
        'drupalSettings' => [
          'aeiPruebaGratuita' => [
            'showSuccess' => $showSuccess,
          ],
        ],
      ],
      '#cache' => [
        'contexts' => ['url.path', 'url.query_args:ok'],
        'tags' => ['config:jaraba_andalucia_ei.settings'],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Procesa el envío del formulario de lead de prueba gratuita.
   *
   * Crea una entidad NegocioProspectadoEi con los datos del formulario.
   * Incluye protección honeypot anti-spam y validación de provincia.
   * TENANT-001: Asigna tenant_id desde TenantContextService o default grupo 5.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La solicitud HTTP con los datos POST del formulario.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Redirección a la landing con parámetro ?ok=1 en caso de éxito,
   *   o 403 si se detecta un bot.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Si el campo honeypot no está vacío (bot detectado).
   */
  public function submit(Request $request): Response {
    // Honeypot anti-spam: campo oculto 'website' debe estar vacío.
    $honeypot = $request->request->get('website', '');
    if ($honeypot !== '') {
      $this->logger->warning('Bot detectado en prueba gratuita. Honeypot: @value, IP: @ip', [
        '@value' => $honeypot,
        '@ip' => $request->getClientIp(),
      ]);
      throw new AccessDeniedHttpException('Acceso denegado.');
    }

    // Validar campos requeridos.
    $nombre_negocio = trim((string) $request->request->get('nombre_negocio', ''));
    $persona_contacto = trim((string) $request->request->get('persona_contacto', ''));
    $telefono = trim((string) $request->request->get('telefono', ''));
    $email = trim((string) $request->request->get('email', ''));
    $provincia = trim((string) $request->request->get('provincia', ''));
    $sector = trim((string) $request->request->get('sector', ''));

    if ($nombre_negocio === '' || $persona_contacto === '' || $telefono === '' || $email === '' || $provincia === '' || $sector === '') {
      $this->logger->warning('Envío de prueba gratuita con campos requeridos vacíos desde IP @ip.', [
        '@ip' => $request->getClientIp(),
      ]);
      return $this->buildRedirect();
    }

    // Validar provincia (solo sevilla o malaga).
    $provincias_validas = ['sevilla', 'malaga'];
    if (!in_array($provincia, $provincias_validas, TRUE)) {
      $this->logger->warning('Provincia inválida en prueba gratuita: @prov', [
        '@prov' => $provincia,
      ]);
      return $this->buildRedirect();
    }

    // Campos opcionales.
    $municipio = trim((string) $request->request->get('municipio', ''));
    $web = trim((string) $request->request->get('web', ''));
    $problema = trim((string) $request->request->get('problema', ''));
    $rrss = trim((string) $request->request->get('rrss', ''));

    // Servicios seleccionados (array de checkboxes).
    $servicios = $request->request->all('servicio');
    $pack_compatible = count($servicios) > 0 ? json_encode(array_values($servicios), JSON_UNESCAPED_UNICODE) : '';

    // Clasificación de urgencia: rojo si no tiene web NI RRSS, amarillo por defecto.
    $clasificacion_urgencia = ($web === '' && $rrss === '') ? 'rojo' : 'amarillo';

    // TENANT-001: Resolver tenant_id.
    $tenant_id = NULL;
    if ($this->tenantContext !== NULL) {
      try {
        $tenant_id = $this->tenantContext->getCurrentTenantId();
      }
      catch (\Throwable $e) {
        $this->logger->notice('No se pudo resolver tenant para lead público: @msg', [
          '@msg' => $e->getMessage(),
        ]);
      }
    }
    // Default: grupo 5 (Andalucía +ei) si no se resuelve tenant.
    if ($tenant_id === NULL) {
      $tenant_id = 5;
    }

    try {
      /** @var \Drupal\jaraba_andalucia_ei\Entity\NegocioProspectadoEi $entity */
      $entity = $this->entityTypeManager
        ->getStorage('negocio_prospectado_ei')
        ->create([
          'nombre_negocio' => $nombre_negocio,
          'persona_contacto' => $persona_contacto,
          'telefono' => $telefono,
          'email' => $email,
          'provincia' => $provincia,
          'direccion' => $municipio,
          'sector' => $sector,
          'url_web' => $web,
          'pack_compatible' => $pack_compatible,
          'notas' => $problema,
          'clasificacion_urgencia' => $clasificacion_urgencia,
          'estado_embudo' => 'identificado',
          'tenant_id' => $tenant_id,
        ]);
      $entity->save();

      $this->logger->info('Lead prueba gratuita creado: @nombre (ID: @id, provincia: @prov, sector: @sector).', [
        '@nombre' => $nombre_negocio,
        '@id' => $entity->id(),
        '@prov' => $provincia,
        '@sector' => $sector,
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error al crear lead prueba gratuita: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }

    return $this->buildRedirect(['ok' => '1']);
  }

  /**
   * Construye la redirección a la landing de prueba gratuita.
   *
   * ROUTE-LANGPREFIX-001: URL construida via Url::fromRoute().
   *
   * @param array<string, string> $query
   *   Parámetros de query opcionales.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Respuesta de redirección 302.
   */
  protected function buildRedirect(array $query = []): RedirectResponse {
    $url = Url::fromRoute('jaraba_andalucia_ei.prueba_gratuita', [], [
      'query' => $query,
    ])->toString();

    return new RedirectResponse($url);
  }

}
