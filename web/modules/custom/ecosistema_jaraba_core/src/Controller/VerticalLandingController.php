<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

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
class VerticalLandingController extends ControllerBase {

  // =========================================================================
  // F4 LANDING PAGES — 9 secciones completas (Doc 180)
  // =========================================================================

  /**
   * Landing AgroConecta — Productores rurales.
   *
   * Ruta: /agroconecta
   */
  public function agroconecta(): array {
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
  public function comercioconecta(): array {
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
  public function serviciosconecta(): array {
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
  public function empleabilidad(): array {
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
  public function emprendimientoLanding(): array {
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

  // =========================================================================
  // LANDING PAGES LEGACY — Refactorizadas al formato de 9 secciones
  // =========================================================================

  /**
   * Landing Empleabilidad - Candidatos (legacy).
   *
   * Ruta: /empleo
   */
  public function empleo(): array {
    return $this->buildLanding([
      'key' => 'empleo',
      'color' => 'innovation',
      'hero' => [
        'headline' => $this->t('Encuentra tu próximo empleo'),
        'subheadline' => $this->t('Ofertas personalizadas con IA, preparación de entrevistas y seguimiento profesional'),
        'icon' => ['category' => 'verticals', 'name' => 'briefcase'],
        'cta' => [
          'text' => $this->t('Crear perfil gratis'),
          'url' => Url::fromRoute('user.register')->toString(),
        ],
        'cta_secondary' => [
          'text' => $this->t('Ya tengo cuenta'),
          'url' => Url::fromRoute('user.login')->toString(),
        ],
      ],
      'features' => [
        ['icon' => ['category' => 'business', 'name' => 'target'], 'title' => $this->t('Matching inteligente'), 'description' => $this->t('Ofertas que realmente encajan contigo basadas en tus competencias')],
        ['icon' => ['category' => 'business', 'name' => 'cv-optimized'], 'title' => $this->t('CV optimizado'), 'description' => $this->t('El copiloto te ayuda a mejorar tu CV para cada oferta')],
        ['icon' => ['category' => 'business', 'name' => 'interview'], 'title' => $this->t('Prepara entrevistas'), 'description' => $this->t('Simulaciones con IA para llegar seguro a tus entrevistas')],
        ['icon' => ['category' => 'business', 'name' => 'tracking-board'], 'title' => $this->t('Seguimiento de candidaturas'), 'description' => $this->t('Dashboard para ver el estado de todas tus aplicaciones')],
      ],
      'final_cta' => [
        'headline' => $this->t('¿Listo para encontrar tu empleo?'),
        'cta' => [
          'text' => $this->t('Crear perfil gratis'),
          'url' => Url::fromRoute('user.register')->toString(),
        ],
      ],
    ]);
  }

  /**
   * Landing Empleabilidad - Empresas/Reclutadores (legacy).
   *
   * Ruta: /talento
   */
  public function talento(): array {
    return $this->buildLanding([
      'key' => 'talento',
      'color' => 'innovation',
      'hero' => [
        'headline' => $this->t('Encuentra el talento que necesitas'),
        'subheadline' => $this->t('Filtrado inteligente, matching por competencias y gestión simplificada'),
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
      'features' => [
        ['icon' => ['category' => 'ui', 'name' => 'search'], 'title' => $this->t('Búsqueda avanzada'), 'description' => $this->t('Filtra por skills, experiencia, disponibilidad y ubicación')],
        ['icon' => ['category' => 'ai', 'name' => 'screening'], 'title' => $this->t('Preselección con IA'), 'description' => $this->t('El copiloto analiza CVs y sugiere los mejores candidatos')],
        ['icon' => ['category' => 'analytics', 'name' => 'dashboard'], 'title' => $this->t('Analytics de contratación'), 'description' => $this->t('Métricas de tiempo, conversión y calidad de contrataciones')],
        ['icon' => ['category' => 'ui', 'name' => 'users'], 'title' => $this->t('Gestión colaborativa'), 'description' => $this->t('Tu equipo puede evaluar y comentar candidatos en tiempo real')],
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
   * Landing Emprendimiento (legacy).
   *
   * Ruta: /emprender
   */
  public function emprender(): array {
    return $this->buildLanding([
      'key' => 'emprender',
      'color' => 'impulse',
      'hero' => [
        'headline' => $this->t('Valida tu idea con metodología'),
        'subheadline' => $this->t('Lean Startup, Business Model Canvas y un copiloto IA que te guía paso a paso'),
        'icon' => ['category' => 'verticals', 'name' => 'rocket'],
        'cta' => [
          'text' => $this->t('Empezar ahora'),
          'url' => Url::fromRoute('user.register')->toString(),
        ],
        'cta_secondary' => [
          'text' => $this->t('Ver metodología'),
          'url' => '/metodologia',
        ],
      ],
      'features' => [
        ['icon' => ['category' => 'ai', 'name' => 'lightbulb'], 'title' => $this->t('Valida tu idea'), 'description' => $this->t('Metodología Lean para validar hipótesis antes de invertir')],
        ['icon' => ['category' => 'business', 'name' => 'canvas'], 'title' => $this->t('Business Model Canvas'), 'description' => $this->t('Diseña tu modelo de negocio con asistencia del copiloto')],
        ['icon' => ['category' => 'business', 'name' => 'target'], 'title' => $this->t('Tareas guiadas'), 'description' => $this->t('El copiloto te asigna tareas y desbloquea etapas progresivas')],
        ['icon' => ['category' => 'business', 'name' => 'achievement'], 'title' => $this->t('Acceso a mentores'), 'description' => $this->t('Conecta con expertos que te ayudan a crecer')],
      ],
      'final_cta' => [
        'headline' => $this->t('¿Listo para emprender?'),
        'cta' => [
          'text' => $this->t('Empezar ahora'),
          'url' => Url::fromRoute('user.register')->toString(),
        ],
      ],
    ]);
  }

  /**
   * Landing Comercio/Marketplace (legacy).
   *
   * Ruta: /comercio
   */
  public function comercio(): array {
    return $this->buildLanding([
      'key' => 'comercio',
      'color' => 'success',
      'hero' => [
        'headline' => $this->t('Vende tus productos y servicios'),
        'subheadline' => $this->t('Marketplace de impacto con visibilidad local, pagos seguros y gestión simplificada'),
        'icon' => ['category' => 'business', 'name' => 'cart'],
        'cta' => [
          'text' => $this->t('Crear mi tienda'),
          'url' => Url::fromRoute('user.register')->toString(),
        ],
        'cta_secondary' => [
          'text' => $this->t('Ver marketplace'),
          'url' => '/marketplace',
        ],
      ],
      'features' => [
        ['icon' => ['category' => 'ui', 'name' => 'storefront'], 'title' => $this->t('Tu tienda online'), 'description' => $this->t('Perfil profesional con catálogo de productos y servicios')],
        ['icon' => ['category' => 'business', 'name' => 'money'], 'title' => $this->t('Pagos seguros'), 'description' => $this->t('Stripe Connect para cobros directos en tu cuenta')],
        ['icon' => ['category' => 'verticals', 'name' => 'leaf'], 'title' => $this->t('Producción local'), 'description' => $this->t('Destaca tu origen local y prácticas sostenibles')],
        ['icon' => ['category' => 'ui', 'name' => 'package'], 'title' => $this->t('Gestión de pedidos'), 'description' => $this->t('Dashboard para gestionar pedidos y envíos')],
      ],
      'final_cta' => [
        'headline' => $this->t('¿Listo para vender online?'),
        'cta' => [
          'text' => $this->t('Crear mi tienda'),
          'url' => Url::fromRoute('user.register')->toString(),
        ],
      ],
    ]);
  }

  /**
   * Landing Instituciones B2G (legacy).
   *
   * Ruta: /instituciones
   */
  public function instituciones(): array {
    return $this->buildLanding([
      'key' => 'instituciones',
      'color' => 'corporate',
      'hero' => [
        'headline' => $this->t('Tu plataforma de desarrollo local'),
        'subheadline' => $this->t('Formación, empleo y emprendimiento con tu marca. Impulsado por IA.'),
        'icon' => ['category' => 'business', 'name' => 'institution'],
        'cta' => [
          'text' => $this->t('Solicitar demo'),
          'url' => '/demo',
        ],
        'cta_secondary' => [
          'text' => $this->t('Ver casos de éxito'),
          'url' => '/casos',
        ],
      ],
      'features' => [
        ['icon' => ['category' => 'ui', 'name' => 'building'], 'title' => $this->t('Tu marca, tu plataforma'), 'description' => $this->t('Identidad corporativa propia: logo, colores y dominio personalizado')],
        ['icon' => ['category' => 'business', 'name' => 'ecosystem'], 'title' => $this->t('Formación y empleo'), 'description' => $this->t('Conecta talento local con empresas de tu territorio')],
        ['icon' => ['category' => 'ai', 'name' => 'screening'], 'title' => $this->t('Copiloto IA incluido'), 'description' => $this->t('Asistencia inteligente para candidatos y emprendedores')],
        ['icon' => ['category' => 'analytics', 'name' => 'dashboard'], 'title' => $this->t('Métricas de impacto'), 'description' => $this->t('Dashboards ODS y reportes para justificar subvenciones')],
      ],
      'final_cta' => [
        'headline' => $this->t('¿Listo para impulsar tu territorio?'),
        'cta' => [
          'text' => $this->t('Solicitar demo'),
          'url' => '/demo',
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
  protected function buildLanding(array $data): array {
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
    ];
  }

}
