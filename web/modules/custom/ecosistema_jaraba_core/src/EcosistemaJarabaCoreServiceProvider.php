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

        // UnifiedPromptBuilder: Combina Skills + Knowledge + Corrections + RAG.
        // Depende de jaraba_skills y jaraba_tenant_knowledge.
        // Solo se registra cuando ambos módulos están instalados.
        if (isset($modules['jaraba_skills']) && isset($modules['jaraba_tenant_knowledge'])) {
            $container->register('ecosistema_jaraba_core.unified_prompt_builder', 'Drupal\ecosistema_jaraba_core\Service\UnifiedPromptBuilder')
                ->addArgument(new Reference('entity_type.manager'))
                ->addArgument(new Reference('jaraba_skills.skill_manager'))
                ->addArgument(new Reference('jaraba_tenant_knowledge.manager'))
                ->addArgument(new Reference('jaraba_tenant_knowledge.indexer'))
                ->addArgument(new Reference('logger.channel.ecosistema_jaraba_core'));
        }
    }

}
