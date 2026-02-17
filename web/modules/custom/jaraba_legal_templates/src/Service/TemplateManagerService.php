<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_templates\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestion de plantillas juridicas.
 *
 * Estructura: Listado, filtrado y renderizado de merge-fields.
 * Logica: renderTemplate() resuelve {{ field.subfield }} en el body.
 */
class TemplateManagerService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Lista plantillas por tipo.
   */
  public function listByType(string $type, int $limit = 25, int $offset = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage('legal_template');
      $query = $storage->getQuery()
        ->condition('template_type', $type)
        ->condition('is_active', TRUE)
        ->accessCheck(TRUE)
        ->sort('name', 'ASC')
        ->range($offset, $limit);
      $ids = $query->execute();

      return array_map(fn($t) => $this->serializeTemplate($t), $storage->loadMultiple($ids));
    }
    catch (\Exception $e) {
      $this->logger->error('Error listing templates: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Obtiene plantillas del sistema.
   */
  public function getSystemTemplates(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('legal_template');
      $ids = $storage->getQuery()
        ->condition('is_system', TRUE)
        ->accessCheck(TRUE)
        ->sort('template_type', 'ASC')
        ->execute();

      return array_map(fn($t) => $this->serializeTemplate($t), $storage->loadMultiple($ids));
    }
    catch (\Exception $e) {
      $this->logger->error('Error getting system templates: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Renderiza una plantilla resolviendo merge-fields.
   *
   * @param string $body
   *   El cuerpo de la plantilla con {{ campo.subcampo }}.
   * @param array $data
   *   Datos para resolver los merge-fields.
   *
   * @return string
   *   Cuerpo con merge-fields resueltos.
   */
  public function renderTemplate(string $body, array $data): string {
    return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/', function ($matches) use ($data) {
      $path = explode('.', $matches[1]);
      $value = $data;
      foreach ($path as $key) {
        if (is_array($value) && array_key_exists($key, $value)) {
          $value = $value[$key];
        }
        else {
          return $matches[0];
        }
      }
      return is_scalar($value) ? (string) $value : $matches[0];
    }, $body) ?? $body;
  }

  /**
   * Serializa una plantilla.
   */
  public function serializeTemplate($template): array {
    return [
      'id' => (int) $template->id(),
      'uuid' => $template->uuid(),
      'name' => $template->get('name')->value ?? '',
      'template_type' => $template->get('template_type')->value ?? '',
      'is_system' => (bool) $template->get('is_system')->value,
      'is_active' => (bool) $template->get('is_active')->value,
      'usage_count' => (int) ($template->get('usage_count')->value ?? 0),
      'merge_fields' => $template->get('merge_fields')->getValue()[0] ?? [],
      'created' => $template->get('created')->value ?? '',
    ];
  }

}
