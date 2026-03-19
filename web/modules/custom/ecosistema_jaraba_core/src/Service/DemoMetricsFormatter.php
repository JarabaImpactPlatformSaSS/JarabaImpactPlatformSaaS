<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Formatea métricas de demo para renderizado universal.
 *
 * Resuelve el gap donde 8 de 11 perfiles no mostraban métricas
 * porque el template tenía condiciones fijas para claves agro/comercio.
 *
 * Cada clave de demo_data se traduce a: etiqueta humana + formato + icono.
 */
class DemoMetricsFormatter {

  use StringTranslationTrait;

  /**
   * Mapa completo de claves → configuración de renderizado.
   *
   * @return array<string, array{label: TranslatableMarkup, format: string, icon_category: string, icon_name: string, highlight: bool}>
   */
  public function getMetricsConfig(): array {
    return [
      // === JarabaLex (legal) ===
      'active_cases' => [
        'label' => $this->t('Expedientes activos'),
        'format' => 'number',
        'icon_category' => 'verticals',
        'icon_name' => 'legal',
        'highlight' => FALSE,
      ],
      'clients_managed' => [
        'label' => $this->t('Clientes gestionados'),
        'format' => 'number',
        'icon_category' => 'business',
        'icon_name' => 'briefcase',
        'highlight' => FALSE,
      ],
      'consultations_month' => [
        'label' => $this->t('Consultas del mes'),
        'format' => 'number',
        'icon_category' => 'ui',
        'icon_name' => 'calendar',
        'highlight' => FALSE,
      ],

      // === Emprendimiento ===
      'monthly_revenue' => [
        'label' => $this->t('Ingresos del mes'),
        'format' => 'eur',
        'icon_category' => 'analytics',
        'icon_name' => 'chart-bar',
        'highlight' => TRUE,
      ],
      'active_clients' => [
        'label' => $this->t('Clientes activos'),
        'format' => 'number',
        'icon_category' => 'business',
        'icon_name' => 'briefcase',
        'highlight' => FALSE,
      ],
      'projects_in_progress' => [
        'label' => $this->t('Proyectos en marcha'),
        'format' => 'number',
        'icon_category' => 'business',
        'icon_name' => 'target',
        'highlight' => FALSE,
      ],
      'conversion_rate' => [
        'label' => $this->t('Tasa de conversión'),
        'format' => 'percent',
        'icon_category' => 'analytics',
        'icon_name' => 'conversion',
        'highlight' => FALSE,
      ],

      // === Formación ===
      'courses_available' => [
        'label' => $this->t('Cursos publicados'),
        'format' => 'number',
        'icon_category' => 'education',
        'icon_name' => 'book-open',
        'highlight' => FALSE,
      ],
      'students_enrolled' => [
        'label' => $this->t('Alumnos inscritos'),
        'format' => 'number',
        'icon_category' => 'education',
        'icon_name' => 'graduation-cap',
        'highlight' => FALSE,
      ],
      'completion_rate' => [
        'label' => $this->t('Tasa de finalización'),
        'format' => 'percent',
        'icon_category' => 'analytics',
        'icon_name' => 'gauge',
        'highlight' => FALSE,
      ],

      // === Agro / Comercio (ya funcionaban) ===
      'products_count' => [
        'label' => $this->t('Productos'),
        'format' => 'number',
        'icon_category' => 'commerce',
        'icon_name' => 'store',
        'highlight' => FALSE,
      ],
      'orders_last_month' => [
        'label' => $this->t('Pedidos del mes'),
        'format' => 'number',
        'icon_category' => 'commerce',
        'icon_name' => 'cart',
        'highlight' => FALSE,
      ],
      'revenue_last_month' => [
        'label' => $this->t('Facturación del mes'),
        'format' => 'eur',
        'icon_category' => 'analytics',
        'icon_name' => 'chart-bar',
        'highlight' => TRUE,
      ],
      'customers_count' => [
        'label' => $this->t('Clientes'),
        'format' => 'number',
        'icon_category' => 'business',
        'icon_name' => 'briefcase',
        'highlight' => FALSE,
      ],
      'products_available' => [
        'label' => $this->t('Productos disponibles'),
        'format' => 'number',
        'icon_category' => 'commerce',
        'icon_name' => 'store',
        'highlight' => TRUE,
      ],
      'tenants_active' => [
        'label' => $this->t('Tiendas activas'),
        'format' => 'number',
        'icon_category' => 'commerce',
        'icon_name' => 'store',
        'highlight' => FALSE,
      ],
      'categories' => [
        'label' => $this->t('Categorías'),
        'format' => 'number',
        'icon_category' => 'ui',
        'icon_name' => 'layers',
        'highlight' => FALSE,
      ],

      // === Servicios ===
      'services_offered' => [
        'label' => $this->t('Servicios activos'),
        'format' => 'number',
        'icon_category' => 'business',
        'icon_name' => 'handshake',
        'highlight' => FALSE,
      ],
      'bookings_last_month' => [
        'label' => $this->t('Reservas del mes'),
        'format' => 'number',
        'icon_category' => 'ui',
        'icon_name' => 'calendar',
        'highlight' => FALSE,
      ],
      'clients_active' => [
        'label' => $this->t('Clientes activos'),
        'format' => 'number',
        'icon_category' => 'business',
        'icon_name' => 'briefcase',
        'highlight' => FALSE,
      ],

      // === Empleabilidad ===
      'jobs_available' => [
        'label' => $this->t('Ofertas disponibles'),
        'format' => 'number',
        'icon_category' => 'verticals',
        'icon_name' => 'empleo',
        'highlight' => TRUE,
      ],
      'applications_sent' => [
        'label' => $this->t('Candidaturas enviadas'),
        'format' => 'number',
        'icon_category' => 'actions',
        'icon_name' => 'send',
        'highlight' => FALSE,
      ],
      'interviews_scheduled' => [
        'label' => $this->t('Entrevistas programadas'),
        'format' => 'number',
        'icon_category' => 'ui',
        'icon_name' => 'calendar',
        'highlight' => FALSE,
      ],
      'profile_views' => [
        'label' => $this->t('Visitas a tu perfil'),
        'format' => 'number',
        'icon_category' => 'analytics',
        'icon_name' => 'active',
        'highlight' => FALSE,
      ],

      // === Andalucía +ei ===
      'beneficiaries_reached' => [
        'label' => $this->t('Beneficiarios alcanzados'),
        'format' => 'number',
        'icon_category' => 'business',
        'icon_name' => 'ecosystem',
        'highlight' => TRUE,
      ],
      'active_programs' => [
        'label' => $this->t('Programas activos'),
        'format' => 'number',
        'icon_category' => 'ui',
        'icon_name' => 'layers',
        'highlight' => FALSE,
      ],
      'funding_secured' => [
        'label' => $this->t('Financiación captada'),
        'format' => 'eur',
        'icon_category' => 'analytics',
        'icon_name' => 'chart-bar',
        'highlight' => FALSE,
      ],
      'volunteer_hours' => [
        'label' => $this->t('Horas de voluntariado'),
        'format' => 'number',
        'icon_category' => 'ui',
        'icon_name' => 'clock',
        'highlight' => FALSE,
      ],

      // === Content Hub ===
      'articles_published' => [
        'label' => $this->t('Artículos publicados'),
        'format' => 'number',
        'icon_category' => 'content',
        'icon_name' => 'edit',
        'highlight' => FALSE,
      ],
      'monthly_views' => [
        'label' => $this->t('Visitas del mes'),
        'format' => 'number',
        'icon_category' => 'analytics',
        'icon_name' => 'active',
        'highlight' => TRUE,
      ],
      'subscribers' => [
        'label' => $this->t('Suscriptores'),
        'format' => 'number',
        'icon_category' => 'ui',
        'icon_name' => 'users',
        'highlight' => FALSE,
      ],
      'engagement_rate' => [
        'label' => $this->t('Tasa de interacción'),
        'format' => 'percent',
        'icon_category' => 'analytics',
        'icon_name' => 'gauge',
        'highlight' => FALSE,
      ],
    ];
  }

  /**
   * Formatea un valor según su tipo.
   *
   * @param string $key
   *   Clave de la métrica.
   * @param int|float $value
   *   Valor numérico.
   *
   * @return string
   *   Valor formateado para mostrar.
   */
  public function formatValue(string $key, int|float $value): string {
    $config = $this->getMetricsConfig()[$key] ?? NULL;
    $format = $config['format'] ?? 'number';

    return match ($format) {
      'eur' => '€' . number_format($value, 0, ',', '.'),
      'percent' => $value . '%',
      default => number_format($value, 0, ',', '.'),
    };
  }

  /**
   * Obtiene la etiqueta traducida para una clave.
   */
  public function getLabel(string $key): TranslatableMarkup {
    $config = $this->getMetricsConfig()[$key] ?? NULL;
    return $config['label'] ?? $this->t('Métrica');
  }

  /**
   * Obtiene el icono para una clave.
   *
   * @return array{category: string, name: string}
   */
  public function getIcon(string $key): array {
    $config = $this->getMetricsConfig()[$key] ?? NULL;
    return [
      'category' => $config['icon_category'] ?? 'analytics',
      'name' => $config['icon_name'] ?? 'gauge',
    ];
  }

  /**
   * Indica si la métrica debe resaltarse.
   */
  public function isHighlight(string $key): bool {
    $config = $this->getMetricsConfig()[$key] ?? NULL;
    return $config['highlight'] ?? FALSE;
  }

  /**
   * Prepara todas las métricas para renderizado en Twig.
   *
   * @param array<string, mixed> $metrics
   *   Métricas del perfil demo (pueden incluir valores no numéricos).
   *
   * @return array<int, array{key: string, value: string, label: string, icon_category: string, icon_name: string, highlight: bool}>
   */
  public function prepareForRender(array $metrics): array {
    $prepared = [];
    foreach ($metrics as $key => $value) {
      if (!is_int($value) && !is_float($value)) {
        continue;
      }
      $config = $this->getMetricsConfig()[$key] ?? NULL;
      if ($config === NULL) {
        continue;
      }
      $prepared[] = [
        'key' => $key,
        'value' => $this->formatValue($key, $value),
        'label' => (string) $this->getLabel($key),
        'icon_category' => $config['icon_category'],
        'icon_name' => $config['icon_name'],
        'highlight' => $config['highlight'],
      ];
    }
    return $prepared;
  }

}
