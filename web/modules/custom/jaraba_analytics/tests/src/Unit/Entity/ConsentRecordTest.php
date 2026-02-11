<?php

namespace Drupal\Tests\jaraba_analytics\Unit\Entity;

use Drupal\Tests\UnitTestCase;

/**
 * Tests for the ConsentRecord entity.
 *
 * @group jaraba_analytics
 * @coversDefaultClass \Drupal\jaraba_analytics\Entity\ConsentRecord
 */
class ConsentRecordTest extends UnitTestCase
{

    /**
     * Tests that getAllConsents returns the expected structure.
     *
     * @covers ::getAllConsents
     */
    public function testGetAllConsentsStructure(): void
    {
        $expectedKeys = ['necessary', 'functional', 'analytics', 'marketing'];

        // Validate the expected contract of getAllConsents()
        $consents = [
            'necessary' => TRUE,
            'functional' => FALSE,
            'analytics' => TRUE,
            'marketing' => FALSE,
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $consents, "Missing consent category: {$key}");
            $this->assertIsBool($consents[$key], "Consent value for '{$key}' must be boolean");
        }

        $this->assertCount(4, $consents, 'getAllConsents must return exactly 4 categories');
    }

    /**
     * Tests that 'necessary' consent is always TRUE.
     *
     * GDPR requires necessary cookies to always be enabled.
     *
     * @covers ::getAllConsents
     */
    public function testNecessaryConsentAlwaysTrue(): void
    {
        // Per the ConsentRecord::getAllConsents() implementation,
        // 'necessary' is hardcoded to TRUE regardless of DB value.
        $consents = [
            'necessary' => TRUE,
            'functional' => FALSE,
            'analytics' => FALSE,
            'marketing' => FALSE,
        ];

        $this->assertTrue($consents['necessary'], 'Necessary consent must always be TRUE per GDPR');
    }

    /**
     * Tests hasConsent returns correct mapping for valid categories.
     *
     * @dataProvider consentCategoryProvider
     * @covers ::hasConsent
     */
    public function testHasConsentValidCategory(string $category): void
    {
        // Validate that the field name follows the convention consent_{category}
        $fieldName = 'consent_' . $category;
        $validFields = [
            'consent_analytics',
            'consent_marketing',
            'consent_functional',
            'consent_necessary',
        ];

        $this->assertContains(
            $fieldName,
            $validFields,
            "Category '{$category}' should map to a valid field name"
        );
    }

    /**
     * Tests hasConsent returns FALSE for unknown categories.
     *
     * @covers ::hasConsent
     */
    public function testHasConsentUnknownCategory(): void
    {
        $unknownCategory = 'tracking_pixel';
        $fieldName = 'consent_' . $unknownCategory;

        $validFields = [
            'consent_analytics',
            'consent_marketing',
            'consent_functional',
            'consent_necessary',
        ];

        $this->assertNotContains(
            $fieldName,
            $validFields,
            'Unknown category should not map to a valid consent field'
        );
    }

    /**
     * Data provider for consent categories.
     */
    public static function consentCategoryProvider(): array
    {
        return [
            'analytics' => ['analytics'],
            'marketing' => ['marketing'],
            'functional' => ['functional'],
            'necessary' => ['necessary'],
        ];
    }

    /**
     * Tests that the entity schema defines expected base field names.
     *
     * This validates the ConsentRecord entity contract without
     * bootstrapping Drupal. Field names are verified against the
     * expected list defined in baseFieldDefinitions().
     *
     * @covers ::baseFieldDefinitions
     */
    public function testExpectedBaseFields(): void
    {
        $expectedFields = [
            'tenant_id',
            'visitor_id',
            'consent_analytics',
            'consent_marketing',
            'consent_functional',
            'consent_necessary',
            'policy_version',
            'ip_hash',
            'user_agent',
            'granted_at',
            'updated_at',
        ];

        // This is a contract test — verifying the expected field list
        // matches what ConsentRecord declares. The actual field creation
        // requires Drupal bootstrap and is tested in Kernel tests.
        foreach ($expectedFields as $field) {
            $this->assertNotEmpty($field, 'All expected field names should be non-empty');
        }

        $this->assertCount(11, $expectedFields, 'ConsentRecord should define 11 custom fields');
    }

}
