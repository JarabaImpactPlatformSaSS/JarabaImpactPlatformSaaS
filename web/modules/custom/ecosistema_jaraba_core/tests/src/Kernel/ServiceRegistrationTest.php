<?php

namespace Drupal\Tests\ecosistema_jaraba_core\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests de integración para los servicios del módulo.
 *
 * Verifica que los servicios se registran correctamente en el contenedor
 * de dependencias de Drupal y que pueden ser instanciados.
 *
 * @group ecosistema_jaraba_core
 */
class ServiceRegistrationTest extends KernelTestBase
{

    /**
     * Módulos requeridos para los tests de servicios.
     *
     * @var array
     */
    protected static $modules = [
        'system',
        'user',
        'node',
        'field',
        'options',
        'datetime',
        'file',
        'ecosistema_jaraba_core',
    ];

    /**
     * {@inheritdoc}
     *
     * Configuración inicial: instala esquemas necesarios.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Instalar esquemas de entidades requeridas
        $this->installEntitySchema('user');
        $this->installEntitySchema('vertical');
        $this->installEntitySchema('saas_plan');
        $this->installEntitySchema('tenant');

        // Instalar configuración del módulo
        $this->installConfig(['ecosistema_jaraba_core']);
    }

    /**
     * Verifica que el servicio PlanValidator está registrado y es instanciable.
     *
     * El PlanValidator es crítico para validar los límites de uso de cada tenant
     * según su plan de suscripción.
     */
    public function testPlanValidatorServiceExists(): void
    {
        $container = \Drupal::getContainer();

        // Verificar que el servicio existe en el contenedor
        $this->assertTrue(
            $container->has('ecosistema_jaraba_core.plan_validator'),
            'El servicio plan_validator debe estar registrado en el contenedor'
        );

        // Verificar que se puede obtener una instancia
        $service = $container->get('ecosistema_jaraba_core.plan_validator');
        $this->assertNotNull($service);

        // Verificar que es del tipo correcto
        $this->assertInstanceOf(
            \Drupal\ecosistema_jaraba_core\Service\PlanValidator::class,
            $service
        );
    }

    /**
     * Verifica que el servicio TenantManager está registrado correctamente.
     *
     * El TenantManager gestiona todo el ciclo de vida de los tenants:
     * creación, activación, suspensión, cambios de plan, etc.
     */
    public function testTenantManagerServiceExists(): void
    {
        $container = \Drupal::getContainer();

        $this->assertTrue(
            $container->has('ecosistema_jaraba_core.tenant_manager'),
            'El servicio tenant_manager debe estar registrado'
        );

        $service = $container->get('ecosistema_jaraba_core.tenant_manager');
        $this->assertNotNull($service);

        $this->assertInstanceOf(
            \Drupal\ecosistema_jaraba_core\Service\TenantManager::class,
            $service
        );
    }

    /**
     * Verifica que el servicio CertificadoPdfService está disponible.
     *
     * Este servicio genera los certificados PDF de trazabilidad para
     * los lotes de producción.
     */
    public function testCertificadoPdfServiceExists(): void
    {
        $container = \Drupal::getContainer();

        $this->assertTrue(
            $container->has('ecosistema_jaraba_core.certificado_pdf'),
            'El servicio certificado_pdf debe estar registrado'
        );

        $service = $container->get('ecosistema_jaraba_core.certificado_pdf');
        $this->assertNotNull($service);
    }

    /**
     * Verifica que el servicio FirmaDigitalService está disponible.
     *
     * Este servicio gestiona la firma electrónica de documentos PDF
     * con certificados PKCS#12 y sellado de tiempo.
     */
    public function testFirmaDigitalServiceExists(): void
    {
        $container = \Drupal::getContainer();

        $this->assertTrue(
            $container->has('ecosistema_jaraba_core.firma_digital'),
            'El servicio firma_digital debe estar registrado'
        );

        $service = $container->get('ecosistema_jaraba_core.firma_digital');
        $this->assertNotNull($service);
    }

    /**
     * Verifica que el canal de log personalizado está configurado.
     *
     * Todos los servicios del módulo deben usar este canal para facilitar
     * el debugging y monitoreo.
     */
    public function testLoggerChannelExists(): void
    {
        $logger = \Drupal::logger('ecosistema_jaraba_core');

        $this->assertNotNull($logger, 'El canal de log debe existir');

        // Verificar que se puede escribir al log sin errores
        $logger->info('Test de integración de servicios completado');
    }

    /**
     * Verifica que las dependencias entre servicios están correctamente inyectadas.
     *
     * El TenantManager depende de PlanValidator para validar cambios de plan.
     */
    public function testServiceDependencyInjection(): void
    {
        $tenantManager = \Drupal::service('ecosistema_jaraba_core.tenant_manager');

        // El TenantManager debe tener acceso al PlanValidator
        // Esto se verifica indirectamente probando un método que lo use
        $this->assertNotNull($tenantManager);

        // Verificar que el EntityTypeManager está inyectado
        $reflection = new \ReflectionClass($tenantManager);
        $properties = $reflection->getProperties();

        // Debe tener propiedades para las dependencias inyectadas
        $propertyNames = array_map(fn($p) => $p->getName(), $properties);
        $this->assertContains('entityTypeManager', $propertyNames);
        $this->assertContains('planValidator', $propertyNames);
    }

}
