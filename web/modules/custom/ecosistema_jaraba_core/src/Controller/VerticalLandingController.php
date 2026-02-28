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
        'subheadline' => $this->t('Marketplace agro con trazabilidad QR, envio MRW/SEUR, Copilot IA, prevision de demanda, analytics avanzados y cobro seguro via Stripe Connect. Listo en 10 minutos.'),
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
        ['title' => $this->t('Sube tus productos'), 'description' => $this->t('Con una foto, la IA escribe la descripcion, sugiere precios y categoriza automaticamente')],
        ['title' => $this->t('Comparte tu tienda'), 'description' => $this->t('Un link, un QR de trazabilidad, redes sociales y WhatsApp Business')],
        ['title' => $this->t('Cobra y envia'), 'description' => $this->t('Stripe procesa pagos, MRW/SEUR gestionan el envio con tracking en tiempo real')],
      ],
      'features' => [
        ['icon' => ['category' => 'ai', 'name' => 'screening'], 'title' => $this->t('Producer Copilot (IA)'), 'description' => $this->t('Genera descripciones, sugiere precios competitivos, responde resenas y predice la demanda de tus productos.')],
        ['icon' => ['category' => 'business', 'name' => 'qr-code'], 'title' => $this->t('QR de Trazabilidad'), 'description' => $this->t('Tus clientes escanean y ven el recorrido completo: del campo a su mesa, con prueba de integridad criptografica.')],
        ['icon' => ['category' => 'business', 'name' => 'store'], 'title' => $this->t('Marketplace Profesional'), 'description' => $this->t('Catalogo con busqueda avanzada, categorias, colecciones estacionales y filtros por certificacion.')],
        ['icon' => ['category' => 'business', 'name' => 'package'], 'title' => $this->t('Envio MRW y SEUR'), 'description' => $this->t('Calculo automatico de tarifas por zona, generacion de etiquetas y tracking en tiempo real.')],
        ['icon' => ['category' => 'business', 'name' => 'money'], 'title' => $this->t('Cobro Seguro via Stripe'), 'description' => $this->t('Split payments multi-vendedor via Stripe Connect. El dinero en tu cuenta en 48h.')],
        ['icon' => ['category' => 'ui', 'name' => 'star'], 'title' => $this->t('Resenas Verificadas'), 'description' => $this->t('Sistema de valoracion 5 estrellas con moderacion, respuestas del productor y Schema.org.')],
        ['icon' => ['category' => 'business', 'name' => 'chart'], 'title' => $this->t('Analytics en Tiempo Real'), 'description' => $this->t('Dashboard con metricas diarias: ingresos, pedidos, productos mas vistos y conversion.')],
        ['icon' => ['category' => 'ai', 'name' => 'brain'], 'title' => $this->t('Prevision de Demanda IA'), 'description' => $this->t('Predicciones ML de demanda por producto. Anticipa que cultivar, cuanto producir y cuando vender.')],
        ['icon' => ['category' => 'business', 'name' => 'achievement'], 'title' => $this->t('Certificaciones Digitales'), 'description' => $this->t('Muestra sellos eco, DOP, comercio justo y certificados de calidad verificados.')],
        ['icon' => ['category' => 'ui', 'name' => 'phone'], 'title' => $this->t('Pedidos WhatsApp'), 'description' => $this->t('Notificaciones de pedidos via WhatsApp Business API y recuperacion automatica de carritos.')],
        ['icon' => ['category' => 'business', 'name' => 'tag'], 'title' => $this->t('Promociones y Cupones'), 'description' => $this->t('Descuentos porcentuales, fijos o 2x1. Cupones con limite de uso y validez temporal.')],
        ['icon' => ['category' => 'ai', 'name' => 'radar'], 'title' => $this->t('Inteligencia de Mercado'), 'description' => $this->t('Monitoreo de precios de la competencia y tendencias del mercado agroalimentario.')],
        ['icon' => ['category' => 'business', 'name' => 'handshake'], 'title' => $this->t('Hub B2B Partners'), 'description' => $this->t('Portal de partners para distribuidores, logistica y organismos certificadores con documentos compliance.')],
        ['icon' => ['category' => 'ui', 'name' => 'bell'], 'title' => $this->t('Notificaciones Multi-Canal'), 'description' => $this->t('Email, SMS y push configurables por evento: nuevo pedido, envio, resena, alerta de stock.')],
        ['icon' => ['category' => 'ui', 'name' => 'heart'], 'title' => $this->t('Portal del Cliente'), 'description' => $this->t('Historial de pedidos, direcciones guardadas, favoritos y preferencias alimentarias personalizadas.')],
      ],
      'social_proof' => [
        'testimonials' => [
          ['quote' => $this->t('Antes vendia solo en el mercado del pueblo. Ahora envio a toda Espana con MRW y facturo 3x mas. El tracking da confianza al cliente.'), 'author' => 'Antonio', 'role' => $this->t('olivarero en Jaen')],
          ['quote' => $this->t('La IA me escribio las descripciones y me sugirio precios competitivos. En una tarde tenia 20 productos online con trazabilidad QR.'), 'author' => 'Maria', 'role' => $this->t('quesera en Extremadura')],
        ],
        'metrics' => [
          ['value' => '3x', 'label' => $this->t('mas ingresos promedio')],
          ['value' => '500+', 'label' => $this->t('productores conectados')],
          ['value' => '48h', 'label' => $this->t('tiempo medio de cobro')],
        ],
      ],
      'lead_magnet' => [
        'title' => $this->t('Guia: Vende Online sin Intermediarios'),
        'description' => $this->t('Descarga inmediata en PDF con estrategias probadas.'),
        'url' => '/agroconecta/guia-vende-online',
        'cta_text' => $this->t('Descargar guia gratis'),
        'icon' => ['category' => 'ui', 'name' => 'download'],
      ],
      'pricing' => [
        'headline' => $this->t('Planes para productores'),
        'from_price' => '0',
        'currency' => 'EUR',
        'period' => $this->t('mes'),
        'cta_text' => $this->t('Ver todos los planes'),
        'cta_url' => Url::fromRoute('ecosistema_jaraba_core.planes')->toString(),
        'features_preview' => [
          $this->t('5 productos gratis'),
          $this->t('Copilot IA para descripciones'),
          $this->t('Cobro via Stripe Connect'),
          $this->t('Envio MRW/SEUR desde Starter'),
          $this->t('Analytics avanzados desde Profesional'),
          $this->t('Hub B2B y API desde Enterprise'),
        ],
      ],
      'faq' => [
        ['question' => $this->t('¿Cuanto tiempo tarda en estar operativo?'), 'answer' => $this->t('Tu tienda online estara lista en 10 minutos. Sube productos, configura pagos y la IA genera descripciones automaticamente.')],
        ['question' => $this->t('¿Cuales son las comisiones?'), 'answer' => $this->t('Plan Free: 10% por transaccion. Starter: 8%. Profesional: 5%. Enterprise: 3%. Transparente, sin costes ocultos.')],
        ['question' => $this->t('¿Necesito conocimientos tecnicos?'), 'answer' => $this->t('No. Esta disenado para productores del campo. La IA te ayuda en cada paso: descripciones, precios, respuestas a resenas y predicciones de demanda.')],
        ['question' => $this->t('¿Como recibo los pagos?'), 'answer' => $this->t('Via Stripe Connect con split payments multi-vendedor. El dinero llega a tu cuenta bancaria en 48 horas, sin intermediarios.')],
        ['question' => $this->t('¿Como funciona el envio?'), 'answer' => $this->t('Integracion directa con MRW y SEUR. Calculo automatico de tarifas por zona, generacion de etiquetas y tracking en tiempo real para ti y tu cliente.')],
        ['question' => $this->t('¿Que es la trazabilidad QR?'), 'answer' => $this->t('Cada producto tiene un QR que el cliente escanea para ver todo el recorrido: cosecha, transporte y venta, con prueba de integridad criptografica.')],
        ['question' => $this->t('¿Como funcionan las resenas?'), 'answer' => $this->t('Los clientes valoran de 1 a 5 estrellas. Moderacion automatica, respuesta del productor y la IA te ayuda a redactar respuestas profesionales.')],
        ['question' => $this->t('¿Que hace el Copilot IA?'), 'answer' => $this->t('Genera descripciones de producto, sugiere precios competitivos, predice demanda estacional, analiza competencia y redacta respuestas a resenas.')],
        ['question' => $this->t('¿Que son los analytics avanzados?'), 'answer' => $this->t('Dashboard con metricas diarias: ingresos, pedidos, productos mas vistos, conversion y prediccion de demanda. Disponible desde el plan Profesional.')],
        ['question' => $this->t('¿Puedo crear promociones y cupones?'), 'answer' => $this->t('Si. Descuentos porcentuales, fijos o 2x1. Cupones con codigo, limite de uso y fecha de validez. Evaluacion automatica en checkout.')],
        ['question' => $this->t('¿Que es el Hub B2B Partners?'), 'answer' => $this->t('Portal para gestionar relaciones con distribuidores, empresas de logistica y organismos certificadores. Documentos compliance con audit trail.')],
        ['question' => $this->t('¿Puedo usar mi propio dominio?'), 'answer' => $this->t('Si, desde el plan Profesional tienes dominio personalizado. En Free y Starter usas un subdominio de la plataforma.')],
        ['question' => $this->t('¿Hay recuperacion de carritos abandonados?'), 'answer' => $this->t('Si. El sistema detecta carritos abandonados y envia automaticamente emails y SMS de recuperacion. Disponible desde Starter.')],
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
        'subheadline' => $this->t('Marketplace multi-comercio, ofertas flash geolocalizadas, click & collect, carrito con Stripe, copilot IA, envíos con tracking, SEO local y analytics — todo integrado con tu TPV, desde 0 €/mes.'),
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
        ['title' => $this->t('Configura tu tienda online'), 'description' => $this->t('Sube productos, sincroniza con tu TPV y publica tu tienda en minutos')],
        ['title' => $this->t('Tus clientes compran 24/7'), 'description' => $this->t('Carrito, checkout con Stripe, click & collect y envío a domicilio')],
        ['title' => $this->t('La IA optimiza tu negocio'), 'description' => $this->t('Copilot genera descripciones, sugiere precios y recupera carritos abandonados')],
      ],
      'features' => [
        ['icon' => ['category' => 'business', 'name' => 'store'], 'title' => $this->t('Marketplace Multi-Comercio'), 'description' => $this->t('Tu tienda online propia dentro de un marketplace de proximidad. Perfil verificado, catálogo completo y pedidos multi-vendor con sub-pedidos por comercio.')],
        ['icon' => ['category' => 'business', 'name' => 'lightning'], 'title' => $this->t('Ofertas Flash Geolocalizadas'), 'description' => $this->t('Crea descuentos temporales geolocalizados para productos que caducan o stock excedente. Con tracking de conversión y reclamaciones.')],
        ['icon' => ['category' => 'business', 'name' => 'qr-code'], 'title' => $this->t('QR de Escaparate Dinámico'), 'description' => $this->t('Códigos QR con A/B testing que muestran precios, disponibilidad y botón de compra. Ventas aunque estés cerrado.')],
        ['icon' => ['category' => 'business', 'name' => 'package'], 'title' => $this->t('Click & Collect'), 'description' => $this->t('Tus clientes reservan online y recogen en tienda. Con gestión de horarios de recogida y notificaciones automáticas.')],
        ['icon' => ['category' => 'business', 'name' => 'cart'], 'title' => $this->t('Carrito y Checkout Completo'), 'description' => $this->t('Carrito de compra real con cupones, variaciones de producto, cálculo de envío y pago seguro via Stripe Connect.')],
        ['icon' => ['category' => 'business', 'name' => 'money'], 'title' => $this->t('Pagos Stripe Connect (Split)'), 'description' => $this->t('Cobro automático multi-vendor: cada comerciante recibe su parte menos comisión. Reembolsos y payouts transparentes.')],
        ['icon' => ['category' => 'business', 'name' => 'sync'], 'title' => $this->t('Integración TPV Bidireccional'), 'description' => $this->t('Sincronización automática de stock, precios y pedidos con tu sistema de caja. Sin duplicados ni errores.')],
        ['icon' => ['category' => 'ui', 'name' => 'search'], 'title' => $this->t('SEO Local Automático'), 'description' => $this->t('Schema.org LocalBusiness, NAP consistente y posicionamiento en búsquedas de proximidad. Apareces en "tiendas cerca de mí".')],
        ['icon' => ['category' => 'ai', 'name' => 'brain'], 'title' => $this->t('Copilot IA para Comerciantes'), 'description' => $this->t('Genera descripciones de producto, sugiere precios competitivos, crea posts para redes sociales, responde reseñas y diseña emails promocionales.')],
        ['icon' => ['category' => 'business', 'name' => 'chart'], 'title' => $this->t('Dashboard con Analytics'), 'description' => $this->t('KPIs de ventas, productos más vendidos, tendencias de demanda, tasa de conversión y predicción de stock con IA.')],
        ['icon' => ['category' => 'business', 'name' => 'truck'], 'title' => $this->t('Gestión de Envíos con Tracking'), 'description' => $this->t('Configura zonas de envío, tarifas por peso/distancia, múltiples carriers y tracking en tiempo real para tus clientes.')],
        ['icon' => ['category' => 'ui', 'name' => 'star'], 'title' => $this->t('Reseñas y Reputación Verificada'), 'description' => $this->t('Sistema de valoraciones post-compra con moderación automática, votos de utilidad y Schema.org para Google.')],
        ['icon' => ['category' => 'business', 'name' => 'achievement'], 'title' => $this->t('Programa de Fidelización'), 'description' => $this->t('Puntos por compra, cupones de descuento, wishlists de cliente. Retención que convierte compradores en habituales.')],
        ['icon' => ['category' => 'ui', 'name' => 'bell'], 'title' => $this->t('Notificaciones Multi-canal'), 'description' => $this->t('Email, SMS y push notifications automáticas: confirmación de pedido, envío, carritos abandonados y ofertas personalizadas.')],
        ['icon' => ['category' => 'business', 'name' => 'refresh'], 'title' => $this->t('Devoluciones y RMA'), 'description' => $this->t('Gestión completa de devoluciones con solicitud online, tracking de RMA e incidencias. El cliente resuelve sin llamarte.')],
      ],
      'social_proof' => [
        'testimonials' => [
          ['quote' => $this->t('Desde que puse el QR en el escaparate, vendo incluso cuando cierro a mediodía. El marketplace me trajo clientes que no sabían que existía.'), 'author' => 'Laura', 'role' => $this->t('librería en Zaragoza')],
          ['quote' => $this->t('Las ofertas flash me han solucionado el problema de productos a punto de caducar. Y el copilot me escribe los posts de Instagram en segundos.'), 'author' => 'Pedro', 'role' => $this->t('frutería en Barcelona')],
        ],
        'metrics' => [
          ['value' => '2,500+', 'label' => $this->t('comercios digitalizados')],
          ['value' => '40%', 'label' => $this->t('aumento de ventas promedio')],
          ['value' => '< 3%', 'label' => $this->t('carritos abandonados recuperados')],
        ],
      ],
      'lead_magnet' => [
        'title' => $this->t('Auditoría SEO Local Gratuita'),
        'description' => $this->t('Descubre cómo te encuentran tus clientes online. Análisis de visibilidad local, NAP y posicionamiento en menos de 2 minutos.'),
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
        'cta_url' => Url::fromRoute('ecosistema_jaraba_core.pricing.page')->toString(),
        'features_preview' => [
          $this->t('10 productos y 1 oferta flash gratis'),
          $this->t('QR de escaparate + SEO local automático'),
          $this->t('Carrito y checkout con Stripe'),
          $this->t('Copilot IA con 20 consultas/mes'),
          $this->t('Envíos con tracking desde Starter'),
          $this->t('Analytics avanzados desde Profesional'),
        ],
      ],
      'faq' => [
        ['question' => $this->t('¿Necesito cambiar mi TPV?'), 'answer' => $this->t('No. La integración es bidireccional: sincronizamos stock, precios y pedidos con tu sistema actual. Cada venta online se registra automáticamente en tu caja sin duplicados.')],
        ['question' => $this->t('¿Cómo ayuda el QR de escaparate?'), 'answer' => $this->t('Tus clientes lo escanean con el móvil y ven precios, disponibilidad y botón de compra directa. Con A/B testing para optimizar qué productos muestras. Ventas aunque estés cerrado.')],
        ['question' => $this->t('¿Aparezco en Google Maps?'), 'answer' => $this->t('Sí. Generamos automáticamente Schema.org LocalBusiness con NAP consistente (nombre, dirección, teléfono) y te posicionamos en búsquedas de proximidad como "tiendas cerca de mí".')],
        ['question' => $this->t('¿Cómo funcionan las ofertas flash?'), 'answer' => $this->t('Creas descuentos temporales geolocalizados que generan urgencia. Ideal para liquidar stock o productos a punto de caducar. Con tracking de reclamaciones y conversión en tiempo real.')],
        ['question' => $this->t('¿Es un marketplace real con carrito?'), 'answer' => $this->t('Sí. Carrito de compra completo con variaciones de producto, cupones de descuento, cálculo de envío y pago seguro via Stripe Connect. Los pedidos multi-comercio se dividen en sub-pedidos por vendedor.')],
        ['question' => $this->t('¿Cómo recibo el dinero de las ventas?'), 'answer' => $this->t('Via Stripe Connect con split automático: cada venta se divide entre plataforma y comerciante. Recibes tu parte en tu cuenta bancaria en 2-7 días con comisión transparente.')],
        ['question' => $this->t('¿Puedo gestionar envíos?'), 'answer' => $this->t('Sí. Configuras zonas de envío, tarifas por peso o distancia y múltiples carriers. Tus clientes ven el coste de envío antes de pagar y reciben tracking en tiempo real.')],
        ['question' => $this->t('¿Qué hace el Copilot IA?'), 'answer' => $this->t('Genera descripciones de producto optimizadas para SEO, sugiere precios competitivos, crea posts para redes sociales, responde reseñas de clientes, diseña emails promocionales y predice demanda de stock.')],
        ['question' => $this->t('¿Cómo funcionan las devoluciones?'), 'answer' => $this->t('El cliente solicita la devolución online con motivo. Tú la apruebas o rechazas desde tu panel. El sistema genera número de RMA, tracking de incidencia y gestiona el reembolso via Stripe.')],
        ['question' => $this->t('¿Qué pasa con los carritos abandonados?'), 'answer' => $this->t('El sistema detecta carritos abandonados automáticamente y envía recordatorios por email para recuperar la venta. Con analytics de tasa de recuperación por producto y campaña.')],
        ['question' => $this->t('¿Cómo funcionan las reseñas?'), 'answer' => $this->t('Después de cada compra completada, el cliente recibe una invitación a valorar. Las reseñas pasan por moderación automática, incluyen votos de utilidad y se publican con Schema.org para Google.')],
        ['question' => $this->t('¿Cuánto cuesta comparado con Shopify o PrestaShop?'), 'answer' => $this->t('Desde 0 €/mes (plan Free: 10 productos, 1 QR, 1 oferta flash, copilot IA con 20 consultas/mes). Frente a Shopify Basic (36 €/mes) o PrestaShop Cloud (30+ €/mes), accedes a marketplace + copilot IA + SEO local por una fracción.')],
        ['question' => $this->t('¿Puedo probarlo sin dar mi tarjeta?'), 'answer' => $this->t('Por supuesto. El plan Free incluye 10 productos, QR de escaparate, 1 oferta flash, SEO local automático y Copilot IA con 20 consultas/mes. Sin tarjeta de crédito.')],
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
      'color' => 'servicios',
      'hero' => [
        'headline' => $this->t('Más clientes, menos papeleo. Tu negocio de servicios digitalizado.'),
        'subheadline' => $this->t('Motor de reservas 24/7, videoconsulta online, marketplace profesional, presupuestador IA, firma digital y cobro automático via Stripe — todo en un solo lugar, desde 0 €/mes.'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'cta' => [
          'text' => $this->t('Empieza gratis ahora'),
          'url' => Url::fromRoute('user.register')->toString(),
        ],
        'cta_secondary' => [
          'text' => $this->t('Ya tengo cuenta'),
          'url' => Url::fromRoute('user.login')->toString(),
        ],
      ],
      'pain_points' => [
        ['icon' => ['category' => 'ui', 'name' => 'phone'], 'text' => $this->t('Gestionar citas por teléfono consume tu tiempo productivo')],
        ['icon' => ['category' => 'ui', 'name' => 'clock'], 'text' => $this->t('Tus clientes no pueden reservar fuera de horario y pierdes reservas')],
        ['icon' => ['category' => 'ui', 'name' => 'eye-off'], 'text' => $this->t('Eres invisible online: sin perfil profesional ni marketplace donde te encuentren')],
        ['icon' => ['category' => 'business', 'name' => 'money'], 'text' => $this->t('Cobrar en efectivo o por transferencia genera impagos y retrasos')],
      ],
      'steps' => [
        ['title' => $this->t('Configura tu perfil'), 'description' => $this->t('Crea tu perfil profesional, define servicios, horarios y tarifas')],
        ['title' => $this->t('Tus clientes reservan online'), 'description' => $this->t('24/7 sin llamarte, con confirmación automática y recordatorios')],
        ['title' => $this->t('Cobra automáticamente'), 'description' => $this->t('Stripe procesa el pago al reservar. Tú recibes tu dinero sin papeleos')],
      ],
      'features' => [
        ['icon' => ['category' => 'ui', 'name' => 'calendar'], 'title' => $this->t('Motor de Reservas 24/7'), 'description' => $this->t('Tus clientes reservan online con confirmación automática y recordatorios por email. Detección de no-shows.')],
        ['icon' => ['category' => 'ui', 'name' => 'video'], 'title' => $this->t('Videoconsulta Online'), 'description' => $this->t('Sesiones via Jitsi Meet generadas automáticamente al crear la reserva. Sin apps externas.')],
        ['icon' => ['category' => 'business', 'name' => 'store'], 'title' => $this->t('Marketplace Profesional'), 'description' => $this->t('Perfil público con portfolio, certificaciones y reseñas. Tus clientes te encuentran y reservan.')],
        ['icon' => ['category' => 'business', 'name' => 'gift'], 'title' => $this->t('Paquetes y Bonos de Sesiones'), 'description' => $this->t('Ofrece bundles de sesiones con descuento. Fideliza clientes con bonos prepago.')],
        ['icon' => ['category' => 'ui', 'name' => 'star'], 'title' => $this->t('Reseñas y Reputación Verificada'), 'description' => $this->t('Sistema de calificación post-servicio con moderación automática. Schema.org para Google.')],
        ['icon' => ['category' => 'business', 'name' => 'receipt'], 'title' => $this->t('Cobro Automático via Stripe'), 'description' => $this->t('Pagos seguros con Stripe Connect. Comisión transparente del 10%. Tu dinero en 2-7 días.')],
        ['icon' => ['category' => 'ai', 'name' => 'brain'], 'title' => $this->t('Copilot IA para Profesionales'), 'description' => $this->t('Asistente inteligente que optimiza tu oferta, sugiere precios y analiza tendencias de demanda.')],
        ['icon' => ['category' => 'business', 'name' => 'chart'], 'title' => $this->t('Dashboard con Analítica'), 'description' => $this->t('KPIs en tiempo real: ingresos, tasa de reserva, satisfacción, no-shows y tendencias mensuales.')],
        ['icon' => ['category' => 'business', 'name' => 'receipt'], 'title' => $this->t('Presupuestador con IA'), 'description' => $this->t('Genera presupuestos personalizados en segundos. La IA analiza el servicio y ajusta el precio a la complejidad.')],
        ['icon' => ['category' => 'business', 'name' => 'signature'], 'title' => $this->t('Firma Digital PAdES'), 'description' => $this->t('Contratos de servicio con firma digital avanzada via AutoFirma. Validez legal europea.')],
        ['icon' => ['category' => 'ui', 'name' => 'lock'], 'title' => $this->t('Buzón de Confianza'), 'description' => $this->t('Mensajería cifrada end-to-end entre cliente y profesional. Historial vinculado a cada reserva.')],
      ],
      'social_proof' => [
        'testimonials' => [
          ['quote' => $this->t('Antes perdía 2 horas al día gestionando citas por teléfono. Ahora mis clientes reservan solos y yo me centro en atenderles.'), 'author' => 'Carmen', 'role' => $this->t('fisioterapeuta en Madrid')],
          ['quote' => $this->t('El marketplace me trajo 15 clientes nuevos el primer mes. Las reseñas verificadas generan confianza inmediata.'), 'author' => 'David', 'role' => $this->t('consultor IT en Barcelona')],
        ],
        'metrics' => [
          ['value' => '95%', 'label' => $this->t('satisfacción de clientes')],
          ['value' => '60%', 'label' => $this->t('más reservas con agenda online')],
          ['value' => '< 5%', 'label' => $this->t('tasa de no-shows con recordatorios')],
        ],
      ],
      'lead_magnet' => [
        'title' => $this->t('Calculadora de Rentabilidad'),
        'description' => $this->t('Descubre en 2 minutos cuánto puedes facturar digitalizando tu negocio de servicios con reservas online y cobro automático.'),
        'url' => Url::fromRoute('user.register')->toString(),
        'cta_text' => $this->t('Calcular mi rentabilidad'),
        'icon' => ['category' => 'business', 'name' => 'chart'],
      ],
      'pricing' => [
        'headline' => $this->t('Planes para profesionales de servicios'),
        'from_price' => '0',
        'currency' => 'EUR',
        'period' => $this->t('mes'),
        'cta_text' => $this->t('Ver todos los planes'),
        'cta_url' => Url::fromRoute('ecosistema_jaraba_core.pricing.page')->toString(),
        'features_preview' => [
          $this->t('3 servicios y 10 reservas/mes gratis'),
          $this->t('Marketplace con perfil profesional'),
          $this->t('Cobro automático via Stripe'),
          $this->t('Copilot IA incluido'),
          $this->t('Presupuestador IA desde Starter'),
        ],
      ],
      'faq' => [
        ['question' => $this->t('¿Cómo funciona el motor de reservas?'), 'answer' => $this->t('Configuras tus horarios y servicios. Tus clientes ven la disponibilidad en tiempo real y reservan slots libres. Reciben confirmación automática por email y recordatorios antes de la cita.')],
        ['question' => $this->t('¿Puedo hacer videoconsultas con mis clientes?'), 'answer' => $this->t('Sí. Al crear una reserva de tipo online, se genera automáticamente una sala Jitsi Meet. Tu cliente recibe el enlace por email. Sin instalar apps.')],
        ['question' => $this->t('¿Cómo funciona el cobro via Stripe?'), 'answer' => $this->t('Conectas tu cuenta Stripe una vez. Cuando un cliente reserva, el pago se procesa automáticamente. Recibes el dinero en tu cuenta bancaria en 2-7 días, con comisión transparente del 10%.')],
        ['question' => $this->t('¿Qué es el marketplace profesional?'), 'answer' => $this->t('Un directorio público donde tus clientes te encuentran. Tu perfil incluye portfolio, certificaciones, reseñas verificadas y botón de reserva directa. Optimizado para Google.')],
        ['question' => $this->t('¿Puedo ofrecer paquetes de sesiones?'), 'answer' => $this->t('Sí. Crea bundles de sesiones con descuento (ej: 5 sesiones por el precio de 4). Tus clientes compran el bono y van consumiendo sesiones. Disponible desde plan Profesional.')],
        ['question' => $this->t('¿Cómo funcionan las reseñas?'), 'answer' => $this->t('Después de cada servicio completado, el cliente recibe una invitación a valorar. Las reseñas pasan por moderación automática y se publican con formato Schema.org para aparecer en Google.')],
        ['question' => $this->t('¿Qué hace el Copilot IA?'), 'answer' => $this->t('Analiza tu actividad y sugiere mejoras: optimización de precios según demanda, horarios con más reservas, servicios complementarios y tendencias de tu sector.')],
        ['question' => $this->t('¿Qué pasa si un cliente no se presenta?'), 'answer' => $this->t('El sistema detecta no-shows automáticamente. Puedes configurar política de cancelación con cobro parcial y el cliente queda marcado para futuras reservas.')],
        ['question' => $this->t('¿Cuánto cuesta comparado con otras plataformas?'), 'answer' => $this->t('Desde 0 €/mes (plan Free: 3 servicios, 10 reservas/mes). Frente a Doctolib, Treatwell o Calendly Pro (30-90 €/mes), accedes a reservas + marketplace + cobro + IA por una fracción.')],
        ['question' => $this->t('¿Puedo probarlo sin dar mi tarjeta?'), 'answer' => $this->t('Por supuesto. El plan Free incluye 3 servicios, 10 reservas/mes, perfil en marketplace y Copilot IA con 10 consultas/mes. Sin tarjeta de crédito.')],
        ['question' => $this->t('¿Cómo funciona el presupuestador automático?'), 'answer' => $this->t('Cuando un servicio tiene precio tipo "bajo presupuesto", la IA genera una estimación personalizada basada en la complejidad descrita por el cliente. El profesional revisa y envía el presupuesto final.')],
        ['question' => $this->t('¿La firma digital tiene validez legal?'), 'answer' => $this->t('Sí. Utilizamos firma PAdES (PDF Advanced Electronic Signatures) via AutoFirma con certificados FNMT. Validez legal en toda la Unión Europea según eIDAS.')],
        ['question' => $this->t('¿Cómo funciona el Buzón de Confianza?'), 'answer' => $this->t('Al confirmar una reserva, se abre automáticamente un canal cifrado end-to-end entre cliente y profesional. Los mensajes, archivos y documentos quedan vinculados a la reserva con trazabilidad completa.')],
      ],
      'final_cta' => [
        'headline' => $this->t('Digitaliza tu negocio de servicios hoy mismo'),
        'cta' => [
          'text' => $this->t('Crea tu perfil profesional gratis'),
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
        'subheadline' => $this->t('Diagnóstico IA en 3 minutos, CV Builder con 4 plantillas, matching inteligente, simulador de entrevistas, health score profesional, copilot con 6 modos IA, LMS gamificado y portal de empleo completo. Desde 0 €/mes.'),
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
        ['title' => $this->t('Haz tu diagnóstico'), 'description' => $this->t('En 3 minutos la IA evalúa tu nivel en 3 dimensiones (LinkedIn, CV ATS, estrategia), te asigna un perfil y genera un plan de acción con PDF.')],
        ['title' => $this->t('Aprende y practica'), 'description' => $this->t('Rutas formativas adaptativas, simulador de entrevistas con IA, CV Builder con 4 plantillas profesionales y copilot con 6 modos especializados.')],
        ['title' => $this->t('Consigue tu trabajo'), 'description' => $this->t('Portal de empleo con matching inteligente, alertas personalizadas, web push, credenciales verificables y acompañamiento hasta la contratación.')],
      ],
      'features' => [
        // --- 6 features originales, mejoradas ---
        ['icon' => ['category' => 'ai', 'name' => 'screening'], 'title' => $this->t('Diagnóstico Express con IA'), 'description' => $this->t('3 preguntas, 3 minutos. La IA evalúa tu nivel LinkedIn (40%), CV ATS (35%) y estrategia (25%). Te asigna uno de 5 perfiles con plan de acción y PDF descargable.')],
        ['icon' => ['category' => 'business', 'name' => 'path'], 'title' => $this->t('Rutas Formativas Adaptativas'), 'description' => $this->t('LMS completo con cursos, lecciones y actividades. El motor adaptativo ajusta contenido a tu nivel. Progreso con xAPI, certificaciones y gamificación.')],
        ['icon' => ['category' => 'business', 'name' => 'cv-optimized'], 'title' => $this->t('CV Builder con IA'), 'description' => $this->t('Responde preguntas sobre tu experiencia y la IA genera un CV profesional optimizado para ATS. 4 plantillas: Modern, Tech, Classic y Minimal. Descarga en PDF.')],
        ['icon' => ['category' => 'business', 'name' => 'target'], 'title' => $this->t('Matching Inteligente de Ofertas'), 'description' => $this->t('Algoritmo ML que calcula tu match score con cada oferta. Solo ves ofertas que encajan con tu experiencia real. Alertas configurables y favoritos.')],
        ['icon' => ['category' => 'business', 'name' => 'interview'], 'title' => $this->t('Simulador de Entrevistas con IA'), 'description' => $this->t('Practica con preguntas reales de tu sector. La IA te da feedback en tiempo real, mejora tus respuestas y te prepara para la entrevista real.')],
        ['icon' => ['category' => 'business', 'name' => 'achievement'], 'title' => $this->t('Credenciales Verificables'), 'description' => $this->t('Certificados OpenBadge que empleadores verifican con un clic. Cada curso completado, cada habilidad validada — credenciales con validez real.')],
        // --- 9 features NUEVAS (código real existente) ---
        ['icon' => ['category' => 'analytics', 'name' => 'chart'], 'title' => $this->t('Health Score Profesional'), 'description' => $this->t('Puntuación 0-100 en 5 dimensiones: perfil, formación, aplicaciones, entrevistas y engagement. Ve tu empleabilidad en tiempo real y dónde mejorar.')],
        ['icon' => ['category' => 'ai', 'name' => 'brain'], 'title' => $this->t('Copilot IA con 6 Modos'), 'description' => $this->t('Asistente IA que detecta automáticamente lo que necesitas: coach de perfil, asesor laboral, preparación de entrevistas, guía formativa, optimizador de CV o ayuda general.')],
        ['icon' => ['category' => 'ui', 'name' => 'map'], 'title' => $this->t('Journey Personalizado'), 'description' => $this->t('3 itinerarios adaptados: Candidato (7 pasos de búsqueda a contratación), Empresa (6 pasos de publicación a contratación) y Orientador (gestión de casos). Nudges proactivos de la IA.')],
        ['icon' => ['category' => 'business', 'name' => 'search'], 'title' => $this->t('Portal de Empleo Completo'), 'description' => $this->t('Búsqueda avanzada de ofertas, aplicación con un clic, alertas personalizadas, favoritos y historial de aplicaciones. Todo integrado con tu perfil.')],
        ['icon' => ['category' => 'analytics', 'name' => 'dashboard'], 'title' => $this->t('Dashboard para Empresas'), 'description' => $this->t('Las empresas publican ofertas, reciben candidatos rankeados por IA, gestionan entrevistas con scorecard y acceden a analytics de contratación.')],
        ['icon' => ['category' => 'ui', 'name' => 'link'], 'title' => $this->t('Importación LinkedIn'), 'description' => $this->t('Importa tu perfil completo desde LinkedIn: experiencia, educación, habilidades y recomendaciones. La IA infiere competencias adicionales de tu historial.')],
        ['icon' => ['category' => 'ui', 'name' => 'mail'], 'title' => $this->t('Email Nurturing Automatizado'), 'description' => $this->t('5 secuencias automáticas: onboarding, reactivación, upsell, preparación entrevista y post-empleo. Emails adaptados a tu fase real en la plataforma.')],
        ['icon' => ['category' => 'ui', 'name' => 'star'], 'title' => $this->t('LMS Gamificado'), 'description' => $this->t('Badges, leaderboards, puntos XP y certificaciones verificables. Aprende compitiendo: cada curso completado suma a tu perfil y tus credenciales.')],
        ['icon' => ['category' => 'ui', 'name' => 'bell'], 'title' => $this->t('Notificaciones Web Push'), 'description' => $this->t('Alertas instantáneas cuando hay una oferta que encaja, cuando te preseleccionan o cuando tu aplicación avanza. Sin abrir la plataforma.')],
      ],
      'social_proof' => [
        'testimonials' => [
          ['quote' => $this->t('A los 52 pensé que no volvería a trabajar. El diagnóstico me mostró mis fortalezas ocultas y en 3 semanas encontré un puesto que encajaba perfecto.'), 'author' => 'Rosa', 'role' => $this->t('administrativa, 52 años')],
          ['quote' => $this->t('El simulador de entrevistas me quitó los nervios. El copilot me preparó preguntas reales de mi sector y fui a la entrevista mucho más seguro.'), 'author' => 'Miguel', 'role' => $this->t('técnico en Sevilla')],
          ['quote' => $this->t('Importé mi LinkedIn, la IA me generó un CV optimizado para ATS y empecé a recibir ofertas que realmente encajaban. El health score me motivó cada día.'), 'author' => 'Laura', 'role' => $this->t('marketing digital en Madrid')],
        ],
        'metrics' => [
          ['value' => '85%', 'label' => $this->t('tasa de colocación')],
          ['value' => '3 sem.', 'label' => $this->t('promedio para encontrar trabajo')],
          ['value' => '6', 'label' => $this->t('modos IA del copilot')],
          ['value' => '4', 'label' => $this->t('plantillas CV profesional')],
        ],
      ],
      'lead_magnet' => [
        'title' => $this->t('Diagnóstico Express TTV'),
        'description' => $this->t('3 preguntas, 3 minutos, reporte PDF. Descubre tu perfil de empleabilidad, tus gaps y tu plan de acción personalizado.'),
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
        'cta_url' => Url::fromRoute('ecosistema_jaraba_core.planes')->toString(),
        'features_preview' => [
          $this->t('Diagnóstico gratuito con PDF'),
          $this->t('CV Builder con IA (1 CV)'),
          $this->t('Copilot IA (5 mensajes/día)'),
          $this->t('Matching inteligente (10 ofertas/día)'),
          $this->t('Health score profesional'),
          $this->t('LMS gamificado con badges'),
        ],
      ],
      'faq' => [
        // --- 5 FAQs originales, mejoradas ---
        ['question' => $this->t('¿Es realmente gratuito el diagnóstico?'), 'answer' => $this->t('Sí, totalmente gratuito y sin pedir tarjeta de crédito. En 3 minutos evalúa tu nivel en 3 dimensiones (LinkedIn, CV ATS, estrategia), te asigna uno de 5 perfiles y genera un plan de acción descargable en PDF.')],
        ['question' => $this->t('¿Cómo funciona el CV Builder?'), 'answer' => $this->t('Respondes preguntas sobre tu experiencia y la IA genera un CV profesional optimizado para sistemas ATS. Elige entre 4 plantillas (Modern, Tech, Classic, Minimal) y descárgalo en PDF listo para enviar.')],
        ['question' => $this->t('¿Puedo practicar entrevistas con IA?'), 'answer' => $this->t('Sí, el simulador usa preguntas reales de tu sector. La IA te da feedback inmediato, analiza tus respuestas y te sugiere mejoras. Es uno de los 6 modos del copilot IA.')],
        ['question' => $this->t('¿Las ofertas están filtradas por mi sector?'), 'answer' => $this->t('El matching inteligente calcula un score de compatibilidad con cada oferta. Solo ves las que realmente encajan con tu experiencia, habilidades y preferencias. Puedes configurar alertas y web push.')],
        ['question' => $this->t('¿Qué son las credenciales verificables?'), 'answer' => $this->t('Certificados OpenBadge que los empleadores pueden verificar con un clic. Cada curso completado y habilidad validada genera una credencial con validez real y verificable.')],
        // --- 8 FAQs NUEVAS ---
        ['question' => $this->t('¿Qué es el health score profesional?'), 'answer' => $this->t('Una puntuación de 0 a 100 que mide tu empleabilidad en 5 dimensiones: completitud del perfil, formación, actividad de aplicaciones, preparación de entrevistas y engagement con la plataforma. Te muestra exactamente dónde mejorar.')],
        ['question' => $this->t('¿Qué hace el copilot IA?'), 'answer' => $this->t('Es un asistente con 6 modos especializados que detecta automáticamente lo que necesitas: coach de perfil y LinkedIn, asesor de mercado laboral, preparación de entrevistas, guía formativa, optimizador de CV y ayuda general de la plataforma.')],
        ['question' => $this->t('¿Puedo importar mi perfil de LinkedIn?'), 'answer' => $this->t('Sí, importa tu perfil completo: experiencia laboral, educación, habilidades y recomendaciones. La IA infiere competencias adicionales de tu historial para enriquecer tu perfil automáticamente.')],
        ['question' => $this->t('¿Cómo funciona el LMS gamificado?'), 'answer' => $this->t('Cursos con lecciones, actividades y evaluaciones. Sistema de puntos XP, badges por logros, leaderboards y certificaciones verificables OpenBadge. El motor adaptativo ajusta la dificultad a tu nivel.')],
        ['question' => $this->t('¿Las empresas también usan la plataforma?'), 'answer' => $this->t('Sí, las empresas tienen su propio dashboard: publican ofertas, reciben candidatos rankeados por IA, gestionan entrevistas con scorecard de competencias y acceden a analytics de contratación.')],
        ['question' => $this->t('¿Recibo notificaciones de ofertas nuevas?'), 'answer' => $this->t('Sí, configura alertas por sector, ubicación y salario. Recibes notificaciones web push instantáneas cuando hay una oferta compatible, cuando te preseleccionan o cuando tu aplicación avanza.')],
        ['question' => $this->t('¿Qué emails automatizados recibiré?'), 'answer' => $this->t('5 secuencias adaptadas a tu fase: bienvenida y onboarding, reactivación si llevas días sin actividad, preparación pre-entrevista, sugerencia de upgrade cuando alcanzas límites y seguimiento post-empleo.')],
        ['question' => $this->t('¿Qué incluye el plan gratuito?'), 'answer' => $this->t('1 diagnóstico con PDF, 1 CV con IA, 5 mensajes de copilot al día, 10 ofertas visibles al día, 3 aplicaciones diarias, 1 alerta activa, health score profesional, LMS gamificado y credenciales verificables. Sin tarjeta de crédito.')],
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
        'subheadline' => $this->t('Calculadora de madurez, Business Model Canvas con IA, validación de MVP, mentoría 1:1, health score emprendedor, 15 insignias digitales, copilot proactivo y acceso a financiación. Todo en una plataforma.'),
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
        ['title' => $this->t('Diagnostica tu idea'), 'description' => $this->t('Calculadora de madurez digital + evaluación IA de viabilidad. Reporte PDF en 5 minutos con gaps y prioridades.')],
        ['title' => $this->t('Diseña y valida'), 'description' => $this->t('Business Model Canvas con IA + hipótesis + motor de experimentos A/B. Lean Startup sin teoría: datos reales.')],
        ['title' => $this->t('Lanza y crece'), 'description' => $this->t('Mentoría 1:1 + financiación + credenciales digitales + puentes a Formación, Comercio y Servicios.')],
      ],
      'features' => [
        // --- 6 features originales, mejoradas ---
        ['icon' => ['category' => 'analytics', 'name' => 'dashboard'], 'title' => $this->t('Calculadora de Madurez Digital'), 'description' => $this->t('Diagnóstico completo de tu nivel digital, operacional y financiero. Reporte PDF personalizado en menos de 5 minutos con plan de acción.')],
        ['icon' => ['category' => 'business', 'name' => 'canvas'], 'title' => $this->t('Business Model Canvas con IA'), 'description' => $this->t('Describe tu problema, solución y clientes, y la IA genera un canvas coherente. Iterativo: refina bloques hasta que el modelo sea sólido.')],
        ['icon' => ['category' => 'ai', 'name' => 'lightbulb'], 'title' => $this->t('Validación de MVP con Lean Startup'), 'description' => $this->t('Crea hipótesis, diseña experimentos baratos y obtén feedback real. Motor de experimentos A/B integrado con 10 eventos de conversión.')],
        ['icon' => ['category' => 'ui', 'name' => 'users'], 'title' => $this->t('Mentoría 1:1 con Empresarios'), 'description' => $this->t('Sesiones individuales con empresarios experimentados. Matching inteligente según tu sector y fase. Seguimiento continuo de cada sesión.')],
        ['icon' => ['category' => 'business', 'name' => 'money'], 'title' => $this->t('Proyecciones Financieras'), 'description' => $this->t('Plantillas profesionales para tu plan de negocio: ingresos, gastos, punto de equilibrio, escenarios. La IA sugiere métricas del sector.')],
        ['icon' => ['category' => 'business', 'name' => 'achievement'], 'title' => $this->t('Acceso a Financiación'), 'description' => $this->t('Conexión directa con líneas ICO, ENISA, ángeles inversores y aceleradoras. Alertas automáticas cuando cumples criterios de elegibilidad.')],
        // --- 9 features NUEVAS (código real existente) ---
        ['icon' => ['category' => 'analytics', 'name' => 'chart'], 'title' => $this->t('Health Score Emprendedor'), 'description' => $this->t('Puntuación 0-100 en 5 dimensiones: canvas, hipótesis validadas, velocidad de experimentos, engagement con copilot y preparación financiera.')],
        ['icon' => ['category' => 'business', 'name' => 'certificate'], 'title' => $this->t('15 Insignias + 3 Diplomas Digitales'), 'description' => $this->t('Gana insignias por cada hito: primer canvas, MVP lanzado, primera venta, pitch preparado. 3 diplomas compuestos: Básico, Avanzado y Expert.')],
        ['icon' => ['category' => 'ui', 'name' => 'star'], 'title' => $this->t('Niveles de Expertise con Beneficios'), 'description' => $this->t('5 niveles de explorador a master con XP acumulado. Cada nivel desbloquea beneficios: mentoría prioritaria, descuentos, acceso a eventos.')],
        ['icon' => ['category' => 'ai', 'name' => 'brain'], 'title' => $this->t('Copilot IA Proactivo'), 'description' => $this->t('7 reglas de activación automática: detecta inactividad, canvas incompleto, hipótesis estancadas o financiación disponible y te nudgea con el modo IA adecuado.')],
        ['icon' => ['category' => 'ui', 'name' => 'map'], 'title' => $this->t('Journey Personalizado por Avatar'), 'description' => $this->t('3 itinerarios adaptados: Emprendedor (5 fases, 10 pasos), Mentor y Gestor de Programa. Cada paso con intervención IA y tracking de progreso.')],
        ['icon' => ['category' => 'analytics', 'name' => 'experiments'], 'title' => $this->t('Motor de Experimentos A/B'), 'description' => $this->t('Framework completo para testear ideas: 4 ámbitos de experimento, 10 eventos de conversión rastreados y resultados con significancia estadística.')],
        ['icon' => ['category' => 'ui', 'name' => 'share-2'], 'title' => $this->t('Puentes a Formación, Comercio y Servicios'), 'description' => $this->t('Cuando tu startup crece, conexión inteligente con otros verticales: formación para tu equipo, comercio para vender, servicios para externalizar.')],
        ['icon' => ['category' => 'ui', 'name' => 'mail'], 'title' => $this->t('Email Nurturing Automatizado'), 'description' => $this->t('Secuencias de email personalizadas según tu fase: activación, engagement, conversión. Contenido adaptado a tu progreso real en la plataforma.')],
        ['icon' => ['category' => 'business', 'name' => 'cart'], 'title' => $this->t('Cross-Sell Inteligente'), 'description' => $this->t('4 ofertas personalizadas según tu momento: curso modelo de negocio, kit de validación, preparación de pitch y membresía comunidad.')],
      ],
      'social_proof' => [
        'testimonials' => [
          ['quote' => $this->t('La calculadora de madurez me abrió los ojos. Estaba invirtiendo en lo que no necesitaba. En 3 meses lancé mi MVP con el canvas de IA.'), 'author' => 'Carlos', 'role' => $this->t('emprendedor en Bilbao')],
          ['quote' => $this->t('El Canvas con IA me ahorró meses de prueba y error. Las insignias me motivaron y el copilot proactivo me avisó de financiación ENISA.'), 'author' => 'Ana', 'role' => $this->t('startup en Málaga')],
          ['quote' => $this->t('El health score me mostró que tenía el canvas perfecto pero 0 validación real. El motor de experimentos cambió todo: 3 hipótesis validadas en 2 semanas.'), 'author' => 'Javier', 'role' => $this->t('emprendedor en Valencia')],
        ],
        'metrics' => [
          ['value' => '340+', 'label' => $this->t('negocios lanzados')],
          ['value' => '€2.3M', 'label' => $this->t('financiación conseguida')],
          ['value' => '15', 'label' => $this->t('insignias digitales')],
          ['value' => '<60d', 'label' => $this->t('tiempo medio a MVP')],
        ],
      ],
      'lead_magnet' => [
        'title' => $this->t('Calculadora de Madurez Digital'),
        'description' => $this->t('Análisis en menos de 5 minutos con reporte PDF personalizado. Diagnóstico digital, operacional y financiero con plan de acción.'),
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
        'cta_url' => Url::fromRoute('ecosistema_jaraba_core.planes')->toString(),
        'features_preview' => [
          $this->t('Calculadora de madurez + reporte PDF'),
          $this->t('Business Model Canvas con IA'),
          $this->t('Motor de experimentos A/B'),
          $this->t('Health score emprendedor'),
          $this->t('15 insignias + 3 diplomas'),
          $this->t('Copilot IA proactivo'),
        ],
      ],
      'faq' => [
        // --- 5 FAQs originales, mejoradas ---
        ['question' => $this->t('¿Qué es la Calculadora de Madurez?'), 'answer' => $this->t('Diagnóstico de tu negocio en 3 dimensiones: digital, operacional y financiero. Identifica gaps, prioridades y genera un reporte PDF con plan de acción personalizado en menos de 5 minutos.')],
        ['question' => $this->t('¿Cómo me ayuda la IA en el Business Model Canvas?'), 'answer' => $this->t('Describes tu problema, solución y clientes, y la IA genera un canvas coherente con los 9 bloques. Es iterativo: puedes refinar cada bloque con sugerencias de la IA hasta que el modelo sea sólido.')],
        ['question' => $this->t('¿Puedo acceder a mentoría directa?'), 'answer' => $this->t('Sí, desde el plan Starter tienes sesiones 1:1 con empresarios exitosos. El matching es inteligente según tu sector y fase. En plan Pro las sesiones son ilimitadas.')],
        ['question' => $this->t('¿Cómo valido mi MVP sin gastar?'), 'answer' => $this->t('El motor de experimentos A/B te guía: creas hipótesis, diseñas experimentos baratos y mides resultados reales. 4 ámbitos de experimento y 10 eventos de conversión para datos fiables.')],
        ['question' => $this->t('¿Cómo accedo a financiación?'), 'answer' => $this->t('Conectamos con líneas ICO, ENISA, ángeles inversores y aceleradoras. El copilot proactivo te avisa automáticamente cuando cumples criterios de elegibilidad para cada programa.')],
        // --- 8 FAQs NUEVAS ---
        ['question' => $this->t('¿Qué es el health score emprendedor?'), 'answer' => $this->t('Una puntuación de 0 a 100 que mide tu progreso en 5 dimensiones: completitud del canvas, hipótesis validadas, velocidad de experimentos, engagement con el copilot y preparación financiera. Te ayuda a ver dónde enfocar esfuerzos.')],
        ['question' => $this->t('¿Cómo funcionan las insignias y diplomas?'), 'answer' => $this->t('Ganas insignias automáticamente al alcanzar hitos: primer canvas, MVP lanzado, primera venta, pitch preparado, etc. Son 15 insignias que se combinan en 3 diplomas: Emprendedor Digital Básico, Avanzado y Expert.')],
        ['question' => $this->t('¿Qué son los niveles de expertise?'), 'answer' => $this->t('Un sistema de progresión con XP acumulado en 5 niveles: Explorador, Iniciado, Profesional, Experto y Master. Cada nivel desbloquea beneficios reales: mentoría prioritaria, descuentos, invitaciones a eventos exclusivos.')],
        ['question' => $this->t('¿El copilot actúa por su cuenta?'), 'answer' => $this->t('El copilot tiene 7 reglas de activación: detecta inactividad, canvas incompleto, hipótesis estancadas, MVP validado sin mentor, financiación disponible no explorada, etc. Te sugiere acciones con el modo IA adecuado: consultor, sparring, coach financiero.')],
        ['question' => $this->t('¿Qué es el journey personalizado?'), 'answer' => $this->t('Son 3 itinerarios adaptados a tu rol: Emprendedor (5 fases con 10 pasos desde la idea hasta la escala), Mentor (4 pasos de acompañamiento) y Gestor de Programa (monitorización de KPIs). Cada paso tiene intervención IA.')],
        ['question' => $this->t('¿Puedo conectar con otros servicios de la plataforma?'), 'answer' => $this->t('Sí, los puentes cross-vertical se activan según tu momento: formación para tu equipo cuando escalas, comercio para vender tu producto post-MVP y servicios para externalizar desarrollo técnico.')],
        ['question' => $this->t('¿Cómo funciona el motor de experimentos?'), 'answer' => $this->t('Creas hipótesis sobre tu negocio, diseñas experimentos A/B con métricas claras y la plataforma rastrea 10 eventos de conversión para darte resultados con significancia estadística. 4 ámbitos: onboarding, UX del canvas, engagement copilot y funnel de upgrade.')],
        ['question' => $this->t('¿Qué incluye el plan gratuito?'), 'answer' => $this->t('Calculadora de madurez (1 uso), 1 Business Model Canvas, 3 hipótesis activas, 2 experimentos al mes, 5 sesiones diarias de copilot IA y acceso a la comunidad de emprendedores. Sin tarjeta de crédito.')],
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
        'subheadline' => $this->t('Matching inteligente en 5 dimensiones, mini-ATS con 8 estados, asistente IA del reclutador con 6 acciones, perfil de empresa verificado, notificaciones multi-canal y detección de fraude anti-spam.'),
        'icon' => ['category' => 'business', 'name' => 'talent-search'],
        'cta' => [
          'text' => $this->t('Publicar oferta'),
          'url' => Url::fromRoute('user.register')->toString(),
        ],
        'cta_secondary' => [
          'text' => $this->t('Ver planes'),
          'url' => Url::fromRoute('ecosistema_jaraba_core.planes')->toString(),
        ],
      ],
      'pain_points' => [
        'title' => $this->t('Retos de la contratación actual'),
        'items' => [
          ['title' => $this->t('CVs desbordantes'), 'description' => $this->t('Recibes cientos de candidaturas y no tienes tiempo para revisarlas todas con la atención que merecen. Necesitas filtrado inteligente.')],
          ['title' => $this->t('Falta de matching real'), 'description' => $this->t('Los portales genéricos no filtran por competencias reales. Nuestro algoritmo pondera skills, experiencia, educación, ubicación y semántica.')],
          ['title' => $this->t('Procesos lentos y desconectados'), 'description' => $this->t('Desde la publicación hasta la contratación pasan semanas de gestión manual, emails y hojas de cálculo sin trazabilidad.')],
        ],
      ],
      'steps' => [
        'title' => $this->t('Cómo funciona'),
        'items' => [
          ['step' => '1', 'title' => $this->t('Publica tu oferta'), 'description' => $this->t('Define el perfil con 30+ campos: competencias técnicas, soft skills, salario, modalidad remota, ubicación. El Asistente IA te ayuda a redactar la oferta perfecta y predice +45% más candidaturas.')],
          ['step' => '2', 'title' => $this->t('Matching automático 5D'), 'description' => $this->t('El algoritmo analiza candidaturas ponderando: skills (35%), experiencia (20%), educación (10%), ubicación (15%) y similitud semántica via Qdrant (20%). Score de compatibilidad 0-100.')],
          ['step' => '3', 'title' => $this->t('Gestiona con mini-ATS'), 'description' => $this->t('Pipeline de 8 estados (aplicado, screening, preseleccionado, entrevistado, ofertado, contratado). Evalúa en equipo, programa entrevistas y gestiona todo desde un panel unificado.')],
        ],
      ],
      'features' => [
        // --- 4 features originales mejoradas ---
        ['icon' => ['category' => 'ui', 'name' => 'search'], 'title' => $this->t('Búsqueda Avanzada de Talento'), 'description' => $this->t('Filtra por skills, experiencia, disponibilidad, ubicación, modalidad remota, rango salarial y nivel educativo. Búsqueda semántica via Qdrant para encontrar perfiles que los filtros básicos no detectan.')],
        ['icon' => ['category' => 'ai', 'name' => 'screening'], 'title' => $this->t('Asistente IA del Reclutador'), 'description' => $this->t('RecruiterAssistantAgent con 6 acciones: screening automático, ranking por score, optimización de ofertas (+45% candidaturas), generación de preguntas de entrevista, analytics del proceso y comunicaciones con candidatos.')],
        ['icon' => ['category' => 'analytics', 'name' => 'dashboard'], 'title' => $this->t('Dashboard Analytics Completo'), 'description' => $this->t('Métricas en tiempo real: ofertas activas, candidaturas pendientes, tasa de conversión (candidaturas/vistas), tiempo medio de contratación y desglose de rendimiento por oferta.')],
        ['icon' => ['category' => 'ui', 'name' => 'users'], 'title' => $this->t('Gestión Colaborativa en Equipo'), 'description' => $this->t('Invita compañeros para evaluar, comentar y votar candidatos. Todo queda en el historial de la candidatura con trazabilidad completa por usuario y fecha.')],
        // --- 8 features nuevas respaldadas por codigo real ---
        ['icon' => ['category' => 'business', 'name' => 'workflow'], 'title' => $this->t('Mini-ATS con 8 Estados'), 'description' => $this->t('Pipeline profesional de selección: aplicado, screening, preseleccionado, entrevistado, ofertado, contratado, rechazado o retirado. Cada transición notifica automáticamente al candidato.')],
        ['icon' => ['category' => 'ai', 'name' => 'matching'], 'title' => $this->t('Matching Inteligente 5 Dimensiones'), 'description' => $this->t('Algoritmo ponderado: skills técnicas (35%), experiencia (20%), educación (10%), ubicación geográfica (15%) y similitud semántica Qdrant (20%). Score de compatibilidad 0-100 por candidato.')],
        ['icon' => ['category' => 'business', 'name' => 'verified'], 'title' => $this->t('Perfil de Empresa Verificado'), 'description' => $this->t('Crea tu perfil con logo, sector, tamaño, web y LinkedIn. Las empresas verificadas aparecen destacadas y generan mayor confianza en los candidatos.')],
        ['icon' => ['category' => 'business', 'name' => 'health-score'], 'title' => $this->t('Health Score del Reclutador'), 'description' => $this->t('Puntuación 0-100 de la salud de tu proceso de selección con 5 fases: Arrancando, Construyendo, Creciendo, Competitivo y Líder Talento. Detección automática de gaps y rutas de optimización.')],
        ['icon' => ['category' => 'ui', 'name' => 'notification'], 'title' => $this->t('Notificaciones Multi-Canal'), 'description' => $this->t('Emails automáticos por cada cambio de estado del ATS (al candidato y al reclutador). Notificaciones Web Push via VAPID sin dependencia de Firebase. Webhooks ActiveCampaign.')],
        ['icon' => ['category' => 'ui', 'name' => 'bell'], 'title' => $this->t('Alertas de Talento Inteligentes'), 'description' => $this->t('Los candidatos configuran alertas por keywords, ubicación, modalidad, salario y tipo de contrato. Seguimiento de empresa para recibir nuevas ofertas. Digest diario o semanal.')],
        ['icon' => ['category' => 'ui', 'name' => 'shield'], 'title' => $this->t('Detección de Fraude Anti-Spam'), 'description' => $this->t('Sistema automático que detecta candidaturas sospechosas: más de 20 aplicaciones por hora = 100% spam, más de 10 = revisión manual. Integrado con jaraba_predictive para scoring de calidad.')],
        ['icon' => ['category' => 'business', 'name' => 'api'], 'title' => $this->t('API REST Completa'), 'description' => $this->t('Endpoints para publicar ofertas, recibir candidaturas, consultar scores de matching, gestionar estados del ATS y conectar con tu sistema de RRHH existente. Documentación OpenAPI.')],
      ],
      'social_proof' => [
        'metrics' => [
          ['value' => '70', 'suffix' => '%', 'label' => $this->t('Reducción tiempo de contratación')],
          ['value' => '5', 'suffix' => 'D', 'label' => $this->t('Dimensiones del matching IA')],
          ['value' => '8', 'suffix' => '', 'label' => $this->t('Estados del pipeline ATS')],
          ['value' => '24', 'suffix' => 'h', 'label' => $this->t('Primeros matches disponibles')],
        ],
        'testimonials' => [
          ['name' => 'Carmen Ruiz', 'role' => $this->t('Directora de RRHH — Tecnológica, Sevilla'), 'quote' => $this->t('El matching inteligente nos ahorra 15 horas semanales de criba curricular. El score de compatibilidad es sorprendentemente preciso y el ATS integrado elimina las hojas de cálculo.')],
          ['name' => 'David Moreno', 'role' => $this->t('CEO — Startup SaaS, Madrid'), 'quote' => $this->t('Publicamos una oferta de developer senior y en 24 horas teníamos los 5 mejores perfiles rankeados por IA. El asistente incluso nos sugirió preguntas de entrevista técnica.')],
          ['name' => 'Elena Torres', 'role' => $this->t('Responsable de Selección — Consultora, Málaga'), 'quote' => $this->t('La gestión colaborativa permite que todo el equipo evalúe candidatos sin reuniones interminables. Las notificaciones automáticas mantienen informados a los candidatos en cada paso.')],
        ],
      ],
      'lead_magnet' => [
        'title' => $this->t('Guía: Cómo reducir un 70% el tiempo de contratación con IA'),
        'description' => $this->t('Aprende cómo empresas reales han transformado sus procesos de selección con matching inteligente, ATS automatizado y asistente IA.'),
        'resource_url' => '/recursos/guia-talento-ia',
        'cta_text' => $this->t('Descargar guía gratuita'),
      ],
      'pricing' => [
        'title' => $this->t('Planes para empresas'),
        'subtitle' => $this->t('Desde plan gratuito con búsqueda básica hasta Enterprise con API, white-label y soporte dedicado'),
        'cta_url' => Url::fromRoute('ecosistema_jaraba_core.planes')->toString(),
        'features_preview' => [
          $this->t('Búsqueda de talento desde Free'),
          $this->t('Matching IA 5D desde Starter'),
          $this->t('Mini-ATS completo desde Starter'),
          $this->t('Asistente IA ilimitado en Profesional'),
          $this->t('API REST + white-label en Enterprise'),
          $this->t('Soporte dedicado en Enterprise'),
        ],
      ],
      'faq' => [
        // --- 4 FAQs originales mejoradas ---
        ['question' => $this->t('¿Cuántas ofertas puedo publicar?'), 'answer' => $this->t('El plan Free permite 1 oferta activa. El plan Starter incluye 3 ofertas activas simultáneas con matching IA. Los planes Profesional y Enterprise incluyen ofertas ilimitadas, ATS completo y asistente IA.')],
        ['question' => $this->t('¿Cómo funciona el matching con IA?'), 'answer' => $this->t('El MatchingService analiza cada candidatura en 5 dimensiones ponderadas: skills técnicas (35%), experiencia laboral (20%), nivel educativo (10%), ubicación geográfica (15%) y similitud semántica via Qdrant (20%). Genera un score de compatibilidad 0-100 por candidato.')],
        ['question' => $this->t('¿Puedo gestionar candidatos en equipo?'), 'answer' => $this->t('Sí. Puedes invitar a compañeros de equipo para que evalúen, comenten y voten candidatos. Todo queda registrado en el historial de la candidatura con trazabilidad completa: quién evaluó, cuándo y qué puntuación dio.')],
        ['question' => $this->t('¿Se integra con otros ATS?'), 'answer' => $this->t('Ofrecemos API REST completa con endpoints para publicar ofertas, gestionar candidaturas, consultar scores de matching y actualizar estados del pipeline. Documentación OpenAPI disponible para planes Profesional y Enterprise.')],
        // --- 6 FAQs nuevas ---
        ['question' => $this->t('¿Qué es el mini-ATS y cómo funciona?'), 'answer' => $this->t('Es un sistema de seguimiento de candidaturas integrado con 8 estados: aplicado, screening, preseleccionado, entrevistado, ofertado, contratado, rechazado y retirado. Cada transición de estado notifica automáticamente al candidato por email y push.')],
        ['question' => $this->t('¿Qué hace el Asistente IA del Reclutador?'), 'answer' => $this->t('El RecruiterAssistantAgent ofrece 6 acciones: screening automático de candidatos, ranking por score de compatibilidad, optimización de descripciones de oferta (+45% candidaturas), generación de preguntas de entrevista por rol, analytics del proceso de selección y redacción de comunicaciones con candidatos.')],
        ['question' => $this->t('¿Cómo funciona el Health Score del Reclutador?'), 'answer' => $this->t('Es una puntuación 0-100 que evalúa la salud de tu proceso de selección. Detecta automáticamente gaps como ofertas sin salario, candidatos sin revisar o branding débil. Te guía por 5 fases: Arrancando, Construyendo, Creciendo, Competitivo y Líder Talento.')],
        ['question' => $this->t('¿Cómo protege la plataforma contra candidaturas fraudulentas?'), 'answer' => $this->t('El sistema JobApplicationSpamRule detecta patrones sospechosos: más de 20 aplicaciones por hora se marcan como spam (100%), más de 10 como sospechosas (50%). El módulo jaraba_predictive asigna scores de calidad adicionales.')],
        ['question' => $this->t('¿Qué notificaciones reciben los candidatos?'), 'answer' => $this->t('Los candidatos reciben emails automáticos en cada cambio de estado del ATS: confirmación de candidatura, aviso de revisión, preselección, entrevista programada, oferta recibida y resultado final. También notificaciones Web Push para nuevas ofertas que coincidan con sus alertas.')],
        ['question' => $this->t('¿Es gratuito empezar a buscar talento?'), 'answer' => $this->t('Sí. El plan Free incluye 1 oferta activa, búsqueda básica de candidatos y notificaciones por email. Para matching IA, ATS completo y asistente del reclutador, los planes de pago empiezan en 29 EUR/mes.')],
      ],
      'final_cta' => [
        'headline' => $this->t('¿Listo para encontrar talento con IA?'),
        'subheadline' => $this->t('Matching inteligente, ATS integrado y asistente IA del reclutador. Empieza gratis.'),
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
