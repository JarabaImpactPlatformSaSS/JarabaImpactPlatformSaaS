<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Component\Utility\Crypt;

/**
 * Servicio de programa de referidos con IA.
 *
 * PROPÓSITO:
 * Gestiona el programa de referidos, generando códigos únicos,
 * rastreando conversiones y aplicando recompensas automáticamente.
 *
 * Q2 2026 - Sprint 7-8: Expansion Loops
 */
class ReferralProgramService
{

    /**
     * Tipos de recompensa.
     */
    public const REWARD_CREDIT = 'credit';
    public const REWARD_DISCOUNT = 'discount';
    public const REWARD_FREE_MONTH = 'free_month';

    /**
     * Estados de referido.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_REWARDED = 'rewarded';
    public const STATUS_EXPIRED = 'expired';

    /**
     * Configuración del programa.
     */
    protected const PROGRAM_CONFIG = [
        'referrer_reward' => [
            'type' => self::REWARD_CREDIT,
            'value' => 20.00,
            'currency' => 'EUR',
        ],
        'referee_reward' => [
            'type' => self::REWARD_DISCOUNT,
            'value' => 20,
            'unit' => 'percent',
        ],
        'expiration_days' => 30,
        'min_conversion_value' => 29.00,
    ];

    /**
     * Constructor.
     */
    public function __construct(
        protected Connection $database,
    ) {
    }

    /**
     * Genera un código de referido único para un tenant.
     */
    public function generateReferralCode(string $tenantId): string
    {
        // Verificar si ya existe un código activo.
        $existingCode = $this->getActiveCode($tenantId);
        if ($existingCode) {
            return $existingCode;
        }

        // Generar código alfanumérico memorable.
        $code = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', base64_encode(random_bytes(6))), 0, 8));

        $this->database->insert('referral_codes')
            ->fields([
                    'code' => $code,
                    'tenant_id' => $tenantId,
                    'status' => 'active',
                    'uses_count' => 0,
                    'max_uses' => 0, // 0 = ilimitado
                    'created' => time(),
                    'expires' => time() + (365 * 24 * 60 * 60), // 1 año
                ])
            ->execute();

        return $code;
    }

    /**
     * Obtiene el código activo de un tenant.
     */
    public function getActiveCode(string $tenantId): ?string
    {
        $result = $this->database->select('referral_codes', 'rc')
            ->fields('rc', ['code'])
            ->condition('tenant_id', $tenantId)
            ->condition('status', 'active')
            ->condition('expires', time(), '>')
            ->execute()
            ->fetchField();

        return $result ?: NULL;
    }

    /**
     * Valida un código de referido.
     */
    public function validateCode(string $code): array
    {
        $result = $this->database->select('referral_codes', 'rc')
            ->fields('rc')
            ->condition('code', strtoupper($code))
            ->condition('status', 'active')
            ->condition('expires', time(), '>')
            ->execute()
            ->fetchObject();

        if (!$result) {
            return [
                'valid' => FALSE,
                'error' => 'Código inválido o expirado',
            ];
        }

        if ($result->max_uses > 0 && $result->uses_count >= $result->max_uses) {
            return [
                'valid' => FALSE,
                'error' => 'Este código ha alcanzado su límite de uso',
            ];
        }

        return [
            'valid' => TRUE,
            'code' => $result->code,
            'referrer_id' => $result->tenant_id,
            'reward' => self::PROGRAM_CONFIG['referee_reward'],
        ];
    }

    /**
     * Registra el uso de un código de referido.
     */
    public function trackReferral(string $code, string $newTenantId, array $metadata = []): ?string
    {
        $validation = $this->validateCode($code);

        if (!$validation['valid']) {
            return NULL;
        }

        $referralId = Crypt::randomBytesBase64(12);

        $this->database->insert('referrals')
            ->fields([
                    'id' => $referralId,
                    'code' => strtoupper($code),
                    'referrer_tenant_id' => $validation['referrer_id'],
                    'referee_tenant_id' => $newTenantId,
                    'status' => self::STATUS_PENDING,
                    'metadata' => json_encode($metadata),
                    'created' => time(),
                    'expires' => time() + (self::PROGRAM_CONFIG['expiration_days'] * 24 * 60 * 60),
                ])
            ->execute();

        // Incrementar contador de usos.
        $this->database->update('referral_codes')
            ->expression('uses_count', 'uses_count + 1')
            ->condition('code', strtoupper($code))
            ->execute();

        return $referralId;
    }

    /**
     * Convierte un referido cuando el nuevo tenant paga.
     */
    public function convertReferral(string $referralId, float $paymentAmount): bool
    {
        // Verificar monto mínimo.
        if ($paymentAmount < self::PROGRAM_CONFIG['min_conversion_value']) {
            return FALSE;
        }

        $referral = $this->database->select('referrals', 'r')
            ->fields('r')
            ->condition('id', $referralId)
            ->condition('status', self::STATUS_PENDING)
            ->execute()
            ->fetchObject();

        if (!$referral) {
            return FALSE;
        }

        // Actualizar estado.
        $this->database->update('referrals')
            ->fields([
                    'status' => self::STATUS_CONVERTED,
                    'converted_at' => time(),
                    'conversion_amount' => $paymentAmount,
                ])
            ->condition('id', $referralId)
            ->execute();

        // Aplicar recompensa al referidor.
        $this->applyReferrerReward($referral->referrer_tenant_id, $referralId);

        return TRUE;
    }

    /**
     * Aplica recompensa al referidor.
     */
    protected function applyReferrerReward(string $tenantId, string $referralId): void
    {
        $reward = self::PROGRAM_CONFIG['referrer_reward'];

        $this->database->insert('referral_rewards')
            ->fields([
                    'id' => Crypt::randomBytesBase64(12),
                    'tenant_id' => $tenantId,
                    'referral_id' => $referralId,
                    'type' => $reward['type'],
                    'value' => $reward['value'],
                    'currency' => $reward['currency'] ?? 'EUR',
                    'status' => 'pending',
                    'created' => time(),
                ])
            ->execute();

        // Actualizar referral a rewarded.
        $this->database->update('referrals')
            ->fields(['status' => self::STATUS_REWARDED])
            ->condition('id', $referralId)
            ->execute();
    }

    /**
     * Obtiene estadísticas de referidos de un tenant.
     */
    public function getReferralStats(string $tenantId): array
    {
        // Total de referidos.
        $totalReferrals = $this->database->select('referrals', 'r')
            ->condition('referrer_tenant_id', $tenantId)
            ->countQuery()
            ->execute()
            ->fetchField();

        // Convertidos.
        $convertedReferrals = $this->database->select('referrals', 'r')
            ->condition('referrer_tenant_id', $tenantId)
            ->condition('status', [self::STATUS_CONVERTED, self::STATUS_REWARDED], 'IN')
            ->countQuery()
            ->execute()
            ->fetchField();

        // Total ganado.
        $totalEarned = $this->database->select('referral_rewards', 'rr')
            ->condition('tenant_id', $tenantId)
            ->condition('status', 'applied')
            ->execute()
            ->fetchField() ?: 0;

        // Pendiente de cobrar.
        $pendingRewards = $this->database->select('referral_rewards', 'rr')
            ->fields('rr', ['value'])
            ->condition('tenant_id', $tenantId)
            ->condition('status', 'pending')
            ->execute()
            ->fetchAll();

        $pendingAmount = array_sum(array_map(fn($r) => (float) $r->value, $pendingRewards));

        return [
            'code' => $this->getActiveCode($tenantId),
            'total_referrals' => (int) $totalReferrals,
            'converted' => (int) $convertedReferrals,
            'conversion_rate' => $totalReferrals > 0 ? round(($convertedReferrals / $totalReferrals) * 100, 1) : 0,
            'total_earned' => (float) $totalEarned,
            'pending_rewards' => $pendingAmount,
            'share_url' => $this->generateShareUrl($tenantId),
        ];
    }

    /**
     * Genera URL para compartir.
     */
    public function generateShareUrl(string $tenantId): string
    {
        $code = $this->getActiveCode($tenantId) ?? $this->generateReferralCode($tenantId);
        return "https://plataformadeecosistemas.es/registro?ref={$code}";
    }

    /**
     * Genera mensaje de compartición personalizado con IA.
     */
    public function generateShareMessage(string $tenantId, string $platform = 'generic'): string
    {
        $code = $this->getActiveCode($tenantId);
        $reward = self::PROGRAM_CONFIG['referee_reward'];

        $messages = [
            'generic' => "¡Únete a Jaraba Impact Platform con mi código {$code} y obtén {$reward['value']}% de descuento en tu primer mes! 🚀",
            'whatsapp' => "Hola! Te recomiendo Jaraba Impact Platform para vender online. Usa mi código *{$code}* y consigues {$reward['value']}% de descuento 🎁",
            'email' => "Te invito a probar Jaraba Impact Platform, la plataforma que uso para mi tienda online. Con mi código de referido ({$code}) obtienes un {$reward['value']}% de descuento en tu primer mes.",
            'twitter' => "Vendo mis productos online con @JarabaImpact 🛒 Si quieres probarlo, usa mi código {$code} para un {$reward['value']}% off! #ecommerce #ventas",
        ];

        return $messages[$platform] ?? $messages['generic'];
    }

}
