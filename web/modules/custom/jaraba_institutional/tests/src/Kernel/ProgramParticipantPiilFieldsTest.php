<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_institutional\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests PIIL fields on ProgramParticipant entity.
 *
 * @group jaraba_institutional
 */
class ProgramParticipantPiilFieldsTest extends KernelTestBase {

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
    'views',
    'flexible_permissions',
    'group',
    'ecosistema_jaraba_core',
    'jaraba_institutional',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
  }

  /**
   * Tests that PIIL fields exist in base field definitions.
   */
  public function testPiilFieldsExist(): void {
    $fields = \Drupal::service('entity_field.manager')
      ->getBaseFieldDefinitions('program_participant');

    $expectedFields = [
      'piil_registration_number',
      'fundae_group_id',
      'fse_plus_indicator',
      'certification_date',
      'certification_type',
      'digital_skills_level',
      'employment_sector',
    ];

    foreach ($expectedFields as $fieldName) {
      $this->assertArrayHasKey($fieldName, $fields, "Field {$fieldName} should exist on ProgramParticipant.");
    }
  }

  /**
   * Tests that PIIL fields exist on InstitutionalProgram.
   */
  public function testInstitutionalProgramPiilFieldsExist(): void {
    $fields = \Drupal::service('entity_field.manager')
      ->getBaseFieldDefinitions('institutional_program');

    $expectedFields = [
      'piil_program_code',
      'fundae_action_id',
      'fse_plus_priority_axis',
      'cofinancing_rate',
      'target_employment_rate',
      'reporting_frequency',
    ];

    foreach ($expectedFields as $fieldName) {
      $this->assertArrayHasKey($fieldName, $fields, "Field {$fieldName} should exist on InstitutionalProgram.");
    }
  }

}
