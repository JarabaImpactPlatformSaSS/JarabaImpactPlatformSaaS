<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

/**
 * Generates print-ready HTML documents for lead magnet PDF downloads.
 *
 * This service produces branded, print-optimized HTML that browsers can
 * convert to PDF via "Print to PDF" (Ctrl+P). This avoids requiring
 * external libraries like dompdf while still delivering professional
 * documents to lead magnet visitors.
 *
 * Supported lead magnets:
 * - Guia AgroConecta: "Vende Online sin Intermediarios"
 * - Template Propuesta ServiciosConecta: "Propuesta Profesional"
 *
 * @see \Drupal\ecosistema_jaraba_core\Controller\LeadMagnetController
 */
class LeadMagnetPdfService {

  use StringTranslationTrait;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * The logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a LeadMagnetPdfService.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel for ecosistema_jaraba_core.
   */
  public function __construct(
    RendererInterface $renderer,
    LoggerInterface $logger,
  ) {
    $this->renderer = $renderer;
    $this->logger = $logger;
  }

  /**
   * Generates the Guia AgroConecta print-ready HTML document.
   *
   * Produces a branded guide titled "Vende Online sin Intermediarios"
   * personalized with the visitor's name and email. The HTML includes
   * comprehensive @media print CSS for clean PDF output.
   *
   * @param string $name
   *   The visitor's name for personalization.
   * @param string $email
   *   The visitor's email for the footer registration CTA.
   *
   * @return string
   *   Complete HTML document string ready for browser print-to-PDF.
   */
  public function generateGuiaAgroHtml(string $name, string $email): string {
    try {
      $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
      $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
      $generatedDate = date('d/m/Y');

      $sections = $this->buildGuiaAgroSections($safeName);

      $html = $this->buildDocumentShell(
        (string) $this->t('Guia: Vende Online sin Intermediarios'),
        'agroconecta',
        '#2E7D32',
      );

      $html .= $this->buildHeader(
        (string) $this->t('Guia: Vende Online sin Intermediarios'),
        (string) $this->t('AgroConecta - Ecosistema Jaraba'),
        '#2E7D32',
        '#E8F5E9',
      );

      $html .= '<div class="greeting">';
      $html .= '<p>' . $this->t('Hola @name,', ['@name' => $safeName]) . '</p>';
      $html .= '<p>' . $this->t('Gracias por descargar esta guia. A continuacion encontraras los pasos clave para empezar a vender tus productos del campo directamente al consumidor, eliminando intermediarios y aumentando tus margenes.') . '</p>';
      $html .= '</div>';

      foreach ($sections as $index => $section) {
        $html .= $this->buildSection($index + 1, $section['title'], $section['content'], '#2E7D32');
      }

      $html .= $this->buildCtaBox(
        (string) $this->t('Da el Primer Paso Ahora'),
        (string) $this->t('Crea tu tienda online gratis en AgroConecta y empieza a vender sin intermediarios en menos de 10 minutos.'),
        '/registro?vertical=agroconecta&source=guia_vende_online&email=' . urlencode($safeEmail),
        (string) $this->t('Crea tu tienda gratis'),
        '#2E7D32',
      );

      $html .= $this->buildFooter($generatedDate, $safeEmail, '#2E7D32');
      $html .= $this->buildPrintScript();
      $html .= '</body></html>';

      $this->logger->info('Guia AgroConecta HTML generated for @email', [
        '@email' => $email,
      ]);

      return $html;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error generating Guia AgroConecta HTML: @message', [
        '@message' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  /**
   * Generates the Propuesta Profesional print-ready HTML document.
   *
   * Produces a fillable proposal template for ServiciosConecta professionals,
   * personalized with the visitor's details and optional service data.
   *
   * @param string $name
   *   The visitor's name for personalization.
   * @param string $email
   *   The visitor's email for the footer registration CTA.
   * @param array $serviceData
   *   Optional service data for pre-filling the template. Keys:
   *   - 'business_name': (string) Name of the professional's business.
   *   - 'service_type': (string) Type of service offered.
   *   - 'client_name': (string) Target client name placeholder.
   *
   * @return string
   *   Complete HTML document string ready for browser print-to-PDF.
   */
  public function generatePropuestaHtml(string $name, string $email, array $serviceData = []): string {
    try {
      $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
      $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
      $generatedDate = date('d/m/Y');

      $businessName = htmlspecialchars(
        $serviceData['business_name'] ?? (string) $this->t('[Tu Empresa / Nombre Profesional]'),
        ENT_QUOTES,
        'UTF-8',
      );
      $serviceType = htmlspecialchars(
        $serviceData['service_type'] ?? (string) $this->t('[Tipo de Servicio]'),
        ENT_QUOTES,
        'UTF-8',
      );
      $clientName = htmlspecialchars(
        $serviceData['client_name'] ?? (string) $this->t('[Nombre del Cliente]'),
        ENT_QUOTES,
        'UTF-8',
      );

      $html = $this->buildDocumentShell(
        (string) $this->t('Propuesta Profesional - Template'),
        'serviciosconecta',
        '#1565C0',
      );

      $html .= $this->buildHeader(
        (string) $this->t('Propuesta Profesional'),
        (string) $this->t('ServiciosConecta - Ecosistema Jaraba'),
        '#1565C0',
        '#E3F2FD',
      );

      $html .= '<div class="greeting">';
      $html .= '<p>' . $this->t('Hola @name,', ['@name' => $safeName]) . '</p>';
      $html .= '<p>' . $this->t('A continuacion encontraras una plantilla profesional para enviar presupuestos a tus clientes. Personaliza cada seccion con los datos de tu negocio y del servicio que ofreces.') . '</p>';
      $html .= '</div>';

      // Proposal header section.
      $html .= $this->buildPropuestaHeader($businessName, $clientName, $generatedDate, $serviceType);

      // Proposal body sections.
      $propuestaSections = $this->buildPropuestaSections();
      foreach ($propuestaSections as $index => $section) {
        $html .= $this->buildSection($index + 1, $section['title'], $section['content'], '#1565C0');
      }

      // Legal clauses.
      $html .= $this->buildLegalClauses();

      // Signature block.
      $html .= $this->buildSignatureBlock($businessName, $clientName);

      $html .= $this->buildCtaBox(
        (string) $this->t('Gestiona tus Propuestas Profesionalmente'),
        (string) $this->t('Con ServiciosConecta puedes crear, enviar y hacer seguimiento de tus propuestas automaticamente. Empieza gratis.'),
        '/registro?vertical=serviciosconecta&source=template_propuesta&email=' . urlencode($safeEmail),
        (string) $this->t('Gestiona tus clientes gratis'),
        '#1565C0',
      );

      $html .= $this->buildFooter($generatedDate, $safeEmail, '#1565C0');
      $html .= $this->buildPrintScript();
      $html .= '</body></html>';

      $this->logger->info('Propuesta HTML generated for @email', [
        '@email' => $email,
      ]);

      return $html;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error generating Propuesta HTML: @message', [
        '@message' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  /**
   * Builds the HTML document shell with print-optimized CSS.
   *
   * @param string $title
   *   The document title for the <title> tag.
   * @param string $vertical
   *   The vertical identifier for CSS class scoping.
   * @param string $brandColor
   *   The primary brand color as a hex string (e.g., '#2E7D32').
   *
   * @return string
   *   The opening HTML including DOCTYPE, <head>, and opening <body>.
   */
  protected function buildDocumentShell(string $title, string $vertical, string $brandColor): string {
    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$title}</title>
  <style>
    /* ===========================================
       BASE STYLES
       =========================================== */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      font-size: 14px;
      line-height: 1.6;
      color: #333;
      background: #f5f5f5;
      padding: 20px;
    }

    .document-container {
      max-width: 800px;
      margin: 0 auto;
      background: #fff;
      padding: 50px 60px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    /* ===========================================
       HEADER
       =========================================== */
    .doc-header {
      text-align: center;
      padding-bottom: 30px;
      margin-bottom: 30px;
      border-bottom: 3px solid {$brandColor};
    }

    .doc-header .logo-text {
      font-size: 12px;
      color: #666;
      text-transform: uppercase;
      letter-spacing: 3px;
      margin-bottom: 10px;
    }

    .doc-header h1 {
      font-size: 28px;
      color: {$brandColor};
      margin-bottom: 5px;
      font-weight: 700;
    }

    .doc-header .subtitle {
      font-size: 14px;
      color: #888;
    }

    /* ===========================================
       GREETING
       =========================================== */
    .greeting {
      margin-bottom: 30px;
      padding: 20px;
      background: #fafafa;
      border-left: 4px solid {$brandColor};
      border-radius: 0 4px 4px 0;
    }

    .greeting p {
      margin-bottom: 8px;
    }

    .greeting p:first-child {
      font-weight: 600;
      font-size: 16px;
    }

    /* ===========================================
       SECTIONS
       =========================================== */
    .content-section {
      margin-bottom: 30px;
      page-break-inside: avoid;
    }

    .content-section .section-header {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
    }

    .content-section .section-number {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 32px;
      height: 32px;
      background: {$brandColor};
      color: #fff;
      border-radius: 50%;
      font-weight: 700;
      font-size: 14px;
      margin-right: 12px;
      flex-shrink: 0;
    }

    .content-section h2 {
      font-size: 18px;
      color: {$brandColor};
      font-weight: 600;
    }

    .content-section .section-body {
      padding-left: 44px;
    }

    .content-section .section-body p {
      margin-bottom: 10px;
    }

    .content-section .section-body ul {
      margin: 10px 0;
      padding-left: 20px;
    }

    .content-section .section-body li {
      margin-bottom: 6px;
    }

    .content-section .section-body li::marker {
      color: {$brandColor};
    }

    /* ===========================================
       CTA BOX
       =========================================== */
    .cta-box {
      margin: 40px 0;
      padding: 30px;
      text-align: center;
      border: 2px dashed {$brandColor};
      border-radius: 8px;
      background: #fafafa;
    }

    .cta-box h3 {
      font-size: 20px;
      color: {$brandColor};
      margin-bottom: 10px;
    }

    .cta-box p {
      margin-bottom: 15px;
      color: #555;
    }

    .cta-box .cta-button {
      display: inline-block;
      padding: 12px 30px;
      background: {$brandColor};
      color: #fff;
      text-decoration: none;
      border-radius: 6px;
      font-weight: 600;
      font-size: 16px;
    }

    .cta-box .cta-button:hover {
      opacity: 0.9;
    }

    /* ===========================================
       PROPUESTA-SPECIFIC
       =========================================== */
    .propuesta-header-box {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 30px;
      padding: 20px;
      background: #f8f9fa;
      border: 1px solid #e0e0e0;
      border-radius: 6px;
    }

    .propuesta-header-box .field-group h4 {
      font-size: 11px;
      text-transform: uppercase;
      color: #888;
      letter-spacing: 1px;
      margin-bottom: 4px;
    }

    .propuesta-header-box .field-group p {
      font-size: 15px;
      color: #333;
      font-weight: 500;
    }

    .fillable {
      border-bottom: 1px dashed #999;
      padding-bottom: 2px;
      color: #666;
      font-style: italic;
    }

    .legal-clauses {
      margin: 30px 0;
      padding: 20px;
      background: #f9f9f9;
      border: 1px solid #e0e0e0;
      border-radius: 4px;
      font-size: 12px;
      color: #666;
    }

    .legal-clauses h3 {
      font-size: 14px;
      color: #333;
      margin-bottom: 12px;
    }

    .legal-clauses ol {
      padding-left: 20px;
    }

    .legal-clauses li {
      margin-bottom: 8px;
    }

    .signature-block {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 40px;
      margin: 40px 0;
      padding-top: 20px;
    }

    .signature-block .signature-area {
      text-align: center;
    }

    .signature-block .signature-line {
      border-top: 1px solid #333;
      margin-top: 60px;
      padding-top: 8px;
      font-size: 12px;
      color: #666;
    }

    /* ===========================================
       FOOTER
       =========================================== */
    .doc-footer {
      margin-top: 40px;
      padding-top: 20px;
      border-top: 1px solid #e0e0e0;
      text-align: center;
      font-size: 11px;
      color: #999;
    }

    .doc-footer p {
      margin-bottom: 4px;
    }

    /* ===========================================
       PRINT BUTTON (screen only)
       =========================================== */
    .print-bar {
      max-width: 800px;
      margin: 0 auto 20px;
      text-align: right;
    }

    .print-bar button {
      padding: 10px 24px;
      background: {$brandColor};
      color: #fff;
      border: none;
      border-radius: 6px;
      font-size: 14px;
      cursor: pointer;
      font-weight: 600;
    }

    .print-bar button:hover {
      opacity: 0.9;
    }

    /* ===========================================
       PRINT STYLES
       =========================================== */
    @media print {
      body {
        background: #fff;
        padding: 0;
        font-size: 12px;
      }

      .document-container {
        box-shadow: none;
        padding: 20px 30px;
        max-width: 100%;
      }

      .print-bar {
        display: none !important;
      }

      .cta-box {
        border-style: solid;
      }

      .cta-box .cta-button {
        color: {$brandColor} !important;
        background: none !important;
        border: 2px solid {$brandColor};
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      .content-section {
        page-break-inside: avoid;
      }

      .doc-header,
      .greeting,
      .content-section .section-number {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      a[href]::after {
        content: " (" attr(href) ")";
        font-size: 10px;
        color: #666;
      }
    }
  </style>
</head>
<body>
  <div class="print-bar" id="printBar">
    <button onclick="window.print()">Imprimir / Guardar como PDF</button>
  </div>
  <div class="document-container vertical-{$vertical}">
HTML;
  }

  /**
   * Builds the document header section.
   *
   * @param string $title
   *   Main document title.
   * @param string $subtitle
   *   Subtitle or branding line.
   * @param string $brandColor
   *   Brand color hex.
   * @param string $bgColor
   *   Light background color for the header area.
   *
   * @return string
   *   HTML for the header.
   */
  protected function buildHeader(string $title, string $subtitle, string $brandColor, string $bgColor): string {
    return <<<HTML
    <div class="doc-header" style="background: {$bgColor}; padding: 30px; border-radius: 8px 8px 0 0; border-bottom: 3px solid {$brandColor};">
      <div class="logo-text">Ecosistema Jaraba</div>
      <h1>{$title}</h1>
      <div class="subtitle">{$subtitle}</div>
    </div>
HTML;
  }

  /**
   * Builds a numbered content section.
   *
   * @param int $number
   *   The section number.
   * @param string $title
   *   The section title.
   * @param string $content
   *   The section body HTML content.
   * @param string $brandColor
   *   Brand color hex for the number badge.
   *
   * @return string
   *   HTML for the section.
   */
  protected function buildSection(int $number, string $title, string $content, string $brandColor): string {
    return <<<HTML
    <div class="content-section">
      <div class="section-header">
        <span class="section-number" style="background: {$brandColor};">{$number}</span>
        <h2>{$title}</h2>
      </div>
      <div class="section-body">
        {$content}
      </div>
    </div>
HTML;
  }

  /**
   * Builds the CTA box for registration.
   *
   * @param string $heading
   *   CTA heading.
   * @param string $description
   *   CTA description text.
   * @param string $url
   *   CTA button URL.
   * @param string $buttonText
   *   CTA button label.
   * @param string $brandColor
   *   Brand color hex.
   *
   * @return string
   *   HTML for the CTA box.
   */
  protected function buildCtaBox(string $heading, string $description, string $url, string $buttonText, string $brandColor): string {
    return <<<HTML
    <div class="cta-box" style="border-color: {$brandColor};">
      <h3 style="color: {$brandColor};">{$heading}</h3>
      <p>{$description}</p>
      <a href="{$url}" class="cta-button" style="background: {$brandColor};">{$buttonText}</a>
    </div>
HTML;
  }

  /**
   * Builds the document footer with generation info.
   *
   * @param string $date
   *   Generation date string.
   * @param string $email
   *   The recipient's email.
   * @param string $brandColor
   *   Brand color hex.
   *
   * @return string
   *   HTML for the footer.
   */
  protected function buildFooter(string $date, string $email, string $brandColor): string {
    $generated = (string) $this->t('Documento generado el @date para @email', [
      '@date' => $date,
      '@email' => $email,
    ]);
    $platform = (string) $this->t('Ecosistema Jaraba - Plataforma de Impacto Social');
    $website = 'https://ecosistemajaraba.org';

    return <<<HTML
    <div class="doc-footer">
      <p>{$generated}</p>
      <p style="color: {$brandColor}; font-weight: 600;">{$platform}</p>
      <p><a href="{$website}" style="color: {$brandColor};">{$website}</a></p>
    </div>
  </div>
HTML;
  }

  /**
   * Builds the auto-print JavaScript snippet.
   *
   * @return string
   *   A <script> block that offers printing on page load.
   */
  protected function buildPrintScript(): string {
    return <<<HTML
  <script>
    // Auto-suggest print dialog for PDF generation.
    document.addEventListener('DOMContentLoaded', function() {
      var params = new URLSearchParams(window.location.search);
      if (params.get('autoprint') === '1') {
        setTimeout(function() { window.print(); }, 500);
      }
    });
  </script>
HTML;
  }

  /**
   * Builds content sections for the AgroConecta guide.
   *
   * @param string $name
   *   The sanitized visitor name.
   *
   * @return array
   *   Array of sections, each with 'title' and 'content' keys.
   */
  protected function buildGuiaAgroSections(string $name): array {
    return [
      [
        'title' => (string) $this->t('Por que Vender Online sin Intermediarios'),
        'content' => '<p>' . $this->t('Los productores rurales pierden entre un 40% y un 60% de sus margenes a traves de intermediarios. Vendiendo directamente al consumidor final, no solo mejoras tus ingresos, sino que estableces una relacion de confianza con tu cliente.') . '</p>'
          . '<ul>'
          . '<li>' . $this->t('Aumenta tus margenes hasta un 300%') . '</li>'
          . '<li>' . $this->t('Controla tu marca y la presentacion de tus productos') . '</li>'
          . '<li>' . $this->t('Recibe feedback directo de tus clientes') . '</li>'
          . '<li>' . $this->t('Genera fidelidad y ventas recurrentes') . '</li>'
          . '</ul>',
      ],
      [
        'title' => (string) $this->t('Prepara tus Productos para la Venta Online'),
        'content' => '<p>' . $this->t('Antes de abrir tu tienda online, asegurate de tener estos elementos listos:') . '</p>'
          . '<ul>'
          . '<li>' . $this->t('<strong>Fotografias de calidad:</strong> Toma fotos con luz natural, fondo limpio y multiples angulos.') . '</li>'
          . '<li>' . $this->t('<strong>Descripciones detalladas:</strong> Incluye origen, metodo de produccion, peso y conservacion.') . '</li>'
          . '<li>' . $this->t('<strong>Precios competitivos:</strong> Investiga el mercado y calcula tus costes reales.') . '</li>'
          . '<li>' . $this->t('<strong>Embalaje adecuado:</strong> Asegura que el producto llegue en perfectas condiciones.') . '</li>'
          . '</ul>',
      ],
      [
        'title' => (string) $this->t('Monta tu Tienda Online en 10 Minutos'),
        'content' => '<p>' . $this->t('Con AgroConecta, crear tu tienda online es mas facil que nunca:') . '</p>'
          . '<ul>'
          . '<li>' . $this->t('<strong>Paso 1:</strong> Registrate gratis con tu email.') . '</li>'
          . '<li>' . $this->t('<strong>Paso 2:</strong> Completa el perfil de tu finca o negocio.') . '</li>'
          . '<li>' . $this->t('<strong>Paso 3:</strong> Sube tus productos con fotos y descripciones.') . '</li>'
          . '<li>' . $this->t('<strong>Paso 4:</strong> Configura precios y opciones de envio.') . '</li>'
          . '<li>' . $this->t('<strong>Paso 5:</strong> Publica y comparte con tus primeros clientes.') . '</li>'
          . '</ul>',
      ],
      [
        'title' => (string) $this->t('Estrategias de Marketing para Productores'),
        'content' => '<p>' . $this->t('No basta con tener una tienda online, necesitas atraer clientes:') . '</p>'
          . '<ul>'
          . '<li>' . $this->t('<strong>Redes sociales:</strong> Comparte el dia a dia de tu produccion en Instagram y Facebook.') . '</li>'
          . '<li>' . $this->t('<strong>WhatsApp Business:</strong> Crea un catalogo y usa listas de difusion.') . '</li>'
          . '<li>' . $this->t('<strong>Mercados locales:</strong> Usa tarjetas con QR a tu tienda online.') . '</li>'
          . '<li>' . $this->t('<strong>Trazabilidad:</strong> Muestra el origen de tus productos como valor diferencial.') . '</li>'
          . '</ul>',
      ],
      [
        'title' => (string) $this->t('Logistica y Envios sin Complicaciones'),
        'content' => '<p>' . $this->t('La logistica es el mayor reto para productores rurales. Estas son las opciones mas efectivas:') . '</p>'
          . '<ul>'
          . '<li>' . $this->t('<strong>Envio refrigerado:</strong> Para productos perecederos, usa servicios especializados.') . '</li>'
          . '<li>' . $this->t('<strong>Puntos de recogida:</strong> Ofrece recogida en mercados locales o cooperativas.') . '</li>'
          . '<li>' . $this->t('<strong>Suscripciones:</strong> Cestas semanales con envio programado reducen costes.') . '</li>'
          . '<li>' . $this->t('<strong>Grupos de compra:</strong> Coordina pedidos por zona para optimizar rutas.') . '</li>'
          . '</ul>',
      ],
      [
        'title' => (string) $this->t('Aspectos Legales Basicos'),
        'content' => '<p>' . $this->t('Para vender alimentos online en Espana necesitas:') . '</p>'
          . '<ul>'
          . '<li>' . $this->t('Registro sanitario (numero RGSEAA) si aplica a tu producto.') . '</li>'
          . '<li>' . $this->t('Etiquetado conforme al Reglamento (UE) 1169/2011.') . '</li>'
          . '<li>' . $this->t('Inscripcion en el RERA (Registro de Explotaciones Agrarias).') . '</li>'
          . '<li>' . $this->t('Condiciones de venta y politica de devolucion en tu web.') . '</li>'
          . '</ul>'
          . '<p>' . $this->t('<em>AgroConecta te guia paso a paso en cada requisito legal.</em>') . '</p>',
      ],
    ];
  }

  /**
   * Builds the propuesta header with client/business info fields.
   *
   * @param string $businessName
   *   The professional's business name.
   * @param string $clientName
   *   The target client name.
   * @param string $date
   *   The generation date.
   * @param string $serviceType
   *   The type of service offered.
   *
   * @return string
   *   HTML for the propuesta header box.
   */
  protected function buildPropuestaHeader(string $businessName, string $clientName, string $date, string $serviceType): string {
    $deLabel = (string) $this->t('De');
    $paraLabel = (string) $this->t('Para');
    $fechaLabel = (string) $this->t('Fecha');
    $servicioLabel = (string) $this->t('Servicio');
    $refLabel = (string) $this->t('Referencia');

    $reference = 'PROP-' . date('Ymd') . '-001';

    return <<<HTML
    <div class="propuesta-header-box">
      <div class="field-group">
        <h4>{$deLabel}</h4>
        <p class="fillable">{$businessName}</p>
      </div>
      <div class="field-group">
        <h4>{$paraLabel}</h4>
        <p class="fillable">{$clientName}</p>
      </div>
      <div class="field-group">
        <h4>{$fechaLabel}</h4>
        <p>{$date}</p>
      </div>
      <div class="field-group">
        <h4>{$servicioLabel}</h4>
        <p class="fillable">{$serviceType}</p>
      </div>
      <div class="field-group">
        <h4>{$refLabel}</h4>
        <p>{$reference}</p>
      </div>
    </div>
HTML;
  }

  /**
   * Builds the content sections for the proposal template.
   *
   * @return array
   *   Array of sections with 'title' and 'content' keys.
   */
  protected function buildPropuestaSections(): array {
    return [
      [
        'title' => (string) $this->t('Resumen Ejecutivo'),
        'content' => '<p class="fillable">' . $this->t('[Describe brevemente el contexto del proyecto, la necesidad del cliente y como tu servicio la resuelve. Maximo 3-4 lineas.]') . '</p>',
      ],
      [
        'title' => (string) $this->t('Alcance del Servicio'),
        'content' => '<p>' . $this->t('El servicio propuesto incluye:') . '</p>'
          . '<ul>'
          . '<li class="fillable">' . $this->t('[Entregable 1: Descripcion detallada]') . '</li>'
          . '<li class="fillable">' . $this->t('[Entregable 2: Descripcion detallada]') . '</li>'
          . '<li class="fillable">' . $this->t('[Entregable 3: Descripcion detallada]') . '</li>'
          . '</ul>'
          . '<p>' . $this->t('<strong>No incluido:</strong>') . '</p>'
          . '<ul>'
          . '<li class="fillable">' . $this->t('[Elemento excluido 1]') . '</li>'
          . '<li class="fillable">' . $this->t('[Elemento excluido 2]') . '</li>'
          . '</ul>',
      ],
      [
        'title' => (string) $this->t('Metodologia y Plazos'),
        'content' => '<p>' . $this->t('El proyecto se desarrollara en las siguientes fases:') . '</p>'
          . '<ul>'
          . '<li class="fillable">' . $this->t('<strong>Fase 1 - Diagnostico:</strong> [X dias] - Analisis de la situacion actual.') . '</li>'
          . '<li class="fillable">' . $this->t('<strong>Fase 2 - Desarrollo:</strong> [X dias] - Implementacion del servicio.') . '</li>'
          . '<li class="fillable">' . $this->t('<strong>Fase 3 - Entrega:</strong> [X dias] - Revision y entrega final.') . '</li>'
          . '</ul>'
          . '<p>' . $this->t('<strong>Plazo total estimado:</strong> <span class="fillable">[X semanas/meses]</span>') . '</p>',
      ],
      [
        'title' => (string) $this->t('Inversion y Condiciones de Pago'),
        'content' => '<table style="width:100%; border-collapse: collapse; margin: 10px 0;">'
          . '<tr style="background: #f5f5f5;"><th style="padding: 8px; border: 1px solid #ddd; text-align: left;">' . $this->t('Concepto') . '</th><th style="padding: 8px; border: 1px solid #ddd; text-align: right;">' . $this->t('Importe') . '</th></tr>'
          . '<tr><td style="padding: 8px; border: 1px solid #ddd;" class="fillable">' . $this->t('[Servicio principal]') . '</td><td style="padding: 8px; border: 1px solid #ddd; text-align: right;" class="fillable">' . $this->t('[0,00 EUR]') . '</td></tr>'
          . '<tr><td style="padding: 8px; border: 1px solid #ddd;" class="fillable">' . $this->t('[Servicio adicional]') . '</td><td style="padding: 8px; border: 1px solid #ddd; text-align: right;" class="fillable">' . $this->t('[0,00 EUR]') . '</td></tr>'
          . '<tr style="background: #f5f5f5; font-weight: bold;"><td style="padding: 8px; border: 1px solid #ddd;">' . $this->t('TOTAL (IVA no incluido)') . '</td><td style="padding: 8px; border: 1px solid #ddd; text-align: right;" class="fillable">' . $this->t('[0,00 EUR]') . '</td></tr>'
          . '</table>'
          . '<p>' . $this->t('<strong>Condiciones de pago:</strong>') . '</p>'
          . '<ul>'
          . '<li>' . $this->t('50% a la aceptacion de la propuesta.') . '</li>'
          . '<li>' . $this->t('50% a la entrega final del proyecto.') . '</li>'
          . '</ul>',
      ],
      [
        'title' => (string) $this->t('Garantias y Soporte'),
        'content' => '<ul>'
          . '<li class="fillable">' . $this->t('[Garantia: p.ej. 30 dias de soporte post-entrega]') . '</li>'
          . '<li class="fillable">' . $this->t('[Revisiones incluidas: p.ej. 2 rondas de revision]') . '</li>'
          . '<li class="fillable">' . $this->t('[Canal de soporte: email, telefono, etc.]') . '</li>'
          . '</ul>',
      ],
    ];
  }

  /**
   * Builds the legal clauses section for the proposal.
   *
   * @return string
   *   HTML for the legal clauses.
   */
  protected function buildLegalClauses(): string {
    $title = (string) $this->t('Clausulas Legales');

    return <<<HTML
    <div class="legal-clauses">
      <h3>{$title}</h3>
      <ol>
        <li>{$this->t('Esta propuesta tiene una validez de 30 dias naturales desde la fecha de emision.')}</li>
        <li>{$this->t('Los precios indicados no incluyen IVA (21%) salvo indicacion expresa.')}</li>
        <li>{$this->t('Cualquier modificacion en el alcance del proyecto sera presupuestada por separado.')}</li>
        <li>{$this->t('La propiedad intelectual del trabajo entregado se transfiere al cliente tras el pago total.')}</li>
        <li>{$this->t('Ambas partes se comprometen a mantener la confidencialidad de la informacion compartida.')}</li>
        <li>{$this->t('En caso de desistimiento, se facturara el trabajo realizado hasta la fecha.')}</li>
      </ol>
    </div>
HTML;
  }

  /**
   * Builds the signature block for the proposal.
   *
   * @param string $businessName
   *   The professional's business name.
   * @param string $clientName
   *   The client's name.
   *
   * @return string
   *   HTML for the dual signature block.
   */
  protected function buildSignatureBlock(string $businessName, string $clientName): string {
    $profesionalLabel = (string) $this->t('El Profesional');
    $clienteLabel = (string) $this->t('El Cliente');

    return <<<HTML
    <div class="signature-block">
      <div class="signature-area">
        <p><strong>{$profesionalLabel}</strong></p>
        <div class="signature-line">{$businessName}</div>
      </div>
      <div class="signature-area">
        <p><strong>{$clienteLabel}</strong></p>
        <div class="signature-line">{$clientName}</div>
      </div>
    </div>
HTML;
  }

}
