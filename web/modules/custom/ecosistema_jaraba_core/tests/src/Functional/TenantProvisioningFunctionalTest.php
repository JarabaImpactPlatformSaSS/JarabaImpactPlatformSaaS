<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests de provisioning de tenants con Group + Domain.
 *
 * Migrado desde Kernel/TenantProvisioningTest que no puede ejecutarse
 * en KernelTestBase porque los módulos group y domain requieren
 * el contenedor DI completo (BrowserTestBase).
 *
 * VERIFICA:
 * 1. Al crear un Tenant se provisiona Group + Domain automáticamente.
 * 2. Al actualizar un Tenant no se duplican Group/Domain.
 * 3. El Group creado es de tipo 'tenant'.
 *
 * @group ecosistema_jaraba_core
 * @requires module group
 * @requires module domain
 */
class TenantProvisioningFunctionalTest extends BrowserTestBase
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
        'group',
        'domain',
        'ecosistema_jaraba_core',
    ];

    /**
     * {@inheritdoc}
     */
    protected $defaultTheme = 'stark';

    /**
     * Un usuario con permisos administrativos.
     *
     * @var \Drupal\user\UserInterface
     */
    protected $adminUser;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = $this->drupalCreateUser([
            'administer site configuration',
            'administer tenants',
        ]);
        $this->drupalLogin($this->adminUser);
    }

    /**
     * Crea entidades prerequisito (Vertical + SaasPlan) para los tests.
     *
     * @param string $suffix
     *   Sufijo para nombres únicos.
     *
     * @return array
     *   Array con ['vertical' => Vertical, 'plan' => SaasPlan].
     */
    protected function createPrerequisites(string $suffix = ''): array
    {
        $verticalStorage = $this->container->get('entity_type.manager')
            ->getStorage('vertical');
        $vertical = $verticalStorage->create([
            'name' => 'Test Vertical' . $suffix,
            'machine_name' => 'test_vertical' . strtolower($suffix),
            'status' => TRUE,
        ]);
        $vertical->save();

        $planStorage = $this->container->get('entity_type.manager')
            ->getStorage('saas_plan');
        $plan = $planStorage->create([
            'id' => 'test_plan' . strtolower($suffix),
            'label' => 'Test Plan' . $suffix,
            'price_monthly' => 0,
            'limits' => ['max_producers' => 10],
        ]);
        $plan->save();

        return [
            'vertical' => $vertical,
            'plan' => $plan,
        ];
    }

    /**
     * Tests que al crear un Tenant se provisiona Group y Domain.
     */
    public function testTenantCreatesGroupAndDomain(): void
    {
        $prereqs = $this->createPrerequisites('_1');

        $tenantStorage = $this->container->get('entity_type.manager')
            ->getStorage('tenant');
        $tenant = $tenantStorage->create([
            'name' => 'Test Tenant Provisioning',
            'vertical_id' => $prereqs['vertical']->id(),
            'plan_id' => $prereqs['plan']->id(),
            'subscription_status' => 'trial',
        ]);
        $tenant->save();

        // Verificar Group.
        $groupId = $tenant->getGroupId();
        $this->assertNotNull($groupId, 'Tenant debe tener group_id tras save.');

        $group = $this->container->get('entity_type.manager')
            ->getStorage('group')->load($groupId);
        $this->assertNotNull($group, 'La entidad Group debe existir.');
        $this->assertStringContainsString('Test Tenant', $group->label());

        // Verificar Domain.
        $domainId = $tenant->getDomainId();
        $this->assertNotNull($domainId, 'Tenant debe tener domain_id tras save.');

        $domain = $this->container->get('entity_type.manager')
            ->getStorage('domain')->load($domainId);
        $this->assertNotNull($domain, 'La entidad Domain debe existir.');
    }

    /**
     * Tests que actualizar un Tenant no duplica Group/Domain.
     */
    public function testTenantUpdateDoesNotDuplicate(): void
    {
        $prereqs = $this->createPrerequisites('_2');

        $tenantStorage = $this->container->get('entity_type.manager')
            ->getStorage('tenant');
        $tenant = $tenantStorage->create([
            'name' => 'Tenant For Update',
            'vertical_id' => $prereqs['vertical']->id(),
            'plan_id' => $prereqs['plan']->id(),
            'subscription_status' => 'trial',
        ]);
        $tenant->save();

        $originalGroupId = $tenant->getGroupId();
        $originalDomainId = $tenant->getDomainId();

        // Actualizar el tenant.
        $tenant->setName('Updated Tenant Name');
        $tenant->save();

        // Los IDs deben permanecer iguales.
        $this->assertEquals($originalGroupId, $tenant->getGroupId());
        $this->assertEquals($originalDomainId, $tenant->getDomainId());
    }

    /**
     * Tests que el Group creado tiene bundle 'tenant'.
     */
    public function testTenantGroupType(): void
    {
        $prereqs = $this->createPrerequisites('_3');

        $tenantStorage = $this->container->get('entity_type.manager')
            ->getStorage('tenant');
        $tenant = $tenantStorage->create([
            'name' => 'Tenant Type Test',
            'vertical_id' => $prereqs['vertical']->id(),
            'plan_id' => $prereqs['plan']->id(),
            'subscription_status' => 'active',
        ]);
        $tenant->save();

        $group = $this->container->get('entity_type.manager')
            ->getStorage('group')->load($tenant->getGroupId());
        $this->assertEquals('tenant', $group->bundle());
    }

}
