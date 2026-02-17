<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestion de regiones por tenant.
 *
 * ESTRUCTURA:
 * Gestiona la configuracion regional de cada tenant: pais, moneda,
 * jurisdiccion fiscal y residencia de datos. Cada tenant tiene una
 * entidad TenantRegion asociada que almacena estas preferencias.
 *
 * LOGICA:
 * - Resuelve la region del tenant actual via TenantContextService.
 * - Permite crear o actualizar la configuracion regional.
 * - Expone catalogos de regiones, monedas y jurisdicciones disponibles.
 * - Los catalogos usan TranslatableMarkup para internacionalizacion.
 *
 * SINTAXIS:
 * - Constructor con propiedades promovidas (PHP 8.3).
 * - Todas las cadenas visibles al usuario usan TranslatableMarkup.
 * - Llamadas externas envueltas en try/catch con logger.
 * - Retorna arrays estructurados en todos los metodos publicos.
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\TenantContextService
 */
class RegionManagerService {

  /**
   * Constructor del servicio.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para acceder al storage de TenantRegion.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext
   *   Servicio de contexto de tenant para resolver el tenant actual.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de logger para registrar errores y eventos.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly TenantContextService $tenantContext,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Obtiene la configuracion regional del tenant actual.
   *
   * LOGICA:
   * 1. Resuelve el tenant actual via TenantContextService.
   * 2. Consulta la entidad tenant_region donde tenant_id = tenant actual.
   * 3. Retorna la primera coincidencia o NULL si no existe configuracion.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   La entidad TenantRegion del tenant actual, o NULL si no esta configurada.
   */
  public function getRegion(): mixed {
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      if (!$tenant) {
        $this->logger->warning('RegionManager: No se pudo resolver el tenant actual.');
        return NULL;
      }

      $tenantId = $tenant->id();
      $storage = $this->entityTypeManager->getStorage('tenant_region');

      // Consultar la region asociada al tenant actual.
      $results = $storage->loadByProperties([
        'tenant_id' => $tenantId,
      ]);

      if (empty($results)) {
        return NULL;
      }

      // Retornar la primera coincidencia (un tenant tiene una sola region).
      return reset($results);

    }
    catch (\Exception $e) {
      $this->logger->error('RegionManager: Error obteniendo region del tenant: @error', [
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Crea o actualiza la configuracion regional del tenant actual.
   *
   * LOGICA:
   * 1. Resuelve el tenant actual.
   * 2. Busca una TenantRegion existente para ese tenant.
   * 3. Si existe, actualiza los campos con los datos proporcionados.
   * 4. Si no existe, crea una nueva entidad TenantRegion.
   * 5. Guarda la entidad y retorna la instancia resultante.
   *
   * @param array $data
   *   Datos de la region con claves opcionales:
   *   - 'country_code': Codigo ISO 3166-1 alpha-2 del pais (ej: 'ES').
   *   - 'currency': Codigo ISO 4217 de la moneda (ej: 'EUR').
   *   - 'jurisdiction': Jurisdiccion fiscal aplicable.
   *   - 'data_region': Region de residencia de datos (ej: 'eu-west').
   *   - 'timezone': Zona horaria (ej: 'Europe/Madrid').
   *   - 'language': Codigo de idioma (ej: 'es').
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   La entidad TenantRegion creada o actualizada.
   *
   * @throws \RuntimeException
   *   Si no se puede resolver el tenant actual.
   */
  public function setRegion(array $data): mixed {
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      if (!$tenant) {
        throw new \RuntimeException((string) new TranslatableMarkup(
          'No se puede configurar la region sin un tenant activo.'
        ));
      }

      $tenantId = $tenant->id();
      $storage = $this->entityTypeManager->getStorage('tenant_region');

      // Buscar configuracion existente del tenant.
      $existing = $storage->loadByProperties([
        'tenant_id' => $tenantId,
      ]);

      if (!empty($existing)) {
        // Actualizar la entidad existente.
        $region = reset($existing);
        foreach ($data as $field => $value) {
          if ($region->hasField($field)) {
            $region->set($field, $value);
          }
        }
      }
      else {
        // Crear nueva entidad TenantRegion.
        $data['tenant_id'] = $tenantId;
        $region = $storage->create($data);
      }

      $region->save();

      $this->logger->info('RegionManager: Region actualizada para tenant @id: pais=@country, moneda=@currency', [
        '@id' => $tenantId,
        '@country' => $data['country_code'] ?? 'N/A',
        '@currency' => $data['currency'] ?? 'N/A',
      ]);

      return $region;

    }
    catch (\RuntimeException $e) {
      // Re-lanzar excepciones de logica de negocio.
      throw $e;
    }
    catch (\Exception $e) {
      $this->logger->error('RegionManager: Error configurando region: @error', [
        '@error' => $e->getMessage(),
      ]);
      throw new \RuntimeException((string) new TranslatableMarkup(
        'Error al guardar la configuracion regional: @error',
        ['@error' => $e->getMessage()]
      ));
    }
  }

  /**
   * Retorna las regiones de datos disponibles con sus etiquetas.
   *
   * LOGICA:
   * Catalogo estatico de regiones de residencia de datos soportadas
   * por la plataforma. Cada region corresponde a una zona geografica
   * donde se pueden alojar los datos del tenant cumpliendo GDPR.
   *
   * @return array
   *   Array asociativo donde la clave es el ID de la region y el valor
   *   es un array con:
   *   - 'label': Nombre legible de la region (TranslatableMarkup).
   *   - 'location': Ubicacion fisica del datacenter.
   *   - 'gdpr_compliant': Si cumple GDPR.
   *   - 'available': Si esta disponible para nuevos tenants.
   */
  public function getAvailableRegions(): array {
    return [
      'eu-west' => [
        'label' => new TranslatableMarkup('Europa Occidental'),
        'location' => new TranslatableMarkup('Irlanda / Frankfurt'),
        'gdpr_compliant' => TRUE,
        'available' => TRUE,
      ],
      'eu-south' => [
        'label' => new TranslatableMarkup('Europa Sur'),
        'location' => new TranslatableMarkup('Madrid / Milan'),
        'gdpr_compliant' => TRUE,
        'available' => TRUE,
      ],
      'eu-north' => [
        'label' => new TranslatableMarkup('Europa Norte'),
        'location' => new TranslatableMarkup('Estocolmo / Helsinki'),
        'gdpr_compliant' => TRUE,
        'available' => TRUE,
      ],
      'latam' => [
        'label' => new TranslatableMarkup('Latinoamerica'),
        'location' => new TranslatableMarkup('Sao Paulo / Ciudad de Mexico'),
        'gdpr_compliant' => FALSE,
        'available' => TRUE,
      ],
      'us-east' => [
        'label' => new TranslatableMarkup('Estados Unidos Este'),
        'location' => new TranslatableMarkup('Virginia'),
        'gdpr_compliant' => FALSE,
        'available' => TRUE,
      ],
      'us-west' => [
        'label' => new TranslatableMarkup('Estados Unidos Oeste'),
        'location' => new TranslatableMarkup('Oregon'),
        'gdpr_compliant' => FALSE,
        'available' => TRUE,
      ],
    ];
  }

  /**
   * Retorna las monedas soportadas con sus etiquetas.
   *
   * LOGICA:
   * Catalogo de monedas ISO 4217 soportadas por la plataforma.
   * Incluye las monedas principales de la UE y Latinoamerica.
   *
   * @return array
   *   Array asociativo donde la clave es el codigo ISO 4217 y el valor
   *   es un array con:
   *   - 'label': Nombre de la moneda (TranslatableMarkup).
   *   - 'symbol': Simbolo de la moneda.
   *   - 'decimal_places': Numero de decimales estandar.
   */
  public function getAvailableCurrencies(): array {
    return [
      'EUR' => [
        'label' => new TranslatableMarkup('Euro'),
        'symbol' => "\u{20AC}",
        'decimal_places' => 2,
      ],
      'USD' => [
        'label' => new TranslatableMarkup('Dolar estadounidense'),
        'symbol' => '$',
        'decimal_places' => 2,
      ],
      'GBP' => [
        'label' => new TranslatableMarkup('Libra esterlina'),
        'symbol' => "\u{00A3}",
        'decimal_places' => 2,
      ],
      'CHF' => [
        'label' => new TranslatableMarkup('Franco suizo'),
        'symbol' => 'CHF',
        'decimal_places' => 2,
      ],
      'SEK' => [
        'label' => new TranslatableMarkup('Corona sueca'),
        'symbol' => 'kr',
        'decimal_places' => 2,
      ],
      'DKK' => [
        'label' => new TranslatableMarkup('Corona danesa'),
        'symbol' => 'kr',
        'decimal_places' => 2,
      ],
      'PLN' => [
        'label' => new TranslatableMarkup('Esloti polaco'),
        'symbol' => "z\u{0142}",
        'decimal_places' => 2,
      ],
      'CZK' => [
        'label' => new TranslatableMarkup('Corona checa'),
        'symbol' => "K\u{010D}",
        'decimal_places' => 2,
      ],
      'RON' => [
        'label' => new TranslatableMarkup('Leu rumano'),
        'symbol' => 'lei',
        'decimal_places' => 2,
      ],
      'HUF' => [
        'label' => new TranslatableMarkup('Forinto hungaro'),
        'symbol' => 'Ft',
        'decimal_places' => 0,
      ],
      'BGN' => [
        'label' => new TranslatableMarkup('Lev bulgaro'),
        'symbol' => "лв",
        'decimal_places' => 2,
      ],
      'MXN' => [
        'label' => new TranslatableMarkup('Peso mexicano'),
        'symbol' => 'MX$',
        'decimal_places' => 2,
      ],
      'BRL' => [
        'label' => new TranslatableMarkup('Real brasileno'),
        'symbol' => 'R$',
        'decimal_places' => 2,
      ],
      'COP' => [
        'label' => new TranslatableMarkup('Peso colombiano'),
        'symbol' => 'COL$',
        'decimal_places' => 0,
      ],
      'ARS' => [
        'label' => new TranslatableMarkup('Peso argentino'),
        'symbol' => 'AR$',
        'decimal_places' => 2,
      ],
      'CLP' => [
        'label' => new TranslatableMarkup('Peso chileno'),
        'symbol' => 'CL$',
        'decimal_places' => 0,
      ],
    ];
  }

  /**
   * Retorna las jurisdicciones fiscales soportadas.
   *
   * LOGICA:
   * Catalogo de jurisdicciones fiscales que la plataforma puede gestionar.
   * Cada jurisdiccion tiene sus propias reglas de IVA, facturacion y
   * requisitos regulatorios asociados.
   *
   * @return array
   *   Array asociativo donde la clave es el codigo de jurisdiccion y el valor
   *   es un array con:
   *   - 'label': Nombre de la jurisdiccion (TranslatableMarkup).
   *   - 'country_code': Codigo ISO del pais.
   *   - 'tax_authority': Nombre de la autoridad fiscal.
   *   - 'vat_prefix': Prefijo del numero de IVA.
   *   - 'eu_member': Si es miembro de la UE.
   *   - 'oss_applicable': Si aplica el regimen OSS.
   */
  public function getAvailableJurisdictions(): array {
    return [
      'ES' => [
        'label' => new TranslatableMarkup('Espana'),
        'country_code' => 'ES',
        'tax_authority' => new TranslatableMarkup('Agencia Tributaria (AEAT)'),
        'vat_prefix' => 'ES',
        'eu_member' => TRUE,
        'oss_applicable' => TRUE,
      ],
      'DE' => [
        'label' => new TranslatableMarkup('Alemania'),
        'country_code' => 'DE',
        'tax_authority' => new TranslatableMarkup('Bundeszentralamt fur Steuern'),
        'vat_prefix' => 'DE',
        'eu_member' => TRUE,
        'oss_applicable' => TRUE,
      ],
      'FR' => [
        'label' => new TranslatableMarkup('Francia'),
        'country_code' => 'FR',
        'tax_authority' => new TranslatableMarkup('Direction Generale des Finances Publiques'),
        'vat_prefix' => 'FR',
        'eu_member' => TRUE,
        'oss_applicable' => TRUE,
      ],
      'IT' => [
        'label' => new TranslatableMarkup('Italia'),
        'country_code' => 'IT',
        'tax_authority' => new TranslatableMarkup('Agenzia delle Entrate'),
        'vat_prefix' => 'IT',
        'eu_member' => TRUE,
        'oss_applicable' => TRUE,
      ],
      'PT' => [
        'label' => new TranslatableMarkup('Portugal'),
        'country_code' => 'PT',
        'tax_authority' => new TranslatableMarkup('Autoridade Tributaria e Aduaneira'),
        'vat_prefix' => 'PT',
        'eu_member' => TRUE,
        'oss_applicable' => TRUE,
      ],
      'NL' => [
        'label' => new TranslatableMarkup('Paises Bajos'),
        'country_code' => 'NL',
        'tax_authority' => new TranslatableMarkup('Belastingdienst'),
        'vat_prefix' => 'NL',
        'eu_member' => TRUE,
        'oss_applicable' => TRUE,
      ],
      'BE' => [
        'label' => new TranslatableMarkup('Belgica'),
        'country_code' => 'BE',
        'tax_authority' => new TranslatableMarkup('SPF Finances'),
        'vat_prefix' => 'BE',
        'eu_member' => TRUE,
        'oss_applicable' => TRUE,
      ],
      'IE' => [
        'label' => new TranslatableMarkup('Irlanda'),
        'country_code' => 'IE',
        'tax_authority' => new TranslatableMarkup('Revenue Commissioners'),
        'vat_prefix' => 'IE',
        'eu_member' => TRUE,
        'oss_applicable' => TRUE,
      ],
      'AT' => [
        'label' => new TranslatableMarkup('Austria'),
        'country_code' => 'AT',
        'tax_authority' => new TranslatableMarkup('Bundesministerium fur Finanzen'),
        'vat_prefix' => 'ATU',
        'eu_member' => TRUE,
        'oss_applicable' => TRUE,
      ],
      'PL' => [
        'label' => new TranslatableMarkup('Polonia'),
        'country_code' => 'PL',
        'tax_authority' => new TranslatableMarkup('Krajowa Administracja Skarbowa'),
        'vat_prefix' => 'PL',
        'eu_member' => TRUE,
        'oss_applicable' => TRUE,
      ],
      'GB' => [
        'label' => new TranslatableMarkup('Reino Unido'),
        'country_code' => 'GB',
        'tax_authority' => new TranslatableMarkup('HMRC'),
        'vat_prefix' => 'GB',
        'eu_member' => FALSE,
        'oss_applicable' => FALSE,
      ],
      'MX' => [
        'label' => new TranslatableMarkup('Mexico'),
        'country_code' => 'MX',
        'tax_authority' => new TranslatableMarkup('SAT'),
        'vat_prefix' => '',
        'eu_member' => FALSE,
        'oss_applicable' => FALSE,
      ],
      'CO' => [
        'label' => new TranslatableMarkup('Colombia'),
        'country_code' => 'CO',
        'tax_authority' => new TranslatableMarkup('DIAN'),
        'vat_prefix' => '',
        'eu_member' => FALSE,
        'oss_applicable' => FALSE,
      ],
      'BR' => [
        'label' => new TranslatableMarkup('Brasil'),
        'country_code' => 'BR',
        'tax_authority' => new TranslatableMarkup('Receita Federal'),
        'vat_prefix' => '',
        'eu_member' => FALSE,
        'oss_applicable' => FALSE,
      ],
      'AR' => [
        'label' => new TranslatableMarkup('Argentina'),
        'country_code' => 'AR',
        'tax_authority' => new TranslatableMarkup('AFIP'),
        'vat_prefix' => '',
        'eu_member' => FALSE,
        'oss_applicable' => FALSE,
      ],
      'CL' => [
        'label' => new TranslatableMarkup('Chile'),
        'country_code' => 'CL',
        'tax_authority' => new TranslatableMarkup('SII'),
        'vat_prefix' => '',
        'eu_member' => FALSE,
        'oss_applicable' => FALSE,
      ],
      'US' => [
        'label' => new TranslatableMarkup('Estados Unidos'),
        'country_code' => 'US',
        'tax_authority' => new TranslatableMarkup('IRS'),
        'vat_prefix' => '',
        'eu_member' => FALSE,
        'oss_applicable' => FALSE,
      ],
    ];
  }

}
