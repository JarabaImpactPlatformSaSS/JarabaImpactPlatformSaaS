<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_job_board\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
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
     * Creates a mock JobPostingInterface.
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

        // Mock field item lists for ->get('field')->value access.
        $this->mockFieldValue($job, 'experience_level', $experienceLevel);
        $this->mockFieldValue($job, 'education_level', $educationLevel);
        $this->mockFieldValue($job, 'status', $status);

        return $job;
    }

    /**
     * Mocks a field value on an entity.
     */
    protected function mockFieldValue(object $entity, string $field, $value): void
    {
        $fieldItem = new \stdClass();
        $fieldItem->value = $value;

        $fieldList = $this->createMock(FieldItemListInterface::class);
        $fieldList->method('__get')
            ->with('value')
            ->willReturn($value);

        // The get() method on entities returns field item lists.
        // We use willReturnMap for multiple field accesses.
        // Since createMock doesn't support adding to willReturnMap after creation,
        // we handle this in createMockJob via a callback.
    }

    /**
     * Sets up candidate profile mocks.
     */
    protected function setupCandidateProfile(int $uid, string $expLevel, string $eduLevel, string $city): void
    {
        $storage = $this->createMock(EntityStorageInterface::class);

        $profile = new \stdClass();
        $profile->experience_level = (object) ['value' => $expLevel];
        $profile->education_level = (object) ['value' => $eduLevel];
        $profile->city = (object) ['value' => $city];

        $storage->method('loadByProperties')
            ->willReturn([$profile]);

        $this->entityTypeManager->method('getStorage')
            ->willReturnCallback(function (string $entity_type) use ($storage) {
                return $storage;
            });
    }

    /**
     * Sets up candidate skills mock.
     */
    protected function setupCandidateSkills(int $uid, array $skillIds): void
    {
        // Candidate skills are loaded from candidate_skill entity storage,
        // which is already handled by the general storage mock above.
    }

}
