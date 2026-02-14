<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador API para publicar páginas desde el Canvas Editor.
 *
 * PROPÓSITO:
 * Resuelve el Publish 404: el Canvas Editor (canvas-editor.js L216) envía
 * POST /api/v1/pages/{id}/publish pero la ruta no existía. Este controller
 * implementa la transición de estado Draft → Published con:
 * - Auto-generación de path_alias SEO-friendly si está vacío.
 * - Verificación de aislamiento multi-tenant (TENANT-001).
 * - Creación de revisión con log message (AUDIT-CONS-001).
 * - Devolución de URL pública (contrato JS: data.url).
 *
 * SEGURIDAD:
 * - AUDIT-SEC-002: Requiere permiso publish own/any page content.
 * - AUDIT-SEC-004: CSRF token validado por Drupal (cookie auth).
 * - TENANT-001: Verificación de tenant_id del usuario vs entidad.
 *
 * @see web/modules/custom/jaraba_page_builder/js/canvas-editor.js L204-245
 * @see \Drupal\jaraba_page_builder\Entity\PageContent
 */
class PageContentPublishController extends ControllerBase {

  /**
   * Servicio de transliteración para generación de slugs.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected TransliterationInterface $transliteration;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->transliteration = $container->get('transliteration');
    return $instance;
  }

  /**
   * POST /api/v1/pages/{page_content}/publish
   *
   * Publica una página del Page Builder. Acciones:
   * 1. Verifica acceso granular (own/any + tenant isolation).
   * 2. Auto-genera path_alias SEO-friendly si está vacío.
   * 3. Transiciona status a TRUE (publicado).
   * 4. Crea nueva revisión con log descriptivo.
   * 5. Devuelve JSON con URL pública para el canvas-editor.js.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $page_content
   *   La entidad PageContent (auto-cargada por param converter).
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON: { success, url, message, revision_id } o { error }.
   */
  public function publish(ContentEntityInterface $page_content, Request $request): JsonResponse {
    try {
      $account = $this->currentUser();

      // ---------------------------------------------------------------
      // AUDIT-SEC-002: Verificación de permisos granular (own/any).
      // ---------------------------------------------------------------
      if (!$this->hasPublishAccess($page_content, $account)) {
        return new JsonResponse(
          ['error' => $this->t('No tienes permiso para publicar esta página.')],
          Response::HTTP_FORBIDDEN
        );
      }

      // ---------------------------------------------------------------
      // TENANT-001: Aislamiento multi-tenant.
      // Verifica que el usuario pertenece al mismo tenant que la página.
      // ---------------------------------------------------------------
      if (!$this->checkTenantAccess($page_content, $account)) {
        $this->getLogger('jaraba_page_builder')->warning(
          'TENANT-001: Usuario @uid intentó publicar página @id del tenant @tid.',
          [
            '@uid' => $account->id(),
            '@id' => $page_content->id(),
            '@tid' => $page_content->get('tenant_id')->target_id ?? 'NULL',
          ]
        );

        return new JsonResponse(
          ['error' => $this->t('Acceso denegado.')],
          Response::HTTP_FORBIDDEN
        );
      }

      // ---------------------------------------------------------------
      // Auto-generar path_alias SEO-friendly si está vacío.
      // Usa transliteración de Drupal core para soporte multi-idioma.
      // ---------------------------------------------------------------
      $pathAlias = $page_content->get('path_alias')->value ?? '';
      if (empty(trim($pathAlias))) {
        $title = $page_content->get('title')->value ?? '';
        if (!empty($title)) {
          $slug = $this->generateSeoSlug($title, $page_content->get('langcode')->value ?? 'es');
          $slug = $this->ensureUniqueSlug($slug, (int) $page_content->id());
          $page_content->set('path_alias', $slug);
        }
      }

      // ---------------------------------------------------------------
      // Transición de estado: publicar la página.
      // ---------------------------------------------------------------
      $page_content->set('status', TRUE);

      // ---------------------------------------------------------------
      // Crear nueva revisión con log descriptivo.
      // Patrón copiado de CanvasApiController::saveCanvas() L132-140.
      // ---------------------------------------------------------------
      if (method_exists($page_content, 'setNewRevision')) {
        $page_content->setNewRevision(TRUE);
      }
      if ($page_content instanceof \Drupal\Core\Entity\RevisionLogInterface) {
        $page_content->setRevisionLogMessage(
          $this->t('Página publicada desde el Canvas Editor por @user.', [
            '@user' => $account->getDisplayName(),
          ])
        );
        $page_content->setRevisionCreationTime(\Drupal::time()->getRequestTime());
      }

      $page_content->save();

      // ---------------------------------------------------------------
      // Construir URL pública para devolver al canvas-editor.js.
      // Sigue el patrón de HreflangService y SitemapController:
      // usa path_alias si existe, fallback a /page/{id}.
      // ---------------------------------------------------------------
      $publicUrl = $this->buildPublicUrl($page_content, $request);

      $this->getLogger('jaraba_page_builder')->info(
        'Página @id publicada por usuario @uid. URL: @url',
        [
          '@id' => $page_content->id(),
          '@uid' => $account->id(),
          '@url' => $publicUrl,
        ]
      );

      return new JsonResponse([
        'success' => TRUE,
        'url' => $publicUrl,
        'message' => $this->t('Página publicada correctamente.'),
        'revision_id' => $page_content->getRevisionId(),
      ]);

    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_page_builder')->error(
        'Error publicando página @id: @error',
        [
          '@id' => $page_content->id(),
          '@error' => $e->getMessage(),
        ]
      );

      return new JsonResponse(
        ['error' => $this->t('Error al publicar la página: @msg', ['@msg' => $e->getMessage()])],
        Response::HTTP_INTERNAL_SERVER_ERROR
      );
    }
  }

  /**
   * Verifica permisos granulares de publicación (own/any).
   *
   * AUDIT-SEC-002: Sigue el patrón del PageContentAccessControlHandler
   * con admin bypass + verificación own/any.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $page_content
   *   La entidad PageContent.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Cuenta del usuario actual.
   *
   * @return bool
   *   TRUE si el usuario tiene permiso para publicar.
   */
  protected function hasPublishAccess(ContentEntityInterface $page_content, AccountInterface $account): bool {
    // Admin bypass.
    if ($account->hasPermission('administer page builder')) {
      return TRUE;
    }

    $isOwner = (int) $page_content->getOwnerId() === (int) $account->id();

    if ($isOwner && $account->hasPermission('publish own page content')) {
      return TRUE;
    }

    if ($account->hasPermission('publish any page content')) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Verifica aislamiento multi-tenant.
   *
   * TENANT-001: Comprueba que el usuario pertenece al mismo tenant
   * (Group) que la página. Usa el módulo Group para verificar membresía.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $page_content
   *   La entidad PageContent.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Cuenta del usuario actual.
   *
   * @return bool
   *   TRUE si el acceso por tenant es válido.
   */
  protected function checkTenantAccess(ContentEntityInterface $page_content, AccountInterface $account): bool {
    // Admin bypass: administradores pueden operar cross-tenant.
    if ($account->hasPermission('administer page builder')) {
      return TRUE;
    }

    $pageTenantId = $page_content->get('tenant_id')->target_id ?? NULL;

    // Si la página no tiene tenant asignado, permitir (páginas globales).
    if (empty($pageTenantId)) {
      return TRUE;
    }

    // Verificar membresía del usuario en el grupo (tenant).
    try {
      /** @var \Drupal\group\Entity\GroupInterface|null $group */
      $group = $this->entityTypeManager()
        ->getStorage('group')
        ->load($pageTenantId);

      if (!$group) {
        // Tenant no existe, denegar por seguridad.
        return FALSE;
      }

      // Verificar si el usuario es miembro del grupo.
      $membership = $group->getMember($account);
      return $membership !== FALSE;

    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_page_builder')->warning(
        'Error verificando tenant para usuario @uid en grupo @gid: @error',
        [
          '@uid' => $account->id(),
          '@gid' => $pageTenantId,
          '@error' => $e->getMessage(),
        ]
      );
      // En caso de error, denegar por seguridad.
      return FALSE;
    }
  }

  /**
   * Genera un slug SEO-friendly desde un título.
   *
   * Usa PhpTransliteration de Drupal core para soporte multi-idioma
   * completo (ñ→n, á→a, ü→u, etc.). Produce slugs tipo:
   * - "Nuestros Servicios de Consultoría" → "/nuestros-servicios-de-consultoria"
   * - "Productos Agrícolas 2026" → "/productos-agricolas-2026"
   *
   * @param string $title
   *   Título de la página.
   * @param string $langcode
   *   Código de idioma para transliteración contextual.
   *
   * @return string
   *   Slug con prefijo / (ej: "/nuestros-servicios").
   */
  protected function generateSeoSlug(string $title, string $langcode = 'es'): string {
    // Paso 1: Transliterar caracteres Unicode a ASCII.
    $slug = $this->transliteration->transliterate($title, $langcode, '-');

    // Paso 2: Convertir a minúsculas.
    $slug = mb_strtolower($slug);

    // Paso 3: Reemplazar cualquier carácter no alfanumérico por guión.
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

    // Paso 4: Eliminar guiones al inicio y final.
    $slug = trim($slug, '-');

    // Paso 5: Colapsar guiones múltiples.
    $slug = preg_replace('/-{2,}/', '-', $slug);

    // Paso 6: Limitar longitud (SEO recomienda < 75 chars en path).
    if (mb_strlen($slug) > 128) {
      $slug = mb_substr($slug, 0, 128);
      // No cortar a mitad de palabra.
      $lastDash = strrpos($slug, '-');
      if ($lastDash !== FALSE && $lastDash > 80) {
        $slug = substr($slug, 0, $lastDash);
      }
    }

    // Paso 7: Prefijo / obligatorio (consistente con PageConfigApiController).
    return '/' . $slug;
  }

  /**
   * Asegura unicidad del slug contra la base de datos.
   *
   * Si "/nuestros-servicios" ya existe para otra entidad, genera
   * "/nuestros-servicios-2", "/nuestros-servicios-3", etc.
   *
   * @param string $slug
   *   Slug candidato (con prefijo /).
   * @param int $currentEntityId
   *   ID de la entidad actual (para excluirla de la verificación).
   *
   * @return string
   *   Slug único garantizado.
   */
  protected function ensureUniqueSlug(string $slug, int $currentEntityId): string {
    $storage = $this->entityTypeManager()->getStorage('page_content');
    $baseSlug = $slug;
    $counter = 1;

    while (TRUE) {
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('path_alias', $slug)
        ->condition('id', $currentEntityId, '<>');

      $existing = $query->execute();

      if (empty($existing)) {
        return $slug;
      }

      $counter++;
      $slug = $baseSlug . '-' . $counter;

      // Safety valve: prevenir bucle infinito.
      if ($counter > 100) {
        return $baseSlug . '-' . $currentEntityId;
      }
    }
  }

  /**
   * Construye la URL pública de la página publicada.
   *
   * Sigue el patrón de HreflangService::getTranslatedUrl() y
   * SitemapController::generateUrlEntry(): usa path_alias si existe,
   * fallback a la ruta canónica /page/{id}.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $page_content
   *   La entidad PageContent.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request HTTP (para obtener scheme + host).
   *
   * @return string
   *   URL absoluta de la página publicada.
   */
  protected function buildPublicUrl(ContentEntityInterface $page_content, Request $request): string {
    $pathAlias = $page_content->get('path_alias')->value ?? '';

    if (!empty($pathAlias)) {
      return $request->getSchemeAndHttpHost() . $pathAlias;
    }

    // Fallback a URL canónica de la entidad.
    try {
      return \Drupal\Core\Url::fromRoute('entity.page_content.canonical', [
        'page_content' => $page_content->id(),
      ])->setAbsolute()->toString();
    }
    catch (\Exception $e) {
      return $request->getSchemeAndHttpHost() . '/page/' . $page_content->id();
    }
  }

}
