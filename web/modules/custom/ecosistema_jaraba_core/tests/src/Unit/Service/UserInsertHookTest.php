<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\ecosistema_jaraba_core\ValueObject\AvatarDetectionResult;
use Drupal\Tests\UnitTestCase;

/**
 * Tests para hook_user_insert y AvatarDetectionResult.
 *
 * Verifica la logica de deteccion de avatar y creacion del
 * value object durante el registro de usuarios.
 *
 * @group ecosistema_jaraba_core
 */
class UserInsertHookTest extends UnitTestCase {

  /**
   * Test 1: AvatarDetectionResult default tiene valores correctos.
   */
  public function testDefaultResultHasCorrectValues(): void {
    $result = AvatarDetectionResult::createDefault();

    $this->assertEquals('general', $result->avatarType);
    $this->assertNull($result->vertical);
    $this->assertEquals('default', $result->detectionSource);
    $this->assertNull($result->programaOrigen);
    $this->assertEquals(0.1, $result->confidence);
    $this->assertFalse($result->isDetected());
  }

  /**
   * Test 2: AvatarDetectionResult detectado tiene isDetected=true.
   */
  public function testDetectedResultIsDetected(): void {
    $result = new AvatarDetectionResult(
      avatarType: 'jobseeker',
      vertical: 'empleabilidad',
      detectionSource: 'role',
      programaOrigen: NULL,
      confidence: 0.6,
    );

    $this->assertTrue($result->isDetected());
    $this->assertEquals('jobseeker', $result->avatarType);
    $this->assertEquals('empleabilidad', $result->vertical);
  }

  /**
   * Test 3: toArray serializa correctamente.
   */
  public function testToArraySerializesCorrectly(): void {
    $result = new AvatarDetectionResult(
      avatarType: 'entrepreneur',
      vertical: 'emprendimiento',
      detectionSource: 'utm',
      programaOrigen: 'Emprende Andalucia',
      confidence: 0.9,
    );

    $array = $result->toArray();

    $this->assertEquals('entrepreneur', $array['avatar_type']);
    $this->assertEquals('emprendimiento', $array['vertical']);
    $this->assertEquals('utm', $array['detection_source']);
    $this->assertEquals('Emprende Andalucia', $array['programa_origen']);
    $this->assertEquals(0.9, $array['confidence']);
    $this->assertTrue($array['is_detected']);
  }

}
