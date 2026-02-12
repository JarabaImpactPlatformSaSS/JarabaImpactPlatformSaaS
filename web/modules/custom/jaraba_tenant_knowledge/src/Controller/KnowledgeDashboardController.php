<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\jaraba_tenant_knowledge\Service\TenantKnowledgeManager;

/**
 * CONTROLLER DASHBOARD DE KNOWLEDGE TRAINING
 *
 * PROPÓSITO:
 * Controla las páginas frontend del sistema de entrenamiento
 * de conocimiento del tenant. Sigue el patrón "Frontend Page"
 * de las directrices del proyecto
 *
 * PATRÓN:
 * - Render arrays simples con templates Twig custom
 * - Librería CSS/JS attached
 * - Body classes vía hook_preprocess_html
 *
 * @see jaraba_tenant_knowledge_preprocess_html()
 * @see /frontend-page-pattern workflow
 */
class KnowledgeDashboardController extends ControllerBase
{

    /**
     * Constructor.
     */
    public function __construct(
        protected TenantKnowledgeManager $knowledgeManager,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_tenant_knowledge.manager'),
        );
    }

    /**
     * Página principal del dashboard de Knowledge Training.
     *
     * PROPÓSITO:
     * Vista general del estado del conocimiento del tenant.
     * Muestra progreso de completitud y acceso a secciones.
     *
     * @return array
     *   Render array con el dashboard.
     */
    public function dashboard(): array
    {
        // Obtener configuración del tenant actual.
        $config = $this->knowledgeManager->getOrCreateConfig();

        // Calcular estadísticas.
        $statistics = [
            'completeness' => $config ? $config->calculateCompletenessScore() : 0,
            'faqs_count' => $this->knowledgeManager->countFaqs(),
            'policies_count' => $this->knowledgeManager->countPolicies(),
            'documents_count' => $this->knowledgeManager->countDocuments(),
        ];

        // Definir secciones del dashboard.
        $sections = [
            [
                'id' => 'business-info',
                'title' => $this->t('Información del Negocio'),
                'description' => $this->t('Configura los datos básicos de tu empresa.'),
                'icon' => 'Building2',
                'url' => '/knowledge/config',
                'status' => $statistics['completeness'] >= 50 ? 'complete' : 'incomplete',
                'progress' => $statistics['completeness'],
            ],
            [
                'id' => 'faqs',
                'title' => $this->t('Preguntas Frecuentes'),
                'description' => $this->t('Añade las preguntas más comunes de tus clientes.'),
                'icon' => 'HelpCircle',
                'url' => '/knowledge/faqs',
                'status' => $statistics['faqs_count'] > 0 ? 'complete' : 'empty',
                'count' => $statistics['faqs_count'],
            ],
            [
                'id' => 'policies',
                'title' => $this->t('Políticas'),
                'description' => $this->t('Define tus políticas de devolución, envío, etc.'),
                'icon' => 'FileText',
                'url' => '/knowledge/policies',
                'status' => $statistics['policies_count'] > 0 ? 'complete' : 'empty',
                'count' => $statistics['policies_count'],
            ],
            [
                'id' => 'documents',
                'title' => $this->t('Documentos'),
                'description' => $this->t('Sube manuales, catálogos y otros documentos.'),
                'icon' => 'FolderOpen',
                'url' => '/knowledge/documents',
                'status' => $statistics['documents_count'] > 0 ? 'complete' : 'empty',
                'count' => $statistics['documents_count'],
            ],
            [
                'id' => 'test',
                'title' => $this->t('Probar Copiloto'),
                'description' => $this->t('Prueba cómo responde tu asistente IA.'),
                'icon' => 'MessageSquare',
                'url' => '/knowledge/test',
                'status' => 'always',
            ],
        ];

        return [
            '#theme' => 'knowledge_dashboard',
            '#config' => $config,
            '#statistics' => $statistics,
            '#sections' => $sections,
            '#attached' => [
                'library' => [
                    'jaraba_tenant_knowledge/dashboard',
                ],
            ],
        ];
    }

    /**
     * Página de edición de configuración del negocio.
     */
    public function editConfig(): array
    {
        $config = $this->knowledgeManager->getOrCreateConfig();

        if (!$config) {
            return [
                '#markup' => $this->t('Error: No se pudo obtener la configuración del tenant.'),
            ];
        }

        // Obtener el formulario de la entidad.
        $form = $this->entityFormBuilder()->getForm($config, 'edit');

        return [
            '#type' => 'container',
            '#attributes' => ['class' => ['knowledge-config-page']],
            'form' => $form,
            '#attached' => [
                'library' => [
                    'jaraba_tenant_knowledge/dashboard',
                ],
            ],
        ];
    }

    /**
     * Página de FAQs.
     */
    public function faqs(): array
    {
        // TODO: Implementar en Sprint TK2.
        return [
            '#markup' => $this->t('Sección de FAQs - Próximamente'),
            '#attached' => [
                'library' => ['jaraba_tenant_knowledge/dashboard'],
            ],
        ];
    }

    /**
     * Página para añadir FAQ.
     */
    public function addFaq(): array
    {
        // TODO: Implementar en Sprint TK2.
        return [
            '#markup' => $this->t('Formulario de FAQ - Próximamente'),
        ];
    }

    /**
     * Página de Políticas.
     */
    public function policies(): array
    {
        // TODO: Implementar en Sprint TK3.
        return [
            '#markup' => $this->t('Sección de Políticas - Próximamente'),
            '#attached' => [
                'library' => ['jaraba_tenant_knowledge/dashboard'],
            ],
        ];
    }

    /**
     * Página de Documentos.
     */
    public function documents(): array
    {
        // TODO: Implementar en Sprint TK4.
        return [
            '#markup' => $this->t('Sección de Documentos - Próximamente'),
            '#attached' => [
                'library' => ['jaraba_tenant_knowledge/dashboard'],
            ],
        ];
    }

    /**
     * Consola de pruebas del Copiloto.
     *
     * PROPÓSITO:
     * Permite al tenant probar cómo responde el Copiloto
     * con el conocimiento que ha configurado.
     */
    public function testConsole(): array
    {
        // Estadísticas de conocimiento.
        $statistics = [
            'faqs_count' => $this->knowledgeManager->countFaqs(),
            'policies_count' => $this->knowledgeManager->countPolicies(),
            'documents_count' => $this->knowledgeManager->countDocuments(),
            'products_count' => $this->countProducts(),
            'corrections_count' => $this->countCorrections(),
        ];

        // Calcular cobertura (porcentaje de secciones completadas).
        $totalSections = 5;
        $completedSections = 0;
        if ($statistics['faqs_count'] > 0)
            $completedSections++;
        if ($statistics['policies_count'] > 0)
            $completedSections++;
        if ($statistics['documents_count'] > 0)
            $completedSections++;
        if ($statistics['products_count'] > 0)
            $completedSections++;

        $config = $this->knowledgeManager->getOrCreateConfig();
        if ($config && $config->calculateCompletenessScore() >= 50) {
            $completedSections++;
        }

        $coverage = round(($completedSections / $totalSections) * 100);

        // Sugerencias de preguntas basadas en el contenido.
        $suggestions = $this->generateSuggestions();

        return [
            '#theme' => 'knowledge_test_console',
            '#statistics' => $statistics,
            '#coverage' => $coverage,
            '#suggestions' => $suggestions,
            '#attached' => [
                'library' => [
                    'jaraba_tenant_knowledge/test-console',
                ],
                'drupalSettings' => [
                    'jarabaTenantKnowledge' => [
                        'testApiUrl' => '/api/v1/knowledge/test',
                    ],
                ],
            ],
        ];
    }

    /**
     * Genera sugerencias de preguntas basadas en el contenido.
     */
    protected function generateSuggestions(): array
    {
        $suggestions = [
            $this->t('¿Cuál es el horario de atención?'),
            $this->t('¿Cuál es la política de devoluciones?'),
            $this->t('¿Qué métodos de pago aceptan?'),
            $this->t('¿Hacen envíos internacionales?'),
        ];

        return $suggestions;
    }

    /**
     * Cuenta productos del tenant actual.
     */
    protected function countProducts(): int
    {
        $tenantId = $this->getCurrentTenantId();
        if (!$tenantId) {
            return 0;
        }

        return (int) $this->entityTypeManager()
            ->getStorage('tenant_product_enrichment')
            ->getQuery()
            ->accessCheck(TRUE)
            ->condition('tenant_id', $tenantId)
            ->condition('is_published', TRUE)
            ->count()
            ->execute();
    }

    /**
     * Cuenta correcciones del tenant actual.
     */
    protected function countCorrections(): int
    {
        $tenantId = $this->getCurrentTenantId();
        if (!$tenantId) {
            return 0;
        }

        return (int) $this->entityTypeManager()
            ->getStorage('tenant_ai_correction')
            ->getQuery()
            ->accessCheck(TRUE)
            ->condition('tenant_id', $tenantId)
            ->condition('status', 'applied')
            ->count()
            ->execute();
    }

    /**
     * Obtiene el tenant ID actual.
     */
    protected function getCurrentTenantId(): ?int
    {
        if (\Drupal::hasService('jaraba_multitenancy.tenant_context')) {
            $tenantContext = \Drupal::service('jaraba_multitenancy.tenant_context');
            $tenant = $tenantContext->getCurrentTenant();
            return $tenant ? (int) $tenant->id() : NULL;
        }
        return NULL;
    }

    /**
     * Página de Productos/Enriquecimiento.
     */
    public function products(): array
    {
        return [
            '#type' => 'container',
            '#attributes' => ['class' => ['knowledge-products-page']],
            'header' => [
                '#markup' => '<h2>' . $this->t('Enriquecimiento de Productos') . '</h2>' .
                    '<p>' . $this->t('Añade información detallada sobre tus productos para que el copiloto pueda responder mejor.') . '</p>',
            ],
            'add_button' => [
                '#type' => 'link',
                '#title' => $this->t('+ Nuevo Producto'),
                '#url' => \Drupal\Core\Url::fromRoute('jaraba_tenant_knowledge.product.add'),
                '#attributes' => ['class' => ['button', 'button--primary']],
            ],
            'list' => $this->entityTypeManager()->getListBuilder('tenant_product_enrichment')->render(),
            '#attached' => [
                'library' => ['jaraba_tenant_knowledge/dashboard'],
            ],
        ];
    }

    /**
     * Página de Correcciones de IA.
     */
    public function corrections(): array
    {
        return [
            '#type' => 'container',
            '#attributes' => ['class' => ['knowledge-corrections-page']],
            'header' => [
                '#markup' => '<h2>' . $this->t('Correcciones de IA') . '</h2>' .
                    '<p>' . $this->t('Cuando el copiloto se equivoque, registra la corrección para que aprenda.') . '</p>',
            ],
            'add_button' => [
                '#type' => 'link',
                '#title' => $this->t('+ Nueva Corrección'),
                '#url' => \Drupal\Core\Url::fromRoute('jaraba_tenant_knowledge.correction.add'),
                '#attributes' => ['class' => ['button', 'button--primary']],
            ],
            'list' => $this->entityTypeManager()->getListBuilder('tenant_ai_correction')->render(),
            '#attached' => [
                'library' => ['jaraba_tenant_knowledge/dashboard'],
            ],
        ];
    }

}

