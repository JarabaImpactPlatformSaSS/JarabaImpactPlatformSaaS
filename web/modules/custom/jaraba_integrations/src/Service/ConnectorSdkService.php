<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Service;

use Psr\Log\LoggerInterface;

/**
 * Servicio de SDK para desarrolladores de conectores.
 *
 * PROPOSITO:
 * Proporciona herramientas para que desarrolladores externos creen
 * conectores para el marketplace. Genera scaffold de codigo,
 * documentacion de la API y entorno de pruebas.
 *
 * LOGICA:
 * - Genera estructura basica de archivos para un nuevo conector
 * - Proporciona schema de validacion para el manifest del conector
 * - Ofrece sandbox de pruebas con datos ficticios
 */
class ConnectorSdkService {

  /**
   * Version actual del SDK.
   */
  public const SDK_VERSION = '1.0.0';

  /**
   * Constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log del modulo.
   */
  public function __construct(
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Genera el scaffold para un nuevo conector.
   *
   * @param string $connectorName
   *   Nombre del conector (machine name).
   * @param string $displayName
   *   Nombre visible del conector.
   * @param string $category
   *   Categoria del conector.
   *
   * @return array
   *   Array con la estructura de archivos generada.
   */
  public function generateScaffold(string $connectorName, string $displayName, string $category): array {
    $scaffold = [
      'manifest.json' => $this->generateManifest($connectorName, $displayName, $category),
      'README.md' => $this->generateReadme($connectorName, $displayName),
      'config.schema.json' => $this->generateConfigSchema(),
      'handlers/install.php' => $this->generateInstallHandler($connectorName),
      'handlers/uninstall.php' => $this->generateUninstallHandler($connectorName),
      'handlers/webhook.php' => $this->generateWebhookHandler($connectorName),
    ];

    $this->logger->info('Scaffold generado para conector @name.', ['@name' => $connectorName]);

    return $scaffold;
  }

  /**
   * Genera el manifest JSON para un conector.
   */
  protected function generateManifest(string $name, string $displayName, string $category): string {
    $manifest = [
      'sdk_version' => self::SDK_VERSION,
      'connector' => [
        'machine_name' => $name,
        'display_name' => $displayName,
        'category' => $category,
        'version' => '1.0.0',
        'description' => '',
        'author' => '',
        'homepage' => '',
        'icon' => 'icon.svg',
      ],
      'capabilities' => [
        'oauth2' => FALSE,
        'webhooks' => FALSE,
        'sync' => FALSE,
        'realtime' => FALSE,
      ],
      'config_fields' => [],
      'permissions' => [
        'read_data' => TRUE,
        'write_data' => FALSE,
      ],
    ];

    return json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  }

  /**
   * Genera el README para un conector.
   */
  protected function generateReadme(string $name, string $displayName): string {
    return sprintf(
      "# %s Connector\n\n" .
      "Conector para integrar %s con la plataforma Jaraba SaaS.\n\n" .
      "## Instalacion\n\n" .
      "1. Sube los archivos al marketplace\n" .
      "2. Envia a revision\n" .
      "3. Una vez aprobado, estara disponible para instalacion\n\n" .
      "## Configuracion\n\n" .
      "Configura las credenciales en el panel de integraciones.\n\n" .
      "## SDK Version\n\n" .
      "Este conector fue creado con SDK v%s\n",
      $displayName,
      $displayName,
      self::SDK_VERSION,
    );
  }

  /**
   * Genera el schema de configuracion.
   */
  protected function generateConfigSchema(): string {
    $schema = [
      '$schema' => 'https://json-schema.org/draft/2020-12/schema',
      'type' => 'object',
      'properties' => [
        'api_key' => [
          'type' => 'string',
          'description' => 'Clave de API del servicio externo.',
        ],
        'api_url' => [
          'type' => 'string',
          'format' => 'uri',
          'description' => 'URL base de la API.',
        ],
      ],
      'required' => ['api_key'],
    ];

    return json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  }

  /**
   * Genera el handler de instalacion.
   */
  protected function generateInstallHandler(string $name): string {
    return sprintf(
      "<?php\n\n" .
      "declare(strict_types=1);\n\n" .
      "/**\n * Handler de instalacion para %s.\n */\n" .
      "function %s_install(array \$config): bool {\n" .
      "  // Validar configuracion y establecer conexion.\n" .
      "  return TRUE;\n" .
      "}\n",
      $name,
      $name,
    );
  }

  /**
   * Genera el handler de desinstalacion.
   */
  protected function generateUninstallHandler(string $name): string {
    return sprintf(
      "<?php\n\n" .
      "declare(strict_types=1);\n\n" .
      "/**\n * Handler de desinstalacion para %s.\n */\n" .
      "function %s_uninstall(): void {\n" .
      "  // Limpiar datos y revocar tokens.\n" .
      "}\n",
      $name,
      $name,
    );
  }

  /**
   * Genera el handler de webhooks.
   */
  protected function generateWebhookHandler(string $name): string {
    return sprintf(
      "<?php\n\n" .
      "declare(strict_types=1);\n\n" .
      "/**\n * Handler de webhooks para %s.\n */\n" .
      "function %s_webhook(array \$payload): array {\n" .
      "  // Procesar webhook entrante.\n" .
      "  return ['status' => 'ok'];\n" .
      "}\n",
      $name,
      $name,
    );
  }

  /**
   * Valida un manifest de conector.
   *
   * @param array $manifest
   *   Datos del manifest.
   *
   * @return array
   *   Array de errores de validacion (vacio si es valido).
   */
  public function validateManifest(array $manifest): array {
    $errors = [];

    if (empty($manifest['connector']['machine_name'])) {
      $errors[] = t('El nombre de maquina del conector es obligatorio.');
    }

    if (empty($manifest['connector']['display_name'])) {
      $errors[] = t('El nombre visible del conector es obligatorio.');
    }

    if (empty($manifest['connector']['version'])) {
      $errors[] = t('La version del conector es obligatoria.');
    }

    if (!isset($manifest['sdk_version'])) {
      $errors[] = t('La version del SDK es obligatoria.');
    }

    return $errors;
  }

}
