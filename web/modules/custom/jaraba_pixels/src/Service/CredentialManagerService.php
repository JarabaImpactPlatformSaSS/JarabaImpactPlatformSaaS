<?php

namespace Drupal\jaraba_pixels\Service;

use Drupal\Core\Database\Connection;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * Servicio para gestionar credenciales de pixel por tenant.
 *
 * Proporciona operaciones CRUD seguras para almacenar y recuperar
 * tokens de acceso de plataformas de marketing.
 */
class CredentialManagerService
{

    /**
     * Conexión a base de datos.
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected Connection $database;

    /**
     * Servicio de contexto de tenant.
     *
     * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
     */
    protected TenantContextService $tenantContext;

    /**
     * Constructor.
     */
    public function __construct(
        Connection $database,
        TenantContextService $tenant_context,
    ) {
        $this->database = $database;
        $this->tenantContext = $tenant_context;
    }

    /**
     * Obtiene las credenciales de una plataforma para el tenant actual.
     *
     * @param string $platform
     *   Plataforma (meta, google, linkedin, tiktok).
     * @param int|null $tenant_id
     *   ID del tenant. Si es NULL, usa el contexto actual.
     *
     * @return array|null
     *   Datos de la credencial o NULL si no existe.
     */
    public function getCredential(string $platform, ?int $tenant_id = NULL): ?array
    {
        $tenant_id = $tenant_id ?? $this->getCurrentTenantId();
        if (!$tenant_id) {
            return NULL;
        }

        $result = $this->database->select('pixel_credentials', 'pc')
            ->fields('pc')
            ->condition('tenant_id', $tenant_id)
            ->condition('platform', $platform)
            ->execute()
            ->fetchAssoc();

        if ($result) {
            // Desencriptar tokens sensibles.
            $result['access_token'] = $this->decrypt($result['access_token']);
            $result['api_secret'] = $this->decrypt($result['api_secret']);
        }

        return $result ?: NULL;
    }

    /**
     * Obtiene todas las credenciales del tenant actual.
     *
     * @param int|null $tenant_id
     *   ID del tenant.
     *
     * @return array
     *   Array de credenciales indexado por plataforma.
     */
    public function getAllCredentials(?int $tenant_id = NULL): array
    {
        $tenant_id = $tenant_id ?? $this->getCurrentTenantId();
        if (!$tenant_id) {
            return [];
        }

        $results = $this->database->select('pixel_credentials', 'pc')
            ->fields('pc')
            ->condition('tenant_id', $tenant_id)
            ->execute()
            ->fetchAllAssoc('platform', \PDO::FETCH_ASSOC);

        // Desencriptar tokens.
        foreach ($results as &$credential) {
            $credential['access_token'] = $this->decrypt($credential['access_token']);
            $credential['api_secret'] = $this->decrypt($credential['api_secret']);
        }

        return $results;
    }

    /**
     * Guarda o actualiza credenciales de una plataforma.
     *
     * @param string $platform
     *   Plataforma.
     * @param array $data
     *   Datos de la credencial.
     * @param int|null $tenant_id
     *   ID del tenant.
     *
     * @return bool
     *   TRUE si se guardó correctamente.
     */
    public function saveCredential(string $platform, array $data, ?int $tenant_id = NULL): bool
    {
        $tenant_id = $tenant_id ?? $this->getCurrentTenantId();
        if (!$tenant_id) {
            return FALSE;
        }

        $now = time();
        $existing = $this->getCredential($platform, $tenant_id);

        $fields = [
            'tenant_id' => $tenant_id,
            'platform' => $platform,
            'pixel_id' => $data['pixel_id'] ?? '',
            'access_token' => $this->encrypt($data['access_token'] ?? ''),
            'api_secret' => $this->encrypt($data['api_secret'] ?? ''),
            'status' => $data['status'] ?? 'enabled',
            'test_mode' => (int) ($data['test_mode'] ?? FALSE),
            'test_event_code' => $data['test_event_code'] ?? '',
            'updated' => $now,
        ];

        try {
            if ($existing) {
                // Actualizar.
                $this->database->update('pixel_credentials')
                    ->fields($fields)
                    ->condition('tenant_id', $tenant_id)
                    ->condition('platform', $platform)
                    ->execute();
            } else {
                // Insertar.
                $fields['created'] = $now;
                $this->database->insert('pixel_credentials')
                    ->fields($fields)
                    ->execute();
            }
            return TRUE;
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_pixels')->error('Error saving credential: @message', [
                '@message' => $e->getMessage(),
            ]);
            return FALSE;
        }
    }

    /**
     * Elimina las credenciales de una plataforma.
     *
     * @param string $platform
     *   Plataforma.
     * @param int|null $tenant_id
     *   ID del tenant.
     *
     * @return bool
     *   TRUE si se eliminó correctamente.
     */
    public function deleteCredential(string $platform, ?int $tenant_id = NULL): bool
    {
        $tenant_id = $tenant_id ?? $this->getCurrentTenantId();
        if (!$tenant_id) {
            return FALSE;
        }

        try {
            $this->database->delete('pixel_credentials')
                ->condition('tenant_id', $tenant_id)
                ->condition('platform', $platform)
                ->execute();
            return TRUE;
        } catch (\Exception $e) {
            return FALSE;
        }
    }

    /**
     * Actualiza el timestamp de última verificación.
     *
     * @param string $platform
     *   Plataforma.
     * @param int|null $tenant_id
     *   ID del tenant.
     */
    public function markAsVerified(string $platform, ?int $tenant_id = NULL): void
    {
        $tenant_id = $tenant_id ?? $this->getCurrentTenantId();
        if (!$tenant_id) {
            return;
        }

        $this->database->update('pixel_credentials')
            ->fields(['last_verified' => time()])
            ->condition('tenant_id', $tenant_id)
            ->condition('platform', $platform)
            ->execute();
    }

    /**
     * Actualiza el estado de una credencial.
     *
     * @param string $platform
     *   Plataforma.
     * @param string $status
     *   Nuevo estado (enabled, disabled, error).
     * @param int|null $tenant_id
     *   ID del tenant.
     */
    public function updateStatus(string $platform, string $status, ?int $tenant_id = NULL): void
    {
        $tenant_id = $tenant_id ?? $this->getCurrentTenantId();
        if (!$tenant_id) {
            return;
        }

        $this->database->update('pixel_credentials')
            ->fields(['status' => $status, 'updated' => time()])
            ->condition('tenant_id', $tenant_id)
            ->condition('platform', $platform)
            ->execute();
    }

    /**
     * Obtiene credenciales habilitadas para envío.
     *
     * @param int $tenant_id
     *   ID del tenant.
     *
     * @return array
     *   Array de credenciales activas.
     */
    public function getEnabledCredentials(int $tenant_id): array
    {
        $results = $this->database->select('pixel_credentials', 'pc')
            ->fields('pc')
            ->condition('tenant_id', $tenant_id)
            ->condition('status', 'enabled')
            ->execute()
            ->fetchAllAssoc('platform', \PDO::FETCH_ASSOC);

        foreach ($results as &$credential) {
            $credential['access_token'] = $this->decrypt($credential['access_token']);
            $credential['api_secret'] = $this->decrypt($credential['api_secret']);
        }

        return $results;
    }

    /**
     * Obtiene el ID del tenant actual.
     *
     * @return int|null
     *   ID del tenant o NULL.
     */
    protected function getCurrentTenantId(): ?int
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        return $tenant ? (int) $tenant->id() : NULL;
    }

    /**
     * Encripta un valor sensible.
     *
     * @param string|null $value
     *   Valor a encriptar.
     *
     * @return string|null
     *   Valor encriptado.
     */
    protected function encrypt(?string $value): ?string
    {
        if (empty($value)) {
            return $value;
        }

        // Usar la clave de encriptación de Drupal.
        $key = \Drupal::service('settings')->get('hash_salt', 'default_key');
        $iv = substr(hash('sha256', $key), 0, 16);

        return base64_encode(openssl_encrypt($value, 'AES-256-CBC', $key, 0, $iv));
    }

    /**
     * Desencripta un valor.
     *
     * @param string|null $value
     *   Valor encriptado.
     *
     * @return string|null
     *   Valor desencriptado.
     */
    protected function decrypt(?string $value): ?string
    {
        if (empty($value)) {
            return $value;
        }

        try {
            $key = \Drupal::service('settings')->get('hash_salt', 'default_key');
            $iv = substr(hash('sha256', $key), 0, 16);

            return openssl_decrypt(base64_decode($value), 'AES-256-CBC', $key, 0, $iv);
        } catch (\Exception $e) {
            return NULL;
        }
    }

}
