<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jaraba_multiregion\Service\CurrencyConverterService;
use Drupal\jaraba_multiregion\Service\RegionalComplianceService;
use Drupal\jaraba_multiregion\Service\RegionManagerService;
use Drupal\jaraba_multiregion\Service\TaxCalculatorService;
use Drupal\jaraba_multiregion\Service\ViesValidatorService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller de la API REST Multi-Region.
 *
 * Estructura: 10 endpoints JSON para gestion de regiones, calculo de IVA,
 *   validacion VIES, conversion de moneda y compliance regional. Sigue el
 *   patron del ecosistema con envelope estandar {data}/{data,meta}/{error}.
 *
 * Logica: Cada endpoint retorna JsonResponse con el envelope apropiado.
 *   Los metodos de escritura usan store() en lugar de create()
 *   (API-NAMING-001). Los metodos de lectura soportan paginacion
 *   mediante query params limit/offset.
 *
 * Sintaxis: ControllerBase con DI via create(). Todos los strings
 *   orientados al usuario usan TranslatableMarkup. Cada metodo incluye
 *   try/catch para retornar {error} con codigo HTTP apropiado.
 *
 * @see \Drupal\jaraba_multiregion\Service\RegionManagerService
 * @see \Drupal\jaraba_multiregion\Service\TaxCalculatorService
 * @see \Drupal\jaraba_multiregion\Service\ViesValidatorService
 * @see \Drupal\jaraba_multiregion\Service\CurrencyConverterService
 * @see \Drupal\jaraba_multiregion\Service\RegionalComplianceService
 */
class MultiRegionApiController extends ControllerBase {

  /**
   * Constructor con inyeccion de dependencias.
   *
   * Estructura: Recibe los 5 servicios del modulo jaraba_multiregion.
   * Logica: PHP 8.x promoted properties para asignacion automatica.
   * Sintaxis: Parametros tipados con readonly en constructor.
   */
  public function __construct(
    protected RegionManagerService $regionManager,
    protected TaxCalculatorService $taxCalculator,
    protected ViesValidatorService $viesValidator,
    protected CurrencyConverterService $currencyConverter,
    protected RegionalComplianceService $regionalCompliance,
  ) {}

  /**
   * {@inheritdoc}
   *
   * Estructura: Factory method estatico requerido por ControllerBase.
   * Logica: Resuelve los 5 servicios desde el contenedor DI.
   * Sintaxis: Usa IDs de servicio definidos en jaraba_multiregion.services.yml.
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_multiregion.region_manager'),
      $container->get('jaraba_multiregion.tax_calculator'),
      $container->get('jaraba_multiregion.vies_validator'),
      $container->get('jaraba_multiregion.currency_converter'),
      $container->get('jaraba_multiregion.regional_compliance'),
    );
  }

  // ============================================
  // REGIONES
  // ============================================

  /**
   * GET /api/v1/regions/current — Configuracion regional del tenant actual.
   *
   * Estructura: Endpoint de lectura sin parametros de entrada.
   * Logica: Consulta RegionManagerService para obtener la configuracion
   *   regional del tenant autenticado. Retorna 404 si no hay region.
   * Sintaxis: Envelope {data: {...}} o {error: '...'} con 404.
   */
  public function showCurrentRegion(): JsonResponse {
    try {
      $region = $this->regionManager->getCurrentRegion();

      if (!$region) {
        return new JsonResponse([
          'error' => (string) new TranslatableMarkup('No region configurada para el tenant actual.'),
        ], 404);
      }

      return new JsonResponse([
        'data' => $this->serializeRegion($region),
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al obtener la configuracion regional.'),
      ], 500);
    }
  }

  /**
   * PATCH /api/v1/regions/current — Actualizar configuracion regional.
   *
   * Estructura: Endpoint de escritura que recibe JSON en el body.
   * Logica: Valida que el body contenga al menos un campo actualizable.
   *   Delega la actualizacion a RegionManagerService y retorna la
   *   region actualizada en el envelope {data}.
   * Sintaxis: Decodifica JSON del Request, valida y retorna JsonResponse.
   */
  public function updateCurrentRegion(Request $request): JsonResponse {
    try {
      $content = json_decode($request->getContent(), TRUE);

      if (empty($content)) {
        return new JsonResponse([
          'error' => (string) new TranslatableMarkup('El cuerpo de la solicitud no puede estar vacio.'),
        ], 422);
      }

      $region = $this->regionManager->getCurrentRegion();

      if (!$region) {
        return new JsonResponse([
          'error' => (string) new TranslatableMarkup('No region configurada para el tenant actual.'),
        ], 404);
      }

      $updatable = [
        'legal_jurisdiction',
        'data_region',
        'primary_dc',
        'base_currency',
        'display_currencies',
        'stripe_account_country',
        'vat_number',
        'gdpr_representative',
      ];

      foreach ($updatable as $field) {
        if (isset($content[$field])) {
          $region->set($field, $content[$field]);
        }
      }

      $region->save();

      return new JsonResponse([
        'data' => $this->serializeRegion($region),
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al actualizar la configuracion regional.'),
      ], 500);
    }
  }

  // ============================================
  // CALCULO DE IVA / TAX RULES
  // ============================================

  /**
   * POST /api/v1/tax/calculate — Calcular impuesto aplicable.
   *
   * Estructura: Endpoint POST que recibe datos de transaccion.
   * Logica: Espera seller_country, buyer_country, buyer_is_business,
   *   buyer_vat (opcional) y amount. Delega el calculo al
   *   TaxCalculatorService y retorna tasa, importe, reverse charge
   *   y articulo fiscal aplicable.
   * Sintaxis: Envelope {data: {rate, amount, reverse_charge, article}}.
   */
  public function calculateTax(Request $request): JsonResponse {
    try {
      $content = json_decode($request->getContent(), TRUE);

      $required = ['seller_country', 'buyer_country', 'buyer_is_business', 'amount'];
      foreach ($required as $field) {
        if (!isset($content[$field])) {
          return new JsonResponse([
            'error' => (string) new TranslatableMarkup('Campo obligatorio ausente: @field.', ['@field' => $field]),
          ], 422);
        }
      }

      $result = $this->taxCalculator->calculate(
        $content['seller_country'],
        $content['buyer_country'],
        (bool) $content['buyer_is_business'],
        $content['buyer_vat'] ?? NULL,
        (float) $content['amount'],
      );

      return new JsonResponse([
        'data' => [
          'rate' => $result['rate'],
          'amount' => $result['amount'],
          'reverse_charge' => $result['reverse_charge'],
          'article' => $result['article'],
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al calcular el impuesto.'),
      ], 500);
    }
  }

  /**
   * GET /api/v1/tax/rules — Listado paginado de reglas fiscales.
   *
   * Estructura: Endpoint GET con paginacion via query params.
   * Logica: Acepta limit (max 50, default 20) y offset (default 0).
   *   Consulta TaxCalculatorService para obtener las reglas y el total.
   * Sintaxis: Envelope {data: [...], meta: {total, limit, offset}}.
   */
  public function listTaxRules(Request $request): JsonResponse {
    try {
      $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
      $offset = max(0, (int) $request->query->get('offset', 0));

      $result = $this->taxCalculator->getTaxRules($limit, $offset);

      $data = [];
      foreach ($result['rules'] as $rule) {
        $data[] = $this->serializeTaxRule($rule);
      }

      return new JsonResponse([
        'data' => $data,
        'meta' => [
          'total' => $result['total'],
          'limit' => $limit,
          'offset' => $offset,
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al listar reglas fiscales.'),
      ], 500);
    }
  }

  /**
   * POST /api/v1/tax/rules — Crear regla fiscal (API-NAMING-001: store).
   *
   * Estructura: Endpoint POST para creacion de regla fiscal.
   * Logica: Espera country_code, tax_name, standard_rate y opcionalmente
   *   reduced_rate, super_reduced_rate, digital_services_rate,
   *   oss_threshold, reverse_charge_enabled, effective_from, effective_to.
   *   Valida campos obligatorios y delega al storage de entidad.
   * Sintaxis: Retorna {data: {...}} con codigo 201 en caso de exito.
   */
  public function storeTaxRule(Request $request): JsonResponse {
    try {
      $content = json_decode($request->getContent(), TRUE);

      $required = ['country_code', 'tax_name', 'standard_rate'];
      foreach ($required as $field) {
        if (!isset($content[$field])) {
          return new JsonResponse([
            'error' => (string) new TranslatableMarkup('Campo obligatorio ausente: @field.', ['@field' => $field]),
          ], 422);
        }
      }

      $storage = $this->entityTypeManager()->getStorage('tax_rule');
      $rule = $storage->create([
        'country_code' => $content['country_code'],
        'tax_name' => $content['tax_name'],
        'eu_member' => $content['eu_member'] ?? FALSE,
        'standard_rate' => (float) $content['standard_rate'],
        'reduced_rate' => isset($content['reduced_rate']) ? (float) $content['reduced_rate'] : NULL,
        'super_reduced_rate' => isset($content['super_reduced_rate']) ? (float) $content['super_reduced_rate'] : NULL,
        'digital_services_rate' => isset($content['digital_services_rate']) ? (float) $content['digital_services_rate'] : NULL,
        'oss_threshold' => isset($content['oss_threshold']) ? (float) $content['oss_threshold'] : NULL,
        'reverse_charge_enabled' => $content['reverse_charge_enabled'] ?? FALSE,
        'effective_from' => $content['effective_from'] ?? NULL,
        'effective_to' => $content['effective_to'] ?? NULL,
      ]);
      $rule->save();

      return new JsonResponse([
        'data' => $this->serializeTaxRule($rule),
      ], 201);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al crear la regla fiscal.'),
      ], 500);
    }
  }

  // ============================================
  // VALIDACION VIES
  // ============================================

  /**
   * POST /api/v1/vies/validate — Validar numero de IVA via VIES.
   *
   * Estructura: Endpoint POST que recibe un numero de IVA.
   * Logica: Espera vat_number en el body. Delega la validacion al
   *   ViesValidatorService que consulta el servicio VIES de la UE.
   *   Retorna validez, nombre y direccion de la empresa.
   * Sintaxis: Envelope {data: {is_valid, company_name, company_address}}.
   */
  public function validateVies(Request $request): JsonResponse {
    try {
      $content = json_decode($request->getContent(), TRUE);

      if (empty($content['vat_number'])) {
        return new JsonResponse([
          'error' => (string) new TranslatableMarkup('El numero de IVA es obligatorio.'),
        ], 422);
      }

      $result = $this->viesValidator->validate($content['vat_number']);

      return new JsonResponse([
        'data' => [
          'is_valid' => $result['is_valid'],
          'company_name' => $result['company_name'] ?? '',
          'company_address' => $result['company_address'] ?? '',
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al validar el numero de IVA.'),
      ], 500);
    }
  }

  /**
   * GET /api/v1/vies/history — Historial de validaciones VIES.
   *
   * Estructura: Endpoint GET con paginacion via query params.
   * Logica: Acepta limit (max 50, default 20) y offset (default 0).
   *   Consulta ViesValidatorService para obtener el historial
   *   de validaciones realizadas y el total de registros.
   * Sintaxis: Envelope {data: [...], meta: {total, limit, offset}}.
   */
  public function viesHistory(Request $request): JsonResponse {
    try {
      $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
      $offset = max(0, (int) $request->query->get('offset', 0));

      $result = $this->viesValidator->getHistory($limit, $offset);

      $data = [];
      foreach ($result['validations'] as $validation) {
        $data[] = $this->serializeViesValidation($validation);
      }

      return new JsonResponse([
        'data' => $data,
        'meta' => [
          'total' => $result['total'],
          'limit' => $limit,
          'offset' => $offset,
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al obtener historial VIES.'),
      ], 500);
    }
  }

  // ============================================
  // CONVERSION DE MONEDA
  // ============================================

  /**
   * POST /api/v1/currency/convert — Convertir importe entre monedas.
   *
   * Estructura: Endpoint POST que recibe importe y par de monedas.
   * Logica: Espera amount, from y to en el body. Delega la conversion
   *   al CurrencyConverterService que usa las tasas almacenadas.
   * Sintaxis: Envelope {data: {amount, rate, from, to}}.
   */
  public function convertCurrency(Request $request): JsonResponse {
    try {
      $content = json_decode($request->getContent(), TRUE);

      $required = ['amount', 'from', 'to'];
      foreach ($required as $field) {
        if (!isset($content[$field])) {
          return new JsonResponse([
            'error' => (string) new TranslatableMarkup('Campo obligatorio ausente: @field.', ['@field' => $field]),
          ], 422);
        }
      }

      $result = $this->currencyConverter->convert(
        (float) $content['amount'],
        $content['from'],
        $content['to'],
      );

      return new JsonResponse([
        'data' => [
          'amount' => $result['amount'],
          'rate' => $result['rate'],
          'from' => $content['from'],
          'to' => $content['to'],
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al convertir la moneda.'),
      ], 500);
    }
  }

  /**
   * GET /api/v1/currency/rates — Tasas de cambio actuales.
   *
   * Estructura: Endpoint GET con parametro opcional base currency.
   * Logica: Acepta query param base (default EUR). Consulta el
   *   CurrencyConverterService para obtener las tasas vigentes
   *   referenciadas a la moneda base especificada.
   * Sintaxis: Envelope {data: [...]}.
   */
  public function listCurrencyRates(Request $request): JsonResponse {
    try {
      $base = $request->query->get('base', 'EUR');

      $rates = $this->currencyConverter->getCurrentRates($base);

      return new JsonResponse([
        'data' => $rates,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al obtener tasas de cambio.'),
      ], 500);
    }
  }

  // ============================================
  // COMPLIANCE REGIONAL
  // ============================================

  /**
   * GET /api/v1/regions/compliance — Verificacion de compliance.
   *
   * Estructura: Endpoint GET sin parametros de entrada.
   * Logica: Consulta RegionalComplianceService para verificar el
   *   cumplimiento normativo del tenant actual segun su jurisdiccion.
   *   Retorna checks individuales, estado global e incidencias.
   * Sintaxis: Envelope {data: {checks, overall, issues}}.
   */
  public function checkCompliance(): JsonResponse {
    try {
      $result = $this->regionalCompliance->checkCurrentTenant();

      return new JsonResponse([
        'data' => [
          'checks' => $result['checks'],
          'overall' => $result['overall'],
          'issues' => $result['issues'],
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Error al verificar el compliance regional.'),
      ], 500);
    }
  }

  // ============================================
  // SERIALIZACION
  // ============================================

  /**
   * Serializa una entidad TenantRegion para respuesta JSON.
   *
   * Estructura: Metodo protegido de serializacion interna.
   * Logica: Extrae los campos relevantes de la entidad y los
   *   convierte a un array asociativo con tipos correctos.
   * Sintaxis: Retorna array con claves snake_case.
   */
  protected function serializeRegion(object $region): array {
    return [
      'id' => (int) $region->id(),
      'tenant_id' => $region->get('tenant_id')->target_id ?? NULL,
      'legal_jurisdiction' => $region->get('legal_jurisdiction')->value ?? '',
      'data_region' => $region->get('data_region')->value ?? '',
      'primary_dc' => $region->get('primary_dc')->value ?? '',
      'base_currency' => $region->get('base_currency')->value ?? '',
      'display_currencies' => $region->get('display_currencies')->value ?? '',
      'stripe_account_country' => $region->get('stripe_account_country')->value ?? '',
      'vat_number' => $region->get('vat_number')->value ?? '',
      'vies_validated' => (bool) ($region->get('vies_validated')->value ?? FALSE),
      'vies_validated_at' => $region->get('vies_validated_at')->value ?? NULL,
      'gdpr_representative' => $region->get('gdpr_representative')->value ?? '',
      'created' => $region->get('created')->value ?? NULL,
      'changed' => $region->get('changed')->value ?? NULL,
    ];
  }

  /**
   * Serializa una entidad TaxRule para respuesta JSON.
   *
   * Estructura: Metodo protegido de serializacion interna.
   * Logica: Convierte campos numericos a float y booleanos
   *   a bool nativo para consistencia en el JSON de salida.
   * Sintaxis: Retorna array con claves snake_case y tipos nativos.
   */
  protected function serializeTaxRule(object $rule): array {
    return [
      'id' => (int) $rule->id(),
      'country_code' => $rule->get('country_code')->value ?? '',
      'tax_name' => $rule->get('tax_name')->value ?? '',
      'eu_member' => (bool) ($rule->get('eu_member')->value ?? FALSE),
      'standard_rate' => (float) ($rule->get('standard_rate')->value ?? 0),
      'reduced_rate' => $rule->get('reduced_rate')->value !== NULL ? (float) $rule->get('reduced_rate')->value : NULL,
      'super_reduced_rate' => $rule->get('super_reduced_rate')->value !== NULL ? (float) $rule->get('super_reduced_rate')->value : NULL,
      'digital_services_rate' => $rule->get('digital_services_rate')->value !== NULL ? (float) $rule->get('digital_services_rate')->value : NULL,
      'oss_threshold' => $rule->get('oss_threshold')->value !== NULL ? (float) $rule->get('oss_threshold')->value : NULL,
      'reverse_charge_enabled' => (bool) ($rule->get('reverse_charge_enabled')->value ?? FALSE),
      'effective_from' => $rule->get('effective_from')->value ?? NULL,
      'effective_to' => $rule->get('effective_to')->value ?? NULL,
      'created' => $rule->get('created')->value ?? NULL,
      'changed' => $rule->get('changed')->value ?? NULL,
    ];
  }

  /**
   * Serializa una entidad ViesValidation para respuesta JSON.
   *
   * Estructura: Metodo protegido de serializacion interna.
   * Logica: Extrae los datos del resultado de validacion VIES
   *   incluyendo metadatos de la peticion y respuesta.
   * Sintaxis: Retorna array con claves snake_case.
   */
  protected function serializeViesValidation(object $validation): array {
    return [
      'id' => (int) $validation->id(),
      'vat_number' => $validation->get('vat_number')->value ?? '',
      'country_code' => $validation->get('country_code')->value ?? '',
      'is_valid' => (bool) ($validation->get('is_valid')->value ?? FALSE),
      'company_name' => $validation->get('company_name')->value ?? '',
      'company_address' => $validation->get('company_address')->value ?? '',
      'request_identifier' => $validation->get('request_identifier')->value ?? '',
      'validated_at' => $validation->get('validated_at')->value ?? NULL,
    ];
  }

}
