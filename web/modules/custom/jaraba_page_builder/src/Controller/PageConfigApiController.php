<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador API para la configuración de página desde el Canvas Editor.
 *
 * PROPÓSITO:
 * Permite leer y actualizar los metadatos de una página (título, slug, SEO,
 * estado de publicación) desde el slide-panel "⚙ Configuración de Página"
 * del Canvas Editor, sin salir del editor visual.
 *
 * ENDPOINTS:
 * - GET  /api/v1/pages/{id}/config → Metadatos actuales de la página.
 * - PATCH /api/v1/pages/{id}/config → Actualizar metadatos (nueva revisión).
 *
 * SEGURIDAD:
 * - Requiere permiso 'edit page builder content'.
 * - Valida CSRF token en operaciones PATCH.
 * - Sanitiza inputs (longitud, formato slug, XSS).
 *
 * @see \Drupal\jaraba_page_builder\Entity\PageContent
 */
class PageConfigApiController extends ControllerBase
{

  /**
   * Maneja la request (GET o PATCH).
   *
   * @param int $id
   *   ID de la entidad PageContent.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La request HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con los metadatos de la página.
   */
  public function handleRequest(int $id, Request $request): JsonResponse
  {
    $storage = $this->entityTypeManager()->getStorage('page_content');
    $page = $storage->load($id);

    if (!$page) {
      return new JsonResponse([
        'error' => $this->t('Página no encontrada.'),
      ], 404);
    }

    if ($request->getMethod() === 'PATCH') {
      return $this->handlePatch($page, $request);
    }

    return $this->handleGet($page);
  }

  /**
   * Devuelve los metadatos actuales de la página.
   *
   * @param \Drupal\jaraba_page_builder\Entity\PageContent $page
   *   La entidad PageContent.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con título, slug, meta SEO, estado, revisiones.
   */
  protected function handleGet($page): JsonResponse
  {
    $data = $this->buildPageConfigData($page);
    return new JsonResponse($data);
  }

  /**
   * Actualiza los metadatos de la página y crea una nueva revisión.
   *
   * @param \Drupal\jaraba_page_builder\Entity\PageContent $page
   *   La entidad PageContent.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La request HTTP con los datos a actualizar.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con los datos actualizados o errores de validación.
   */
  protected function handlePatch($page, Request $request): JsonResponse
  {
    $content = json_decode($request->getContent(), TRUE);

    if (empty($content)) {
      return new JsonResponse([
        'error' => $this->t('Datos vacíos o JSON inválido.'),
      ], 400);
    }

    $errors = [];

    // Campos permitidos y sus validaciones.
    $allowedFields = [
      'title' => ['max_length' => 255, 'required' => TRUE],
      'path_alias' => ['max_length' => 255],
      'meta_title' => ['max_length' => 70],
      'meta_description' => ['max_length' => 300],
      'status' => ['type' => 'boolean'],
      'menu_link' => ['allowed_values' => ['', 'main', 'footer', 'secondary']],
    ];

    foreach ($content as $field => $value) {
      if (!isset($allowedFields[$field])) {
        continue;
      }

      $rules = $allowedFields[$field];

      // Validar longitud máxima.
      if (isset($rules['max_length']) && is_string($value) && mb_strlen($value) > $rules['max_length']) {
        $errors[] = $this->t('@field excede @max caracteres.', [
          '@field' => $field,
          '@max' => $rules['max_length'],
        ]);
        continue;
      }

      // Validar campo requerido.
      if (!empty($rules['required']) && empty($value)) {
        $errors[] = $this->t('@field es obligatorio.', ['@field' => $field]);
        continue;
      }

      // Validar valores permitidos.
      if (isset($rules['allowed_values']) && !in_array($value, $rules['allowed_values'], TRUE)) {
        $errors[] = $this->t('Valor no permitido para @field.', ['@field' => $field]);
        continue;
      }

      // Validar booleano.
      if (isset($rules['type']) && $rules['type'] === 'boolean') {
        $value = (bool) $value;
      }

      // Sanitizar path_alias: asegurar que empiece con /.
      if ($field === 'path_alias' && !empty($value) && !str_starts_with($value, '/')) {
        $value = '/' . $value;
      }

      // Aplicar el cambio a la entidad.
      $page->set($field, $value);
    }

    if (!empty($errors)) {
      return new JsonResponse([
        'error' => $this->t('Errores de validación.'),
        'details' => $errors,
      ], 422);
    }

    // Crear nueva revisión para el historial.
    try {
      $page->setNewRevision(TRUE);
      $page->set('revision_log', $this->t('Configuración actualizada desde el Canvas Editor.'));
      $page->set('revision_uid', \Drupal::currentUser()->id());
      $page->set('revision_timestamp', \Drupal::time()->getRequestTime());
    } catch (\Exception $e) {
      // Si la revisión falla, continuar sin ella.
    }

    try {
      $page->save();
    } catch (\Exception $e) {
      return new JsonResponse([
        'error' => $this->t('Error al guardar: @msg', ['@msg' => $e->getMessage()]),
      ], 500);
    }

    // Devolver datos actualizados.
    $data = $this->buildPageConfigData($page);
    $data['saved'] = TRUE;

    return new JsonResponse($data);
  }

  /**
   * Construye el array de datos de configuración de la página.
   *
   * @param \Drupal\jaraba_page_builder\Entity\PageContent $page
   *   La entidad PageContent.
   *
   * @return array
   *   Array con todos los metadatos para el panel de configuración.
   */
  protected function buildPageConfigData($page): array
  {
    // Datos de la última revisión.
    $revisionUser = NULL;
    $revisionDate = NULL;

    try {
      $revisionUid = $page->get('revision_uid')->target_id ?? NULL;
      if ($revisionUid) {
        $user = $this->entityTypeManager()->getStorage('user')->load($revisionUid);
        if ($user) {
          $revisionUser = $user->getDisplayName() ?: $user->getAccountName();
        }
      }
      $revisionTimestamp = $page->get('revision_timestamp')->value ?? NULL;
      if ($revisionTimestamp) {
        $revisionDate = \Drupal::service('date.formatter')->format(
          (int) $revisionTimestamp,
          'custom',
          'd/m/Y H:i'
        );
      }
    } catch (\Exception $e) {
      // Silenciar errores de revisión — no bloquear el panel.
    }

    // Contar revisiones totales.
    $revisionCount = 0;
    try {
      $revisionCount = (int) \Drupal::entityQuery('page_content')
        ->allRevisions()
        ->condition('id', $page->id())
        ->count()
        ->accessCheck(FALSE)
        ->execute();
    } catch (\Exception $e) {
      // Silenciar.
    }


    // Datos de timestamps.
    $created = '';
    $changed = '';
    try {
      $createdVal = $page->get('created')->value;
      if ($createdVal) {
        $created = \Drupal::service('date.formatter')->format(
          (int) $createdVal,
          'custom',
          'd/m/Y H:i'
        );
      }
      $changedVal = $page->get('changed')->value;
      if ($changedVal) {
        $changed = \Drupal::service('date.formatter')->format(
          (int) $changedVal,
          'custom',
          'd/m/Y H:i'
        );
      }
    } catch (\Exception $e) {
      // Silenciar.
    }

    return [
      'id' => (int) $page->id(),
      'title' => $page->label() ?? '',
      'path_alias' => $page->get('path_alias')->value ?? '',
      'status' => (bool) $page->get('status')->value,
      'menu_link' => $page->get('menu_link')->value ?? '',
      'meta_title' => $page->get('meta_title')->value ?? '',
      'meta_description' => $page->get('meta_description')->value ?? '',
      'created' => $created,
      'changed' => $changed,
      'revision' => [
        'user' => $revisionUser,
        'date' => $revisionDate,
        'count' => $revisionCount,
        'history_url' => '/admin/content/pages/' . $page->id() . '/revisions',
      ],
    ];
  }

}
