<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Service;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Genera especificacion OpenAPI 3.0 dinamicamente.
 *
 * Introspecciona las ContentEntity del sistema para generar
 * schemas JSON y documentacion de endpoints.
 */
class OpenApiSpecService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
  ) {}

  /**
   * Genera la especificacion OpenAPI 3.0 completa.
   *
   * @return array
   *   Spec OpenAPI como array asociativo.
   */
  public function generateSpec(): array {
    return [
      'openapi' => '3.0.3',
      'info' => [
        'title' => 'Jaraba Impact Platform API',
        'description' => 'API publica de la plataforma Jaraba Impact Platform SaaS.',
        'version' => '1.0.0',
        'contact' => [
          'name' => 'Jaraba Impact Platform',
          'url' => 'https://jarabaimpact.com',
        ],
        'license' => [
          'name' => 'Proprietary',
        ],
      ],
      'servers' => [
        [
          'url' => '/api/v1',
          'description' => 'API v1',
        ],
      ],
      'paths' => $this->getApiPaths(),
      'components' => [
        'schemas' => $this->getEntitySchemas(),
        'securitySchemes' => $this->getAuthSchemas(),
      ],
      'security' => [
        ['oauth2' => ['read', 'write']],
      ],
    ];
  }

  /**
   * Genera JSON Schema para cada entity publica.
   *
   * @return array
   *   Schemas por entity type.
   */
  public function getEntitySchemas(): array {
    $schemas = [];
    $publicEntityTypes = [
      'analytics_event', 'analytics_daily', 'content_article',
      'support_ticket', 'notification',
    ];

    foreach ($publicEntityTypes as $entityTypeId) {
      try {
        if (!$this->entityTypeManager->hasDefinition($entityTypeId)) {
          continue;
        }

        $fields = $this->entityFieldManager->getBaseFieldDefinitions($entityTypeId);
        $properties = [];
        $required = [];

        foreach ($fields as $fieldName => $field) {
          $fieldType = $field->getType();
          $properties[$fieldName] = $this->fieldToJsonSchema($fieldType, $field);

          if ($field->isRequired()) {
            $required[] = $fieldName;
          }
        }

        $schemas[$entityTypeId] = [
          'type' => 'object',
          'description' => (string) $this->entityTypeManager->getDefinition($entityTypeId)->getLabel(),
          'properties' => $properties,
        ];

        if ($required !== []) {
          $schemas[$entityTypeId]['required'] = $required;
        }
      }
      catch (\Throwable) {
        continue;
      }
    }

    return $schemas;
  }

  /**
   * Genera schemas de autenticacion.
   *
   * @return array
   *   Esquemas OAuth2/HMAC.
   */
  public function getAuthSchemas(): array {
    return [
      'oauth2' => [
        'type' => 'oauth2',
        'flows' => [
          'clientCredentials' => [
            'tokenUrl' => '/oauth/token',
            'scopes' => [
              'read' => 'Read access',
              'write' => 'Write access',
            ],
          ],
        ],
      ],
      'hmac' => [
        'type' => 'apiKey',
        'in' => 'header',
        'name' => 'X-Signature',
        'description' => 'HMAC-SHA256 signature for webhook endpoints.',
      ],
      'csrfToken' => [
        'type' => 'apiKey',
        'in' => 'header',
        'name' => 'X-CSRF-Token',
        'description' => 'CSRF token from /session/token endpoint.',
      ],
    ];
  }

  /**
   * Mapea tipo de campo Drupal a JSON Schema.
   *
   * @param string $fieldType
   *   Tipo de campo Drupal.
   * @param mixed $field
   *   Definicion del campo.
   *
   * @return array
   *   JSON Schema para el campo.
   */
  protected function fieldToJsonSchema(string $fieldType, $field): array {
    return match ($fieldType) {
      'integer' => ['type' => 'integer'],
      'float', 'decimal' => ['type' => 'number'],
      'boolean' => ['type' => 'boolean'],
      'datetime' => ['type' => 'string', 'format' => 'date-time'],
      'created', 'changed' => ['type' => 'integer', 'description' => 'Unix timestamp'],
      'entity_reference' => ['type' => 'integer', 'description' => 'Entity reference ID'],
      'list_string' => [
        'type' => 'string',
        'enum' => array_keys($field->getSetting('allowed_values') ?? []),
      ],
      'map' => ['type' => 'object', 'additionalProperties' => TRUE],
      'string_long', 'text_long' => ['type' => 'string', 'maxLength' => 65535],
      default => ['type' => 'string', 'maxLength' => (int) ($field->getSetting('max_length') ?? 255)],
    };
  }

  /**
   * Genera paths del API.
   *
   * @return array
   *   Paths OpenAPI.
   */
  protected function getApiPaths(): array {
    return [
      '/analytics/event' => [
        'post' => [
          'summary' => 'Track an analytics event',
          'operationId' => 'trackEvent',
          'tags' => ['Analytics'],
          'requestBody' => [
            'required' => TRUE,
            'content' => [
              'application/json' => [
                'schema' => ['$ref' => '#/components/schemas/analytics_event'],
              ],
            ],
          ],
          'responses' => [
            '200' => ['description' => 'Event tracked successfully'],
            '400' => ['description' => 'Invalid request'],
            '403' => ['description' => 'Access denied'],
          ],
        ],
      ],
      '/analytics/dashboard' => [
        'get' => [
          'summary' => 'Get dashboard KPIs',
          'operationId' => 'getDashboard',
          'tags' => ['Analytics'],
          'responses' => [
            '200' => [
              'description' => 'Dashboard data',
              'content' => [
                'application/json' => [
                  'schema' => ['type' => 'object'],
                ],
              ],
            ],
          ],
        ],
      ],
      '/openapi.json' => [
        'get' => [
          'summary' => 'OpenAPI specification',
          'operationId' => 'getOpenApiSpec',
          'tags' => ['Documentation'],
          'responses' => [
            '200' => [
              'description' => 'OpenAPI 3.0.3 specification',
              'content' => [
                'application/json' => [
                  'schema' => ['type' => 'object'],
                ],
              ],
            ],
          ],
        ],
      ],
    ];
  }

}
