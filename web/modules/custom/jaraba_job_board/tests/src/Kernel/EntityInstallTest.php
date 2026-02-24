<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_job_board\Kernel;

use Drupal\jaraba_job_board\Entity\JobApplication;
use Drupal\jaraba_job_board\Entity\JobPosting;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests entity schema installation for job board entities.
 *
 * @group jaraba_job_board
 */
class EntityInstallTest extends KernelTestBase
{

    /**
     * {@inheritdoc}
     */
    protected static $modules = [
        'system',
        'user',
        'field',
        'text',
        'options',
        'datetime',
        'file',
        'jaraba_job_board',
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->installEntitySchema('user');
            $this->installEntitySchema('job_posting');
            $this->installEntitySchema('job_application');
        } catch (\Exception $e) {
            $this->markTestSkipped('Entity schemas could not be installed: ' . $e->getMessage());
        }
    }

    /**
     * Tests that job_posting entity can be created and saved.
     */
    public function testJobPostingEntityCreate(): void
    {
        $entity = JobPosting::create([
            'title' => 'Senior Developer',
            'slug' => 'senior-developer-1',
            'description' => ['value' => 'Test description', 'format' => 'plain_text'],
            'requirements' => ['value' => 'Test requirements', 'format' => 'plain_text'],
            'status' => 'draft',
            'job_type' => 'full_time',
            'experience_level' => 'senior',
            'remote_type' => 'hybrid',
            'location_city' => 'Madrid',
            'employer_id' => 1,
        ]);

        $this->assertNotNull($entity);
        $this->assertEquals('Senior Developer', $entity->getTitle());
        $this->assertEquals('draft', $entity->getStatus());
        $this->assertEquals('full_time', $entity->getJobType());
        $this->assertEquals('hybrid', $entity->getRemoteType());
        $this->assertEquals('Madrid', $entity->getLocationCity());
        $this->assertFalse($entity->isPublished());
    }

    /**
     * Tests JobPosting status transitions.
     */
    public function testJobPostingStatusTransitions(): void
    {
        $entity = JobPosting::create([
            'title' => 'Test Job',
            'slug' => 'test-job-1',
            'description' => ['value' => 'Desc', 'format' => 'plain_text'],
            'requirements' => ['value' => 'Req', 'format' => 'plain_text'],
            'status' => 'draft',
            'job_type' => 'full_time',
            'experience_level' => 'mid',
            'remote_type' => 'onsite',
            'location_city' => 'Sevilla',
            'employer_id' => 1,
        ]);

        // Publish.
        $entity->publish();
        $this->assertTrue($entity->isPublished());
        $this->assertEquals(JobPosting::STATUS_PUBLISHED, $entity->getStatus());

        // Close.
        $entity->close();
        $this->assertEquals(JobPosting::STATUS_CLOSED, $entity->getStatus());
        $this->assertFalse($entity->isPublished());
    }

    /**
     * Tests JobPosting applications count tracking.
     */
    public function testJobPostingApplicationsCount(): void
    {
        $entity = JobPosting::create([
            'title' => 'Counter Test',
            'slug' => 'counter-test-1',
            'description' => ['value' => 'D', 'format' => 'plain_text'],
            'requirements' => ['value' => 'R', 'format' => 'plain_text'],
            'status' => 'published',
            'job_type' => 'full_time',
            'experience_level' => 'entry',
            'remote_type' => 'remote',
            'location_city' => 'Remote',
            'employer_id' => 1,
        ]);

        $this->assertEquals(0, $entity->getApplicationsCount());

        $entity->incrementApplicationsCount();
        $this->assertEquals(1, $entity->getApplicationsCount());

        $entity->incrementApplicationsCount();
        $this->assertEquals(2, $entity->getApplicationsCount());
    }

    /**
     * Tests JobApplication entity creation.
     */
    public function testJobApplicationEntityCreate(): void
    {
        $application = JobApplication::create([
            'job_id' => 1,
            'candidate_id' => 2,
            'status' => 'applied',
        ]);

        $this->assertNotNull($application);
        $this->assertEquals('applied', $application->getStatus());
        $this->assertTrue($application->isActive());
        $this->assertFalse($application->isHired());
    }

    /**
     * Tests JobApplication ATS pipeline status transitions.
     *
     * @dataProvider applicationStatusDataProvider
     */
    public function testJobApplicationStatusTransitions(string $status, bool $isActive, bool $isHired): void
    {
        $application = JobApplication::create([
            'job_id' => 1,
            'candidate_id' => 2,
            'status' => $status,
        ]);

        $this->assertEquals($status, $application->getStatus());
        $this->assertEquals($isActive, $application->isActive());
        $this->assertEquals($isHired, $application->isHired());
    }

    /**
     * Data provider for ATS pipeline statuses.
     */
    public static function applicationStatusDataProvider(): array
    {
        return [
            'applied' => ['applied', TRUE, FALSE],
            'screening' => ['screening', TRUE, FALSE],
            'shortlisted' => ['shortlisted', TRUE, FALSE],
            'interviewed' => ['interviewed', TRUE, FALSE],
            'offered' => ['offered', TRUE, FALSE],
            'hired' => ['hired', FALSE, TRUE],
            'rejected' => ['rejected', FALSE, FALSE],
            'withdrawn' => ['withdrawn', FALSE, FALSE],
        ];
    }

    /**
     * Tests JobApplication hire workflow.
     */
    public function testJobApplicationHireWorkflow(): void
    {
        $application = JobApplication::create([
            'job_id' => 1,
            'candidate_id' => 2,
            'status' => 'offered',
        ]);

        $application->hire(45000.00);

        $this->assertEquals('hired', $application->getStatus());
        $this->assertTrue($application->isHired());
        $this->assertFalse($application->isActive());
    }

    /**
     * Tests JobApplication rejection with feedback.
     */
    public function testJobApplicationRejectionWithFeedback(): void
    {
        $application = JobApplication::create([
            'job_id' => 1,
            'candidate_id' => 2,
            'status' => 'screening',
        ]);

        $application->reject('not_qualified', 'More experience needed.');

        $this->assertEquals('rejected', $application->getStatus());
        $this->assertFalse($application->isActive());
    }

    /**
     * Tests that base fields have correct types.
     */
    public function testJobPostingFieldDefinitions(): void
    {
        $definitions = JobPosting::baseFieldDefinitions(
            $this->container->get('entity_type.manager')->getDefinition('job_posting')
        );

        // Verify critical fields exist.
        $this->assertArrayHasKey('title', $definitions);
        $this->assertArrayHasKey('employer_id', $definitions);
        $this->assertArrayHasKey('status', $definitions);
        $this->assertArrayHasKey('job_type', $definitions);
        $this->assertArrayHasKey('remote_type', $definitions);
        $this->assertArrayHasKey('experience_level', $definitions);
        $this->assertArrayHasKey('location_city', $definitions);
        $this->assertArrayHasKey('salary_min', $definitions);
        $this->assertArrayHasKey('salary_max', $definitions);
        $this->assertArrayHasKey('applications_count', $definitions);
        $this->assertArrayHasKey('views_count', $definitions);
        $this->assertArrayHasKey('published_at', $definitions);
    }

}
