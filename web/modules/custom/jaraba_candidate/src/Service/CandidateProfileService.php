<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_candidate\Entity\CandidateProfileInterface;

/**
 * Service for managing candidate profiles.
 */
class CandidateProfileService
{

    /**
     * The entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * The current user.
     */
    protected AccountProxyInterface $currentUser;

    /**
     * The database connection.
     */
    protected Connection $database;

    /**
     * The logger.
     */
    protected $logger;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        AccountProxyInterface $current_user,
        Connection $database,
        LoggerChannelFactoryInterface $logger_factory
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->currentUser = $current_user;
        $this->database = $database;
        $this->logger = $logger_factory->get('jaraba_candidate');
    }

    /**
     * Gets a profile by user ID.
     */
    public function getProfileByUserId(int $user_id): ?CandidateProfileInterface
    {
        $profiles = $this->entityTypeManager
            ->getStorage('candidate_profile')
            ->loadByProperties(['user_id' => $user_id]);

        return !empty($profiles) ? reset($profiles) : NULL;
    }

    /**
     * Creates a new profile for a user.
     */
    public function createProfile(int $user_id, array $data = []): CandidateProfileInterface
    {
        $user = $this->entityTypeManager->getStorage('user')->load($user_id);

        $profile = $this->entityTypeManager
            ->getStorage('candidate_profile')
            ->create([
                'user_id' => $user_id,
                'email' => $user ? $user->getEmail() : '',
                'first_name' => $data['first_name'] ?? '',
                'last_name' => $data['last_name'] ?? '',
            ] + $data);

        $profile->save();

        $this->logger->info('Created profile for user @user', ['@user' => $user_id]);

        return $profile;
    }

    /**
     * Gets skills for a profile.
     */
    public function getSkills(int $profile_id): array
    {
        // TODO: Implement skills retrieval from candidate_skill entity
        return [];
    }

    /**
     * Gets the current user's profile.
     */
    public function getCurrentUserProfile(): ?CandidateProfileInterface
    {
        if ($this->currentUser->isAnonymous()) {
            return NULL;
        }
        return $this->getProfileByUserId((int) $this->currentUser->id());
    }

}
