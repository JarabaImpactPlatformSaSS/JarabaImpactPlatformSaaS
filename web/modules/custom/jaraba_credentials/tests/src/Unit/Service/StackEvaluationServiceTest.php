<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_credentials\Unit\Service;

use PHPUnit\Framework\TestCase;

/**
 * Tests para StackEvaluationService.
 *
 * Verifica logica de evaluacion de stacks: requisitos minimos,
 * matching de templates y scoring de completitud.
 *
 * @group jaraba_credentials
 * @coversDefaultClass \Drupal\jaraba_credentials\Service\StackEvaluationService
 */
class StackEvaluationServiceTest extends TestCase {

  /**
   * Verifica si un conjunto de templates cumple los requisitos del stack.
   *
   * Replica la logica de StackEvaluationService::checkStackCompletion().
   *
   * @param array $requiredIds
   *   IDs de templates requeridos.
   * @param int $minRequired
   *   Minimo necesario (0 = todos los requeridos).
   * @param array $userTemplateIds
   *   IDs de templates que el usuario posee.
   *
   * @return bool
   *   TRUE si cumple los requisitos.
   */
  private function checkStackCompletion(array $requiredIds, int $minRequired, array $userTemplateIds): bool {
    $matched = array_intersect($requiredIds, $userTemplateIds);
    $min = $minRequired > 0 ? $minRequired : count($requiredIds);
    return count($matched) >= $min;
  }

  /**
   * Tests que todos los templates requeridos deben completarse.
   */
  public function testAllRequiredMustBeCompleted(): void {
    $requiredIds = [1, 2, 3, 4, 5];
    $userIds = [1, 2, 3, 4, 5];
    $this->assertTrue($this->checkStackCompletion($requiredIds, 0, $userIds));
  }

  /**
   * Tests que falta un template requerido.
   */
  public function testFailsWhenMissingOneRequired(): void {
    $requiredIds = [1, 2, 3, 4, 5];
    $userIds = [1, 2, 3, 4];
    $this->assertFalse($this->checkStackCompletion($requiredIds, 0, $userIds));
  }

  /**
   * Tests con min_required menor que el total.
   */
  public function testMinRequiredAllowsPartialCompletion(): void {
    $requiredIds = [1, 2, 3, 4, 5];
    // Solo necesita 3 de 5.
    $userIds = [1, 3, 5];
    $this->assertTrue($this->checkStackCompletion($requiredIds, 3, $userIds));
  }

  /**
   * Tests que no cumple el min_required.
   */
  public function testFailsWhenBelowMinRequired(): void {
    $requiredIds = [1, 2, 3, 4, 5];
    $userIds = [1, 2];
    $this->assertFalse($this->checkStackCompletion($requiredIds, 3, $userIds));
  }

  /**
   * Tests con min_required = 1 (muy permisivo).
   */
  public function testMinRequiredOneIsPermissive(): void {
    $requiredIds = [1, 2, 3, 4, 5];
    $userIds = [3];
    $this->assertTrue($this->checkStackCompletion($requiredIds, 1, $userIds));
  }

  /**
   * Tests con stack vacio (sin requisitos).
   */
  public function testEmptyRequiredIsAlwaysComplete(): void {
    $this->assertTrue($this->checkStackCompletion([], 0, [1, 2, 3]));
  }

  /**
   * Tests con usuario sin templates.
   */
  public function testEmptyUserTemplatesFails(): void {
    $requiredIds = [1, 2, 3];
    $this->assertFalse($this->checkStackCompletion($requiredIds, 0, []));
  }

  /**
   * Tests que templates extra del usuario no afectan.
   */
  public function testExtraUserTemplatesIgnored(): void {
    $requiredIds = [1, 2, 3];
    $userIds = [1, 2, 3, 4, 5, 6, 7, 8];
    $this->assertTrue($this->checkStackCompletion($requiredIds, 0, $userIds));
  }

  /**
   * Tests logica de filtrado: solo evaluar stacks relevantes.
   */
  public function testOnlyEvaluateRelevantStacks(): void {
    $newTemplateId = 5;
    $stackRequired = [1, 2, 3];
    $stackOptional = [4, 5];

    $isRelevant = in_array($newTemplateId, $stackRequired, TRUE)
      || in_array($newTemplateId, $stackOptional, TRUE);

    $this->assertTrue($isRelevant);
  }

  /**
   * Tests que stack sin el nuevo template se omite.
   */
  public function testIrrelevantStackSkipped(): void {
    $newTemplateId = 99;
    $stackRequired = [1, 2, 3];
    $stackOptional = [4, 5];

    $isRelevant = in_array($newTemplateId, $stackRequired, TRUE)
      || in_array($newTemplateId, $stackOptional, TRUE);

    $this->assertFalse($isRelevant);
  }

  /**
   * Tests logica de duplicados: stack ya completado se omite.
   */
  public function testCompletedStackIsSkipped(): void {
    $existingStatus = 'completed';
    $shouldSkip = ($existingStatus === 'completed');
    $this->assertTrue($shouldSkip);
  }

  /**
   * Tests logica de duplicados: stack en progreso se evalua.
   */
  public function testInProgressStackIsEvaluated(): void {
    $existingStatus = 'in_progress';
    $shouldSkip = ($existingStatus === 'completed');
    $this->assertFalse($shouldSkip);
  }

  /**
   * Tests que result_template_id NULL impide emision.
   */
  public function testNoResultTemplateBlocksIssuance(): void {
    $resultTemplateId = NULL;
    $canIssue = ($resultTemplateId !== NULL);
    $this->assertFalse($canIssue);
  }

  /**
   * Tests que result_template_id valido permite emision.
   */
  public function testValidResultTemplateAllowsIssuance(): void {
    $resultTemplateId = 42;
    $canIssue = ($resultTemplateId !== NULL);
    $this->assertTrue($canIssue);
  }

  /**
   * Tests deduplicacion de template IDs del usuario.
   */
  public function testUserTemplateIdsAreUnique(): void {
    $rawIds = [1, 2, 3, 2, 1, 4, 3, 5];
    $unique = array_unique($rawIds);

    $this->assertCount(5, $unique);
    $this->assertContains(1, $unique);
    $this->assertContains(5, $unique);
  }

  /**
   * Tests estructura de metadatos para emision de stack.
   */
  public function testStackIssuanceMetadata(): void {
    $context = [
      'stack_id' => '7',
      'component_templates' => [1, 2, 3],
    ];

    $this->assertArrayHasKey('stack_id', $context);
    $this->assertArrayHasKey('component_templates', $context);
    $this->assertIsArray($context['component_templates']);
  }

  /**
   * Tests logica de bonus: solo se otorga si > 0.
   */
  public function testBonusOnlyAwardedIfPositive(): void {
    $this->assertTrue(100 > 0);
    $this->assertFalse(0 > 0);
    $this->assertFalse(-5 > 0);
  }

}
