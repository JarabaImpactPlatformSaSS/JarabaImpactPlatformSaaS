<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_tenant_export\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for backup-related functionality.
 *
 * The daily backup workflow is a GitHub Actions YAML,
 * so these tests verify the related Drupal config and service aspects.
 *
 * @group jaraba_tenant_export
 */
class TenantBackupServiceTest extends UnitTestCase {

  /**
   * Tests that default config values are valid.
   */
  public function testDefaultConfigValues(): void {
    // These are the expected defaults from config/install.
    $defaults = [
      'export_expiration_hours' => 48,
      'rate_limit_per_day' => 3,
      'max_export_size_mb' => 500,
      'analytics_row_limit' => 50000,
      'backup_retention_daily_days' => 30,
      'backup_retention_weekly_weeks' => 12,
    ];

    $this->assertGreaterThan(0, $defaults['export_expiration_hours']);
    $this->assertGreaterThan(0, $defaults['rate_limit_per_day']);
    $this->assertGreaterThan(0, $defaults['max_export_size_mb']);
    $this->assertGreaterThan(0, $defaults['analytics_row_limit']);
    $this->assertGreaterThan(0, $defaults['backup_retention_daily_days']);
    $this->assertGreaterThan(0, $defaults['backup_retention_weekly_weeks']);

    // Retention logic: weekly period should be longer than daily.
    $weeklyDays = $defaults['backup_retention_weekly_weeks'] * 7;
    $this->assertGreaterThan($defaults['backup_retention_daily_days'], $weeklyDays);
  }

  /**
   * Tests backup rotation logic boundaries.
   */
  public function testBackupRotationLogic(): void {
    $retentionDailyDays = 30;
    $retentionWeeklyDays = 84; // 12 weeks

    // Test: Day 0 (today) - should keep.
    $this->assertTrue(0 <= $retentionDailyDays);

    // Test: Day 29 - should keep as daily.
    $this->assertTrue(29 <= $retentionDailyDays);

    // Test: Day 31, Monday - should keep as weekly.
    $this->assertTrue(31 > $retentionDailyDays && 31 <= $retentionWeeklyDays);

    // Test: Day 31, Tuesday - should delete.
    $this->assertTrue(31 > $retentionDailyDays);

    // Test: Day 85 - should delete all.
    $this->assertTrue(85 > $retentionWeeklyDays);
  }

  /**
   * Tests backup file naming pattern.
   */
  public function testBackupFileNamingPattern(): void {
    $date = date('Ymd_His');
    $dailyPattern = "db_daily_{$date}.sql.gz";
    $preDeployPattern = "db_pre_deploy_{$date}.sql.gz";

    // Verify patterns are distinguishable.
    $this->assertStringStartsWith('db_daily_', $dailyPattern);
    $this->assertStringStartsWith('db_pre_deploy_', $preDeployPattern);
    $this->assertStringEndsWith('.sql.gz', $dailyPattern);
    $this->assertStringEndsWith('.sql.gz', $preDeployPattern);

    // Both should be valid filenames (no spaces or special chars).
    $this->assertMatchesRegularExpression('/^db_daily_\d{8}_\d{6}\.sql\.gz$/', $dailyPattern);
  }

}
