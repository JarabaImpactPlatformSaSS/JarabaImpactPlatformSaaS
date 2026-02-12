<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service for managing courses.
 */
class CourseService
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
     * The database.
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
        $this->logger = $logger_factory->get('jaraba_lms');
    }

    /**
     * Gets published courses.
     */
    public function getPublishedCourses(int $limit = 50): array
    {
        return $this->entityTypeManager
            ->getStorage('lms_course')
            ->loadByProperties(['is_published' => TRUE]);
    }

    /**
     * Gets a course by ID.
     */
    public function getCourse(int $id): mixed
    {
        return $this->entityTypeManager
            ->getStorage('lms_course')
            ->load($id);
    }

    /**
     * Gets courses by difficulty.
     */
    public function getCoursesByDifficulty(string $level): array
    {
        return $this->entityTypeManager
            ->getStorage('lms_course')
            ->loadByProperties([
                'is_published' => TRUE,
                'difficulty_level' => $level,
            ]);
    }

    /**
     * Gets featured courses.
     */
    public function getFeaturedCourses(int $limit = 6): array
    {
        return $this->entityTypeManager
            ->getStorage('lms_course')
            ->loadByProperties([
                'is_published' => TRUE,
                'is_featured' => TRUE,
            ]);
    }

}
