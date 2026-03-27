<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Landing de Certificación y Franquicia Digital del Método Jaraba.
 *
 * Ruta pública /certificacion orientada a 2 audiencias:
 * 1) Profesionales que quieren certificarse en Supervisión de Agentes IA.
 * 2) Entidades que quieren licenciar el Método Jaraba para sus programas.
 *
 * Formulario dual con campos condicionales según tipo de usuario.
 * Integración CRM via CopilotLeadCaptureService (opcional @?).
 *
 * @see docs/implementacion/20260327c-Plan_Implementacion_Metodo_Jaraba_SaaS_Clase_Mundial_v1_Claude.md
 */
class CertificacionLandingController extends ControllerBase {

  /**
   * CRM lead capture service (optional cross-module).
   */
  protected ?object $leadCaptureService = NULL;

  /**
   * Logger channel.
   */
  protected LoggerInterface $loggerChannel;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    if ($container->has('jaraba_copilot_v2.lead_capture')) {
      $instance->leadCaptureService = $container->get('jaraba_copilot_v2.lead_capture');
    }
    /** @var \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory */
    $loggerFactory = $container->get('logger.factory');
    $instance->loggerChannel = $loggerFactory->get('jaraba_certificacion');
    return $instance;
  }

  /**
   * Renders the certification/franchise landing page.
   *
   * @return array
   *   Render array with #theme 'certificacion_landing'.
   */
  /**
   * @return array<string, mixed>
   */
  public function landing(): array {
    return [
      '#theme' => 'certificacion_landing',
      '#hero' => $this->buildHero(),
      '#caminos' => $this->buildCaminos(),
      '#profesionales' => $this->buildProfesionales(),
      '#entidades' => $this->buildEntidades(),
      '#comparativa' => $this->buildComparativa(),
      '#faq' => $this->buildFaq(),
      '#trust' => $this->buildTrust(),
      '#form_config' => $this->buildFormConfig(),
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_theme/certificacion-landing',
        ],
      ],
      '#cache' => [
        'contexts' => ['url.path', 'languages:language_content'],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Procesa el envío del formulario de contacto.
   *
   * Endpoint POST /certificacion/enviar.
   * Honeypot anti-spam + CRM lead capture + email notificación.
   */
  public function submit(Request $request): Response {
    // Honeypot anti-spam: campo oculto 'website' debe estar vacío.
    $honeypot = $request->request->get('website', '');
    if ($honeypot !== '') {
      $this->loggerChannel->warning('Bot detectado en certificación. Honeypot: @value, IP: @ip', [
        '@value' => $honeypot,
        '@ip' => $request->getClientIp(),
      ]);
      throw new AccessDeniedHttpException('Acceso denegado.');
    }

    $tipo = $request->request->get('tipo', '');
    $email = $request->request->get('email', '');
    $nombre = $request->request->get('nombre', '');
    $telefono = $request->request->get('telefono', '');
    $rgpd = $request->request->get('rgpd', '');

    // Validación básica.
    if ($email === '' || $nombre === '' || $rgpd === '') {
      return new JsonResponse([
        'success' => FALSE,
        'message' => (string) $this->t('Por favor completa todos los campos obligatorios.'),
      ], 400);
    }

    // Construir contexto según tipo.
    $context = [
      'source' => 'certificacion_landing',
      'tipo_formulario' => $tipo,
      'nombre' => $nombre,
      'telefono' => $telefono,
    ];

    if ($tipo === 'profesional') {
      $context['situacion'] = $request->request->get('situacion', '');
      $context['aplicacion'] = $request->request->get('aplicacion', '');
      $context['provincia'] = $request->request->get('provincia', '');
      $context['como_conocio'] = $request->request->get('como_conocio', '');
      $context['opportunity_type'] = 'certificacion_profesional';
    }
    elseif ($tipo === 'entidad') {
      $context['entidad_nombre'] = $request->request->get('entidad_nombre', '');
      $context['entidad_tipo'] = $request->request->get('entidad_tipo', '');
      $context['cargo'] = $request->request->get('cargo', '');
      $context['programa'] = $request->request->get('programa', '');
      $context['num_participantes'] = $request->request->get('num_participantes', '');
      $context['provincia'] = $request->request->get('provincia', '');
      $context['opportunity_type'] = 'franquicia_entidad';
    }

    // CRM lead capture (opcional).
    $crmResult = ['created' => FALSE];
    if ($this->leadCaptureService) {
      try {
        $crmResult = $this->leadCaptureService->createCrmLead(
          $email,
          '__global__',
          $context,
        );
      }
      catch (\Throwable $e) {
        $this->loggerChannel->error('CRM lead capture error: @e', ['@e' => $e->getMessage()]);
      }
    }

    // Log del lead.
    $this->loggerChannel->info('Certificación lead recibido: @tipo @email (@nombre)', [
      '@tipo' => $tipo,
      '@email' => $email,
      '@nombre' => $nombre,
    ]);

    // Email de notificación a José (via hook_mail si disponible).
    $this->sendNotificationEmail($context, $email);

    return new JsonResponse([
      'success' => TRUE,
      'message' => (string) $this->t('¡Gracias! Nos pondremos en contacto contigo en menos de 24 horas.'),
      'crm_created' => $crmResult['created'] ?? FALSE,
    ]);
  }

  /**
   * Sección 1: Hero.
   */
  /**
   * @return array<string, mixed>
   */
  protected function buildHero(): array {
    return [
      'title' => $this->t('Certifícate en Supervisión de Agentes IA'),
      'subtitle' => $this->t('El Método Jaraba forma profesionales que saben dirigir IA para generar impacto. Certifica tu competencia.'),
      'cta_profesional' => [
        'text' => $this->t('Soy profesional'),
        'url' => '#profesionales',
        'track' => 'cert_hero_profesional',
      ],
      'cta_entidad' => [
        'text' => $this->t('Represento a una entidad'),
        'url' => '#entidades',
        'track' => 'cert_hero_entidad',
      ],
    ];
  }

  /**
   * Sección 2: 2 caminos (split visual).
   */
  /**
   * @return array<string, mixed>
   */
  protected function buildCaminos(): array {
    return [
      'profesional' => [
        'title' => $this->t('Soy profesional'),
        'subtitle' => $this->t('Quiero certificarme'),
        'description' => $this->t('Demuestra que sabes supervisar agentes IA con resultados medibles. Portfolio, rúbrica y certificado digital.'),
        'icon' => ['category' => 'users', 'name' => 'group'],
        'url' => '#profesionales',
      ],
      'entidad' => [
        'title' => $this->t('Represento a una entidad'),
        'subtitle' => $this->t('Quiero licenciar el método'),
        'description' => $this->t('Replica ecosistemas de impacto digital en tu territorio. Plataforma + formación + acompañamiento.'),
        'icon' => ['category' => 'business', 'name' => 'building'],
        'url' => '#entidades',
      ],
    ];
  }

  /**
   * Sección 3: Para profesionales — rúbrica 4 niveles.
   */
  /**
   * @return array<string, mixed>
   */
  protected function buildProfesionales(): array {
    return [
      'title' => $this->t('Certificación para profesionales'),
      'rubrica' => [
        [
          'level' => 1,
          'name' => $this->t('Novel'),
          'description' => $this->t('Conoce las herramientas básicas de IA. Sabe formular instrucciones simples. Necesita guía para evaluar resultados.'),
          'indicators' => [
            $this->t('Formula prompts básicos'),
            $this->t('Identifica errores evidentes en outputs'),
            $this->t('Usa una herramienta IA con asistencia'),
          ],
        ],
        [
          'level' => 2,
          'name' => $this->t('Aprendiz'),
          'description' => $this->t('Formula instrucciones claras. Evalúa outputs con criterio propio. Itera para mejorar resultados.'),
          'indicators' => [
            $this->t('Diseña prompts estructurados con contexto'),
            $this->t('Detecta sesgos y errores no obvios'),
            $this->t('Combina 2+ herramientas IA en una tarea'),
          ],
        ],
        [
          'level' => 3,
          'name' => $this->t('Competente'),
          'description' => $this->t('Integra múltiples agentes IA. Genera outputs profesionales publicables. Tiene portfolio demostrable.'),
          'indicators' => [
            $this->t('Crea flujos multi-agente con supervisión'),
            $this->t('Portfolio con 5+ outputs profesionales'),
            $this->t('Aplica las 4 competencias de forma autónoma'),
          ],
        ],
        [
          'level' => 4,
          'name' => $this->t('Autónomo'),
          'description' => $this->t('Diseña sistemas de supervisión IA para terceros. Genera ingresos con la competencia. Puede formar a otros.'),
          'indicators' => [
            $this->t('Diseña workflows IA para clientes'),
            $this->t('Factura por servicios de supervisión IA'),
            $this->t('Forma y mentoriza a otros supervisores'),
          ],
        ],
      ],
      'tipos' => [
        [
          'name' => $this->t('Profesional'),
          'nivel_min' => 2,
          'description' => $this->t('Dominas las 4 competencias. Tienes portfolio.'),
        ],
        [
          'name' => $this->t('Especialista'),
          'nivel_min' => 3,
          'description' => $this->t('Aplicas el método de forma autónoma con resultados.'),
        ],
        [
          'name' => $this->t('Formador'),
          'nivel_min' => 4,
          'description' => $this->t('Puedes formar y evaluar a otros profesionales.'),
        ],
      ],
    ];
  }

  /**
   * Sección 4: Para entidades — licencia del método.
   */
  /**
   * @return array<string, mixed>
   */
  protected function buildEntidades(): array {
    return [
      'title' => $this->t('Licencie el Método Jaraba para sus programas'),
      'subtitle' => $this->t('Infraestructura digital + metodología probada + acompañamiento para programas de empleo, emprendimiento y desarrollo local.'),
      'componentes' => [
        $this->t('Plataforma SaaS multi-tenant con 10 verticales'),
        $this->t('Metodología certificada con rúbrica de evaluación'),
        $this->t('11 agentes IA integrados para participantes'),
        $this->t('Dashboard de impacto con indicadores verificables'),
        $this->t('Formación de formadores (Train-the-Trainer)'),
        $this->t('Soporte técnico y acompañamiento pedagógico'),
        $this->t('Marca blanca configurable por territorio'),
      ],
      'stat_value' => '46',
      'stat_suffix' => '%',
      'stat_label' => $this->t('de inserción laboral en la 1ª Edición'),
      'caso' => $this->t('Programa Andalucía +ei — Junta de Andalucía, fondos FSE+'),
    ];
  }

  /**
   * Sección 5: Comparativa.
   */
  /**
   * @return array<string, mixed>
   */
  protected function buildComparativa(): array {
    return [
      'title' => $this->t('¿Por qué el Método Jaraba?'),
      'columns' => [
        'generic' => $this->t('Formación genérica en IA'),
        'metodo' => $this->t('Método Jaraba'),
      ],
      'rows' => [
        [
          'feature' => $this->t('Certificación con rúbrica verificable'),
          'generic' => FALSE,
          'metodo' => TRUE,
        ],
        [
          'feature' => $this->t('Portfolio de evidencias profesionales'),
          'generic' => FALSE,
          'metodo' => TRUE,
        ],
        [
          'feature' => $this->t('Plataforma SaaS integrada'),
          'generic' => FALSE,
          'metodo' => TRUE,
        ],
        [
          'feature' => $this->t('11 agentes IA especializados'),
          'generic' => FALSE,
          'metodo' => TRUE,
        ],
        [
          'feature' => $this->t('Resultados medibles (46% inserción)'),
          'generic' => FALSE,
          'metodo' => TRUE,
        ],
        [
          'feature' => $this->t('Replicable por territorio'),
          'generic' => FALSE,
          'metodo' => TRUE,
        ],
        [
          'feature' => $this->t('Teoría sobre IA'),
          'generic' => TRUE,
          'metodo' => TRUE,
        ],
        [
          'feature' => $this->t('Certificado de asistencia'),
          'generic' => TRUE,
          'metodo' => TRUE,
        ],
      ],
    ];
  }

  /**
   * Sección 6: FAQ con Schema.org FAQPage.
   */
  /**
   * @return array<string, mixed>
   */
  protected function buildFaq(): array {
    return [
      'title' => $this->t('Preguntas frecuentes'),
      'items' => [
        [
          'question' => $this->t('¿Necesito conocimientos previos de IA?'),
          'answer' => $this->t('No. El método empieza desde cero. Lo importante es tu criterio profesional y tus ganas de aprender a dirigir agentes IA, no tu experiencia técnica previa.'),
        ],
        [
          'question' => $this->t('¿Cuánto dura la certificación?'),
          'answer' => $this->t('El Ciclo de Impacto Digital dura 90 días. La evaluación del portfolio se realiza al finalizar. El certificado es válido durante 2 años con opción de renovación.'),
        ],
        [
          'question' => $this->t('¿Es online o presencial?'),
          'answer' => $this->t('El método es blended: la plataforma y los agentes IA están disponibles 24/7 online. Las sesiones de acompañamiento pueden ser presenciales u online según el programa.'),
        ],
        [
          'question' => $this->t('¿Qué plataforma se usa?'),
          'answer' => $this->t('La Plataforma de Ecosistemas Digitales: un SaaS con 10 verticales, 11 agentes IA, CRM, facturación, LMS, page builder y más. Todo integrado en un solo acceso.'),
        ],
        [
          'question' => $this->t('¿Cuánto cuesta la certificación?'),
          'answer' => $this->t('Depende del tipo de certificación y del programa. Para participantes de programas públicos (FSE+, PIIL) es gratuita. Para profesionales independientes, consulta nuestro equipo.'),
        ],
        [
          'question' => $this->t('¿Qué validez tiene el certificado?'),
          'answer' => $this->t('El certificado es emitido por Plataforma de Ecosistemas Digitales S.L. con estándar Open Badge 3.0 verificable digitalmente. Validez: 2 años con renovación.'),
        ],
        [
          'question' => $this->t('¿Cómo funciona la licencia para entidades?'),
          'answer' => $this->t('Setup inicial + suscripción SaaS mensual + royalty por participante certificado. Incluye formación de formadores, marca blanca y soporte técnico. Cada territorio tiene exclusividad.'),
        ],
      ],
    ];
  }

  /**
   * Sección 8: Trust signals.
   */
  /**
   * @return array<string, mixed>
   */
  protected function buildTrust(): array {
    return [
      'stat_value' => '46',
      'stat_suffix' => '%',
      'stat_label' => $this->t('inserción laboral'),
      'stat_source' => $this->t('Programa Andalucía +ei, 1ª Edición. Colectivos vulnerables.'),
      'quote_text' => $this->t('Primera implementación del Método Jaraba: Programa Andalucía +ei, Junta de Andalucía, fondos FSE+.'),
      'trust_logos' => TRUE,
    ];
  }

  /**
   * Configuración del formulario de contacto.
   */
  /**
   * @return array<string, mixed>
   */
  protected function buildFormConfig(): array {
    $submitUrl = '';
    try {
      $submitUrl = Url::fromRoute('jaraba_page_builder.certificacion_submit')->toString();
    }
    catch (\Exception $e) {
      $submitUrl = '/certificacion/enviar';
    }

    return [
      'submit_url' => $submitUrl,
      'provincias' => $this->getProvincias(),
      'situaciones' => [
        'empleado' => $this->t('Empleado/a'),
        'desempleado' => $this->t('Desempleado/a'),
        'emprendiendo' => $this->t('Emprendiendo'),
        'autonomo' => $this->t('Autónomo/a'),
        'otro' => $this->t('Otro'),
      ],
      'aplicaciones' => [
        'empleabilidad' => $this->t('Empleabilidad'),
        'emprendimiento' => $this->t('Emprendimiento'),
        'digitalizacion' => $this->t('Digitalización'),
        'no_claro' => $this->t('No lo tengo claro'),
      ],
      'como_conocio' => [
        'rrss' => $this->t('Redes sociales'),
        'google' => $this->t('Búsqueda en Google'),
        'recomendacion' => $this->t('Recomendación'),
        'programa_publico' => $this->t('Programa público'),
        'otro' => $this->t('Otro'),
      ],
      'tipos_entidad' => [
        'colaboradora_pil' => $this->t('Entidad colaboradora PIL'),
        'fundacion' => $this->t('Fundación'),
        'camara_comercio' => $this->t('Cámara de Comercio'),
        'ayuntamiento' => $this->t('Ayuntamiento'),
        'diputacion' => $this->t('Diputación'),
        'otro' => $this->t('Otro'),
      ],
      'num_participantes' => [
        'lt25' => $this->t('Menos de 25'),
        '25_50' => '25-50',
        '50_100' => '50-100',
        '100_200' => '100-200',
        'gt200' => $this->t('Más de 200'),
      ],
    ];
  }

  /**
   * Envía email de notificación al equipo.
   */
  /**
   * @param array<string, mixed> $context
   */
  protected function sendNotificationEmail(array $context, string $email): void {
    try {
      $mailManager = \Drupal::service('plugin.manager.mail');
      $params = [
        'subject' => 'Nuevo lead de certificación: ' . ($context['tipo_formulario'] ?? 'desconocido'),
        'body' => [
          'Tipo: ' . ($context['tipo_formulario'] ?? ''),
          'Nombre: ' . ($context['nombre'] ?? ''),
          'Email: ' . $email,
          'Teléfono: ' . ($context['telefono'] ?? ''),
          'Provincia: ' . ($context['provincia'] ?? ''),
        ],
      ];
      $siteMail = \Drupal::config('system.site')->get('mail');
      $mailManager->mail('jaraba_page_builder', 'certificacion_lead', $siteMail, 'es', $params);
    }
    catch (\Throwable $e) {
      $this->loggerChannel->warning('Email de notificación no enviado: @e', ['@e' => $e->getMessage()]);
    }
  }

  /**
   * Lista de provincias de España.
   */
  /**
   * @return array<string, string>
   */
  protected function getProvincias(): array {
    return [
      'alava' => 'Álava', 'albacete' => 'Albacete', 'alicante' => 'Alicante',
      'almeria' => 'Almería', 'asturias' => 'Asturias', 'avila' => 'Ávila',
      'badajoz' => 'Badajoz', 'barcelona' => 'Barcelona', 'burgos' => 'Burgos',
      'caceres' => 'Cáceres', 'cadiz' => 'Cádiz', 'cantabria' => 'Cantabria',
      'castellon' => 'Castellón', 'ciudad_real' => 'Ciudad Real', 'cordoba' => 'Córdoba',
      'coruna' => 'A Coruña', 'cuenca' => 'Cuenca', 'girona' => 'Girona',
      'granada' => 'Granada', 'guadalajara' => 'Guadalajara', 'guipuzcoa' => 'Guipúzcoa',
      'huelva' => 'Huelva', 'huesca' => 'Huesca', 'illes_balears' => 'Illes Balears',
      'jaen' => 'Jaén', 'leon' => 'León', 'lleida' => 'Lleida',
      'lugo' => 'Lugo', 'madrid' => 'Madrid', 'malaga' => 'Málaga',
      'murcia' => 'Murcia', 'navarra' => 'Navarra', 'ourense' => 'Ourense',
      'palencia' => 'Palencia', 'las_palmas' => 'Las Palmas', 'pontevedra' => 'Pontevedra',
      'la_rioja' => 'La Rioja', 'salamanca' => 'Salamanca', 'sc_tenerife' => 'S.C. Tenerife',
      'segovia' => 'Segovia', 'sevilla' => 'Sevilla', 'soria' => 'Soria',
      'tarragona' => 'Tarragona', 'teruel' => 'Teruel', 'toledo' => 'Toledo',
      'valencia' => 'Valencia', 'valladolid' => 'Valladolid', 'vizcaya' => 'Vizcaya',
      'zamora' => 'Zamora', 'zaragoza' => 'Zaragoza', 'ceuta' => 'Ceuta',
      'melilla' => 'Melilla',
    ];
  }

}
