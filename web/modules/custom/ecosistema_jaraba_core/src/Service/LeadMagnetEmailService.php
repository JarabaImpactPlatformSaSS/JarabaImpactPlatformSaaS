<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

/**
 * Servicio de envio de emails para lead magnets.
 *
 * Genera el cuerpo HTML para cada tipo de lead magnet y envia
 * el email a traves del MailManager de Drupal. Cada email incluye:
 * - Resultados personalizados del lead magnet
 * - CTA "Descargar resultados"
 * - CTA "Registrate" con enlace a registro por vertical
 *
 * DIRECTRICES:
 * - i18n: $this->t() en todos los textos
 * - PHP 8.4 strict types
 * - Logging de exito/fallo
 *
 * @see \Drupal\ecosistema_jaraba_core\Controller\LeadMagnetController
 */
class LeadMagnetEmailService {

  use StringTranslationTrait;

  /**
   * Mapa de tipo de lead magnet a clave de mail hook.
   */
  protected const MAIL_KEYS = [
    'calculadora_madurez' => 'lead_magnet_calculadora',
    'guia_vende_online' => 'lead_magnet_guia',
    'auditoria_seo' => 'lead_magnet_auditoria',
    'template_propuesta' => 'lead_magnet_propuesta',
  ];

  /**
   * Mapa de tipo de lead magnet a vertical para URL de registro.
   */
  protected const VERTICAL_MAP = [
    'calculadora_madurez' => 'emprendimiento',
    'guia_vende_online' => 'agroconecta',
    'auditoria_seo' => 'comercioconecta',
    'template_propuesta' => 'serviciosconecta',
  ];

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mailManager
   *   El servicio de gestion de correo.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de logging.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factoria de configuracion.
   */
  public function __construct(
    protected readonly MailManagerInterface $mailManager,
    protected readonly LoggerInterface $logger,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Envia los resultados de un lead magnet por email.
   *
   * @param string $type
   *   Tipo de lead magnet: calculadora_madurez, guia_vende_online,
   *   auditoria_seo, template_propuesta.
   * @param string $email
   *   Direccion de email del destinatario.
   * @param string $name
   *   Nombre del destinatario.
   * @param array $data
   *   Datos especificos del lead magnet (score, answers, etc.).
   *
   * @return bool
   *   TRUE si el email se envio correctamente, FALSE en caso contrario.
   */
  public function sendResults(string $type, string $email, string $name, array $data): bool {
    $mailKey = self::MAIL_KEYS[$type] ?? NULL;
    if (!$mailKey) {
      $this->logger->error('Lead magnet email: tipo desconocido @type', [
        '@type' => $type,
      ]);
      return FALSE;
    }

    $vertical = self::VERTICAL_MAP[$type] ?? 'emprendimiento';
    $body = $this->buildEmailBody($type, $name, $data, $vertical);

    $params = [
      'name' => $name,
      'type' => $type,
      'vertical' => $vertical,
      'data' => $data,
      'body' => $body,
      'subject' => $this->getSubject($type, $name, $data),
    ];

    try {
      $result = $this->mailManager->mail(
        'ecosistema_jaraba_core',
        $mailKey,
        $email,
        'es',
        $params,
        NULL,
        TRUE
      );

      if (!empty($result['result'])) {
        $this->logger->info('Lead magnet email enviado: @type a @email (@name)', [
          '@type' => $type,
          '@email' => $email,
          '@name' => $name,
        ]);
        return TRUE;
      }

      $this->logger->error('Lead magnet email fallo al enviar: @type a @email', [
        '@type' => $type,
        '@email' => $email,
      ]);
      return FALSE;
    }
    catch (\Exception $e) {
      $this->logger->error('Lead magnet email excepcion: @type a @email - @error', [
        '@type' => $type,
        '@email' => $email,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Genera el asunto del email segun el tipo de lead magnet.
   *
   * @param string $type
   *   Tipo de lead magnet.
   * @param string $name
   *   Nombre del destinatario.
   * @param array $data
   *   Datos del lead magnet.
   *
   * @return string
   *   Asunto del email.
   */
  protected function getSubject(string $type, string $name, array $data): string {
    return match ($type) {
      'calculadora_madurez' => (string) $this->t('Tu resultado de Madurez Digital: @score/100', [
        '@score' => $data['score'] ?? 0,
      ]),
      'guia_vende_online' => (string) $this->t('@name, aqui tienes tu Guia para Vender Online', [
        '@name' => $name,
      ]),
      'auditoria_seo' => (string) $this->t('Resultados de tu Auditoria SEO Local'),
      'template_propuesta' => (string) $this->t('@name, tu Template de Propuesta Profesional esta listo', [
        '@name' => $name,
      ]),
      default => (string) $this->t('Tus resultados de Jaraba Impact Platform'),
    };
  }

  /**
   * Construye el cuerpo HTML del email segun el tipo de lead magnet.
   *
   * @param string $type
   *   Tipo de lead magnet.
   * @param string $name
   *   Nombre del destinatario.
   * @param array $data
   *   Datos del lead magnet.
   * @param string $vertical
   *   Vertical asociada.
   *
   * @return array
   *   Array de lineas del cuerpo del email.
   */
  protected function buildEmailBody(string $type, string $name, array $data, string $vertical): array {
    $body = [];

    // Saludo comun.
    $body[] = (string) $this->t('Hola @name,', ['@name' => $name]);
    $body[] = '';

    // Contenido especifico por tipo.
    switch ($type) {
      case 'calculadora_madurez':
        $body = array_merge($body, $this->buildCalculadoraBody($data));
        break;

      case 'guia_vende_online':
        $body = array_merge($body, $this->buildGuiaBody($data));
        break;

      case 'auditoria_seo':
        $body = array_merge($body, $this->buildAuditoriaBody($data));
        break;

      case 'template_propuesta':
        $body = array_merge($body, $this->buildPropuestaBody($data));
        break;
    }

    // CTAs comunes.
    $body[] = '';
    $body[] = '---';
    $body[] = '';
    $body[] = (string) $this->t('Descargar resultados: @url', [
      '@url' => $this->getSiteUrl() . '/' . $vertical . '?results=download',
    ]);
    $body[] = '';
    $body[] = (string) $this->t('Registrate gratis para acceder a todas las herramientas: @url', [
      '@url' => $this->getSiteUrl() . '/registro?vertical=' . $vertical . '&source=lead_magnet_' . $type,
    ]);
    $body[] = '';
    $body[] = '---';
    $body[] = (string) $this->t('El equipo de Jaraba Impact Platform');

    return $body;
  }

  /**
   * Construye el cuerpo del email para la Calculadora de Madurez Digital.
   *
   * @param array $data
   *   Datos: score, level, recommendations.
   *
   * @return array
   *   Lineas del cuerpo.
   */
  protected function buildCalculadoraBody(array $data): array {
    $score = (int) ($data['score'] ?? 0);
    $level = $this->getMaturityLevel($score);

    $body = [];
    $body[] = (string) $this->t('Gracias por completar la Calculadora de Madurez Digital.');
    $body[] = '';
    $body[] = (string) $this->t('Tu puntuacion: @score/100', ['@score' => $score]);
    $body[] = (string) $this->t('Nivel de madurez: @level', ['@level' => $level]);
    $body[] = '';

    if ($score < 30) {
      $body[] = (string) $this->t('Tu negocio esta en fase inicial de digitalizacion. Hay mucho potencial de mejora.');
      $body[] = (string) $this->t('Recomendaciones:');
      $body[] = (string) $this->t('  - Crea una presencia web basica');
      $body[] = (string) $this->t('  - Digitaliza tu facturacion');
      $body[] = (string) $this->t('  - Activa perfiles en redes sociales');
    }
    elseif ($score < 60) {
      $body[] = (string) $this->t('Tu negocio tiene una base digital. Es momento de optimizar y automatizar.');
      $body[] = (string) $this->t('Recomendaciones:');
      $body[] = (string) $this->t('  - Implementa un CRM para gestionar clientes');
      $body[] = (string) $this->t('  - Automatiza tareas repetitivas');
      $body[] = (string) $this->t('  - Invierte en marketing digital');
    }
    else {
      $body[] = (string) $this->t('Tu negocio tiene un buen nivel de digitalizacion. Enfocate en escalar.');
      $body[] = (string) $this->t('Recomendaciones:');
      $body[] = (string) $this->t('  - Integra herramientas con IA');
      $body[] = (string) $this->t('  - Analiza datos para tomar decisiones');
      $body[] = (string) $this->t('  - Explora nuevos canales de venta online');
    }

    // Include individual answers if provided.
    $answers = $data['answers'] ?? [];
    if (!empty($answers)) {
      $body[] = '';
      $body[] = (string) $this->t('Detalle de tus respuestas:');
      foreach ($answers as $questionId => $answer) {
        $body[] = (string) $this->t('  - @question: @answer', [
          '@question' => $questionId,
          '@answer' => is_array($answer) ? ($answer['label'] ?? '') : $answer,
        ]);
      }
    }

    return $body;
  }

  /**
   * Construye el cuerpo del email para la Guia Vende Online.
   *
   * @param array $data
   *   Datos del formulario.
   *
   * @return array
   *   Lineas del cuerpo.
   */
  protected function buildGuiaBody(array $data): array {
    $body = [];
    $body[] = (string) $this->t('Gracias por solicitar la Guia "Vende Online sin Intermediarios".');
    $body[] = '';
    $body[] = (string) $this->t('En esta guia encontraras:');
    $body[] = (string) $this->t('  - Como montar tu tienda online en 10 minutos');
    $body[] = (string) $this->t('  - Estrategias para eliminar intermediarios');
    $body[] = (string) $this->t('  - Como cobrar directamente sin comisiones ocultas');
    $body[] = (string) $this->t('  - Consejos para llegar a clientes de toda Espana');

    $productType = $data['product_type'] ?? '';
    if (!empty($productType)) {
      $body[] = '';
      $body[] = (string) $this->t('Hemos personalizado las recomendaciones para tu tipo de producto: @type', [
        '@type' => $productType,
      ]);
    }

    return $body;
  }

  /**
   * Construye el cuerpo del email para la Auditoria SEO Local.
   *
   * @param array $data
   *   Datos: business_name, website_url, checks.
   *
   * @return array
   *   Lineas del cuerpo.
   */
  protected function buildAuditoriaBody(array $data): array {
    $body = [];
    $businessName = $data['business_name'] ?? '';
    $websiteUrl = $data['website_url'] ?? '';

    $body[] = (string) $this->t('Aqui tienes los resultados de tu Auditoria SEO Local.');
    $body[] = '';

    if (!empty($businessName)) {
      $body[] = (string) $this->t('Negocio: @name', ['@name' => $businessName]);
    }
    if (!empty($websiteUrl)) {
      $body[] = (string) $this->t('Web analizada: @url', ['@url' => $websiteUrl]);
    }

    $body[] = '';
    $body[] = (string) $this->t('Puntos analizados:');
    $body[] = (string) $this->t('  - Presencia en Google Maps: @status', [
      '@status' => $data['google_maps'] ?? (string) $this->t('Pendiente de verificar'),
    ]);
    $body[] = (string) $this->t('  - SEO basico de tu web: @status', [
      '@status' => $data['seo_basic'] ?? (string) $this->t('Pendiente de verificar'),
    ]);
    $body[] = (string) $this->t('  - Resenas y reputacion online: @status', [
      '@status' => $data['reviews'] ?? (string) $this->t('Pendiente de verificar'),
    ]);
    $body[] = (string) $this->t('  - Comparativa con competidores locales: @status', [
      '@status' => $data['competitors'] ?? (string) $this->t('Pendiente de verificar'),
    ]);

    $body[] = '';
    $body[] = (string) $this->t('Para un analisis completo y recomendaciones personalizadas, registrate en la plataforma.');

    return $body;
  }

  /**
   * Construye el cuerpo del email para el Template Propuesta Profesional.
   *
   * @param array $data
   *   Datos: service_type, speciality.
   *
   * @return array
   *   Lineas del cuerpo.
   */
  protected function buildPropuestaBody(array $data): array {
    $body = [];
    $body[] = (string) $this->t('Tu Template de Propuesta Profesional esta listo para descargar.');
    $body[] = '';
    $body[] = (string) $this->t('El template incluye:');
    $body[] = (string) $this->t('  - Plantilla de presupuesto profesional');
    $body[] = (string) $this->t('  - Estructura de propuesta de servicios');
    $body[] = (string) $this->t('  - Clausulas legales basicas');
    $body[] = (string) $this->t('  - Guia de personalizacion');

    $serviceType = $data['service_type'] ?? '';
    if (!empty($serviceType)) {
      $body[] = '';
      $body[] = (string) $this->t('Hemos adaptado el template para tu area: @type', [
        '@type' => $serviceType,
      ]);
    }

    return $body;
  }

  /**
   * Determina el nivel de madurez digital basado en el score.
   *
   * @param int $score
   *   Puntuacion 0-100.
   *
   * @return string
   *   Etiqueta del nivel de madurez.
   */
  protected function getMaturityLevel(int $score): string {
    if ($score < 20) {
      return (string) $this->t('Analogico');
    }
    if ($score < 40) {
      return (string) $this->t('Basico');
    }
    if ($score < 60) {
      return (string) $this->t('Intermedio');
    }
    if ($score < 80) {
      return (string) $this->t('Avanzado');
    }
    return (string) $this->t('Digital Nativo');
  }

  /**
   * Obtiene la URL base del sitio.
   *
   * @return string
   *   URL del sitio sin trailing slash.
   */
  protected function getSiteUrl(): string {
    $siteConfig = $this->configFactory->get('system.site');
    $baseUrl = \Drupal::request()->getSchemeAndHttpHost();
    return rtrim($baseUrl, '/');
  }

}
