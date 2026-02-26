<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Proveedor de servicios condicionales del módulo ecosistema_jaraba_core.
 *
 * Registra servicios que dependen de módulos opcionales (no declarados
 * como dependencia dura en .info.yml). Esto evita errores de compilación
 * del contenedor DI en entornos donde esos módulos no están instalados
 * (ej: Kernel tests, instalaciones mínimas).
 *
 * @see https://www.drupal.org/docs/drupal-apis/services-and-dependency-injection/altering-existing-services
 */
class EcosistemaJarabaCoreServiceProvider extends ServiceProviderBase
{

    /**
     * {@inheritdoc}
     */
    public function register(ContainerBuilder $container): void
    {
        $modules = $container->getParameter('container.modules');

        // @deprecated v6.3.0 — Alias de compatibilidad para stripe_connect.
        // Código legacy en StripeController.php usa 'ecosistema_jaraba_core.stripe_connect'.
        // Solo se registra cuando jaraba_foc está instalado.
        // Eliminar en v7.0 junto con las referencias en StripeController.
        if (isset($modules['jaraba_foc'])) {
            $container->setAlias('ecosistema_jaraba_core.stripe_connect', 'jaraba_foc.stripe_connect');
        }

        // TD-003: Aliases backward-compatible para servicios extraídos a jaraba_billing.
        // Solo se registran cuando jaraba_billing está instalado, evitando errores
        // de compilación del contenedor en Kernel tests o instalaciones mínimas.
        if (isset($modules['jaraba_billing'])) {
            $container->setAlias('ecosistema_jaraba_core.plan_validator', 'jaraba_billing.plan_validator');
            $container->setAlias('ecosistema_jaraba_core.tenant_subscription', 'jaraba_billing.tenant_subscription');
            $container->setAlias('ecosistema_jaraba_core.tenant_metering', 'jaraba_billing.tenant_metering');
            $container->setAlias('ecosistema_jaraba_core.pricing_engine', 'jaraba_billing.pricing_engine');
            $container->setAlias('ecosistema_jaraba_core.reverse_trial', 'jaraba_billing.reverse_trial');
            $container->setAlias('ecosistema_jaraba_core.expansion_revenue', 'jaraba_billing.expansion_revenue');
            $container->setAlias('ecosistema_jaraba_core.impact_credit', 'jaraba_billing.impact_credit');

            // Inject billing services into TenantManager (replaces ~ NULL placeholders).
            $tenantManager = $container->getDefinition('ecosistema_jaraba_core.tenant_manager');
            $args = $tenantManager->getArguments();
            $args[2] = new Reference('ecosistema_jaraba_core.plan_validator');
            $args[4] = new Reference('ecosistema_jaraba_core.tenant_subscription');
            $tenantManager->setArguments($args);
        }

        // Admin Center Aggregator: inyectar servicios opcionales de FOC y CS.
        // jaraba_foc.saas_metrics → arg[3], jaraba_customer_success.health_calculator → arg[4].
        $aggregator = $container->getDefinition('ecosistema_jaraba_core.admin_center_aggregator');
        $aggregatorArgs = $aggregator->getArguments();
        if (isset($modules['jaraba_foc'])) {
            $aggregatorArgs[3] = new Reference('jaraba_foc.saas_metrics');
        }
        if (isset($modules['jaraba_customer_success'])) {
            $aggregatorArgs[4] = new Reference('jaraba_customer_success.health_calculator');
        }
        $aggregator->setArguments($aggregatorArgs);

        // Admin Center Finance: inyectar servicios opcionales de FOC.
        // jaraba_foc.saas_metrics → arg[1], jaraba_foc.metrics_calculator → arg[2].
        $finance = $container->getDefinition('ecosistema_jaraba_core.admin_center_finance');
        $financeArgs = $finance->getArguments();
        if (isset($modules['jaraba_foc'])) {
            $financeArgs[1] = new Reference('jaraba_foc.saas_metrics');
            $financeArgs[2] = new Reference('jaraba_foc.metrics_calculator');
        }
        $finance->setArguments($financeArgs);

        // Admin Center Alerts: inyectar servicios opcionales de FOC y CS.
        // jaraba_foc.alerts → arg[2], jaraba_customer_success.playbook_executor → arg[3].
        $alerts = $container->getDefinition('ecosistema_jaraba_core.admin_center_alerts');
        $alertsArgs = $alerts->getArguments();
        if (isset($modules['jaraba_foc'])) {
            $alertsArgs[2] = new Reference('jaraba_foc.alerts');
        }
        if (isset($modules['jaraba_customer_success'])) {
            $alertsArgs[3] = new Reference('jaraba_customer_success.playbook_executor');
        }
        $alerts->setArguments($alertsArgs);

        // Admin Center Analytics: inyectar AITelemetryService.
        // ecosistema_jaraba_core.ai_telemetry → arg[3].
        $analytics = $container->getDefinition('ecosistema_jaraba_core.admin_center_analytics');
        $analyticsArgs = $analytics->getArguments();
        $analyticsArgs[3] = new Reference('ecosistema_jaraba_core.ai_telemetry');
        $analytics->setArguments($analyticsArgs);

        // Fiscal Compliance Service: inyectar servicios opcionales de los 3 modulos fiscales.
        // jaraba_verifactu.hash_service → arg[2], jaraba_verifactu.remision_service → arg[3],
        // jaraba_facturae.face_client → arg[4], jaraba_einvoice_b2b.payment_status_service → arg[5],
        // ecosistema_jaraba_core.certificate_manager → arg[6].
        $fiscal = $container->getDefinition('ecosistema_jaraba_core.fiscal_compliance');
        $fiscalArgs = $fiscal->getArguments();
        if (isset($modules['jaraba_verifactu'])) {
            $fiscalArgs[2] = new Reference('jaraba_verifactu.hash_service');
            $fiscalArgs[3] = new Reference('jaraba_verifactu.remision_service');
        }
        if (isset($modules['jaraba_facturae'])) {
            $fiscalArgs[4] = new Reference('jaraba_facturae.face_client');
        }
        if (isset($modules['jaraba_einvoice_b2b'])) {
            $fiscalArgs[5] = new Reference('jaraba_einvoice_b2b.payment_status_service');
        }
        $fiscalArgs[6] = new Reference('ecosistema_jaraba_core.certificate_manager');
        $fiscal->setArguments($fiscalArgs);

        // UnifiedPromptBuilder: Combina Skills + Knowledge + Corrections + RAG.
        // Depende de jaraba_skills y jaraba_tenant_knowledge.
        // Solo se registra cuando ambos módulos están instalados.
        if (isset($modules['jaraba_skills']) && isset($modules['jaraba_tenant_knowledge'])) {
            $container->register('ecosistema_jaraba_core.unified_prompt_builder', 'Drupal\ecosistema_jaraba_core\Service\UnifiedPromptBuilder')
                ->addArgument(new Reference('entity_type.manager'))
                ->addArgument(new Reference('jaraba_skills.skill_manager'))
                ->addArgument(new Reference('jaraba_tenant_knowledge.manager'))
                ->addArgument(new Reference('jaraba_tenant_knowledge.indexer'))
                ->addArgument(new Reference('logger.channel.ecosistema_jaraba_core'))
                ->addArgument(new Reference('ecosistema_jaraba_core.tenant_context')) // AUDIT-CONS-N10: Proper DI for tenant context.
                ->addArgument(new Reference('ecosistema_jaraba_core.ai_guardrails')); // FIX-015: Guardrails para queries RAG.
        }
    }

}
