<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_mentoring\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jaraba_mentoring\Entity\MentorProfile;
use Drupal\jaraba_mentoring\Service\MentorMatchingService;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the MentorMatchingService.
 *
 * Verifies the matching algorithm, weight factors, scoring logic,
 * and certification bonus calculations for mentor-entrepreneur matching.
 *
 * @coversDefaultClass \Drupal\jaraba_mentoring\Service\MentorMatchingService
 * @group jaraba_mentoring
 */
class MentorMatchingServiceTest extends UnitTestCase {

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_mentoring\Service\MentorMatchingService
   */
  protected MentorMatchingService $service;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * Mock database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->database = $this->createMock(Connection::class);

    $this->service = new MentorMatchingService(
      $this->entityTypeManager,
      $this->database,
    );
  }

  /**
   * Tests that the service can be instantiated.
   *
   * @covers ::__construct
   */
  public function testServiceExists(): void {
    $this->assertInstanceOf(MentorMatchingService::class, $this->service);
  }

  /**
   * Tests that findMatches returns an empty array when no mentors are found.
   *
   * @covers ::findMatches
   */
  public function testFindMatchesReturnsEmptyWhenNoMentors(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('mentor_profile')
      ->willReturn($storage);

    $result = $this->service->findMatches(['sector' => 'tech']);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Tests that findMatches returns scored results when mentors exist.
   *
   * @covers ::findMatches
   */
  public function testFindMatchesReturnsResults(): void {
    $mentor = $this->createMockMentor(
      sectors: ['tech'],
      stages: ['idea'],
      specializations: ['plan_negocio'],
      rating: 4.5,
      hourlyRate: 50.0,
      certificationLevel: 'certified',
      isAvailable: TRUE,
    );

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([1]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')->with([1])->willReturn([1 => $mentor]);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('mentor_profile')
      ->willReturn($storage);

    $criteria = [
      'sector' => 'tech',
      'stage' => 'idea',
      'specializations' => ['plan_negocio'],
      'max_price' => 100,
    ];

    $result = $this->service->findMatches($criteria);

    $this->assertIsArray($result);
    $this->assertCount(1, $result);
    $this->assertArrayHasKey('mentor', $result[0]);
    $this->assertArrayHasKey('score', $result[0]);
    $this->assertArrayHasKey('breakdown', $result[0]);
    $this->assertIsFloat($result[0]['score']);
    $this->assertGreaterThan(0, $result[0]['score']);
  }

  /**
   * Tests that the weight factors sum to 1.0.
   *
   * The matching algorithm uses weighted scoring; the weights
   * must sum to 1.0 for the score to be meaningful.
   */
  public function testWeightFactorsSumToOne(): void {
    $reflection = new \ReflectionClass(MentorMatchingService::class);
    $property = $reflection->getReflectionConstant('WEIGHTS');
    $weights = $property->getValue();

    $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.001,
      'Weight factors should sum to 1.0.');
  }

  /**
   * Tests the defined weight proportions.
   *
   * Verifies the documented weights:
   * - sector: 25%
   * - stage: 20%
   * - specialization: 20%
   * - rating: 15%
   * - availability: 10%
   * - price: 10%
   */
  public function testWeightFactorValues(): void {
    $reflection = new \ReflectionClass(MentorMatchingService::class);
    $weights = $reflection->getReflectionConstant('WEIGHTS')->getValue();

    $this->assertEquals(0.25, $weights['sector'], 'Sector weight should be 25%.');
    $this->assertEquals(0.20, $weights['stage'], 'Stage weight should be 20%.');
    $this->assertEquals(0.20, $weights['specialization'], 'Specialization weight should be 20%.');
    $this->assertEquals(0.15, $weights['rating'], 'Rating weight should be 15%.');
    $this->assertEquals(0.10, $weights['availability'], 'Availability weight should be 10%.');
    $this->assertEquals(0.10, $weights['price'], 'Price weight should be 10%.');
  }

  /**
   * Tests sector scoring: perfect match returns 100.
   *
   * @covers ::calculateSectorScore
   */
  public function testSectorScorePerfectMatch(): void {
    $mentor = $this->createMockMentor(sectors: ['tech', 'servicios']);
    $criteria = ['sector' => 'tech'];

    $score = $this->invokeProtectedMethod('calculateSectorScore', [$mentor, $criteria]);
    $this->assertEquals(100, $score);
  }

  /**
   * Tests sector scoring: no match returns 20.
   *
   * @covers ::calculateSectorScore
   */
  public function testSectorScoreNoMatch(): void {
    $mentor = $this->createMockMentor(sectors: ['hosteleria']);
    $criteria = ['sector' => 'tech'];

    $score = $this->invokeProtectedMethod('calculateSectorScore', [$mentor, $criteria]);
    $this->assertEquals(20, $score);
  }

  /**
   * Tests sector scoring: no criteria returns 50 (neutral).
   *
   * @covers ::calculateSectorScore
   */
  public function testSectorScoreNoCriteria(): void {
    $mentor = $this->createMockMentor(sectors: ['tech']);
    $criteria = [];

    $score = $this->invokeProtectedMethod('calculateSectorScore', [$mentor, $criteria]);
    $this->assertEquals(50, $score);
  }

  /**
   * Tests stage scoring: perfect match returns 100.
   *
   * @covers ::calculateStageScore
   */
  public function testStageScorePerfectMatch(): void {
    $mentor = $this->createMockMentor(stages: ['idea', 'validacion']);
    $criteria = ['stage' => 'idea'];

    $score = $this->invokeProtectedMethod('calculateStageScore', [$mentor, $criteria]);
    $this->assertEquals(100, $score);
  }

  /**
   * Tests stage scoring: no match returns 20.
   *
   * @covers ::calculateStageScore
   */
  public function testStageScoreNoMatch(): void {
    $mentor = $this->createMockMentor(stages: ['escalado']);
    $criteria = ['stage' => 'idea'];

    $score = $this->invokeProtectedMethod('calculateStageScore', [$mentor, $criteria]);
    $this->assertEquals(20, $score);
  }

  /**
   * Tests specialization scoring with partial overlap.
   *
   * @covers ::calculateSpecializationScore
   */
  public function testSpecializationScorePartialMatch(): void {
    $mentor = $this->createMockMentor(specializations: ['plan_negocio', 'finanzas']);
    $criteria = ['specializations' => ['plan_negocio', 'marketing', 'legal']];

    $score = $this->invokeProtectedMethod('calculateSpecializationScore', [$mentor, $criteria]);
    // 1 out of 3 requested = 33.33%.
    $this->assertEqualsWithDelta(33.33, $score, 0.5);
  }

  /**
   * Tests specialization scoring with full match.
   *
   * @covers ::calculateSpecializationScore
   */
  public function testSpecializationScoreFullMatch(): void {
    $mentor = $this->createMockMentor(specializations: ['plan_negocio', 'marketing']);
    $criteria = ['specializations' => ['plan_negocio', 'marketing']];

    $score = $this->invokeProtectedMethod('calculateSpecializationScore', [$mentor, $criteria]);
    $this->assertEquals(100, $score);
  }

  /**
   * Tests specialization scoring: no criteria returns 50.
   *
   * @covers ::calculateSpecializationScore
   */
  public function testSpecializationScoreNoCriteria(): void {
    $mentor = $this->createMockMentor(specializations: ['plan_negocio']);
    $criteria = [];

    $score = $this->invokeProtectedMethod('calculateSpecializationScore', [$mentor, $criteria]);
    $this->assertEquals(50, $score);
  }

  /**
   * Tests rating score calculation.
   *
   * A 5-star rating should produce 100, 0 should produce 0.
   *
   * @covers ::calculateRatingScore
   */
  public function testRatingScoreCalculation(): void {
    // Perfect rating.
    $mentor5 = $this->createMockMentor(rating: 5.0);
    $score5 = $this->invokeProtectedMethod('calculateRatingScore', [$mentor5]);
    $this->assertEquals(100, $score5);

    // Zero rating.
    $mentor0 = $this->createMockMentor(rating: 0.0);
    $score0 = $this->invokeProtectedMethod('calculateRatingScore', [$mentor0]);
    $this->assertEquals(0, $score0);

    // Mid rating.
    $mentor3 = $this->createMockMentor(rating: 3.0);
    $score3 = $this->invokeProtectedMethod('calculateRatingScore', [$mentor3]);
    $this->assertEquals(60, $score3);
  }

  /**
   * Tests price score for different rate brackets.
   *
   * @covers ::calculatePriceScore
   * @dataProvider priceScoreDataProvider
   */
  public function testPriceScoreCalculation(float $rate, float $maxPrice, float $expectedScore): void {
    $mentor = $this->createMockMentor(hourlyRate: $rate);
    $criteria = ['max_price' => $maxPrice];

    $score = $this->invokeProtectedMethod('calculatePriceScore', [$mentor, $criteria]);
    $this->assertEquals($expectedScore, $score);
  }

  /**
   * Data provider for price score tests.
   */
  public static function priceScoreDataProvider(): array {
    return [
      'rate at 50% of max => 100' => [25.0, 100.0, 100],
      'rate at exactly 50% of max => 100' => [50.0, 100.0, 100],
      'rate at 60% of max => 75' => [60.0, 100.0, 75],
      'rate at 75% of max => 75' => [75.0, 100.0, 75],
      'rate at 80% of max => 50' => [80.0, 100.0, 50],
      'rate at exactly max => 50' => [100.0, 100.0, 50],
      'rate exceeds max => 0' => [120.0, 100.0, 0],
    ];
  }

  /**
   * Tests price score when no max_price is specified.
   *
   * @covers ::calculatePriceScore
   */
  public function testPriceScoreWithNoMaxPrice(): void {
    $mentor = $this->createMockMentor(hourlyRate: 50.0);
    $criteria = [];

    $score = $this->invokeProtectedMethod('calculatePriceScore', [$mentor, $criteria]);
    $this->assertEquals(50, $score, 'No max_price criteria should return neutral score of 50.');
  }

  /**
   * Tests certification bonus values.
   *
   * @covers ::getCertificationBonus
   * @dataProvider certificationBonusDataProvider
   */
  public function testCertificationBonus(string $level, float $expectedBonus): void {
    $mentor = $this->createMockMentor(certificationLevel: $level);

    $bonus = $this->invokeProtectedMethod('getCertificationBonus', [$mentor]);
    $this->assertEquals($expectedBonus, $bonus);
  }

  /**
   * Data provider for certification bonus tests.
   */
  public static function certificationBonusDataProvider(): array {
    return [
      'elite => 10 points' => ['elite', 10.0],
      'premium => 7 points' => ['premium', 7.0],
      'certified => 5 points' => ['certified', 5.0],
      'base => 0 points' => ['base', 0.0],
      'unknown => 0 points' => ['unknown_level', 0.0],
    ];
  }

  /**
   * Tests that the total score is capped at 100.
   *
   * Even with a high certification bonus, score should not exceed 100.
   *
   * @covers ::calculateMatchScore
   */
  public function testScoreIsCappedAt100(): void {
    // Create a mentor that would score perfectly on all dimensions
    // plus an elite certification bonus.
    $mentor = $this->createMockMentor(
      sectors: ['tech'],
      stages: ['idea'],
      specializations: ['plan_negocio'],
      rating: 5.0,
      hourlyRate: 10.0,
      certificationLevel: 'elite',
      isAvailable: TRUE,
    );

    $criteria = [
      'sector' => 'tech',
      'stage' => 'idea',
      'specializations' => ['plan_negocio'],
      'max_price' => 100,
    ];

    $score = $this->invokeProtectedMethod('calculateMatchScore', [$mentor, $criteria]);
    $this->assertLessThanOrEqual(100, $score, 'Score should be capped at 100.');
  }

  /**
   * Tests findMatches respects the limit parameter.
   *
   * @covers ::findMatches
   */
  public function testFindMatchesRespectsLimit(): void {
    $mentors = [];
    for ($i = 1; $i <= 5; $i++) {
      $mentors[$i] = $this->createMockMentor(
        sectors: ['tech'],
        stages: ['idea'],
        specializations: [],
        rating: (float) $i,
        hourlyRate: 50.0,
        certificationLevel: 'base',
        isAvailable: TRUE,
      );
    }

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn(array_keys($mentors));

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')->willReturn($mentors);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('mentor_profile')
      ->willReturn($storage);

    $result = $this->service->findMatches(['sector' => 'tech'], 3);

    $this->assertCount(3, $result, 'findMatches should respect the limit parameter.');
  }

  /**
   * Tests that findMatches sorts results by score descending.
   *
   * @covers ::findMatches
   */
  public function testFindMatchesSortsByScoreDescending(): void {
    // Mentor with low rating.
    $mentorLow = $this->createMockMentor(
      sectors: ['otros'],
      stages: ['escalado'],
      specializations: [],
      rating: 1.0,
      hourlyRate: 200.0,
      certificationLevel: 'base',
      isAvailable: TRUE,
    );

    // Mentor with high rating and matching sector.
    $mentorHigh = $this->createMockMentor(
      sectors: ['tech'],
      stages: ['idea'],
      specializations: ['plan_negocio'],
      rating: 5.0,
      hourlyRate: 30.0,
      certificationLevel: 'elite',
      isAvailable: TRUE,
    );

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')->willReturn([1 => $mentorLow, 2 => $mentorHigh]);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('mentor_profile')
      ->willReturn($storage);

    $criteria = [
      'sector' => 'tech',
      'stage' => 'idea',
      'specializations' => ['plan_negocio'],
      'max_price' => 100,
    ];

    $result = $this->service->findMatches($criteria);

    $this->assertCount(2, $result);
    $this->assertGreaterThan(
      $result[1]['score'],
      $result[0]['score'],
      'Results should be sorted by score descending.'
    );
  }

  /**
   * Tests the score breakdown contains all expected keys.
   *
   * @covers ::getScoreBreakdown
   */
  public function testScoreBreakdownKeys(): void {
    $mentor = $this->createMockMentor(
      sectors: ['tech'],
      stages: ['idea'],
      specializations: [],
      rating: 4.0,
      hourlyRate: 50.0,
      certificationLevel: 'certified',
      isAvailable: TRUE,
    );

    $criteria = [
      'sector' => 'tech',
      'stage' => 'idea',
      'max_price' => 100,
    ];

    $breakdown = $this->invokeProtectedMethod('getScoreBreakdown', [$mentor, $criteria]);

    $expectedKeys = ['sector', 'stage', 'specialization', 'rating', 'availability', 'price', 'certification_bonus'];
    foreach ($expectedKeys as $key) {
      $this->assertArrayHasKey($key, $breakdown, "Breakdown should contain key: {$key}");
      $this->assertIsFloat($breakdown[$key], "Breakdown '{$key}' should be a float.");
    }
  }

  /**
   * Creates a mock MentorProfile with configurable properties.
   *
   * @param array $sectors
   *   Sectors the mentor covers.
   * @param array $stages
   *   Business stages the mentor supports.
   * @param array $specializations
   *   Mentor specializations.
   * @param float $rating
   *   Average rating (0-5).
   * @param float $hourlyRate
   *   Hourly rate in EUR.
   * @param string $certificationLevel
   *   Certification level.
   * @param bool $isAvailable
   *   Whether the mentor is available.
   *
   * @return \Drupal\jaraba_mentoring\Entity\MentorProfile|\PHPUnit\Framework\MockObject\MockObject
   *   The mock mentor profile.
   */
  protected function createMockMentor(
    array $sectors = [],
    array $stages = [],
    array $specializations = [],
    float $rating = 0.0,
    float $hourlyRate = 0.0,
    string $certificationLevel = 'base',
    bool $isAvailable = TRUE,
  ): MentorProfile {
    $mentor = $this->createMock(MentorProfile::class);

    // Mock field value lists for sectors.
    $sectorValues = array_map(fn($s) => ['value' => $s], $sectors);
    $sectorsField = $this->createMock(FieldItemListInterface::class);
    $sectorsField->method('getValue')->willReturn($sectorValues);

    // Mock field value lists for stages.
    $stageValues = array_map(fn($s) => ['value' => $s], $stages);
    $stagesField = $this->createMock(FieldItemListInterface::class);
    $stagesField->method('getValue')->willReturn($stageValues);

    // Mock field value lists for specializations.
    $specValues = array_map(fn($s) => ['value' => $s], $specializations);
    $specsField = $this->createMock(FieldItemListInterface::class);
    $specsField->method('getValue')->willReturn($specValues);

    // Mock availability_slots field.
    $availabilitySlotsField = $this->createMock(FieldItemListInterface::class);
    $availabilitySlotsField->method('isEmpty')->willReturn(TRUE);

    $mentor->method('get')->willReturnCallback(function ($fieldName) use ($sectorsField, $stagesField, $specsField, $availabilitySlotsField) {
      return match ($fieldName) {
        'sectors' => $sectorsField,
        'business_stages' => $stagesField,
        'specializations' => $specsField,
        'availability_slots' => $availabilitySlotsField,
        default => $this->createMock(FieldItemListInterface::class),
      };
    });

    $mentor->method('hasField')->willReturnCallback(function ($fieldName) {
      return in_array($fieldName, ['sectors', 'business_stages', 'specializations', 'availability_slots']);
    });

    $mentor->method('getAverageRating')->willReturn($rating);
    $mentor->method('getHourlyRate')->willReturn($hourlyRate);
    $mentor->method('getCertificationLevel')->willReturn($certificationLevel);
    $mentor->method('isAvailable')->willReturn($isAvailable);

    return $mentor;
  }

  /**
   * Invokes a protected method on the service for testing.
   *
   * @param string $methodName
   *   The method name.
   * @param array $args
   *   The arguments to pass.
   *
   * @return mixed
   *   The method return value.
   */
  protected function invokeProtectedMethod(string $methodName, array $args = []): mixed {
    $reflection = new \ReflectionClass(MentorMatchingService::class);
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(TRUE);
    return $method->invokeArgs($this->service, $args);
  }

}
