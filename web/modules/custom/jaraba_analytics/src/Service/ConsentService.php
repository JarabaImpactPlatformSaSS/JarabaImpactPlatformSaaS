<?php

namespace Drupal\jaraba_analytics\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Servicio para gestión de consentimientos GDPR.
 *
 * Implementa el CMP (Consent Management Platform) nativo,
 * eliminando dependencias de herramientas externas.
 */
class ConsentService
{

    /**
     * Entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Request stack.
     *
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    protected RequestStack $requestStack;

    /**
     * Current user.
     *
     * @var \Drupal\Core\Session\AccountInterface
     */
    protected AccountInterface $currentUser;

    /**
     * Constructor del servicio.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   Entity type manager.
     * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
     *   Request stack.
     * @param \Drupal\Core\Session\AccountInterface $current_user
     *   Current user.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        RequestStack $request_stack,
        AccountInterface $current_user,
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->requestStack = $request_stack;
        $this->currentUser = $current_user;
    }

    /**
     * Obtener consentimiento de un visitante.
     *
     * @param string $visitor_id
     *   ID único del visitante.
     *
     * @return \Drupal\jaraba_analytics\Entity\ConsentRecord|null
     *   Registro de consentimiento o NULL si no existe.
     */
    public function getConsent(string $visitor_id): ?object
    {
        $storage = $this->entityTypeManager->getStorage('consent_record');
        $records = $storage->loadByProperties(['visitor_id' => $visitor_id]);

        return $records ? reset($records) : NULL;
    }

    /**
     * Otorgar consentimiento.
     *
     * @param array $categories
     *   Categorías de consentimiento: ['analytics' => true, 'marketing' => false].
     * @param string $visitor_id
     *   ID único del visitante.
     * @param int|null $tenant_id
     *   ID del tenant (opcional).
     *
     * @return \Drupal\jaraba_analytics\Entity\ConsentRecord
     *   Registro de consentimiento creado/actualizado.
     */
    public function grantConsent(array $categories, string $visitor_id, ?int $tenant_id = NULL): object
    {
        $storage = $this->entityTypeManager->getStorage('consent_record');
        $existing = $this->getConsent($visitor_id);

        $request = $this->requestStack->getCurrentRequest();

        if ($existing) {
            // Actualizar registro existente.
            $existing->set('consent_analytics', $categories['analytics'] ?? FALSE);
            $existing->set('consent_marketing', $categories['marketing'] ?? FALSE);
            $existing->set('consent_functional', $categories['functional'] ?? TRUE);
            $existing->save();

            return $existing;
        }

        // Crear nuevo registro.
        $record = $storage->create([
            'visitor_id' => $visitor_id,
            'tenant_id' => $tenant_id,
            'consent_analytics' => $categories['analytics'] ?? FALSE,
            'consent_marketing' => $categories['marketing'] ?? FALSE,
            'consent_functional' => $categories['functional'] ?? TRUE,
            'consent_necessary' => TRUE,
            'policy_version' => '1.0',
            'ip_hash' => $request ? $this->hashIp($request->getClientIp()) : NULL,
            'user_agent' => $request ? $this->truncateUserAgent($request->headers->get('User-Agent')) : NULL,
        ]);

        $record->save();

        return $record;
    }

    /**
     * Revocar todo el consentimiento de un visitante.
     *
     * @param string $visitor_id
     *   ID único del visitante.
     */
    public function revokeConsent(string $visitor_id): void
    {
        $existing = $this->getConsent($visitor_id);

        if ($existing) {
            $existing->set('consent_analytics', FALSE);
            $existing->set('consent_marketing', FALSE);
            $existing->set('consent_functional', FALSE);
            $existing->save();
        }
    }

    /**
     * Verificar si tiene consentimiento para una categoría.
     *
     * @param string $visitor_id
     *   ID único del visitante.
     * @param string $category
     *   Categoría: 'analytics', 'marketing', 'functional'.
     *
     * @return bool
     *   TRUE si tiene consentimiento.
     */
    public function hasConsent(string $visitor_id, string $category): bool
    {
        $record = $this->getConsent($visitor_id);

        if (!$record) {
            return $category === 'necessary';
        }

        return $record->hasConsent($category);
    }

    /**
     * Hash de IP para cumplimiento GDPR.
     *
     * @param string|null $ip
     *   Dirección IP.
     *
     * @return string|null
     *   Hash SHA-256 de la IP.
     */
    protected function hashIp(?string $ip): ?string
    {
        if (!$ip) {
            return NULL;
        }

        return hash('sha256', $ip . 'jaraba_salt_gdpr');
    }

    /**
     * Truncar User-Agent por privacidad.
     *
     * @param string|null $user_agent
     *   User-Agent completo.
     *
     * @return string|null
     *   User-Agent truncado a 100 caracteres.
     */
    protected function truncateUserAgent(?string $user_agent): ?string
    {
        if (!$user_agent) {
            return NULL;
        }

        return substr($user_agent, 0, 100);
    }

}
