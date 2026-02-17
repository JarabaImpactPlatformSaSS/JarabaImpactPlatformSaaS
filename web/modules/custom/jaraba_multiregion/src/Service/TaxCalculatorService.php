<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

/**
 * Servicio de calculo de impuestos por jurisdiccion.
 *
 * ESTRUCTURA:
 * Implementa la logica de calculo de IVA intracomunitario segun la
 * Directiva 2006/112/CE de la UE y el regimen OSS (One Stop Shop).
 * Gestiona escenarios B2B, B2C, inversion de sujeto pasivo y exportaciones.
 *
 * LOGICA:
 * El calculo sigue un arbol de decision basado en:
 * 1. Mismo pais vendedor/comprador -> IVA local siempre.
 * 2. B2B intracomunitario con VAT valido -> Inversion sujeto pasivo (0%).
 * 3. B2C intracomunitario -> IVA del pais destino (regimen OSS).
 * 4. Fuera de la UE -> Exento (exportacion).
 *
 * Las reglas fiscales se almacenan como entidades TaxRule con tipos
 * impositivos por pais y fechas de vigencia.
 *
 * SINTAXIS:
 * - Constructor con propiedades promovidas (PHP 8.3).
 * - Cadenas visibles usan TranslatableMarkup.
 * - Llamadas a storage envueltas en try/catch con logger.
 * - Retorna arrays estructurados con rate, amount, reverse_charge, article.
 *
 * @see https://eur-lex.europa.eu/eli/dir/2006/112/oj Art. 196 Directiva IVA
 * @see https://ec.europa.eu/taxation_customs/business/vat/oss_en Regimen OSS
 */
class TaxCalculatorService {

  /**
   * Lista de codigos de pais ISO 3166-1 alpha-2 de estados miembros de la UE.
   *
   * Actualizada a 2025 (27 estados miembros tras Brexit).
   *
   * @var string[]
   */
  protected const EU_MEMBER_STATES = [
    'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
    'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
    'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
  ];

  /**
   * Umbral OSS para ventas a distancia intracomunitarias (en EUR).
   *
   * Desde julio 2021, el umbral unico es de 10.000 EUR anuales
   * para todas las ventas a distancia B2C dentro de la UE.
   *
   * @var float
   */
  protected const OSS_THRESHOLD_EUR = 10000.0;

  /**
   * Constructor del servicio.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para acceder al storage de TaxRule.
   * @param \Drupal\jaraba_multiregion\Service\RegionManagerService $regionManager
   *   Servicio de gestion de regiones para contexto del tenant.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de logger para registrar errores y calculos.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly RegionManagerService $regionManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Calcula el IVA aplicable segun escenario fiscal.
   *
   * LOGICA:
   * Logica de decision basada en Art. 196 Directiva 2006/112/CE
   * y regimen OSS (One Stop Shop) de la UE.
   *
   * Escenarios:
   * 1. Mismo pais -> IVA local del pais del vendedor.
   * 2. B2B UE con VAT valido -> Inversion sujeto pasivo (reverse charge, 0%).
   * 3. B2C UE -> IVA del pais destino (digital_services_rate o standard_rate).
   * 4. Fuera UE -> Exento por exportacion.
   *
   * @param string $seller_country
   *   Codigo ISO del pais del vendedor (ej: 'ES').
   * @param string $buyer_country
   *   Codigo ISO del pais del comprador (ej: 'DE').
   * @param bool $buyer_is_business
   *   TRUE si el comprador es una empresa (B2B).
   * @param string|null $buyer_vat
   *   Numero de IVA del comprador (ej: 'DE123456789'), o NULL.
   * @param float $amount
   *   Importe base imponible sobre el que calcular el impuesto.
   *
   * @return array
   *   Array estructurado con:
   *   - 'rate': Tipo impositivo aplicado (porcentaje).
   *   - 'amount': Importe del impuesto calculado.
   *   - 'reverse_charge': TRUE si aplica inversion de sujeto pasivo.
   *   - 'article': Articulo legal o regimen aplicable.
   */
  public function calculate(
    string $seller_country,
    string $buyer_country,
    bool $buyer_is_business,
    ?string $buyer_vat,
    float $amount,
  ): array {
    try {
      // Escenario 1: Mismo pais -> IVA local siempre.
      if ($seller_country === $buyer_country) {
        $rule = $this->getTaxRule($seller_country);
        if (!$rule) {
          return [
            'rate' => 0.0,
            'amount' => 0.0,
            'reverse_charge' => FALSE,
            'article' => (string) new TranslatableMarkup('Sin regla fiscal configurada'),
          ];
        }
        $rate = (float) ($rule->get('standard_rate')->value ?? 0);
        return [
          'rate' => $rate,
          'amount' => round($amount * ($rate / 100), 2),
          'reverse_charge' => FALSE,
          'article' => '',
        ];
      }

      // Escenario 2: B2B UE con VAT valido -> Inversion sujeto pasivo (0%).
      if ($buyer_is_business && $buyer_vat && $this->isEuMember($buyer_country)) {
        return [
          'rate' => 0.0,
          'amount' => 0.0,
          'reverse_charge' => TRUE,
          'article' => 'Art. 196 Directiva 2006/112/CE',
        ];
      }

      // Escenario 3: B2C UE -> IVA del pais destino (bajo regimen OSS).
      if (!$buyer_is_business && $this->isEuMember($buyer_country)) {
        $rule = $this->getTaxRule($buyer_country);
        if (!$rule) {
          return [
            'rate' => 0.0,
            'amount' => 0.0,
            'reverse_charge' => FALSE,
            'article' => (string) new TranslatableMarkup('Sin regla fiscal para pais destino'),
          ];
        }
        // Priorizar tipo para servicios digitales, fallback a tipo general.
        $rate = (float) ($rule->get('digital_services_rate')->value
          ?? $rule->get('standard_rate')->value
          ?? 0);
        return [
          'rate' => $rate,
          'amount' => round($amount * ($rate / 100), 2),
          'reverse_charge' => FALSE,
          'article' => (string) new TranslatableMarkup('Regimen OSS'),
        ];
      }

      // Escenario 4: Fuera UE -> Exento por exportacion.
      return [
        'rate' => 0.0,
        'amount' => 0.0,
        'reverse_charge' => FALSE,
        'article' => (string) new TranslatableMarkup('Exportacion fuera UE'),
      ];

    }
    catch (\Exception $e) {
      $this->logger->error('TaxCalculator: Error calculando impuesto @seller->@buyer: @error', [
        '@seller' => $seller_country,
        '@buyer' => $buyer_country,
        '@error' => $e->getMessage(),
      ]);
      return [
        'rate' => 0.0,
        'amount' => 0.0,
        'reverse_charge' => FALSE,
        'article' => (string) new TranslatableMarkup('Error en calculo fiscal'),
      ];
    }
  }

  /**
   * Obtiene la regla fiscal vigente para un pais.
   *
   * LOGICA:
   * Consulta la entidad tax_rule filtrando por:
   * 1. country_code = pais solicitado.
   * 2. effective_from <= fecha actual.
   * 3. effective_to es NULL (sin fecha fin) o >= fecha actual.
   * Ordena por effective_from descendente para obtener la mas reciente.
   *
   * @param string $countryCode
   *   Codigo ISO del pais (ej: 'ES').
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   La entidad TaxRule vigente, o NULL si no hay regla configurada.
   */
  public function getTaxRule(string $countryCode): mixed {
    try {
      $storage = $this->entityTypeManager->getStorage('tax_rule');
      $now = date('Y-m-d');

      // Consultar reglas vigentes para el pais.
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('country_code', $countryCode)
        ->condition('effective_from', $now, '<=')
        ->sort('effective_from', 'DESC')
        ->range(0, 1);

      $ids = $query->execute();

      if (empty($ids)) {
        return NULL;
      }

      // Cargar la regla mas reciente.
      $rule = $storage->load(reset($ids));

      // Verificar que no haya expirado (effective_to NULL o >= hoy).
      if ($rule) {
        $effectiveTo = $rule->get('effective_to')->value ?? NULL;
        if ($effectiveTo !== NULL && $effectiveTo < $now) {
          $this->logger->warning('TaxCalculator: Regla fiscal expirada para @country (expiro: @date)', [
            '@country' => $countryCode,
            '@date' => $effectiveTo,
          ]);
          return NULL;
        }
      }

      return $rule;

    }
    catch (\Exception $e) {
      $this->logger->error('TaxCalculator: Error obteniendo regla fiscal para @country: @error', [
        '@country' => $countryCode,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Verifica si un codigo de pais corresponde a un estado miembro de la UE.
   *
   * LOGICA:
   * Primero comprueba contra la lista estatica de estados miembros.
   * Si no esta en la lista, intenta verificar consultando entidades tax_rule
   * que tengan el pais marcado como miembro UE (campo eu_member).
   *
   * @param string $countryCode
   *   Codigo ISO del pais a verificar.
   *
   * @return bool
   *   TRUE si el pais es miembro de la UE.
   */
  public function isEuMember(string $countryCode): bool {
    // Verificacion rapida contra lista estatica.
    if (in_array($countryCode, self::EU_MEMBER_STATES, TRUE)) {
      return TRUE;
    }

    // Fallback: consultar en las reglas fiscales almacenadas.
    try {
      $storage = $this->entityTypeManager->getStorage('tax_rule');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('country_code', $countryCode)
        ->condition('eu_member', TRUE)
        ->range(0, 1);

      $ids = $query->execute();
      return !empty($ids);

    }
    catch (\Exception $e) {
      $this->logger->warning('TaxCalculator: Error verificando membresia UE para @country: @error', [
        '@country' => $countryCode,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Verifica si aplica inversion de sujeto pasivo (reverse charge).
   *
   * LOGICA:
   * Metodo de conveniencia que evalua si una transaccion entre dos paises
   * cumple las condiciones para inversion de sujeto pasivo:
   * 1. Paises diferentes.
   * 2. Comprador es empresa (B2B).
   * 3. Comprador tiene VAT valido.
   * 4. Comprador esta en la UE.
   *
   * @param string $sellerCountry
   *   Codigo ISO del pais del vendedor.
   * @param string $buyerCountry
   *   Codigo ISO del pais del comprador.
   * @param bool $buyerIsBusiness
   *   TRUE si el comprador es empresa.
   * @param string|null $buyerVat
   *   Numero de IVA del comprador.
   *
   * @return bool
   *   TRUE si aplica inversion de sujeto pasivo.
   */
  public function isReverseCharge(
    string $sellerCountry,
    string $buyerCountry,
    bool $buyerIsBusiness,
    ?string $buyerVat,
  ): bool {
    // Mismo pais: nunca reverse charge.
    if ($sellerCountry === $buyerCountry) {
      return FALSE;
    }

    // Debe ser B2B con VAT valido y comprador en la UE.
    return $buyerIsBusiness
      && !empty($buyerVat)
      && $this->isEuMember($buyerCountry);
  }

  /**
   * Obtiene el estado del regimen OSS para un pais vendedor.
   *
   * LOGICA:
   * Retorna informacion sobre el umbral OSS (10.000 EUR anuales para
   * ventas B2C intracomunitarias) y si el vendedor esta sujeto al regimen.
   * Consulta la region del tenant para determinar si esta registrado en OSS.
   *
   * @param string $sellerCountry
   *   Codigo ISO del pais del vendedor (ej: 'ES').
   *
   * @return array
   *   Array estructurado con:
   *   - 'threshold_eur': Umbral anual en EUR para activar OSS.
   *   - 'seller_country': Pais del vendedor.
   *   - 'is_eu_seller': Si el vendedor esta en la UE.
   *   - 'oss_registered': Si esta registrado en OSS (segun region del tenant).
   *   - 'description': Descripcion del regimen (TranslatableMarkup).
   */
  public function getOssStatus(string $sellerCountry): array {
    try {
      $isEuSeller = $this->isEuMember($sellerCountry);

      // Consultar si el tenant tiene OSS registrado en su region.
      $region = $this->regionManager->getRegion();
      $ossRegistered = FALSE;
      if ($region && $region->hasField('oss_registered')) {
        $ossRegistered = (bool) ($region->get('oss_registered')->value ?? FALSE);
      }

      return [
        'threshold_eur' => self::OSS_THRESHOLD_EUR,
        'seller_country' => $sellerCountry,
        'is_eu_seller' => $isEuSeller,
        'oss_registered' => $ossRegistered,
        'description' => $isEuSeller
          ? new TranslatableMarkup(
              'Regimen OSS: umbral de @threshold EUR anuales para ventas B2C intracomunitarias. Si se supera, se debe declarar IVA del pais destino.',
              ['@threshold' => number_format(self::OSS_THRESHOLD_EUR, 0, ',', '.')]
            )
          : new TranslatableMarkup('El regimen OSS solo aplica a vendedores establecidos en la UE.'),
      ];

    }
    catch (\Exception $e) {
      $this->logger->error('TaxCalculator: Error obteniendo estado OSS para @country: @error', [
        '@country' => $sellerCountry,
        '@error' => $e->getMessage(),
      ]);
      return [
        'threshold_eur' => self::OSS_THRESHOLD_EUR,
        'seller_country' => $sellerCountry,
        'is_eu_seller' => FALSE,
        'oss_registered' => FALSE,
        'description' => new TranslatableMarkup('Error consultando estado OSS.'),
      ];
    }
  }

}
