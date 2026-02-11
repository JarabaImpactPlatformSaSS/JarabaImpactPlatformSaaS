<?php

namespace Drupal\Tests\ecosistema_jaraba_core\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests de integración para las entidades del módulo ecosistema_jaraba_core.
 *
 * Estos tests verifican que las entidades se instalan correctamente
 * y que las definiciones de campos base funcionan en un entorno Drupal real.
 *
 * @group ecosistema_jaraba_core
 */
class EntityInstallTest extends KernelTestBase
{

    /**
     * Módulos requeridos para los tests.
     *
     * @var array
     */
    protected static $modules = [
        'system',
        'user',
        'node',
        'field',
        'text',
        'options',
        'datetime',
        'ecosistema_jaraba_core',
    ];

    /**
     * {@inheritdoc}
     *
     * Configuración inicial del entorno de prueba.
     * Instala los esquemas necesarios para las entidades que NO dependen
     * de módulos contrib (group, domain).
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Instalar esquemas de sistema y usuario requeridos
        $this->installEntitySchema('user');

        // Instalar las 3 entidades del módulo.
        // Tenant NO referencia entity types contrib (group/domain):
        // - vertical → entity_reference a 'vertical' (propia)
        // - subscription_plan → entity_reference a 'saas_plan' (propia)
        // - domain → string (texto plano, no entity_reference)
        // - admin_user → entity_reference a 'user' (core)
        $this->installEntitySchema('vertical');
        $this->installEntitySchema('saas_plan');
        $this->installEntitySchema('tenant');
    }

    /**
     * Verifica que la entidad Vertical se pueda crear y guardar.
     *
     * Este test comprueba:
     * - La creación de una entidad Vertical
     * - La persistencia correcta de todos los campos
     * - La recuperación de la entidad desde la base de datos
     */
    public function testVerticalEntityCreation(): void
    {
        $storage = \Drupal::entityTypeManager()->getStorage('vertical');

        // Crear una nueva vertical con todos los campos
        $vertical = $storage->create([
            'name' => 'AgroConecta',
            'machine_name' => 'agroconecta',
            'description' => 'Ecosistema para productores agroalimentarios',
            'enabled_features' => ['trazabilidad', 'qr_codes', 'ai_storytelling'],
            'theme_settings' => '{"color_primario": "#FF8C42"}',
            'ai_agents' => ['producer_copilot', 'marketing_agent'],
            'status' => TRUE,
        ]);

        // Guardar la entidad
        $vertical->save();

        // Verificar que se asignó un ID
        $this->assertNotNull($vertical->id());

        // Cargar la entidad desde la base de datos
        $loaded = $storage->load($vertical->id());

        // Verificar que los valores se guardaron correctamente
        $this->assertEquals('AgroConecta', $loaded->getName());
        $this->assertEquals('agroconecta', $loaded->getMachineName());
        $this->assertTrue((bool) $loaded->get('status')->value);
    }

    /**
     * Verifica que la entidad SaasPlan se pueda crear correctamente.
     *
     * Comprueba la creación de planes con precios, límites y features.
     */
    public function testSaasPlanEntityCreation(): void
    {
        $storage = \Drupal::entityTypeManager()->getStorage('saas_plan');

        // Primero necesitamos una vertical
        $verticalStorage = \Drupal::entityTypeManager()->getStorage('vertical');
        $vertical = $verticalStorage->create([
            'name' => 'Test Vertical',
            'machine_name' => 'test_vertical',
            'status' => TRUE,
        ]);
        $vertical->save();

        // Crear el plan SaaS
        $plan = $storage->create([
            'name' => 'Profesional',
            'vertical' => $vertical->id(),
            'price_monthly' => '79.00',
            'price_yearly' => '790.00',
            'limits' => '{"productores": 50, "storage_gb": 25, "ai_queries": 100}',
            'features' => ['trazabilidad_basica', 'trazabilidad_avanzada', 'agentes_ia_limitados'],
            'stripe_price_id' => 'price_test_123',
            'status' => TRUE,
        ]);

        $plan->save();

        // Verificaciones
        $this->assertNotNull($plan->id());

        $loaded = $storage->load($plan->id());
        $this->assertEquals('Profesional', $loaded->getName());
        $this->assertEquals('79.00', $loaded->getPriceMonthly());
        $this->assertEquals('790.00', $loaded->getPriceYearly());
        $this->assertFalse($loaded->isFree());
    }

    /**
     * Verifica que la entidad Tenant se pueda crear con todas sus relaciones.
     *
     * Este test es más complejo ya que Tenant depende de:
     * - Una Vertical
     * - Un SaasPlan
     * - Un Usuario administrador
     */
    public function testTenantEntityCreation(): void
    {
        // Crear dependencias previas

        // 1. Usuario administrador del tenant
        $userStorage = \Drupal::entityTypeManager()->getStorage('user');
        $user = $userStorage->create([
            'name' => 'admin_coop',
            'mail' => 'admin@cooperativa.es',
            'status' => 1,
        ]);
        $user->save();

        // 2. Vertical
        $verticalStorage = \Drupal::entityTypeManager()->getStorage('vertical');
        $vertical = $verticalStorage->create([
            'name' => 'AgroConecta',
            'machine_name' => 'agroconecta',
            'status' => TRUE,
        ]);
        $vertical->save();

        // 3. Plan SaaS
        $planStorage = \Drupal::entityTypeManager()->getStorage('saas_plan');
        $plan = $planStorage->create([
            'name' => 'Básico',
            'vertical' => $vertical->id(),
            'price_monthly' => '29.00',
            'status' => TRUE,
        ]);
        $plan->save();

        // 4. Crear el Tenant
        $tenantStorage = \Drupal::entityTypeManager()->getStorage('tenant');
        $tenant = $tenantStorage->create([
            'name' => 'Cooperativa del Olivar',
            'vertical' => $vertical->id(),
            'subscription_plan' => $plan->id(),
            'domain' => 'coop-olivar',
            'admin_user' => $user->id(),
            'subscription_status' => 'trial',
            'trial_ends' => date('Y-m-d\TH:i:s', strtotime('+14 days')),
        ]);

        $tenant->save();

        // Verificaciones
        $this->assertNotNull($tenant->id());

        $loaded = $tenantStorage->load($tenant->id());
        $this->assertEquals('Cooperativa del Olivar', $loaded->getName());
        $this->assertEquals('coop-olivar', $loaded->getDomain());
        $this->assertEquals('trial', $loaded->getSubscriptionStatus());
        $this->assertTrue($loaded->isOnTrial());
        $this->assertTrue($loaded->isActive());
    }

    /**
     * Verifica que no se puedan crear tenants con dominios duplicados.
     *
     * La unicidad del dominio es crítica para el funcionamiento del multi-tenancy.
     */
    public function testDomainUniquenessConstraint(): void
    {
        // Preparar dependencias mínimas
        $verticalStorage = \Drupal::entityTypeManager()->getStorage('vertical');
        $vertical = $verticalStorage->create([
            'name' => 'Test',
            'machine_name' => 'test',
            'status' => TRUE,
        ]);
        $vertical->save();

        $planStorage = \Drupal::entityTypeManager()->getStorage('saas_plan');
        $plan = $planStorage->create([
            'name' => 'Test Plan',
            'vertical' => $vertical->id(),
            'status' => TRUE,
        ]);
        $plan->save();

        $userStorage = \Drupal::entityTypeManager()->getStorage('user');
        $user = $userStorage->create([
            'name' => 'test_user',
            'mail' => 'test@test.com',
            'status' => 1,
        ]);
        $user->save();

        $tenantStorage = \Drupal::entityTypeManager()->getStorage('tenant');

        // Crear primer tenant con dominio 'mi-dominio'
        $tenant1 = $tenantStorage->create([
            'name' => 'Tenant 1',
            'vertical' => $vertical->id(),
            'subscription_plan' => $plan->id(),
            'domain' => 'mi-dominio',
            'admin_user' => $user->id(),
            'subscription_status' => 'active',
        ]);
        $tenant1->save();

        // Crear segundo tenant con el mismo dominio
        $tenant2 = $tenantStorage->create([
            'name' => 'Tenant 2',
            'vertical' => $vertical->id(),
            'subscription_plan' => $plan->id(),
            'domain' => 'mi-dominio',  // Dominio duplicado
            'admin_user' => $user->id(),
            'subscription_status' => 'active',
        ]);

        // UniqueField constraint se valida vía entity validation API
        $violations = $tenant2->validate();
        $this->assertGreaterThan(0, $violations->count(), 'El dominio duplicado debe producir violaciones de validación');
    }

}
