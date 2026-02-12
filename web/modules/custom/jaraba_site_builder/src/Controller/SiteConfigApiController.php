<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Controller para configuración del sitio.
 */
class SiteConfigApiController extends ControllerBase
{

    /**
     * Constructor.
     */
    public function __construct(
        protected TenantContextService $tenantContext,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('ecosistema_jaraba_core.tenant_context'),
        );
    }

    /**
     * GET /api/v1/site/config - Obtiene la configuración del sitio.
     */
    public function getConfig(): JsonResponse
    {
        try {
            $tenant = $this->tenantContext->getCurrentTenant();
            $tenantId = $tenant ? (int) $tenant->id() : null;

            $storage = $this->entityTypeManager()->getStorage('site_config');
            $entities = $storage->loadByProperties(['tenant_id' => $tenantId]);
            $config = reset($entities);

            if (!$config) {
                return new JsonResponse([
                    'success' => TRUE,
                    'data' => NULL,
                    'message' => $this->t('No hay configuración para este tenant'),
                ]);
            }

            $data = [
                'id' => (int) $config->id(),
                'site_name' => $config->getSiteName(),
                'site_tagline' => $config->getTagline(),
                'contact_email' => $config->get('contact_email')->value,
                'contact_phone' => $config->get('contact_phone')->value,
                'contact_address' => $config->get('contact_address')->value,
                'social_links' => $config->getSocialLinks(),
                'google_analytics_id' => $config->get('google_analytics_id')->value,
                'google_tag_manager_id' => $config->get('google_tag_manager_id')->value,
                'meta_title_suffix' => $config->get('meta_title_suffix')->value,
            ];

            return new JsonResponse([
                'success' => TRUE,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/v1/site/config - Actualiza la configuración del sitio.
     */
    public function updateConfig(Request $request): JsonResponse
    {
        try {
            $tenant = $this->tenantContext->getCurrentTenant();
            $tenantId = $tenant ? (int) $tenant->id() : null;
            $data = json_decode($request->getContent(), TRUE);

            $storage = $this->entityTypeManager()->getStorage('site_config');
            $entities = $storage->loadByProperties(['tenant_id' => $tenantId]);
            $config = reset($entities);

            if (!$config) {
                // Crear nueva configuración.
                $config = $storage->create([
                    'tenant_id' => $tenantId,
                ]);
            }

            // Actualizar campos permitidos.
            $allowedFields = [
                'site_name',
                'site_tagline',
                'contact_email',
                'contact_phone',
                'contact_address',
                'contact_coordinates',
                'google_analytics_id',
                'google_tag_manager_id',
                'meta_title_suffix',
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $config->set($field, $data[$field]);
                }
            }

            // Social links como JSON.
            if (isset($data['social_links']) && is_array($data['social_links'])) {
                $config->setSocialLinks($data['social_links']);
            }

            $config->save();

            return new JsonResponse([
                'success' => TRUE,
                'data' => ['id' => (int) $config->id()],
                'message' => $this->t('Configuración guardada'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/site/config/logo - Sube un nuevo logo.
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        try {
            $tenant = $this->tenantContext->getCurrentTenant();
            $tenantId = $tenant ? (int) $tenant->id() : null;

            // Obtener archivo subido.
            $file = $request->files->get('logo');
            if (!$file) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => $this->t('No se recibió ningún archivo'),
                ], 400);
            }

            // Validar tipo de archivo.
            $allowedMimes = ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'];
            if (!in_array($file->getMimeType(), $allowedMimes)) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => $this->t('Tipo de archivo no permitido'),
                ], 400);
            }

            // Guardar archivo.
            $destination = 'public://site-logos/';
            $fileName = 'logo-' . $tenantId . '-' . time() . '.' . $file->getClientOriginalExtension();

            /** @var \Drupal\file\FileRepositoryInterface $fileRepository */
            $fileRepository = \Drupal::service('file.repository');
            $savedFile = $fileRepository->writeData(
                file_get_contents($file->getPathname()),
                $destination . $fileName
            );

            // Actualizar configuración.
            $storage = $this->entityTypeManager()->getStorage('site_config');
            $entities = $storage->loadByProperties(['tenant_id' => $tenantId]);
            $config = reset($entities);

            if ($config) {
                $config->set('site_logo', $savedFile->id());
                $config->save();
            }

            return new JsonResponse([
                'success' => TRUE,
                'data' => [
                    'file_id' => (int) $savedFile->id(),
                    'url' => \Drupal::service('file_url_generator')->generateAbsoluteString($savedFile->getFileUri()),
                ],
                'message' => $this->t('Logo actualizado'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // Canvas v2: Variantes Header/Footer
    // =========================================================================

    /**
     * POST /api/v1/site-config/header-variant - Cambia la variante de header.
     */
    public function updateHeaderVariant(Request $request): JsonResponse
    {
        try {
            $tenant = $this->tenantContext->getCurrentTenant();
            $tenantId = $tenant ? (int) $tenant->id() : null;
            $data = json_decode($request->getContent(), TRUE);

            $variant = $data['variant'] ?? null;
            $allowedVariants = ['classic', 'centered', 'hero', 'split', 'minimal'];

            if (!$variant || !in_array($variant, $allowedVariants)) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => $this->t('Variante de header no válida'),
                ], 400);
            }

            $storage = $this->entityTypeManager()->getStorage('site_config');
            $entities = $storage->loadByProperties(['tenant_id' => $tenantId]);
            $config = reset($entities);

            if (!$config) {
                $config = $storage->create(['tenant_id' => $tenantId]);
            }

            $config->set('header_type', $variant);
            $config->save();

            return new JsonResponse([
                'success' => TRUE,
                'data' => ['header_type' => $variant],
                'message' => $this->t('Header actualizado a @variant', ['@variant' => $variant]),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/site-config/footer-variant - Cambia la variante de footer.
     */
    public function updateFooterVariant(Request $request): JsonResponse
    {
        try {
            $tenant = $this->tenantContext->getCurrentTenant();
            $tenantId = $tenant ? (int) $tenant->id() : null;
            $data = json_decode($request->getContent(), TRUE);

            $variant = $data['variant'] ?? null;
            $allowedVariants = ['minimal', 'standard', 'mega', 'split'];

            if (!$variant || !in_array($variant, $allowedVariants)) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => $this->t('Variante de footer no válida'),
                ], 400);
            }

            $storage = $this->entityTypeManager()->getStorage('site_config');
            $entities = $storage->loadByProperties(['tenant_id' => $tenantId]);
            $config = reset($entities);

            if (!$config) {
                $config = $storage->create(['tenant_id' => $tenantId]);
            }

            $config->set('footer_type', $variant);
            $config->save();

            return new JsonResponse([
                'success' => TRUE,
                'data' => ['footer_type' => $variant],
                'message' => $this->t('Footer actualizado a @variant', ['@variant' => $variant]),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
