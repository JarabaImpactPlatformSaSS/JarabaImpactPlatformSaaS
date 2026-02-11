<?php

namespace Drupal\jaraba_ab_testing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Servicio de asignación de visitantes a variantes de experimentos A/B.
 *
 * ESTRUCTURA:
 * Servicio que gestiona el ciclo completo de asignación de un visitante
 * a una variante: resolución de asignación previa (cookie), selección
 * ponderada por peso de tráfico, persistencia en cookie con SameSite,
 * e incremento de contadores de visitantes/conversiones en las entidades.
 *
 * LÓGICA:
 * La asignación sigue un flujo determinista:
 * 1. Comprobar cookie `jaraba_ab_{machine_name}` para asignación previa.
 * 2. Si existe, devolver la variante asignada sin reasignar.
 * 3. Si no existe, cargar el experimento por machine_name (status=running).
 * 4. Cargar variantes del experimento y seleccionar por peso normalizado.
 * 5. Crear cookie con TTL 30 días, HttpOnly, Secure, SameSite=Lax.
 * 6. Incrementar campo visitors en la entidad ab_variant seleccionada.
 *
 * Para conversiones: leer cookie, cargar variante, incrementar conversions
 * y sumar revenue si se proporciona.
 *
 * RELACIONES:
 * - VariantAssignmentService -> EntityTypeManagerInterface (dependencia)
 * - VariantAssignmentService -> RequestStack (dependencia)
 * - VariantAssignmentService -> LoggerInterface (dependencia)
 * - VariantAssignmentService <- ABTestingApiController (consumido por)
 * - VariantAssignmentService <- jaraba_ab_testing.routing.yml /api/v1/ab-testing/assign
 * - VariantAssignmentService <- jaraba_ab_testing.routing.yml /api/v1/ab-testing/convert
 *
 * @package Drupal\jaraba_ab_testing\Service
 */
class VariantAssignmentService {

  /**
   * Gestor de tipos de entidad de Drupal.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Pila de peticiones HTTP para acceder a cookies y respuesta.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * Canal de log dedicado para el módulo de A/B testing.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Prefijo de las cookies de asignación de variantes.
   */
  protected const COOKIE_PREFIX = 'jaraba_ab_';

  /**
   * Duración de la cookie de asignación en segundos (30 días).
   */
  protected const COOKIE_LIFETIME = 2592000;

  /**
   * Constructor del servicio de asignación de variantes.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Gestor de tipos de entidad para acceder a storage de experimentos y variantes.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Pila de peticiones para leer/escribir cookies.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log dedicado para trazar asignaciones y errores.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RequestStack $request_stack,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack;
    $this->logger = $logger;
  }

  /**
   * Asigna un visitante a una variante de un experimento.
   *
   * LÓGICA:
   * 1. Comprobar si el visitante ya tiene asignación (cookie).
   * 2. Si la tiene, cargar la variante asignada y retornarla.
   * 3. Si no, cargar el experimento por machine_name con estado 'running'.
   * 4. Cargar todas las variantes del experimento.
   * 5. Seleccionar variante por distribución ponderada de tráfico.
   * 6. Crear cookie de asignación (30 días, HttpOnly, Secure, SameSite=Lax).
   * 7. Incrementar el contador de visitantes de la variante.
   * 8. Retornar array con datos de la variante asignada.
   *
   * @param string $experiment_machine_name
   *   Machine name del experimento (campo machine_name de ab_experiment).
   *
   * @return array|null
   *   Array con datos de la variante asignada:
   *   - 'variant_id' (int): ID de la variante.
   *   - 'variant_name' (string): Nombre de la variante.
   *   - 'is_control' (bool): Si es la variante de control.
   *   - 'experiment_id' (int): ID del experimento.
   *   - 'experiment_name' (string): Nombre del experimento.
   *   O NULL si no se pudo asignar (experimento no encontrado, no activo, etc.).
   */
  public function assignVariant(string $experiment_machine_name): ?array {
    try {
      $cookie_name = self::COOKIE_PREFIX . $experiment_machine_name;

      // 1. Comprobar asignación previa en cookie.
      $existing_variant_id = $this->getCurrentAssignment($experiment_machine_name);

      if ($existing_variant_id !== NULL) {
        // Cargar la variante existente y retornar sus datos.
        $variant = $this->entityTypeManager->getStorage('ab_variant')->load($existing_variant_id);
        if ($variant) {
          $experiment_id = $variant->get('experiment_id')->target_id;
          $experiment = $this->entityTypeManager->getStorage('ab_experiment')->load($experiment_id);

          return [
            'variant_id' => (int) $variant->id(),
            'variant_name' => $variant->get('name')->value ?? '',
            'is_control' => (bool) ($variant->get('is_control')->value ?? FALSE),
            'experiment_id' => (int) $experiment_id,
            'experiment_name' => $experiment ? ($experiment->get('name')->value ?? '') : '',
          ];
        }
      }

      // 2. Cargar experimento por machine_name con estado 'running'.
      $experiment_storage = $this->entityTypeManager->getStorage('ab_experiment');
      $experiment_ids = $experiment_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('machine_name', $experiment_machine_name)
        ->condition('status', 'running')
        ->range(0, 1)
        ->execute();

      if (empty($experiment_ids)) {
        $this->logger->debug('Experimento no encontrado o no activo: @name', [
          '@name' => $experiment_machine_name,
        ]);
        return NULL;
      }

      $experiment_id = (int) reset($experiment_ids);
      $experiment = $experiment_storage->load($experiment_id);

      if (!$experiment) {
        return NULL;
      }

      // 3. Cargar variantes del experimento.
      $variant_storage = $this->entityTypeManager->getStorage('ab_variant');
      $variant_ids = $variant_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('experiment_id', $experiment_id)
        ->execute();

      if (empty($variant_ids)) {
        $this->logger->warning('Experimento @name no tiene variantes configuradas.', [
          '@name' => $experiment_machine_name,
        ]);
        return NULL;
      }

      $variants = $variant_storage->loadMultiple($variant_ids);

      // 4. Seleccionar variante por peso de tráfico.
      $selected = $this->selectVariantByWeight($variants);

      if ($selected === NULL) {
        $this->logger->error('No se pudo seleccionar variante para experimento @name.', [
          '@name' => $experiment_machine_name,
        ]);
        return NULL;
      }

      // 5. Crear cookie de asignación.
      $this->setAssignmentCookie($cookie_name, (int) $selected->id());

      // 6. Incrementar contador de visitantes de la variante.
      $current_visitors = (int) ($selected->get('visitors')->value ?? 0);
      $selected->set('visitors', $current_visitors + 1);
      $selected->save();

      $this->logger->info('Visitante asignado a variante @variant (ID: @vid) del experimento @experiment.', [
        '@variant' => $selected->get('name')->value ?? '',
        '@vid' => $selected->id(),
        '@experiment' => $experiment_machine_name,
      ]);

      return [
        'variant_id' => (int) $selected->id(),
        'variant_name' => $selected->get('name')->value ?? '',
        'is_control' => (bool) ($selected->get('is_control')->value ?? FALSE),
        'experiment_id' => $experiment_id,
        'experiment_name' => $experiment->get('name')->value ?? '',
      ];

    }
    catch (\Exception $e) {
      $this->logger->error('Error asignando variante para experimento @name: @error', [
        '@name' => $experiment_machine_name,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Registra una conversión para la variante asignada al visitante actual.
   *
   * LÓGICA:
   * 1. Leer la cookie de asignación para obtener el variant_id.
   * 2. Cargar la entidad ab_variant.
   * 3. Incrementar el campo conversions.
   * 4. Si se proporciona revenue > 0, sumarlo al campo revenue.
   * 5. Retornar TRUE si la conversión se registró con éxito.
   *
   * @param string $experiment_machine_name
   *   Machine name del experimento.
   * @param float $revenue
   *   Ingreso generado por la conversión (en EUR). Por defecto 0.0.
   *
   * @return bool
   *   TRUE si la conversión se registró correctamente.
   *   FALSE si no hay asignación previa o la variante no existe.
   */
  public function recordConversion(string $experiment_machine_name, float $revenue = 0.0): bool {
    try {
      // 1. Obtener asignación actual desde cookie.
      $variant_id = $this->getCurrentAssignment($experiment_machine_name);

      if ($variant_id === NULL) {
        $this->logger->debug('Conversión ignorada: sin asignación previa para experimento @name.', [
          '@name' => $experiment_machine_name,
        ]);
        return FALSE;
      }

      // 2. Cargar la variante.
      $variant = $this->entityTypeManager->getStorage('ab_variant')->load($variant_id);

      if (!$variant) {
        $this->logger->warning('Conversión ignorada: variante @vid no encontrada para experimento @name.', [
          '@vid' => $variant_id,
          '@name' => $experiment_machine_name,
        ]);
        return FALSE;
      }

      // 3. Incrementar conversiones.
      $current_conversions = (int) ($variant->get('conversions')->value ?? 0);
      $variant->set('conversions', $current_conversions + 1);

      // 4. Sumar revenue si es mayor que 0.
      if ($revenue > 0.0) {
        $current_revenue = (float) ($variant->get('revenue')->value ?? 0.0);
        $variant->set('revenue', $current_revenue + $revenue);
      }

      $variant->save();

      $this->logger->info('Conversión registrada: variante @variant (ID: @vid), experimento @name, revenue: @revenue EUR.', [
        '@variant' => $variant->get('name')->value ?? '',
        '@vid' => $variant_id,
        '@name' => $experiment_machine_name,
        '@revenue' => number_format($revenue, 2),
      ]);

      return TRUE;

    }
    catch (\Exception $e) {
      $this->logger->error('Error registrando conversión para experimento @name: @error', [
        '@name' => $experiment_machine_name,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Selecciona una variante basándose en la distribución ponderada de tráfico.
   *
   * LÓGICA:
   * 1. Recoger el campo traffic_percentage de cada variante.
   * 2. Normalizar los pesos para que sumen 100% (por si están desbalanceados).
   * 3. Generar un número aleatorio entre 0 y el total normalizado.
   * 4. Iterar sobre las variantes acumulando pesos hasta superar el aleatorio.
   * 5. La variante cuyo rango contenga el número aleatorio es la seleccionada.
   *
   * Ejemplo con 3 variantes (30%, 30%, 40%):
   *   Random = 65 -> acumulado: 30, 60, 100 -> selecciona la tercera.
   *
   * @param array $variants
   *   Array de entidades ab_variant cargadas.
   *
   * @return object|null
   *   La entidad ab_variant seleccionada, o NULL si no hay variantes.
   */
  protected function selectVariantByWeight(array $variants): ?object {
    if (empty($variants)) {
      return NULL;
    }

    // Construir array de pesos.
    $weighted = [];
    $total_weight = 0.0;

    foreach ($variants as $variant) {
      $weight = (float) ($variant->get('traffic_percentage')->value ?? 0);
      // Asignar peso mínimo de 1 si es 0 para evitar variantes sin tráfico.
      if ($weight <= 0.0) {
        $weight = 1.0;
      }
      $weighted[] = [
        'variant' => $variant,
        'weight' => $weight,
      ];
      $total_weight += $weight;
    }

    // Si el peso total es 0, no se puede seleccionar.
    if ($total_weight <= 0.0) {
      return reset($variants) ?: NULL;
    }

    // Generar número aleatorio entre 0 y total_weight.
    $random = (mt_rand() / mt_getrandmax()) * $total_weight;

    // Seleccionar variante por acumulación de pesos.
    $cumulative = 0.0;
    foreach ($weighted as $item) {
      $cumulative += $item['weight'];
      if ($random <= $cumulative) {
        return $item['variant'];
      }
    }

    // Fallback: retornar la última variante.
    $last = end($weighted);
    return $last['variant'];
  }

  /**
   * Obtiene la asignación actual del visitante para un experimento.
   *
   * LÓGICA:
   * Lee la cookie `jaraba_ab_{machine_name}` de la petición HTTP actual.
   * Si existe y contiene un ID numérico válido, lo retorna.
   * Si no existe o el valor no es válido, retorna NULL.
   *
   * @param string $experiment_machine_name
   *   Machine name del experimento.
   *
   * @return int|null
   *   ID de la variante asignada, o NULL si no hay asignación.
   */
  public function getCurrentAssignment(string $experiment_machine_name): ?int {
    $request = $this->requestStack->getCurrentRequest();
    if (!$request) {
      return NULL;
    }

    $cookie_name = self::COOKIE_PREFIX . $experiment_machine_name;
    $value = $request->cookies->get($cookie_name);

    if ($value !== NULL && is_numeric($value) && (int) $value > 0) {
      return (int) $value;
    }

    return NULL;
  }

  /**
   * Establece la cookie de asignación de variante.
   *
   * LÓGICA:
   * Crea una cookie con los siguientes parámetros de seguridad:
   * - TTL: 30 días desde el momento actual.
   * - Path: / (disponible en todo el sitio).
   * - HttpOnly: true (no accesible desde JavaScript).
   * - Secure: true (solo se envía por HTTPS).
   * - SameSite: Lax (protección contra CSRF, permite navegación normal).
   *
   * Se usa setrawcookie + header para máximo control de los atributos.
   *
   * @param string $cookie_name
   *   Nombre de la cookie (ej: 'jaraba_ab_homepage_cta').
   * @param int $variant_id
   *   ID de la variante a almacenar.
   */
  protected function setAssignmentCookie(string $cookie_name, int $variant_id): void {
    $expires = time() + self::COOKIE_LIFETIME;
    $value = (string) $variant_id;

    // Construir cookie con atributos de seguridad completos.
    $cookie_header = sprintf(
      '%s=%s; Expires=%s; Max-Age=%d; Path=/; HttpOnly; Secure; SameSite=Lax',
      $cookie_name,
      $value,
      gmdate('D, d M Y H:i:s T', $expires),
      self::COOKIE_LIFETIME
    );

    // Enviar la cookie como header Set-Cookie.
    // Usamos header() directamente para control total de SameSite.
    if (!headers_sent()) {
      header('Set-Cookie: ' . $cookie_header, FALSE);
    }
  }

}
