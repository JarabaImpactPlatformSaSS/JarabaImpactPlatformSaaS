<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\jaraba_billing\Entity\KitDigitalAgreement;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller para las landing pages públicas del Kit Digital.
 *
 * ZERO-REGION-001: Retorna render arrays con #theme.
 * NO-HARDCODE-PRICE-001: Precios via MetaSitePricingService.
 * CONTROLLER-READONLY-001: No readonly en propiedades heredadas.
 */
class KitDigitalController extends ControllerBase {

  /**
   * Servicio de pricing (opcional, OPTIONAL-CROSSMODULE-001).
   *
   * @var object|null
   */
  protected $pricingService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static();
    // CONTROLLER-READONLY-001: asignar manualmente.
    $instance->entityTypeManager = $container->get('entity_type.manager');
    try {
      $instance->pricingService = $container->get('ecosistema_jaraba_core.metasite_pricing');
    }
    catch (\Throwable) {
      $instance->pricingService = NULL;
    }
    return $instance;
  }

  /**
   * Landing principal: /kit-digital.
   *
   * Muestra los 5 paquetes de digitalización con resumen de categorías,
   * precios dinámicos y enlaces a páginas individuales.
   */
  public function landing(): array {
    $paquetes = $this->buildPaquetesData();
    $logos = $this->getLogosObligatorios();

    return [
      '#theme' => 'kit_digital_landing',
      '#paquetes' => $paquetes,
      '#logos' => $logos,
      '#cache' => [
        'tags' => ['config:saas_plan_tier_list'],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Página individual de paquete: /kit-digital/{paquete}.
   *
   * Cada página incluye el contenido obligatorio según Anexo II Red.es:
   * nombre, categorías, segmentos, sectores, funcionalidades, requisitos,
   * precio regular vs bono, duración, logos obligatorios.
   */
  public function paquete(string $paquete): array {
    $paquetesData = $this->buildPaquetesData();
    $paqueteKey = str_replace('-', '_', $paquete);

    if (!isset($paquetesData[$paqueteKey])) {
      throw new NotFoundHttpException();
    }

    $data = $paquetesData[$paqueteKey];
    $logos = $this->getLogosObligatorios();

    // Categorías Kit Digital cubiertas.
    $categorias = $this->getCategoriaDescriptions($data['categorias'] ?? []);

    return [
      '#theme' => 'kit_digital_paquete',
      '#paquete_data' => $data,
      '#logos' => $logos,
      '#categorias' => $categorias,
      '#cache' => [
        'tags' => ['config:saas_plan_tier_list'],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Title callback para la página de paquete.
   */
  public function paqueteTitle(string $paquete): TranslatableMarkup {
    $titles = [
      'comercio-digital' => $this->t('Comercio Digital - Kit Digital'),
      'productor-digital' => $this->t('Productor Digital - Kit Digital'),
      'profesional-digital' => $this->t('Profesional Digital - Kit Digital'),
      'despacho-digital' => $this->t('Despacho Digital - Kit Digital'),
      'emprendedor-digital' => $this->t('Emprendedor Digital - Kit Digital'),
    ];
    return $titles[$paquete] ?? $this->t('Kit Digital');
  }

  /**
   * Construye los datos de los 5 paquetes.
   *
   * NO-HARDCODE-PRICE-001: Precios desde MetaSitePricingService con fallback.
   *
   * @return array
   *   Array indexado por clave de paquete.
   */
  private function buildPaquetesData(): array {
    $pricing = $this->getPricingData();

    return [
      'comercio_digital' => [
        'key' => 'comercio_digital',
        'slug' => 'comercio-digital',
        'label' => $this->t('Comercio Digital'),
        'vertical' => 'comercioconecta',
        'target' => $this->t('Comercios minoristas de proximidad, tiendas de barrio, boutiques.'),
        'description' => $this->t('Tienda online profesional con catálogo, checkout, QR dinámicos, CRM básico y analítica de ventas.'),
        'icon' => ['category' => 'commerce', 'name' => 'store'],
        'categorias' => ['C1', 'C2', 'C3', 'C6'],
        'bono_max' => ['I' => 12000, 'II' => 6000, 'III' => 3000],
        'price_from' => $pricing['comercioconecta']['starter_price'] ?? '39',
        'price_to' => $pricing['comercioconecta']['professional_price'] ?? '99',
        'funcionalidades' => [
          $this->t('Tienda online con catálogo de productos'),
          $this->t('Sitio web profesional responsive con Page Builder'),
          $this->t('QR dinámicos para productos y ofertas flash'),
          $this->t('CRM básico de clientes'),
          $this->t('Dashboard de analítica de ventas'),
          $this->t('Formación inicial incluida (2h onboarding)'),
        ],
        'requisitos_minimos' => [
          $this->t('Plataforma de venta con carrito y checkout'),
          $this->t('Catálogo de productos con fichas'),
          $this->t('Métodos de pago online (Stripe)'),
          $this->t('Diseño responsive / mobile-first'),
          $this->t('Posicionamiento básico en internet (SEO)'),
          $this->t('Autogestión por el beneficiario'),
        ],
        'sectores' => $this->t('Comercio minorista, alimentación, moda, electrónica, hogar, decoración'),
        'duracion' => 12,
        'url' => Url::fromRoute('jaraba_billing.kit_digital.paquete', ['paquete' => 'comercio-digital'])->toString(),
      ],
      'productor_digital' => [
        'key' => 'productor_digital',
        'slug' => 'productor-digital',
        'label' => $this->t('Productor Digital'),
        'vertical' => 'agroconecta',
        'target' => $this->t('Productores agroalimentarios, cooperativas, fincas ecológicas.'),
        'description' => $this->t('Tienda del productor con trazabilidad QR, certificaciones, marketplace y analítica.'),
        'icon' => ['category' => 'verticals', 'name' => 'leaf'],
        'categorias' => ['C1', 'C8', 'C6', 'C2'],
        'bono_max' => ['I' => 10000, 'II' => 6000, 'III' => 3000],
        'price_from' => $pricing['agroconecta']['starter_price'] ?? '49',
        'price_to' => $pricing['agroconecta']['professional_price'] ?? '129',
        'funcionalidades' => [
          $this->t('Tienda propia con branding personalizado'),
          $this->t('Catálogo con trazabilidad QR a nivel de lote'),
          $this->t('Certificaciones DOP/IGP/Eco gestionables'),
          $this->t('Integración shipping (MRW/SEUR/Correos)'),
          $this->t('Dashboard productor con analítica'),
          $this->t('Alta en marketplace AgroConecta'),
        ],
        'requisitos_minimos' => [
          $this->t('Plataforma de venta online'),
          $this->t('Catálogo con fichas de producto'),
          $this->t('Métodos de pago online'),
          $this->t('Diseño responsive'),
          $this->t('SEO básico'),
          $this->t('Autogestión'),
        ],
        'sectores' => $this->t('Agricultura, ganadería, acuicultura, producción ecológica, cooperativas'),
        'duracion' => 12,
        'url' => Url::fromRoute('jaraba_billing.kit_digital.paquete', ['paquete' => 'productor-digital'])->toString(),
      ],
      'profesional_digital' => [
        'key' => 'profesional_digital',
        'slug' => 'profesional-digital',
        'label' => $this->t('Profesional Digital'),
        'vertical' => 'serviciosconecta',
        'target' => $this->t('Consultores, coaches, terapeutas, asesores, autónomos de servicios.'),
        'description' => $this->t('Reservas online, facturación VeriFactu, firma digital, CRM y buzón de confianza.'),
        'icon' => ['category' => 'verticals', 'name' => 'briefcase'],
        'categorias' => ['C4', 'C5', 'C3', 'C7'],
        'bono_max' => ['I' => 17000, 'II' => 6000, 'III' => 3000],
        'price_from' => $pricing['serviciosconecta']['starter_price'] ?? '29',
        'price_to' => $pricing['serviciosconecta']['professional_price'] ?? '79',
        'funcionalidades' => [
          $this->t('Sistema de reservas/booking online con calendario'),
          $this->t('Facturación electrónica VeriFactu compliant'),
          $this->t('Firma digital PAdES integrada'),
          $this->t('Buzón de confianza (documentos cifrados)'),
          $this->t('CRM con pipeline de presupuestos'),
          $this->t('Presupuestador automático'),
        ],
        'requisitos_minimos' => [
          $this->t('Gestión de procesos internos'),
          $this->t('Facturación electrónica'),
          $this->t('Comunicaciones seguras'),
          $this->t('CRM'),
          $this->t('Diseño responsive'),
          $this->t('Autogestión'),
        ],
        'sectores' => $this->t('Consultoría, coaching, terapia, asesoría, formación, servicios profesionales'),
        'duracion' => 12,
        'url' => Url::fromRoute('jaraba_billing.kit_digital.paquete', ['paquete' => 'profesional-digital'])->toString(),
      ],
      'despacho_digital' => [
        'key' => 'despacho_digital',
        'slug' => 'despacho-digital',
        'label' => $this->t('Despacho Digital'),
        'vertical' => 'jarabalex',
        'target' => $this->t('Abogados individuales, despachos pequeños, asesorías jurídicas.'),
        'description' => $this->t('Gestión de expedientes, calendario judicial, facturación legal, IA Copilot con compliance EU AI Act.'),
        'icon' => ['category' => 'legal', 'name' => 'gavel'],
        'categorias' => ['C4', 'C5', 'C7', 'C2'],
        'bono_max' => ['I' => 15000, 'II' => 6000, 'III' => 3000],
        'price_from' => $pricing['jarabalex']['starter_price'] ?? '39',
        'price_to' => $pricing['jarabalex']['professional_price'] ?? '149',
        'funcionalidades' => [
          $this->t('Gestión de expedientes con integración LexNet'),
          $this->t('Calendario judicial sincronizado'),
          $this->t('Facturación legal con time tracking'),
          $this->t('Firma digital PAdES para documentos legales'),
          $this->t('Buzón de confianza cifrado'),
          $this->t('IA Copilot legal (LCIS 9 capas, EU AI Act)'),
          $this->t('Base de conocimiento normativo integrada'),
          $this->t('Sitio web profesional del despacho'),
        ],
        'requisitos_minimos' => [
          $this->t('Gestión de procesos y expedientes'),
          $this->t('Facturación electrónica'),
          $this->t('Comunicaciones seguras'),
          $this->t('Sitio web profesional'),
          $this->t('Diseño responsive'),
          $this->t('Autogestión'),
        ],
        'sectores' => $this->t('Abogacía, asesoría jurídica, consultoría legal, procuraduría'),
        'duracion' => 12,
        'url' => Url::fromRoute('jaraba_billing.kit_digital.paquete', ['paquete' => 'despacho-digital'])->toString(),
      ],
      'emprendedor_digital' => [
        'key' => 'emprendedor_digital',
        'slug' => 'emprendedor-digital',
        'label' => $this->t('Emprendedor Digital'),
        'vertical' => 'emprendimiento',
        'target' => $this->t('Emprendedores, nuevos autónomos, startups, programas de emprendimiento.'),
        'description' => $this->t('Diagnóstico digital, Business Model Canvas con IA, 44 experimentos Lean Startup, proyecciones financieras.'),
        'icon' => ['category' => 'ai', 'name' => 'rocket'],
        'categorias' => ['C4', 'C6', 'C2', 'C3'],
        'bono_max' => ['I' => 14000, 'II' => 6000, 'III' => 3000],
        'price_from' => $pricing['emprendimiento']['starter_price'] ?? '39',
        'price_to' => $pricing['emprendimiento']['professional_price'] ?? '99',
        'funcionalidades' => [
          $this->t('Diagnóstico de madurez digital'),
          $this->t('Business Model Canvas con IA'),
          $this->t('44 experimentos de validación Lean Startup'),
          $this->t('AI Business Copilot v2 (5 modos)'),
          $this->t('Proyecciones financieras'),
          $this->t('Sitio web profesional del negocio'),
          $this->t('CRM para pipeline de clientes/inversores'),
        ],
        'requisitos_minimos' => [
          $this->t('Gestión de procesos de negocio'),
          $this->t('Business Intelligence y analítica'),
          $this->t('Sitio web profesional'),
          $this->t('CRM'),
          $this->t('Diseño responsive'),
          $this->t('Autogestión'),
        ],
        'sectores' => $this->t('Todos los sectores — emprendedores y nuevos autónomos'),
        'duracion' => 12,
        'url' => Url::fromRoute('jaraba_billing.kit_digital.paquete', ['paquete' => 'emprendedor-digital'])->toString(),
      ],
    ];
  }

  /**
   * Obtiene precios dinámicos desde MetaSitePricingService.
   *
   * NO-HARDCODE-PRICE-001: NUNCA hardcodear precios EUR.
   */
  private function getPricingData(): array {
    if (!$this->pricingService) {
      return [];
    }

    try {
      return $this->pricingService->getPricingPreview('_default');
    }
    catch (\Throwable) {
      return [];
    }
  }

  /**
   * Devuelve los paths de logos obligatorios Kit Digital.
   *
   * KIT-DIGITAL-001: Toda página /kit-digital/* DEBE incluir estos logos.
   */
  private function getLogosObligatorios(): array {
    $themePath = '/' . \Drupal::service('extension.list.theme')
      ->getPath('ecosistema_jaraba_theme');

    return [
      [
        'src' => $themePath . '/images/kit-digital-logo.svg',
        'alt' => 'Kit Digital',
      ],
      [
        'src' => $themePath . '/images/next-generation-eu.svg',
        'alt' => 'NextGenerationEU',
      ],
      [
        'src' => $themePath . '/images/plan-recuperacion.svg',
        'alt' => 'Plan de Recuperación, Transformación y Resiliencia',
      ],
      [
        'src' => $themePath . '/images/gobierno-espana.svg',
        'alt' => 'Gobierno de España',
      ],
    ];
  }

  /**
   * Devuelve descripciones legibles de las categorías Kit Digital.
   */
  private function getCategoriaDescriptions(array $categorias): array {
    $all = [
      'C1' => $this->t('Comercio electrónico'),
      'C2' => $this->t('Sitio web y presencia en internet'),
      'C3' => $this->t('Gestión de clientes (CRM)'),
      'C4' => $this->t('Gestión de procesos'),
      'C5' => $this->t('Factura electrónica'),
      'C6' => $this->t('Business Intelligence y analítica'),
      'C7' => $this->t('Comunicaciones seguras'),
      'C8' => $this->t('Marketplace'),
      'C9' => $this->t('Gestión de redes sociales'),
    ];

    $result = [];
    foreach ($categorias as $code) {
      if (isset($all[$code])) {
        $result[] = ['code' => $code, 'label' => $all[$code]];
      }
    }
    return $result;
  }

}
