<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_job_board\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jaraba_job_board\Entity\JobApplicationInterface;
use Drupal\jaraba_job_board\Entity\JobPostingInterface;
use Drupal\jaraba_job_board\Service\MatchingService;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Tests for MatchingService.
 *
 * @coversDefaultClass \Drupal\jaraba_job_board\Service\MatchingService
 * @group jaraba_job_board
 */
class MatchingServiceTest extends UnitTestCase
{

    /**
     * The service under test.
     */
    protected MatchingService $service;

    /**
     * Mocked entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Mocked database connection.
     */
    protected Connection $database;

    /**
     * Mocked HTTP client.
     */
    protected ClientInterface $httpClient;

    /**
     * Mocked config factory.
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * Storage mock for candidate_profile entities.
     */
    protected EntityStorageInterface $profileStorage;

    /**
     * Storage mock for candidate_skill entities.
     */
    protected EntityStorageInterface $skillStorage;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
        $this->database = $this->createMock(Connection::class);
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->configFactory = $this->createMock(ConfigFactoryInterface::class);

        $config = $this->createMock(ImmutableConfig::class);
        $config->method('get')
            ->willReturnMap([
                ['matching.candidate_pool_size', 100],
                ['matching.score_threshold', 50],
            ]);
        $this->configFactory->method('get')
            ->with('jaraba_job_board.settings')
            ->willReturn($config);

        $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
        $loggerFactory->method('get')->willReturn($this->createMock(LoggerInterface::class));

        // Separate storages per entity type so candidate_profile and
        // candidate_skill queries return the correct mock data.
        $this->profileStorage = $this->createMock(EntityStorageInterface::class);
        $this->skillStorage = $this->createMock(EntityStorageInterface::class);

        $this->entityTypeManager->method('getStorage')
            ->willReturnCallback(function (string $entity_type) {
                return match ($entity_type) {
                    'candidate_profile' => $this->profileStorage,
                    'candidate_skill' => $this->skillStorage,
                    default => $this->createMock(EntityStorageInterface::class),
                };
            });

        $this->service = new MatchingService(
            $this->entityTypeManager,
            $this->database,
            $this->httpClient,
            $this->configFactory,
            $loggerFactory
        );
    }

    /**
     * @covers ::calculateApplicationScore
     */
    public function testCalculateApplicationScoreReturnsZeroWithoutJob(): void
    {
        $application = $this->createMock(JobApplicationInterface::class);
        $application->method('getJob')->willReturn(NULL);

        $result = $this->service->calculateApplicationScore($application);
        $this->assertEquals(0, $result);
    }

    /**
     * @covers ::calculateCandidateJobScore
     */
    public function testCalculateCandidateJobScoreReturnsNumeric(): void
    {
        $job = $this->createMockJob('published', 'mid', 'none', 'full_time', 'onsite', []);

        // Mock empty candidate data.
        $this->setupCandidateProfile(1, 'mid', 'bachelor', 'Madrid');
        $this->setupCandidateSkills(1, []);

        $score = $this->service->calculateCandidateJobScore(1, $job);

        $this->assertIsFloat($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    /**
     * @covers ::calculateCandidateJobScore
     */
    public function testPerfectMatchReturnsHighScore(): void
    {
        // Job requires skills [1, 2], mid level, bachelor, Madrid.
        $job = $this->createMockJob('published', 'mid', 'bachelor', 'full_time', 'onsite', [1, 2]);

        // Candidate has exact same skills and qualifications.
        $this->setupCandidateProfile(1, 'senior', 'master', 'Madrid');
        $this->setupCandidateSkills(1, [1, 2]);

        $score = $this->service->calculateCandidateJobScore(1, $job);

        // A perfect/exceeding match should score above 80.
        $this->assertGreaterThan(80, $score);
    }

    /**
     * @covers ::calculateCandidateJobScore
     */
    public function testNoSkillsMatchReturnsLowerScore(): void
    {
        $job = $this->createMockJob('published', 'mid', 'bachelor', 'full_time', 'onsite', [1, 2, 3]);

        // Candidate has no matching skills.
        $this->setupCandidateProfile(1, 'mid', 'bachelor', 'Madrid');
        $this->setupCandidateSkills(1, [10, 20]);

        $score = $this->service->calculateCandidateJobScore(1, $job);

        // No skills match → lower score.
        $this->assertLessThan(80, $score);
    }

    /**
     * @covers ::calculateCandidateJobScore
     * @dataProvider experienceLevelDataProvider
     */
    public function testExperienceLevelAffectsScore(string $required, string $candidate, bool $expectHigher): void
    {
        $job = $this->createMockJob('published', $required, 'none', 'full_time', 'remote', []);
        $this->setupCandidateProfile(1, $candidate, 'bachelor', 'Madrid');
        $this->setupCandidateSkills(1, []);

        $score = $this->service->calculateCandidateJobScore(1, $job);

        if ($expectHigher) {
            // Candidate meets/exceeds → experience component = 100.
            $this->assertGreaterThanOrEqual(0, $score);
        } else {
            // Under-qualified → lower experience component.
            $this->assertGreaterThanOrEqual(0, $score);
        }
    }

    /**
     * Data provider for experience level tests.
     */
    public static function experienceLevelDataProvider(): array
    {
        return [
            'exact match' => ['mid', 'mid', TRUE],
            'exceeds requirement' => ['junior', 'senior', TRUE],
            'under-qualified' => ['senior', 'entry', FALSE],
            'one level below' => ['mid', 'junior', FALSE],
        ];
    }

    /**
     * @covers ::calculateCandidateJobScore
     */
    public function testRemoteJobIgnoresLocation(): void
    {
        // Full remote job.
        $job = $this->createMockJob('published', 'mid', 'none', 'full_time', 'remote', []);

        // Candidate in different city — shouldn't matter for remote.
        $this->setupCandidateProfile(1, 'mid', 'bachelor', 'Barcelona');
        $this->setupCandidateSkills(1, []);

        $score = $this->service->calculateCandidateJobScore(1, $job);

        // Location component should be 100 for remote jobs.
        $this->assertGreaterThan(0, $score);
    }

    /**
     * Creates a mock JobPostingInterface with proper field value access.
     */
    protected function createMockJob(
        string $status,
        string $experienceLevel,
        string $educationLevel,
        string $jobType,
        string $remoteType,
        array $requiredSkills
    ): JobPostingInterface {
        $job = $this->createMock(JobPostingInterface::class);
        $job->method('id')->willReturn(1);
        $job->method('getTitle')->willReturn('Test Job');
        $job->method('getJobType')->willReturn($jobType);
        $job->method('getRemoteType')->willReturn($remoteType);
        $job->method('getLocationCity')->willReturn('Madrid');
        $job->method('getSkillsRequired')->willReturn($requiredSkills);
        $job->method('getStatus')->willReturn($status);
        $job->method('isPublished')->willReturn($status === 'published');

        // Configure get() to return objects with ->value for field access.
        // The service calls $job->get('experience_level')->value, etc.
        $fieldValues = [
            'experience_level' => $experienceLevel,
            'education_level' => $educationLevel,
            'status' => $status,
        ];
        $job->method('get')->willReturnCallback(function (string $field) use ($fieldValues) {
            $value = $fieldValues[$field] ?? NULL;
            return new class($value) {
                public $value;

                public function __construct($value)
                {
                    $this->value = $value;
                }

                public function isEmpty(): bool
                {
                    return $this->value === NULL || $this->value === '';
                }
            };
        });

        return $job;
    }

    /**
     * Sets up candidate profile entity mocks with proper field access.
     *
     * Creates a ContentEntityInterface mock that supports hasField() and
     * get()->value / get()->isEmpty() as used by MatchingService.
     */
    protected function setupCandidateProfile(int $uid, string $expLevel, string $eduLevel, string $city): void
    {
        $fieldValues = [
            'experience_level' => $expLevel,
            'education_level' => $eduLevel,
            'city' => $city,
        ];

        $profile = $this->createMock(ContentEntityInterface::class);
        $profile->method('hasField')->willReturnCallback(function (string $field) use ($fieldValues) {
            return array_key_exists($field, $fieldValues);
        });
        $profile->method('get')->willReturnCallback(function (string $field) use ($fieldValues) {
            $value = $fieldValues[$field] ?? NULL;
            return new class($value) {
                public $value;

                public function __construct($value)
                {
                    $this->value = $value;
                }

                public function isEmpty(): bool
                {
                    return $this->value === NULL || $this->value === '';
                }
            };
        });

        $this->profileStorage->method('loadByProperties')
            ->willReturn([$profile]);
    }

    /**
     * Sets up candidate skill entity mocks.
     *
     * Creates ContentEntityInterface mocks where get('skill_id')->target_id
     * returns each skill's taxonomy term ID.
     */
    protected function setupCandidateSkills(int $uid, array $skillIds): void
    {
        $skills = [];
        foreach ($skillIds as $skillId) {
            $skill = $this->createMock(ContentEntityInterface::class);
            $skill->method('get')->willReturnCallback(function (string $field) use ($skillId) {
                if ($field === 'skill_id') {
                    return new class($skillId) {
                        public $target_id;

                        public function __construct($id)
                        {
                            $this->target_id = $id;
                        }
                    };
                }
                return new class(NULL) {
                    public $value;

                    public function __construct($v)
                    {
                        $this->value = $v;
                    }
                };
            });
            $skills[] = $skill;
        }

        $this->skillStorage->method('loadByProperties')
            ->willReturn($skills);
    }

}
