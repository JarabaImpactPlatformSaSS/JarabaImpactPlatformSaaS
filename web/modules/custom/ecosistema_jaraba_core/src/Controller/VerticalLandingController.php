<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\MetaSitePricingService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controlador para landing pages de los verticales.
 *
 * Cada vertical tiene su propia landing page publica con 9 secciones:
 * Hero, Pain Points, Solution Steps, Features Grid, Social Proof,
 * Lead Magnet, Pricing Preview, FAQ (Schema.org), Final CTA.
 *
 * Rutas legacy: /empleo, /talento, /emprender, /comercio, /instituciones
 * Rutas F4:     /agroconecta, /comercioconecta, /serviciosconecta,
 *               /empleabilidad, /emprendimiento
 *
 * @see docs/implementacion/2026-02-12_F4_Landing_Pages_Verticales_Doc180_Implementacion.md
 */
class VerticalLandingController extends ControllerBase
{

  /**
   * The MetaSite pricing service.
   *
   * Provides dynamic pricing from SaasPlanTier/Features ConfigEntities.
   * Optional: if null, fallback hardcoded pricing is used.
   */
  protected ?MetaSitePricingService $pricingService = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static
  {
    $instance = parent::create($container);
    // Optional injection: doesn't break if service not registered.
    if ($container->has('ecosistema_jaraba_core.metasite_pricing')) {
      $instance->pricingService = $container->get('ecosistema_jaraba_core.metasite_pricing');
    }
    return $instance;
  }

  // =========================================================================
  // F4 LANDING PAGES — 9 secciones completas (Doc 180)
  // =========================================================================

  /**
   * Landing AgroConecta — Productores rurales.
   *
   * Ruta: /agroconecta
   */
  public function agroconecta(): array
  {
    return $this->buildLanding([
      'key' => 'agroconecta',
      'color' => 'agro',
      'hero' => [
        'headline' => $this->t('Vende tus productos del campo sin intermediarios'),
        'subheadline' => $this->t('Tu tienda online en 10 minutos. Cobra directamente. Sin comisiones ocultas.'),
        'icon' => ['category' => 'verticals', 'name' => 'leaf'],
        'cta' => [
          'text' => $this->t('Crea tu tienda gratis'),
          'url' => Url::fromRoute('user.register')->toString(),
        ],
        'cta_secondary' => [
          'text' => $this->t('Ya tengo cuenta'),
          'url' => Url::fromRoute('user.login')->toString(),
        ],
      ],
      'pain_points' => [
        ['icon' => ['category' => 'business', 'name' => 'money'], 'text' => $this->t('Los intermediarios se quedan con el 40% de tu margen')],
        ['icon' => ['category' => 'ui', 'name' => 'clock'], 'text' => $this->t('No tienes tiempo para gestionar una web complicada')],
        ['icon' => ['category' => 'ui', 'name' => 'eye-off'], 'text' => $this->t('Tus clientes no saben que existes')],
        ['icon' => ['category' => 'business', 'name' => 'receipt'], 'text' => $this->t('Cobrar es un lío: transferencias, efectivo, recibos...')],
      ],
      'steps' => [
        ['title' => $this->t('Sube tus productos'), 'description' => $this->t('Con una foto, la IA escribe la descripción')],
        ['title' => $this->t('Comparte tu tienda'), 'description' => $this->t('Un link, un QR, redes sociales')],
        ['title' => $this->t('Cobra al instante'), 'description' => $this->t('El dinero llega a tu cuenta en 48h')],
      ],
      'features' => [
        ['icon' => ['category' => 'ai', 'name' => 'screening'], 'title' => $this->t('Producer Copilot (IA)'), 'description' => $this->t('Escribe descripciones atractivas de tus productos automáticamente')],
        ['icon' => ['category' => 'business', 'name' => 'qr-code'], 'title' => $this->t('QR de Trazabilidad'), 'description' => $this->t('Tus clientes escanean y ven de dónde viene cada producto')],
        ['icon' => ['category' => 'business', 'name' => 'package'], 'title' => $this->t('Gestión de Stock'), 'description' => $this->t('Actualiza disponibilidad desde el móvil mientras trabajas')],
        ['icon' => ['category' => 'ui', 'name' => 'phone'], 'title' => $this->t('Pedidos WhatsApp'), 'description' => $this->t('Recibe notificaciones de pedidos donde ya estás')],
        ['icon' => ['category' => 'business', 'name' => 'money'], 'title' => $this->t('Cobro Seguro'), 'description' => $this->t('Stripe procesa pagos. Sin preocuparte de fraudes')],
        ['icon' => ['category' => 'business', 'name' => 'achievement'], 'title' => $this->t('Certificaciones'), 'description' => $this->t('Muestra tus sellos eco, denominación de origen, etc.')],
      ],
      'social_proof' => [
        'testimonials' => [
          ['quote' => $this->t('Antes vendía solo en el mercado del pueblo. Ahora envío a toda España y facturo 3x más.'), 'author' => 'Antonio', 'role' => $this->t('olivarero en Jaén')],
          ['quote' => $this->t('La IA me escribió las descripciones mejor de lo que yo podría. En una tarde tenía 20 productos online.'), 'author' => 'María', 'role' => $this->t('quesera en Extremadura')],
        ],
        'metrics' => [
          ['value' => '3x', 'label' => $this->t('más ingresos promedio')],
          ['value' => '500+', 'label' => $this->t('productores conectados')],
        ],
      ],
      'lead_magnet' => [
        'title' => $this->t('Guía: Vende Online sin Intermediarios'),
        'description' => $this->t('Descarga inmediata en PDF con estrategias probadas.'),
        'url' => '/agroconecta/guia-vende-online',
        'cta_text' => $this->t('Descargar guía gratis'),
        'icon' => ['category' => 'ui', 'name' => 'download'],
      ],
      'pricing' => [
        'headline' => $this->t('Planes para productores'),
        'from_price' => '0',
        'currency' => 'EUR',
        'period' => $this->t('mes'),
        'cta_text' => $this->t('Ver todos los planes'),
        'cta_url' => '/planes',
        'features_preview' => [
          $this->t('5 productos gratis'),
          $this->t('IA para descripciones'),
          $this->t('QR de trazabilidad'),
          $this->t('Cobro via Stripe'),
        ],
      ],
      'faq' => [
        ['question' => $this->t('¿Cuánto tiempo tarda en estar operativo?'), 'answer' => $this->t('Tu tienda online estará lista en 10 minutos. Sube productos, configura pagos y ¡listo!')],
        ['question' => $this->t('¿Cuáles son las comisiones?'), 'answer' => $this->t('En el plan Free 10%, en Starter+ de 5-8% según volumen. Transparente, sin comisiones ocultas.')],
        ['question' => $this->t('¿Necesito conocimientos técnicos?'), 'answer' => $this->t('No, está diseñado para productores sin experiencia en web. La IA te ayuda en cada paso.')],
        ['question' => $this->t('¿Cómo recibo los pagos?'), 'answer' => $this->t('A través de Stripe. El dinero llega a tu cuenta bancaria en 48h, sin intermediarios.')],
        ['question' => $this->t('¿Puedo usar mi propio dominio?'), 'answer' => $this->t('Sí, en el plan Pro+ tienes dominio personalizado. En Free/Starter es un subdominio.')],
      ],
      'final_cta' => [
        'headline' => $this->t('¿Listo para vender sin intermediarios?'),
        'cta' => [
          'text' => $this->t('Crea tu tienda gratis ahora'),
          'url' => Url::fromRoute('user.register')->toString(),
        ],
      ],
    ]);
  }

  /**
   * Landing ComercioConecta — Comercios de proximidad.
   *
   * Ruta: /comercioconecta
   */
  public function comercioconecta(): array
  {
    return $this->buildLanding([
      'key' => 'comercioconecta',
      'color' => 'success',
      'hero' => [
        'headline' => $this->t('Tu tienda de barrio, ahora también en el móvil de tus clientes'),
        'subheadline' => $this->t('Ofertas flash, click & collect, pedidos online. Todo integrado con tu TPV.'),
        'icon' => ['category' => 'business', 'name' => 'storefront'],
        'cta' => [
          'text' => $this->t('Digitaliza tu comercio'),
          'url' => Url::fromRoute('user.register')->toString(),
        ],
        'cta_secondary' => [
          'text' => $this->t('Ya tengo cuenta'),
          'url' => Url::fromRoute('user.login')->toString(),
        ],
      ],
      'pain_points' => [
        ['icon' => ['category' => 'business', 'name' => 'cart'], 'text' => $this->t('Las grandes superficies se llevan a tus clientes')],
        ['icon' => ['category' => 'ui', 'name' => 'search'], 'text' => $this->t('Tu tienda no aparece en Google cuando buscan cerca')],
        ['icon' => ['category' => 'business', 'name' => 'trending-down'], 'text' => $this->t('No puedes competir con el ecommerce de los grandes')],
        ['icon' => ['category' => 'ui', 'name' => 'clock'], 'text' => $this->t('Gestionar redes sociales te quita tiempo de atender')],
      ],
      'steps' => [
        ['title' => $this->t('Configura tu tienda online'), 'description' => $this->t('Sincroniza con tu TPV en minutos')],
        ['title' => $this->t('Crea ofertas flash'), 'description' => $this->t('Descuentos inteligentes para productos estratégicos')],
        ['title' => $this->t('Vende 24/7'), 'description' => $this->t('Incluso cuando la tienda está cerrada')],
      ],
      'features' => [
        ['icon' => ['category' => 'business', 'name' => 'lightning'], 'title' => $this->t('Ofertas Flash'), 'description' => $this->t('Crea descuentos de última hora para productos que caducan')],
        ['icon' => ['category' => 'business', 'name' => 'qr-code'], 'title' => $this->t('QR de Escaparate'), 'description' => $this->t('Clientes ven precios y compran aunque esté cerrado')],
        ['icon' => ['category' => 'business', 'name' => 'package'], 'title' => $this->t('Click & Collect'), 'description' => $this->t('Reservan online, recogen en tienda')],
        ['icon' => ['category' => 'business', 'name' => 'sync'], 'title' => $this->t('Integración TPV'), 'description' => $this->t('Sync con tu sistema de caja actual')],
        ['icon' => ['category' => 'ui', 'name' => 'search'], 'title' => $this->t('SEO Local Automático'), 'description' => $this->t('Apareces en "tiendas cerca de mí"')],
        ['icon' => ['category' => 'business', 'name' => 'achievement'], 'title' => $this->t('Programa de Fidelización'), 'description' => $this->t('Puntos por compra que retienen clientes')],
      ],
      'social_proof' => [
        'testimonials' => [
          ['quote' => $this->t('Desde que puse el QR en el escaparate, vendo incluso cuando cierro a mediodía.'), 'author' => 'Laura', 'role' => $this->t('librería en Zaragoza')],
          ['quote' => $this->t('Las ofertas flash me han solucionado el problema de productos a punto de caducar.'), 'author' => 'Pedro', 'role' => $this->t('frutería en Barcelona')],
        ],
        'metrics' => [
          ['value' => '2,500+', 'label' => $this->t('comercios digitalizados')],
          ['value' => '40%', 'label' => $this->t('aumento de ventas promedio')],
        ],
      ],
      'lead_magnet' => [
        'title' => $this->t('Auditoría SEO Local Gratuita'),
        'description' => $this->t('Descubre cómo te encuentran tus clientes online. Análisis en menos de 2 minutos.'),
        'url' => '/comercioconecta/auditoria-seo',
        'cta_text' => $this->t('Hacer auditoría gratuita'),
        'icon' => ['category' => 'ui', 'name' => 'search'],
      ],
      'pricing' => [
        'headline' => $this->t('Planes para comercios'),
        'from_price' => '0',
        'currency' => 'EUR',
        'period' => $this->t('mes'),
        'cta_text' => $this->t('Ver todos los planes'),
        'cta_url' => '/planes',
        'features_preview' => [
          $this->t('10 productos gratis'),
          $this->t('QR de escaparate'),
          $this->t('Ofertas flash'),
          $this->t('SEO local automático'),
        ],
      ],
      'faq' => [
        ['question' => $this->t('¿Necesito cambiar mi TPV?'), 'answer' => $this->t('No, nos integramos con los TPV más comunes. Tu sistema actual sigue funcionando.')],
        ['question' => $this->t('¿Cómo ayuda el QR de escaparate?'), 'answer' => $this->t('Tus clientes lo escanean con el móvil y ven precios y disponibilidad. ¡Ventas aunque estés cerrado!')],
        ['question' => $this->t('¿Aparezco en Google Maps?'), 'answer' => $this->t('Sí, nuestro SEO local automático te posiciona en búsquedas geográficas como "tiendas cerca de mí".')],
        ['question' => $this->t('¿Puedo hacer ofertas flash?'), 'answer' => $this->t('Claro, crea descuentos temporales que generan urgencia. Ideal para liquidar stock.')],
        ['question' => $this->t('¿Cómo se sincroniza con mi caja?'), 'answer' => $this->t('Automáticamente. Cada venta online se registra en tu TPV, sin duplicados ni errores.')],
      ],
      'final_cta' => [
        'headline' => $this->t('Lleva tu comercio al siguiente nivel'),
        'cta' => [
          'text' => $this->t('Digitaliza tu comercio hoy'),
          'url' => Url::fromRoute('user.register')->toString(),
        ],
      ],
    ]);
  }

  /**
   * Landing ServiciosConecta — Profesionales liberales.
   *
   * Ruta: /serviciosconecta
   */
  public function serviciosconecta(): array
  {
    return $this->buildLanding([
      'key' => 'serviciosconecta',
      'color' => 'innovation',
      'hero' => [
        'headline' => $this->t('Más clientes, menos papeleo. Tu consulta digitalizada.'),
        'subheadline' => $this->t('Agenda online, videollamadas, cobro automático, firma digital. Todo en un solo lugar.'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'cta' => [
          'text' => $this->t('Profesionaliza tu servicio'),
          'url' => Url::fromRoute('user.register')->toString(),
        ],
        'cta_secondary' => [
          'text' => $this->t('Ya tengo cuenta'),
          'url' => Url::fromRoute('user.login')->toString(),
        ],
      ],
      'pain_points' => [
        ['icon' => ['category' => 'ui', 'name' => 'phone'], 'text' => $this->t('Gestionar citas por teléfono consume tu tiempo productivo')],
        ['icon' => ['category' => 'ui', 'name' => 'clock'], 'text' => $this->t('Tus clientes no pueden reservar fuera de horario')],
        ['icon' => ['category' => 'business', 'name' => 'receipt'], 'text' => $this->t('Cobrar y facturar manualmente es propenso a errores')],
        ['icon' => ['category' => 'ui', 'name' => 'lock'], 'text' => $this->t('No tienes forma segura de intercambiar documentos')],
      ],
      'steps' => [
        ['title' => $this->t('Configura tu agenda'), 'description' => $this->t('Define tus horarios y tipos de servicio')],
        ['title' => $this->t('Tus clientes reservan online'), 'description' => $this->t('24/7 sin llamarte, con confirmación automática')],
        ['title' => $this->t('Cobra y factura al instante'), 'description' => $this->t('Sin papeleos, todo digital y legal')],
      ],
      'features' => [
        ['icon' => ['category' => 'ui', 'name' => 'calendar'], 'title' => $this->t('Booking Engine'), 'description' => $this->t('Tus clientes reservan 24/7 sin llamarte')],
        ['icon' => ['category' => 'ui', 'name' => 'video'], 'title' => $this->t('Videoconsulta Integrada'), 'description' => $this->t('Jitsi Meet sin salir de la plataforma')],
        ['icon' => ['category' => 'business', 'name' => 'signature'], 'title' => $this->t('Firma Digital PAdES'), 'description' => $this->t('Contratos con validez legal')],
        ['icon' => ['category' => 'ui', 'name' => 'lock'], 'title' => $this->t('Buzón de Confianza'), 'description' => $this->t('Intercambio seguro de documentos')],
        ['icon' => ['category' => 'ai', 'name' => 'screening'], 'title' => $this->t('Presupuestador Automático'), 'description' => $this->t('IA genera presupuestos personalizados')],
        ['icon' => ['category' => 'business', 'name' => 'receipt'], 'title' => $this->t('Facturación Automática'), 'description' => $this->t('Emite facturas al confirmar el servicio')],
      ],
      'social_proof' => [
        'testimonials' => [
          ['quote' => $this->t('Antes perdía 2 horas al día gestionando citas por teléfono. Ahora mis clientes reservan solos.'), 'author' => 'Carmen', 'role' => $this->t('fisioterapeuta en Madrid')],
          ['quote' => $this->t('La firma digital me ahorra viajes. Mis clientes firman contratos desde casa con validez legal.'), 'author' => 'Javier', 'role' => $this->t('abogado en Valencia')],
        ],
        'metrics' => [
          ['value' => '95%', 'label' => $this->t('satisfacción de clientes')],
          ['value' => '60%', 'label' => $this->t('más reservas con agenda online')],
        ],
      ],
      'lead_magnet' => [
        'title' => $this->t('Template: Propuesta Profesional'),
        'description' => $this->t('Descarga inmediata. Documento editable listo para usar con tus clientes.'),
        'url' => '/serviciosconecta/template-propuesta',
        'cta_text' => $this->t('Descargar template'),
        'icon' => ['category' => 'ui', 'name' => 'download'],
      ],
      'pricing' => [
        'headline' => $this->t('Planes para profesionales'),
        'from_price' => '0',
        'currency' => 'EUR',
        'period' => $this->t('mes'),
        'cta_text' => $this->t('Ver todos los planes'),
        'cta_url' => '/planes',
        'features_preview' => [
          $this->t('3 servicios publicados gratis'),
          $this->t('10 reservas/mes'),
          $this->t('Agenda online 24/7'),
          $this->t('Cobro via Stripe'),
        ],
      ],
      'faq' => [
        ['question' => $this->t('¿Puedo hacer videollamadas con mis clientes?'), 'answer' => $this->t('Sí, está integrada. Presiona un botón y conecta vía Jitsi Meet, sin apps externas.')],
        ['question' => $this->t('¿Cómo funciona la firma digital?'), 'answer' => $this->t('Genera contratos legales con tu firma digital PAdES. Válido ante notarios y administraciones.')],
        ['question' => $this->t('¿Mis clientes ven mis horarios disponibles?'), 'answer' => $this->t('Sí, visualizan tu agenda en tiempo real y reservan solo slots libres. Confirmación automática.')],
        ['question' => $this->t('¿Se factura automáticamente?'), 'answer' => $this->t('Al confirmar el servicio, se emite factura automáticamente con serie fiscal legal.')],
        ['question' => $this->t('¿Es seguro el buzón de documentos?'), 'answer' => $this->t('Totalmente. Cifrado end-to-end, cumple RGPD, y hay rastro de auditoría.')],
      ],
      'final_cta' => [
        'headline' => $this->t('Profesionaliza tu servicio hoy mismo'),
        'cta' => [
          'text' => $this->t('Empieza tu prueba gratuita'),
          'url' => Url::fromRoute('user.register')->toString(),
        ],
      ],
    ]);
  }

  /**
   * Landing Empleabilidad — Buscadores de empleo.
   *
   * Ruta: /empleabilidad
   */
  public function empleabilidad(): array
  {
    return $this->buildLanding([
      'key' => 'empleabilidad',
      'color' => 'innovation',
      'hero' => [
        'headline' => $this->t('Nunca es tarde para reinventarte profesionalmente'),
        'subheadline' => $this->t('Formación práctica, CV que destaca, ofertas que encajan contigo. Con ayuda de IA y mentores reales.'),
        'icon' => ['category' => 'verticals', 'name' => 'briefcase'],
        'cta' => [
          'text' => $this->t('Haz el diagnóstico gratuito'),
          'url' => '/empleabilidad/diagnostico',
        ],
        'cta_secondary' => [
          'text' => $this->t('Ya tengo cuenta'),
          'url' => Url::fromRoute('user.login')->toString(),
        ],
      ],
      'pain_points' => [
        ['icon' => ['category' => 'ui', 'name' => 'help-circle'], 'text' => $this->t('No sabes por dónde empezar en tu búsqueda de empleo')],
        ['icon' => ['category' => 'business', 'name' => 'cv-optimized'], 'text' => $this->t('Tu CV no destaca entre cientos de candidatos')],
        ['icon' => ['category' => 'business', 'name' => 'target'], 'text' => $this->t('Las ofertas no encajan con tus habilidades reales')],
        ['icon' => ['category' => 'ui', 'name' => 'alert-circle'], 'text' => $this->t('Tienes miedo de la entrevista después de tanto tiempo')],
      ],
      'steps' => [
        ['title' => $this->t('Haz tu diagnóstico'), 'description' => $this->t('En 3 minutos sabes por dónde empezar')],
        ['title' => $this->t('Aprende y practica'), 'description' => $this->t('Cursos personalizados + simulador de entrevistas con IA')],
        ['title' => $this->t('Consigue tu trabajo'), 'description' => $this->t('Ofertas que encajan con tu perfil real')],
      ],
      'features' => [
        ['icon' => ['category' => 'ai', 'name' => 'screening'], 'title' => $this->t('Diagnóstico Express'), 'description' => $this->t('En 3 minutos sabes por dónde empezar')],
        ['icon' => ['category' => 'business', 'name' => 'path'], 'title' => $this->t('Rutas Formativas Personalizadas'), 'description' => $this->t('Cursos adaptados a tu perfil')],
        ['icon' => ['category' => 'business', 'name' => 'cv-optimized'], 'title' => $this->t('CV Builder con IA'), 'description' => $this->t('Genera CV profesional de tus respuestas')],
        ['icon' => ['category' => 'business', 'name' => 'target'], 'title' => $this->t('Matching Inteligente'), 'description' => $this->t('Ofertas que encajan con tu experiencia real')],
        ['icon' => ['category' => 'business', 'name' => 'interview'], 'title' => $this->t('Simulador de Entrevistas'), 'description' => $this->t('Practica con IA antes de la real')],
        ['icon' => ['category' => 'business', 'name' => 'achievement'], 'title' => $this->t('Credenciales Verificables'), 'description' => $this->t('Certificados con blockchain')],
      ],
      'social_proof' => [
        'testimonials' => [
          ['quote' => $this->t('A los 52 pensé que no volvería a trabajar. En 3 semanas encontré un puesto que encajaba perfecto conmigo.'), 'author' => 'Rosa', 'role' => $this->t('administrativa, 52 años')],
          ['quote' => $this->t('El simulador de entrevistas me quitó los nervios. Fui a la real mucho más seguro.'), 'author' => 'Miguel', 'role' => $this->t('técnico en Sevilla')],
        ],
        'metrics' => [
          ['value' => '85%', 'label' => $this->t('tasa de colocación')],
          ['value' => '3 sem.', 'label' => $this->t('promedio para encontrar trabajo')],
        ],
      ],
      'lead_magnet' => [
        'title' => $this->t('Diagnóstico Express TTV'),
        'description' => $this->t('Resultado en menos de 3 minutos. Descubre tu punto de partida.'),
        'url' => '/empleabilidad/diagnostico',
        'cta_text' => $this->t('Iniciar diagnóstico'),
        'icon' => ['category' => 'ai', 'name' => 'screening'],
      ],
      'pricing' => [
        'headline' => $this->t('Planes para tu búsqueda'),
        'from_price' => '0',
        'currency' => 'EUR',
        'period' => $this->t('mes'),
        'cta_text' => $this->t('Ver todos los planes'),
        'cta_url' => '/planes',
        'features_preview' => [
          $this->t('Diagnóstico gratuito'),
          $this->t('Cursos gratuitos'),
          $this->t('1 CV básico'),
          $this->t('10 ofertas/día'),
        ],
      ],
      'faq' => [
        ['question' => $this->t('¿Es realmente gratuito el diagnóstico?'), 'answer' => $this->t('Sí, totalmente gratuito y sin pedir tarjeta de crédito. Te muestra tu nivel y plan personalizado.')],
        ['question' => $this->t('¿Cómo funciona el CV Builder?'), 'answer' => $this->t('Respondes preguntas simples sobre tu experiencia. La IA genera un CV profesional automáticamente.')],
        ['question' => $this->t('¿Puedo practicar entrevistas con IA?'), 'answer' => $this->t('Sí, el simulador hace preguntas reales, da feedback en tiempo real y mejora tus respuestas.')],
        ['question' => $this->t('¿Las ofertas están filtradas por mi sector?'), 'answer' => $this->t('Totalmente. El matching inteligente solo te muestra ofertas que encajan con tu experiencia.')],
        ['question' => $this->t('¿Qué son las credenciales verificables?'), 'answer' => $this->t('Certificados con blockchain que empleadores pueden verificar directamente. Más validez.')],
      ],
      'final_cta' => [
        'headline' => $this->t('Tu nuevo trabajo te espera'),
        'cta' => [
          'text' => $this->t('Comienza tu búsqueda hoy'),
          'url' => Url::fromRoute('user.register')->toString(),
        ],
      ],
    ]);
  }

  /**
   * Landing Emprendimiento — Emprendedores y startups.
   *
   * Ruta: /emprendimiento
   */
  public function emprendimientoLanding(): array
  {
    return $this->buildLanding([
      'key' => 'emprendimiento',
      'color' => 'impulse',
      'hero' => [
        'headline' => $this->t('De idea a negocio rentable. Sin humo, sin atajos.'),
        'subheadline' => $this->t('Metodología validada + herramientas digitales + mentoría experta. Todo lo que necesitas para emprender con garantías.'),
        'icon' => ['category' => 'verticals', 'name' => 'rocket'],
        'cta' => [
          'text' => $this->t('Evalúa tu madurez digital'),
          'url' => '/emprendimiento/calculadora-madurez',
        ],
        'cta_secondary' => [
          'text' => $this->t('Ya tengo cuenta'),
          'url' => Url::fromRoute('user.login')->toString(),
        ],
      ],
      'pain_points' => [
        ['icon' => ['category' => 'ui', 'name' => 'help-circle'], 'text' => $this->t('No sabes si tu idea de negocio es viable')],
        ['icon' => ['category' => 'ui', 'name' => 'settings'], 'text' => $this->t('Necesitas herramientas digitales pero no sabes cuáles')],
        ['icon' => ['category' => 'ui', 'name' => 'users'], 'text' => $this->t('No tienes mentor que te guíe en el camino')],
        ['icon' => ['category' => 'business', 'name' => 'money'], 'text' => $this->t('Miedo a invertir sin validar primero')],
      ],
      'steps' => [
        ['title' => $this->t('Diagnostica tu idea'), 'description' => $this->t('Evalúa madurez digital + viabilidad del negocio')],
        ['title' => $this->t('Diseña y valida'), 'description' => $this->t('Business Model Canvas con IA + herramientas de MVP')],
        ['title' => $this->t('Lanza y crece'), 'description' => $this->t('Mentoría 1:1 + financiación + networking')],
      ],
      'features' => [
        ['icon' => ['category' => 'analytics', 'name' => 'dashboard'], 'title' => $this->t('Calculadora de Madurez Digital'), 'description' => $this->t('Diagnóstico de tu nivel actual')],
        ['icon' => ['category' => 'business', 'name' => 'canvas'], 'title' => $this->t('Business Model Canvas con IA'), 'description' => $this->t('La IA te ayuda a completarlo')],
        ['icon' => ['category' => 'ai', 'name' => 'lightbulb'], 'title' => $this->t('Validación de MVP'), 'description' => $this->t('Herramientas para testear antes de invertir')],
        ['icon' => ['category' => 'ui', 'name' => 'users'], 'title' => $this->t('Mentoring 1:1'), 'description' => $this->t('Sesiones con empresarios experimentados')],
        ['icon' => ['category' => 'business', 'name' => 'money'], 'title' => $this->t('Proyecciones Financieras'), 'description' => $this->t('Plantillas para tu plan de negocio')],
        ['icon' => ['category' => 'business', 'name' => 'achievement'], 'title' => $this->t('Acceso a Financiación'), 'description' => $this->t('Conexión con programas públicos y privados')],
      ],
      'social_proof' => [
        'testimonials' => [
          ['quote' => $this->t('La calculadora de madurez me abrió los ojos. Estaba invirtiendo en lo que no necesitaba.'), 'author' => 'Carlos', 'role' => $this->t('emprendedor en Bilbao')],
          ['quote' => $this->t('El Canvas con IA me ahorró meses de prueba y error. Mi modelo de negocio es sólido ahora.'), 'author' => 'Ana', 'role' => $this->t('startup en Málaga')],
        ],
        'metrics' => [
          ['value' => '340+', 'label' => $this->t('negocios lanzados')],
          ['value' => '€2.3M', 'label' => $this->t('financiación conseguida')],
        ],
      ],
      'lead_magnet' => [
        'title' => $this->t('Calculadora de Madurez Digital'),
        'description' => $this->t('Análisis en menos de 5 minutos con reporte PDF personalizado.'),
        'url' => '/emprendimiento/calculadora-madurez',
        'cta_text' => $this->t('Evaluar mi madurez'),
        'icon' => ['category' => 'analytics', 'name' => 'dashboard'],
      ],
      'pricing' => [
        'headline' => $this->t('Planes para emprendedores'),
        'from_price' => '0',
        'currency' => 'EUR',
        'period' => $this->t('mes'),
        'cta_text' => $this->t('Ver todos los planes'),
        'cta_url' => '/planes',
        'features_preview' => [
          $this->t('Calculadora de madurez'),
          $this->t('1 Business Model Canvas'),
          $this->t('Plantillas básicas'),
          $this->t('Comunidad de emprendedores'),
        ],
      ],
      'faq' => [
        ['question' => $this->t('¿Qué es la Calculadora de Madurez?'), 'answer' => $this->t('Diagnóstico de tu negocio actual: digital, operacional, financiero. Identifica gaps y prioridades.')],
        ['question' => $this->t('¿Cómo me ayuda la IA en el Business Model Canvas?'), 'answer' => $this->t('Responde tus inputs (problema, solución, clientes) y la IA genera canvas coherente. Iterativo.')],
        ['question' => $this->t('¿Puedo acceder a mentoría directa?'), 'answer' => $this->t('Sí, en plan Pro tienes sesiones 1:1 con empresarios exitosos. Guía real para tu negocio.')],
        ['question' => $this->t('¿Cómo valido mi MVP sin gastar?'), 'answer' => $this->t('Usamos metodologías lean: hipótesis, experimentos baratos, feedback real. No necesitas invertir en grande.')],
        ['question' => $this->t('¿Cómo accedo a financiación?'), 'answer' => $this->t('Conectamos con fondos públicos (líneas ICO, ENISA), ángeles inversores y aceleradoras seleccionadas.')],
      ],
      'final_cta' => [
        'headline' => $this->t('Convierte tu idea en un negocio rentable'),
        'cta' => [
          'text' => $this->t('Inicia tu viaje emprendedor hoy'),
          'url' => Url::fromRoute('user.register')->toString(),
        ],
      ],
    ]);
  }

  /**
   * Landing JarabaLex — Inteligencia Legal con IA.
   *
   * Ruta: /jarabalex
   * Plan Elevación JarabaLex Clase Mundial v1 — Fase 0.
   */
  public function jarabalex(): array
  {
    return $this->buildLanding([
      'key' => 'jarabalex',
      'color' => 'legal',
      'hero' => [
        'headline' => $this->t('Inteligencia legal con IA al alcance de todos'),
        'subheadline' => $this->t('Gestiona tu despacho, investiga jurisprudencia, presenta en LexNET y factura — todo con IA integrada, desde 0 €/mes.'),
        'icon' => ['category' => 'legal', 'name' => 'search-legal'],
        'cta' => [
          'text' => $this->t('Haz tu diagnóstico legal gratuito'),
          'url' => '/jarabalex/diagnostico-legal',
        ],
        'cta_secondary' => [
          'text' => $this->t('Ya tengo cuenta'),
          'url' => Url::fromRoute('user.login')->toString(),
        ],
      ],
      'pain_points' => [
        ['icon' => ['category' => 'ui', 'name' => 'search'], 'text' => $this->t('Búsqueda manual en bases de datos desconectadas')],
        ['icon' => ['category' => 'ui', 'name' => 'alert-circle'], 'text' => $this->t('Expedientes repartidos entre carpetas, emails y discos duros')],
        ['icon' => ['category' => 'ui', 'name' => 'clock'], 'text' => $this->t('Plazos procesales que se escapan y citas que se solapan')],
        ['icon' => ['category' => 'business', 'name' => 'money'], 'text' => $this->t('Coste prohibitivo de herramientas premium (3.000–8.000 €/año)')],
      ],
      'steps' => [
        ['title' => $this->t('Busca'), 'description' => $this->t('Búsqueda semántica con IA en 8 fuentes oficiales (CENDOJ, BOE, EUR-Lex...)')],
        ['title' => $this->t('Gestiona'), 'description' => $this->t('Expedientes, plazos, documentos y facturación en un solo lugar con IA')],
        ['title' => $this->t('Presenta'), 'description' => $this->t('Presenta escritos en LexNET y genera documentos con plantillas jurídicas')],
      ],
      'features' => [
        ['icon' => ['category' => 'ai', 'name' => 'brain'], 'title' => $this->t('Búsqueda Semántica'), 'description' => $this->t('Encuentra resoluciones por significado, no solo por palabras clave. Embeddings 3072D.')],
        ['icon' => ['category' => 'legal', 'name' => 'search-legal'], 'title' => $this->t('8 Fuentes Oficiales'), 'description' => $this->t('CENDOJ, BOE, DGT, TEAC, EUR-Lex, CURIA, HUDOC y EDPB integrados.')],
        ['icon' => ['category' => 'legal', 'name' => 'briefcase'], 'title' => $this->t('Gestión Integral de Expedientes'), 'description' => $this->t('Expedientes con plazos, partes, documentos y comunicaciones. 5 expedientes gratis.')],
        ['icon' => ['category' => 'legal', 'name' => 'gavel'], 'title' => $this->t('Integración LexNET'), 'description' => $this->t('Presentación electrónica de escritos al CGPJ directamente desde tu expediente.')],
        ['icon' => ['category' => 'ui', 'name' => 'bell'], 'title' => $this->t('Alertas Inteligentes'), 'description' => $this->t('10 tipos de alerta: derogaciones, nueva doctrina, cambios normativos, plazos procesales.')],
        ['icon' => ['category' => 'ui', 'name' => 'calendar'], 'title' => $this->t('Agenda con Plazos Procesales'), 'description' => $this->t('Citas, vistas judiciales y plazos con alertas automáticas. Sync con Google Calendar y Outlook.')],
        ['icon' => ['category' => 'business', 'name' => 'receipt'], 'title' => $this->t('Facturación Automatizada'), 'description' => $this->t('Minutas, provisiones de fondos y facturación con serie fiscal legal desde Starter.')],
        ['icon' => ['category' => 'legal', 'name' => 'shield-privacy'], 'title' => $this->t('Bóveda Documental Cifrada'), 'description' => $this->t('Almacenamiento cifrado end-to-end con control de acceso granular y trazabilidad.')],
      ],
      'social_proof' => [
        'testimonials' => [
          ['quote' => $this->t('Antes tardaba horas buscando jurisprudencia. Ahora encuentro resoluciones relevantes en segundos con la búsqueda semántica.'), 'author' => 'Elena', 'role' => $this->t('abogada mercantilista en Madrid')],
          ['quote' => $this->t('La facturación automática y el control de plazos han transformado cómo gestionamos el bufete.'), 'author' => 'Roberto', 'role' => $this->t('socio de bufete en Sevilla')],
        ],
        'metrics' => [
          ['value' => '8', 'label' => $this->t('fuentes oficiales integradas')],
          ['value' => '< 3s', 'label' => $this->t('tiempo medio de búsqueda')],
          ['value' => '0', 'label' => $this->t('plazos incumplidos con alertas')],
        ],
      ],
      'lead_magnet' => [
        'title' => $this->t('Diagnóstico Legal Gratuito'),
        'description' => $this->t('Descubre en 2 minutos las áreas de riesgo legal y el nivel de digitalización de tu despacho.'),
        'url' => '/jarabalex/diagnostico-legal',
        'cta_text' => $this->t('Hacer diagnóstico'),
        'icon' => ['category' => 'ai', 'name' => 'screening'],
      ],
      'pricing' => [
        'headline' => $this->t('Planes para profesionales jurídicos'),
        'from_price' => '0',
        'currency' => 'EUR',
        'period' => $this->t('mes'),
        'cta_text' => $this->t('Ver todos los planes'),
        'cta_url' => '/planes',
        'features_preview' => [
          $this->t('5 expedientes gratis'),
          $this->t('10 búsquedas/mes en CENDOJ y BOE'),
          $this->t('Agenda con 10 plazos y alertas'),
          $this->t('Copilot legal incluido'),
        ],
      ],
      'faq' => [
        ['question' => $this->t('¿De dónde proviene la información legal?'), 'answer' => $this->t('De fuentes oficiales públicas: CENDOJ, BOE, DGT, TEAC (España) y EUR-Lex, CURIA, HUDOC, EDPB (Europa). Todo bajo licencia de datos abiertos (Ley 37/2007).')],
        ['question' => $this->t('¿Es fiable la búsqueda con IA?'), 'answer' => $this->t('Sí. Cada resultado incluye enlaces directos a la fuente oficial. La IA busca por significado semántico, pero siempre puedes verificar en la fuente original.')],
        ['question' => $this->t('¿Puedo gestionar expedientes de mi despacho?'), 'answer' => $this->t('Sí. JarabaLex integra gestión completa de expedientes: documentos, plazos procesales, partes, comunicaciones y facturación. 5 expedientes gratis en el plan Free.')],
        ['question' => $this->t('¿Qué es la integración con LexNET?'), 'answer' => $this->t('LexNET es el sistema obligatorio de comunicación electrónica con los juzgados (CGPJ). Desde JarabaLex puedes presentar escritos electrónicamente directamente desde tu expediente, disponible en plan Starter.')],
        ['question' => $this->t('¿Cuánto cuesta comparado con Aranzadi o vLex?'), 'answer' => $this->t('Desde 0€/mes (Free) hasta 99€/mes (Pro). Frente a 3.000–8.000€/año de herramientas tradicionales, accedes a las mismas fuentes oficiales por una fracción.')],
        ['question' => $this->t('¿Incluye normativa europea?'), 'answer' => $this->t('Sí. EUR-Lex (legislación UE), CURIA (TJUE), HUDOC (TEDH) y EDPB (protección de datos) están integrados con detección de primacía.')],
        ['question' => $this->t('¿La facturación cumple con la normativa fiscal?'), 'answer' => $this->t('Totalmente. Series fiscales legales, formato TicketBAI/SII compatible, y exportación para tu asesoría contable. Disponible desde plan Starter.')],
        ['question' => $this->t('¿Qué es el Diagnóstico Legal Gratuito?'), 'answer' => $this->t('Un cuestionario de 5 preguntas que la IA analiza para identificar tus áreas de riesgo legal y recomendarte fuentes y alertas personalizadas.')],
        ['question' => $this->t('¿Es seguro para documentos confidenciales?'), 'answer' => $this->t('Cifrado end-to-end, servidores europeos, cumple RGPD y secreto profesional. Control de acceso granular por expediente.')],
        ['question' => $this->t('¿Puedo probarlo sin dar mi tarjeta?'), 'answer' => $this->t('Por supuesto. El plan Free incluye 5 expedientes, 10 búsquedas/mes, 1 alerta y bóveda de 100 MB. Sin tarjeta de crédito.')],
      ],
      'final_cta' => [
        'headline' => $this->t('Empieza a investigar con inteligencia'),
        'cta' => [
          'text' => $this->t('Crea tu cuenta gratuita'),
          'url' => Url::fromRoute('user.register')->toString(),
        ],
      ],
    ]);
  }
  /**
   * Redirect /despachos → /jarabalex (301 permanent).
   *
   * Plan Unificacion JarabaLex + Despachos v1 — Fase 3.
   * Despachos features consolidated into JarabaLex landing.
   * SEO: 301 redirect preserves link equity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   301 Moved Permanently redirect.
   */
  public function despachos(): RedirectResponse
  {
    return new RedirectResponse(Url::fromRoute('ecosistema_jaraba_core.landing.jarabalex')->toString(), 301);
  }

  // =========================================================================
  // LANDING PAGES LEGACY — Refactorizadas al formato de 9 secciones
  // =========================================================================

  /**
   * Redirect legacy /empleo → /empleabilidad (301 permanent).
   *
   * SEO: Consolidates legacy route to avoid duplicate content.
   * DIRECTRIZ: LEGAL-ROUTE-001 (F4 route naming convention).
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   301 Moved Permanently redirect.
   */
  public function empleo(): RedirectResponse
  {
    return new RedirectResponse('/empleabilidad', 301);
  }

  /**
   * Landing Talento — Empresas y reclutadores.
   *
   * Ruta: /talento
   * F4 full landing: 9 secciones completas.
   * Sprint 3 — Growth Infrastructure (VERT-02).
   */
  public function talento(): array
  {
    return $this->buildLanding([
      'key' => 'talento',
      'color' => 'innovation',
      'hero' => [
        'headline' => $this->t('Encuentra el talento que necesitas'),
        'subheadline' => $this->t('Filtrado inteligente, matching por competencias y gestión simplificada de candidaturas.'),
        'icon' => ['category' => 'business', 'name' => 'talent-search'],
        'cta' => [
          'text' => $this->t('Publicar oferta'),
          'url' => Url::fromRoute('user.register')->toString(),
        ],
        'cta_secondary' => [
          'text' => $this->t('Ver planes'),
          'url' => '/planes',
        ],
      ],
      'pain_points' => [
        'title' => $this->t('Retos de la contratación actual'),
        'items' => [
          ['title' => $this->t('CVs desbordantes'), 'description' => $this->t('Recibes cientos de candidaturas y no tienes tiempo para revisarlas todas con la atención que merecen')],
          ['title' => $this->t('Falta de matching'), 'description' => $this->t('Los portales genéricos no filtran por las competencias reales que necesita tu empresa')],
          ['title' => $this->t('Procesos lentos'), 'description' => $this->t('Desde la publicación hasta la contratación pasan semanas de gestión manual y seguimiento por email')],
        ],
      ],
      'steps' => [
        'title' => $this->t('Cómo funciona'),
        'items' => [
          ['step' => '1', 'title' => $this->t('Publica tu oferta'), 'description' => $this->t('Define el perfil, competencias técnicas y soft skills. El copiloto IA te ayuda a redactar la oferta perfecta.')],
          ['step' => '2', 'title' => $this->t('Matching automático'), 'description' => $this->t('La IA analiza las candidaturas y te sugiere los perfiles más compatibles, ordenados por relevancia.')],
          ['step' => '3', 'title' => $this->t('Gestiona y contrata'), 'description' => $this->t('Evalúa candidatos en equipo, programa entrevistas y gestiona todo el proceso desde un solo panel.')],
        ],
      ],
      'features' => [
        ['icon' => ['category' => 'ui', 'name' => 'search'], 'title' => $this->t('Búsqueda avanzada'), 'description' => $this->t('Filtra por skills, experiencia, disponibilidad y ubicación')],
        ['icon' => ['category' => 'ai', 'name' => 'screening'], 'title' => $this->t('Preselección con IA'), 'description' => $this->t('El copiloto analiza CVs y sugiere los mejores candidatos')],
        ['icon' => ['category' => 'analytics', 'name' => 'dashboard'], 'title' => $this->t('Analytics de contratación'), 'description' => $this->t('Métricas de tiempo, conversión y calidad de contrataciones')],
        ['icon' => ['category' => 'ui', 'name' => 'users'], 'title' => $this->t('Gestión colaborativa'), 'description' => $this->t('Tu equipo puede evaluar y comentar candidatos en tiempo real')],
      ],
      'social_proof' => [
        'metrics' => [
          ['value' => '70', 'suffix' => '%', 'label' => $this->t('Reducción tiempo de contratación')],
          ['value' => '3', 'suffix' => 'x', 'label' => $this->t('Más candidatos relevantes')],
          ['value' => '24', 'suffix' => 'h', 'label' => $this->t('Primeros matches disponibles')],
        ],
      ],
      'lead_magnet' => [
        'title' => $this->t('Guía: Cómo reducir un 70% el tiempo de contratación con IA'),
        'description' => $this->t('Aprende cómo empresas reales han transformado sus procesos de selección con matching inteligente.'),
        'resource_url' => '/recursos/guia-talento-ia',
        'cta_text' => $this->t('Descargar guía gratuita'),
      ],
      'pricing' => [
        'title' => $this->t('Planes para empresas'),
        'cta_url' => '/planes',
      ],
      'faq' => [
        ['question' => $this->t('¿Cuántas ofertas puedo publicar?'), 'answer' => $this->t('Depende del plan. El plan Starter incluye 3 ofertas activas simultáneas. Los planes Profesional y Business incluyen ofertas ilimitadas.')],
        ['question' => $this->t('¿Cómo funciona el matching con IA?'), 'answer' => $this->t('Nuestro copiloto analiza las competencias, experiencia y objetivos profesionales de cada candidato y los compara con los requisitos de tu oferta, generando un score de compatibilidad.')],
        ['question' => $this->t('¿Puedo gestionar candidatos en equipo?'), 'answer' => $this->t('Sí. Puedes invitar a compañeros de equipo para que evalúen, comenten y voten candidatos. Todo queda registrado en el historial de la candidatura.')],
        ['question' => $this->t('¿Se integra con otros ATS?'), 'answer' => $this->t('Ofrecemos API REST completa para integraciones. También disponemos de conectores para las principales herramientas de RRHH del mercado.')],
      ],
      'final_cta' => [
        'headline' => $this->t('¿Listo para encontrar talento?'),
        'cta' => [
          'text' => $this->t('Publicar oferta'),
          'url' => Url::fromRoute('user.register')->toString(),
        ],
      ],
    ]);
  }

  /**
   * Redirect legacy /emprender → /emprendimiento (301 permanent).
   *
   * SEO: Consolidates legacy route to avoid duplicate content.
   * DIRECTRIZ: LEGAL-ROUTE-001 (F4 route naming convention).
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   301 Moved Permanently redirect.
   */
  public function emprender(): RedirectResponse
  {
    return new RedirectResponse('/emprendimiento', 301);
  }

  /**
   * Redirect legacy /comercio → /comercioconecta (301 permanent).
   *
   * SEO: Consolidates legacy route to avoid duplicate content.
   * DIRECTRIZ: LEGAL-ROUTE-001 (F4 route naming convention).
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   301 Moved Permanently redirect.
   */
  public function comercio(): RedirectResponse
  {
    return new RedirectResponse('/comercioconecta', 301);
  }

  /**
   * Redirect legacy /legal → /jarabalex (301 permanent).
   *
   * Plan Unificacion JarabaLex + Despachos v1 — Fase 3.
   * SEO: LEGAL-ROUTE-001 (F4 route naming convention).
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   301 Moved Permanently redirect.
   */
  public function legalRedirect(): RedirectResponse
  {
    return new RedirectResponse(Url::fromRoute('ecosistema_jaraba_core.landing.jarabalex')->toString(), 301);
  }

  /**
   * Landing Instituciones B2G — Entidades públicas y desarrollo local.
   *
   * Ruta: /instituciones
   * F4 full landing: 9 secciones completas.
   * Sprint 3 — Growth Infrastructure (VERT-01).
   */
  public function instituciones(): array
  {
    return $this->buildLanding([
      'key' => 'instituciones',
      'color' => 'corporate',
      'hero' => [
        'headline' => $this->t('Tu plataforma de desarrollo local'),
        'subheadline' => $this->t('Formación, empleo y emprendimiento con tu marca. Impulsado por IA.'),
        'icon' => ['category' => 'business', 'name' => 'institution'],
        'cta' => [
          'text' => $this->t('Solicitar demo'),
          'url' => '/contacto',
        ],
        'cta_secondary' => [
          'text' => $this->t('Ver planes'),
          'url' => '/planes',
        ],
      ],
      'pain_points' => [
        'title' => $this->t('Retos del desarrollo local'),
        'items' => [
          ['title' => $this->t('Plataformas genéricas'), 'description' => $this->t('Las herramientas actuales no reflejan la identidad ni las necesidades específicas de tu territorio')],
          ['title' => $this->t('Datos dispersos'), 'description' => $this->t('Sin un sistema unificado, es imposible medir el impacto real de las políticas de empleo y emprendimiento')],
          ['title' => $this->t('Justificación de fondos'), 'description' => $this->t('Los reportes a fondos europeos (FSE+, FEDER) requieren métricas de impacto precisas y verificables')],
        ],
      ],
      'steps' => [
        'title' => $this->t('Cómo funciona'),
        'items' => [
          ['step' => '1', 'title' => $this->t('Configura tu marca'), 'description' => $this->t('Logo, colores corporativos y dominio personalizado. Tu ciudadanía ve tu marca, no la nuestra.')],
          ['step' => '2', 'title' => $this->t('Activa verticales'), 'description' => $this->t('Empleabilidad, emprendimiento, comercio, agro — selecciona los que necesita tu territorio.')],
          ['step' => '3', 'title' => $this->t('Mide el impacto'), 'description' => $this->t('Dashboards ODS, informes automáticos y métricas listas para fondos europeos.')],
        ],
      ],
      'features' => [
        ['icon' => ['category' => 'ui', 'name' => 'building'], 'title' => $this->t('Tu marca, tu plataforma'), 'description' => $this->t('Identidad corporativa propia: logo, colores y dominio personalizado')],
        ['icon' => ['category' => 'business', 'name' => 'ecosystem'], 'title' => $this->t('Formación y empleo'), 'description' => $this->t('Conecta talento local con empresas de tu territorio')],
        ['icon' => ['category' => 'ai', 'name' => 'screening'], 'title' => $this->t('Copiloto IA incluido'), 'description' => $this->t('Asistencia inteligente para candidatos y emprendedores')],
        ['icon' => ['category' => 'analytics', 'name' => 'dashboard'], 'title' => $this->t('Métricas de impacto'), 'description' => $this->t('Dashboards ODS y reportes para justificar subvenciones')],
      ],
      'social_proof' => [
        'metrics' => [
          ['value' => '7', 'suffix' => '', 'label' => $this->t('Verticales disponibles')],
          ['value' => '100', 'suffix' => 'M€+', 'label' => $this->t('Fondos europeos gestionados')],
          ['value' => '6', 'suffix' => '', 'label' => $this->t('ODS alineados')],
        ],
      ],
      'lead_magnet' => [
        'title' => $this->t('Guía: Cómo digitalizar tu servicio de desarrollo local'),
        'description' => $this->t('Descarga nuestra guía con casos reales de municipios que han transformado sus servicios de empleo y emprendimiento.'),
        'resource_url' => '/recursos/guia-desarrollo-local',
        'cta_text' => $this->t('Descargar guía gratuita'),
      ],
      'pricing' => [
        'title' => $this->t('Planes para instituciones'),
        'cta_url' => '/contacto',
      ],
      'faq' => [
        ['question' => $this->t('¿Podemos usar nuestra propia marca y dominio?'), 'answer' => $this->t('Sí. Cada institución tiene su propia identidad visual: logo, colores corporativos y dominio personalizado (ej: empleo.tuayuntamiento.es).')],
        ['question' => $this->t('¿Qué verticales podemos activar?'), 'answer' => $this->t('Todos los disponibles: Empleabilidad, Emprendimiento, AgroConecta, ComercioConecta, ServiciosConecta y JarabaLex. Puedes activar solo los que necesites.')],
        ['question' => $this->t('¿Genera informes para fondos europeos?'), 'answer' => $this->t('Sí. Los dashboards incluyen métricas de impacto alineadas con los ODS, y los informes están preparados para FSE+, FEDER y otros programas de financiación pública.')],
        ['question' => $this->t('¿Cómo se protegen los datos de los ciudadanos?'), 'answer' => $this->t('Cumplimos RGPD y LOPDGDD. Los datos se alojan en servidores europeos con cifrado end-to-end. Cada tenant tiene aislamiento completo de datos.')],
      ],
      'final_cta' => [
        'headline' => $this->t('¿Listo para impulsar tu territorio?'),
        'cta' => [
          'text' => $this->t('Solicitar demo'),
          'url' => '/contacto',
        ],
      ],
    ]);
  }

  // =========================================================================
  // HELPERS
  // =========================================================================

  /**
   * Construye la estructura de render para landing pages verticales.
   *
   * @param array $data
   *   Datos con estructura de 9 secciones (hero, pain_points, steps,
   *   features, social_proof, lead_magnet, pricing, faq, final_cta).
   *
   * @return array
   *   Render array con template vertical_landing_content.
   */
  protected function buildLanding(array $data): array
  {
    // Enrich pricing data from ConfigEntities if available.
    // Uses MetaSitePricingService cascade resolution:
    //   specific {vertical}_{tier} → _default_{tier} → fallback.
    $verticalKey = $data['key'] ?? '';
    if ($verticalKey && $this->pricingService) {
      $dynamicPricing = $this->pricingService->getFromPrice($verticalKey);
      if (!empty($dynamicPricing['from_price'])) {
        $data['pricing'] = array_merge($data['pricing'] ?? [], $dynamicPricing);
      }
    }

    return [
      '#theme' => 'vertical_landing_content',
      '#vertical_data' => $data,
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_core/global',
          'ecosistema_jaraba_theme/progressive-profiling',
          'ecosistema_jaraba_theme/landing-sections',
        ],
      ],
      '#cache' => [
        'tags' => ['config:saas_plan_tier_list', 'config:saas_plan_features_list'],
        'max-age' => 3600,
      ],
    ];
  }

}
