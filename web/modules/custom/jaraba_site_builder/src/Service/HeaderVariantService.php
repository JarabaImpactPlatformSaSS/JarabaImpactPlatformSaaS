<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_site_builder\Entity\SiteConfig;

/**
 * Servicio para renderizar variantes de header según configuración del tenant.
 *
 * Canvas v2 Full Page Editor:
 * Proporciona pre-renderizado de variantes de header para su uso en el
 * editor visual GrapesJS. Permite cambio de variante sin llamadas API.
 */
class HeaderVariantService
{

    /**
     * Variantes de header disponibles.
     * Alineadas con theme settings: /admin/appearance/settings.
     */
    protected const HEADER_VARIANTS = [
        'classic',
        'centered',
        'hero',
        'split',
        'minimal',
    ];

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected TenantContextService $tenantContext,
        protected RendererInterface $renderer,
        protected SiteStructureService $siteStructure,
    ) {
    }

    /**
     * Obtiene la configuración del site para el tenant actual.
     *
     * @return \Drupal\jaraba_site_builder\Entity\SiteConfig|null
     *   La configuración o NULL si no existe.
     */
    public function getSiteConfig(): ?SiteConfig
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        if (!$tenant) {
            return NULL;
        }
        $tenantId = (int) $tenant->id();

        $configs = $this->entityTypeManager
            ->getStorage('site_config')
            ->loadByProperties(['tenant_id' => $tenantId]);

        return $configs ? reset($configs) : NULL;
    }

    /**
     * Obtiene el tipo de header configurado para el tenant actual.
     *
     * @return string
     *   Tipo de header: standard, compact, mega, minimal.
     */
    public function getConfiguredHeaderType(): string
    {
        $config = $this->getSiteConfig();
        return $config ? $config->getHeaderType() : 'classic';
    }

    /**
     * Renderiza el header según la configuración del tenant.
     *
     * @param string|null $variant
     *   Variante específica a renderizar, o NULL para usar la configurada.
     *
     * @return string
     *   HTML renderizado del header.
     */
    public function renderHeader(?string $variant = NULL): string
    {
        $config = $this->getSiteConfig();
        $type = $variant ?? ($config ? $config->getHeaderType() : 'classic');

        $tenant = $this->tenantContext->getCurrentTenant();
        $navigation = $this->siteStructure->getNavigation(
            $tenant ? (int) $tenant->id() : NULL,
            'header'
        );

        // Extraer valores escalares para evitar errores de FieldItemList en Twig.
        $configData = [
            'tagline' => $config?->get('site_tagline')->value ?? '',
        ];

        $build = [
            '#theme' => 'jaraba_header_' . $type,
            '#config' => $configData,
            '#navigation' => $navigation,
            '#sticky' => $config?->isHeaderSticky() ?? TRUE,
            '#transparent' => $config?->isHeaderTransparent() ?? FALSE,
            '#cta_text' => $config?->getHeaderCtaText() ?? '',
            '#cta_url' => $config?->getHeaderCtaUrl() ?? '',
            '#site_name' => $config?->getSiteName() ?? 'Jaraba',
            '#site_slogan' => $config?->get('site_tagline')->value ?? '',
            '#logo' => $this->getLogo($config),
        ];

        return (string) $this->renderer->renderRoot($build);
    }

    /**
     * Pre-renderiza todas las variantes de header.
     *
     * Útil para el Canvas Editor: permite cambio de variante sin API call.
     *
     * @return array
     *   Array [variant => html].
     */
    public function preRenderAllVariants(): array
    {
        $variants = [];
        foreach (self::HEADER_VARIANTS as $variant) {
            $variants[$variant] = $this->renderHeader($variant);
        }
        return $variants;
    }

    /**
     * Obtiene los datos del header para el Canvas Editor.
     *
     * @return array
     *   Datos estructurados para inicializar el Canvas Editor.
     */
    public function getHeaderDataForCanvas(): array
    {
        $siteConfig = $this->getSiteConfig();
        $currentType = $this->getConfiguredHeaderType();

        return [
            // Estructura que espera el template Twig.
            'config' => [
                'current' => $currentType,
                'sticky' => $siteConfig?->isHeaderSticky() ?? TRUE,
                'transparent' => $siteConfig?->isHeaderTransparent() ?? FALSE,
                'ctaText' => $siteConfig?->getHeaderCtaText() ?? '',
                'ctaUrl' => $siteConfig?->getHeaderCtaUrl() ?? '',
            ],
            'availableTypes' => self::HEADER_VARIANTS,
            'html' => $this->renderHeader(),
            'variants' => $this->preRenderAllVariants(),
        ];
    }

    /**
     * Obtiene la URL del logo del tenant.
     *
     * @param \Drupal\jaraba_site_builder\Entity\SiteConfig|null $config
     *   Configuración del sitio.
     *
     * @return string|null
     *   URL del logo o NULL.
     */
    protected function getLogo(?SiteConfig $config): ?string
    {
        if (!$config || $config->get('site_logo')->isEmpty()) {
            return NULL;
        }

        /** @var \Drupal\file\FileInterface $file */
        $file = $config->get('site_logo')->entity;
        return $file ? $file->createFileUrl() : NULL;
    }

}
