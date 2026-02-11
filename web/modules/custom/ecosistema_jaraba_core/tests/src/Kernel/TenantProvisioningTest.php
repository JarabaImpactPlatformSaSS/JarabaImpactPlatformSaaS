<?php

namespace Drupal\Tests\ecosistema_jaraba_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\ecosistema_jaraba_core\Entity\Tenant;
use Drupal\ecosistema_jaraba_core\Entity\Vertical;
use Drupal\ecosistema_jaraba_core\Entity\SaasPlan;

/**
 * Tests for Tenant provisioning on postSave.
 *
 * This tests that when a Tenant is created:
 * 1. A Group is automatically created
 * 2. A Domain is automatically created
 * 3. Both are linked to the Tenant entity
 *
 * @group ecosistema_jaraba_core
 * @requires module group
 * @requires module domain
 */
class TenantProvisioningTest extends KernelTestBase
{

    /**
     * Modules to enable.
     *
     * @var array
     */
    protected static $modules = [
        'system',
        'user',
        'field',
        'text',
        'options',
        'group',
        'domain',
        'ecosistema_jaraba_core',
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        // Los módulos group y domain no pueden arrancar en aislamiento Kernel:
        // tienen dependencias de servicio que requieren un entorno Drupal completo
        // (BrowserTestBase). El error es CheckExceptionOnInvalidReferenceBehaviorPass
        // durante la compilación del contenedor DI.
        // TODO: Migrar estos tests a Functional (BrowserTestBase) cuando se
        // configure el entorno de testing completo con group + domain.
        $this->markTestSkipped(
            'TenantProvisioningTest requiere BrowserTestBase para arrancar group y domain correctamente.'
        );
    }

    /**
     * Tests that Tenant creation provisions Group and Domain.
     */
    public function testTenantCreatesGroupAndDomain(): void
    {
        // Create prerequisites: Vertical and SaasPlan
        $vertical = Vertical::create([
            'name' => 'Test Vertical',
            'machine_name' => 'test_vertical',
            'status' => TRUE,
        ]);
        $vertical->save();

        $plan = SaasPlan::create([
            'id' => 'test_plan',
            'label' => 'Test Plan',
            'price_monthly' => 0,
            'limits' => ['max_producers' => 10],
        ]);
        $plan->save();

        // Create a tenant
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'vertical_id' => $vertical->id(),
            'plan_id' => $plan->id(),
            'subscription_status' => 'trial',
        ]);
        $tenant->save();

        // Verify Group was created
        $groupId = $tenant->getGroupId();
        $this->assertNotNull($groupId, 'Tenant should have a group_id after save');

        $group = \Drupal::entityTypeManager()->getStorage('group')->load($groupId);
        $this->assertNotNull($group, 'Group entity should exist');
        $this->assertStringContainsString('Test Tenant', $group->label());

        // Verify Domain was created
        $domainId = $tenant->getDomainId();
        $this->assertNotNull($domainId, 'Tenant should have a domain_id after save');

        $domain = \Drupal::entityTypeManager()->getStorage('domain')->load($domainId);
        $this->assertNotNull($domain, 'Domain entity should exist');
    }

    /**
     * Tests that updating a Tenant does not create duplicate Group/Domain.
     */
    public function testTenantUpdateDoesNotDuplicate(): void
    {
        // Create tenant with Group/Domain
        $vertical = Vertical::create([
            'name' => 'Vertical 2',
            'machine_name' => 'vertical_2',
            'status' => TRUE,
        ]);
        $vertical->save();

        $plan = SaasPlan::create([
            'id' => 'plan_2',
            'label' => 'Plan 2',
            'price_monthly' => 10,
            'limits' => ['max_producers' => 20],
        ]);
        $plan->save();

        $tenant = Tenant::create([
            'name' => 'Tenant For Update',
            'vertical_id' => $vertical->id(),
            'plan_id' => $plan->id(),
            'subscription_status' => 'trial',
        ]);
        $tenant->save();

        $originalGroupId = $tenant->getGroupId();
        $originalDomainId = $tenant->getDomainId();

        // Update the tenant
        $tenant->setName('Updated Tenant Name');
        $tenant->save();

        // Verify IDs remain the same
        $this->assertEquals($originalGroupId, $tenant->getGroupId());
        $this->assertEquals($originalDomainId, $tenant->getDomainId());
    }

    /**
     * Tests Group type is 'tenant'.
     */
    public function testTenantGroupType(): void
    {
        $vertical = Vertical::create([
            'name' => 'Vertical 3',
            'machine_name' => 'vertical_3',
            'status' => TRUE,
        ]);
        $vertical->save();

        $plan = SaasPlan::create([
            'id' => 'plan_3',
            'label' => 'Plan 3',
            'price_monthly' => 20,
            'limits' => ['max_producers' => 30],
        ]);
        $plan->save();

        $tenant = Tenant::create([
            'name' => 'Tenant Type Test',
            'vertical_id' => $vertical->id(),
            'plan_id' => $plan->id(),
            'subscription_status' => 'active',
        ]);
        $tenant->save();

        $group = \Drupal::entityTypeManager()->getStorage('group')->load($tenant->getGroupId());
        $this->assertEquals('tenant', $group->bundle());
    }

}
