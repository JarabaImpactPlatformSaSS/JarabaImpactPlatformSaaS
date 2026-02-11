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
