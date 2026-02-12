<?php

declare(strict_types=1);

namespace Drupal\jaraba_self_discovery\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio dedicado para datos RIASEC con fallback a user.data.
 */
class RiasecService
{

    /**
     * Entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * El usuario actual.
     */
    protected AccountInterface $currentUser;

    /**
     * User data service (retrocompatibilidad).
     */
    protected $userData;

    /**
     * Logger.
     */
    protected LoggerInterface $logger;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        AccountInterface $current_user,
        $user_data,
        LoggerInterface $logger
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->currentUser = $current_user;
        $this->userData = $user_data;
        $this->logger = $logger;
    }

    /**
     * Obtiene el ultimo perfil InterestProfile del usuario.
     *
     * @return \Drupal\jaraba_self_discovery\Entity\InterestProfile|null
     *   El ultimo perfil o NULL.
     */
    public function getLatestProfile(?int $uid = NULL)
    {
        $uid = $uid ?: (int) $this->currentUser->id();

        try {
            $storage = $this->entityTypeManager->getStorage('interest_profile');
            $ids = $storage->getQuery()
                ->accessCheck(FALSE)
                ->condition('user_id', $uid)
                ->sort('created', 'DESC')
                ->range(0, 1)
                ->execute();

            if (!empty($ids)) {
                return $storage->load(reset($ids));
            }
        }
        catch (\Exception $e) {
            $this->logger->error('RiasecService::getLatestProfile error: @error', [
                '@error' => $e->getMessage(),
            ]);
        }

        return NULL;
    }

    /**
     * Obtiene el codigo RIASEC (3 letras).
     */
    public function getCode(?int $uid = NULL): ?string
    {
        $uid = $uid ?: (int) $this->currentUser->id();

        // Intentar desde entity primero.
        $profile = $this->getLatestProfile($uid);
        if ($profile) {
            return $profile->getRiasecCode();
        }

        // Fallback a user.data.
        return $this->userData->get('jaraba_self_discovery', $uid, 'riasec_code') ?: NULL;
    }

    /**
     * Obtiene las puntuaciones RIASEC.
     */
    public function getScores(?int $uid = NULL): array
    {
        $uid = $uid ?: (int) $this->currentUser->id();

        $profile = $this->getLatestProfile($uid);
        if ($profile) {
            return $profile->getAllScores();
        }

        // Fallback a user.data.
        return $this->userData->get('jaraba_self_discovery', $uid, 'riasec_scores') ?: [];
    }

    /**
     * Obtiene los tipos dominantes.
     */
    public function getDominantTypes(?int $uid = NULL): array
    {
        $profile = $this->getLatestProfile($uid);
        if ($profile) {
            return $profile->getDominantTypes();
        }

        $code = $this->getCode($uid);
        return $code ? str_split($code) : [];
    }

    /**
     * Obtiene las carreras sugeridas.
     */
    public function getSuggestedCareers(?int $uid = NULL): array
    {
        $profile = $this->getLatestProfile($uid);
        if ($profile) {
            return $profile->getSuggestedCareers();
        }

        return [];
    }

    /**
     * Obtiene una descripcion textual del perfil.
     */
    public function getProfileDescription(?int $uid = NULL): string
    {
        $typeDescriptions = [
            'R' => 'Realista - practico, tecnico',
            'I' => 'Investigador - analitico, cientifico',
            'A' => 'Artistico - creativo, expresivo',
            'S' => 'Social - colaborador, empatico',
            'E' => 'Emprendedor - lider, persuasivo',
            'C' => 'Convencional - organizado, estructurado',
        ];

        $code = $this->getCode($uid);
        if (!$code) {
            return '';
        }

        $letters = str_split($code);
        $descriptions = array_map(fn($l) => $typeDescriptions[$l] ?? $l, $letters);

        return implode(' + ', $descriptions);
    }

}
