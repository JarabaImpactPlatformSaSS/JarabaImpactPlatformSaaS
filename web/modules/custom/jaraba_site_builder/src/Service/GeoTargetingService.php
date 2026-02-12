<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;

/**
 * Servicio de geo-targeting para SEO local.
 *
 * Gestiona la configuración geográfica por página: meta tags de región,
 * coordenadas GPS, y generación de Schema.org LocalBusiness.
 *
 * Fase 4 Doc 179: Geo Targeting.
 */
class GeoTargetingService
{

    use StringTranslationTrait;

    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected TenantContextService $tenantContext,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Obtiene la configuración geo de una página.
     *
     * @param int $pageId
     *   ID de la página.
     *
     * @return array
     *   Datos de geo-targeting.
     */
    public function getGeoConfig(int $pageId): array
    {
        $seoConfig = $this->loadSeoConfig($pageId);
        if (!$seoConfig) {
            return $this->getEmptyGeoConfig();
        }

        return [
            'geo_region' => $seoConfig->getGeoRegion(),
            'geo_position' => $seoConfig->getGeoPosition(),
            'coordinates' => $this->parseCoordinates($seoConfig->getGeoPosition()),
        ];
    }

    /**
     * Actualiza la configuración geo de una página.
     *
     * @param int $pageId
     *   ID de la página.
     * @param array $data
     *   Datos: {geo_region: 'ES-AN', geo_position: '37.39;-5.98'}.
     *
     * @return bool
     *   TRUE si se actualizó correctamente.
     */
    public function updateGeoConfig(int $pageId, array $data): bool
    {
        $seoConfig = $this->loadSeoConfig($pageId);
        if (!$seoConfig) {
            return FALSE;
        }

        if (isset($data['geo_region'])) {
            $region = $this->sanitizeRegionCode($data['geo_region']);
            $seoConfig->set('geo_region', $region);
        }

        if (isset($data['geo_position'])) {
            $position = $this->sanitizePosition($data['geo_position']);
            if ($position !== FALSE) {
                $seoConfig->set('geo_position', $position);
            }
        }

        $seoConfig->save();

        $this->logger->info('Geo-targeting actualizado para página @page.', ['@page' => $pageId]);

        return TRUE;
    }

    /**
     * Genera los meta tags geo para inyectar en <head>.
     *
     * @param int $pageId
     *   ID de la página.
     *
     * @return array
     *   Array de tags para #attached html_head.
     */
    public function generateGeoMetaTags(int $pageId): array
    {
        $seoConfig = $this->loadSeoConfig($pageId);
        if (!$seoConfig) {
            return [];
        }

        $tags = [];

        // geo.region - ISO 3166-2.
        $region = $seoConfig->getGeoRegion();
        if (!empty($region)) {
            $tags[] = [
                ['#tag' => 'meta', '#attributes' => ['name' => 'geo.region', 'content' => $region]],
                'geo_region',
            ];

            // Extraer código de país para geo.country.
            $countryCode = substr($region, 0, 2);
            $tags[] = [
                ['#tag' => 'meta', '#attributes' => ['name' => 'geo.country', 'content' => $countryCode]],
                'geo_country',
            ];
        }

        // geo.position - Latitud;Longitud.
        $position = $seoConfig->getGeoPosition();
        if (!empty($position)) {
            $tags[] = [
                ['#tag' => 'meta', '#attributes' => ['name' => 'geo.position', 'content' => $position]],
                'geo_position',
            ];

            // ICBM (legacy pero usada por motores de búsqueda).
            $icbm = str_replace(';', ', ', $position);
            $tags[] = [
                ['#tag' => 'meta', '#attributes' => ['name' => 'ICBM', 'content' => $icbm]],
                'geo_icbm',
            ];
        }

        return $tags;
    }

    /**
     * Genera Schema.org LocalBusiness para una ubicación.
     *
     * @param int $pageId
     *   ID de la página.
     * @param array $businessData
     *   Datos adicionales del negocio: name, address, phone, openingHours.
     *
     * @return array
     *   Schema.org LocalBusiness JSON-LD.
     */
    public function generateLocalBusinessSchema(int $pageId, array $businessData = []): array
    {
        $seoConfig = $this->loadSeoConfig($pageId);
        if (!$seoConfig) {
            return [];
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $businessData['name'] ?? $seoConfig->getMetaTitle(),
            'description' => $seoConfig->getMetaDescription(),
        ];

        // Coordenadas.
        $coords = $this->parseCoordinates($seoConfig->getGeoPosition());
        if (!empty($coords)) {
            $schema['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => $coords['latitude'],
                'longitude' => $coords['longitude'],
            ];
        }

        // Dirección.
        if (!empty($businessData['address'])) {
            $schema['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $businessData['address'],
            ];
            if (!empty($businessData['city'])) {
                $schema['address']['addressLocality'] = $businessData['city'];
            }
            if (!empty($seoConfig->getGeoRegion())) {
                $schema['address']['addressRegion'] = $seoConfig->getGeoRegion();
            }
            if (!empty($businessData['postal_code'])) {
                $schema['address']['postalCode'] = $businessData['postal_code'];
            }
            if (!empty($businessData['country'])) {
                $schema['address']['addressCountry'] = $businessData['country'];
            }
        }

        // Teléfono.
        if (!empty($businessData['phone'])) {
            $schema['telephone'] = $businessData['phone'];
        }

        // Horario de apertura.
        if (!empty($businessData['opening_hours'])) {
            $schema['openingHoursSpecification'] = [];
            foreach ($businessData['opening_hours'] as $hours) {
                $schema['openingHoursSpecification'][] = [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => $hours['day'] ?? '',
                    'opens' => $hours['opens'] ?? '',
                    'closes' => $hours['closes'] ?? '',
                ];
            }
        }

        return $schema;
    }

    /**
     * Lista de regiones ISO 3166-2 principales para España.
     *
     * @return array
     *   Array de regiones.
     */
    public function getSpanishRegions(): array
    {
        return [
            'ES-AN' => 'Andalucía',
            'ES-AR' => 'Aragón',
            'ES-AS' => 'Asturias',
            'ES-IB' => 'Islas Baleares',
            'ES-CN' => 'Canarias',
            'ES-CB' => 'Cantabria',
            'ES-CL' => 'Castilla y León',
            'ES-CM' => 'Castilla-La Mancha',
            'ES-CT' => 'Cataluña',
            'ES-EX' => 'Extremadura',
            'ES-GA' => 'Galicia',
            'ES-MD' => 'Madrid',
            'ES-MC' => 'Murcia',
            'ES-NC' => 'Navarra',
            'ES-PV' => 'País Vasco',
            'ES-RI' => 'La Rioja',
            'ES-VC' => 'Comunidad Valenciana',
            'ES-CE' => 'Ceuta',
            'ES-ML' => 'Melilla',
        ];
    }

    /**
     * Parsea coordenadas del formato "lat;lon".
     */
    protected function parseCoordinates(string $position): array
    {
        if (empty($position)) {
            return [];
        }

        $parts = explode(';', $position);
        if (count($parts) !== 2) {
            return [];
        }

        $lat = trim($parts[0]);
        $lon = trim($parts[1]);

        if (!is_numeric($lat) || !is_numeric($lon)) {
            return [];
        }

        return [
            'latitude' => (float) $lat,
            'longitude' => (float) $lon,
        ];
    }

    /**
     * Sanitiza un código de región ISO 3166-2.
     */
    protected function sanitizeRegionCode(string $code): string
    {
        return preg_replace('/[^A-Z0-9\-]/', '', strtoupper(trim($code)));
    }

    /**
     * Sanitiza y valida una posición geográfica.
     *
     * @return string|false
     *   Posición validada o FALSE si es inválida.
     */
    protected function sanitizePosition(string $position): string|false
    {
        $parts = explode(';', $position);
        if (count($parts) !== 2) {
            return FALSE;
        }

        $lat = trim($parts[0]);
        $lon = trim($parts[1]);

        if (!is_numeric($lat) || !is_numeric($lon)) {
            return FALSE;
        }

        $lat = (float) $lat;
        $lon = (float) $lon;

        // Validar rangos.
        if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
            return FALSE;
        }

        return $lat . ';' . $lon;
    }

    /**
     * Devuelve configuración geo vacía.
     */
    protected function getEmptyGeoConfig(): array
    {
        return [
            'geo_region' => '',
            'geo_position' => '',
            'coordinates' => [],
        ];
    }

    /**
     * Carga la configuración SEO de una página.
     */
    protected function loadSeoConfig(int $pageId): ?\Drupal\jaraba_site_builder\Entity\SeoPageConfig
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        if (!$tenant) {
            return NULL;
        }

        $storage = $this->entityTypeManager->getStorage('seo_page_config');
        $results = $storage->loadByProperties([
            'page_id' => $pageId,
            'tenant_id' => $tenant->id(),
        ]);

        return empty($results) ? NULL : reset($results);
    }

}
