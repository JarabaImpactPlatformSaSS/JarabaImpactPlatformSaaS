<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\jaraba_copilot_v2\Service\FaqGeneratorService;
use Drupal\jaraba_copilot_v2\Service\CopilotQueryLoggerService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario para generar FAQs automáticas desde preguntas frecuentes.
 *
 * Permite:
 * - Configurar días y límite de FAQs
 * - Preview de FAQs generadas vía LLM
 * - Guardar FAQs como nodos de contenido
 */
class FaqGeneratorForm extends FormBase
{

    /**
     * Constructor del formulario.
     */
    public function __construct(
        protected FaqGeneratorService $faqGenerator,
        protected CopilotQueryLoggerService $queryLogger,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_copilot_v2.faq_generator'),
            $container->get('jaraba_copilot_v2.query_logger'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'jaraba_copilot_faq_generator_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        // Header con estilo admin premium
        $form['#prefix'] = '<div class="admin-premium faq-generator-form">';
        $form['#suffix'] = '</div>';

        // Verificar si hay datos suficientes
        $stats = $this->queryLogger->getStats('all', 30);
        $totalQueries = $stats['total_queries'] ?? 0;

        if ($totalQueries < 5) {
            $form['no_data'] = [
                '#type' => 'container',
                '#attributes' => ['class' => ['messages', 'messages--warning']],
                'message' => [
                    '#markup' => $this->t('No hay suficientes preguntas en el log del copiloto (mínimo 5). Actualmente: @count preguntas en los últimos 30 días.', [
                        '@count' => $totalQueries,
                    ]),
                ],
            ];
        }

        // Estadísticas actuales
        $form['stats'] = [
            '#type' => 'details',
            '#title' => $this->t('📊 Estadísticas de Preguntas'),
            '#open' => TRUE,
            '#attributes' => ['class' => ['faq-stats-box']],
        ];

        $form['stats']['info'] = [
            '#markup' => '<div class="faq-stats-grid">' .
                '<div class="faq-stat"><strong>' . $totalQueries . '</strong><span>' . $this->t('Queries (30d)') . '</span></div>' .
                '<div class="faq-stat"><strong>' . ($stats['feedback_positive'] ?? 0) . '</strong><span>' . $this->t('Feedback +') . '</span></div>' .
                '<div class="faq-stat"><strong>' . ($stats['feedback_negative'] ?? 0) . '</strong><span>' . $this->t('Feedback -') . '</span></div>' .
                '</div>',
        ];

        // Configuración
        $form['config'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('⚙️ Configuración'),
        ];

        $form['config']['days'] = [
            '#type' => 'number',
            '#title' => $this->t('Días a considerar'),
            '#default_value' => 30,
            '#min' => 7,
            '#max' => 90,
            '#description' => $this->t('Analizar preguntas de los últimos X días.'),
        ];

        $form['config']['limit'] = [
            '#type' => 'number',
            '#title' => $this->t('Número de FAQs'),
            '#default_value' => 5,
            '#min' => 1,
            '#max' => 20,
            '#description' => $this->t('Máximo de FAQs a generar.'),
        ];

        // Filtro de segmentación (origen/vertical/tenant)
        $form['config']['source'] = [
            '#type' => 'select',
            '#title' => $this->t('Segmento de origen'),
            '#options' => [
                'all' => $this->t('📋 Todos los orígenes'),
                'public' => $this->t('🌐 Público (landing/home)'),
                'empleabilidad' => $this->t('💼 Empleabilidad'),
                'emprendimiento' => $this->t('🚀 Emprendimiento'),
                'comercio' => $this->t('🛒 Comercio/Marketplace'),
            ],
            '#default_value' => 'all',
            '#description' => $this->t('Filtrar FAQs según la vertical o contexto de origen de las preguntas.'),
        ];


        // Botón de preview
        $form['actions'] = [
            '#type' => 'actions',
        ];

        $form['actions']['preview'] = [
            '#type' => 'submit',
            '#value' => $this->t('🔍 Preview FAQs'),
            '#submit' => ['::previewFaqs'],
            '#attributes' => ['class' => ['button', 'button--primary']],
        ];

        // Si hay FAQs en el estado, mostrarlas
        $generatedFaqs = $form_state->get('generated_faqs');
        if (!empty($generatedFaqs)) {
            $form['results'] = [
                '#type' => 'details',
                '#title' => $this->t('✨ FAQs Generadas (@count)', ['@count' => count($generatedFaqs)]),
                '#open' => TRUE,
                '#attributes' => ['class' => ['faq-results-box']],
            ];

            $faqItems = [];
            foreach ($generatedFaqs as $index => $faq) {
                $faqItems[] = [
                    '#type' => 'container',
                    '#attributes' => ['class' => ['faq-preview-item']],
                    'question' => [
                        '#markup' => '<div class="faq-question"><strong>Q:</strong> ' . htmlspecialchars($faq['question']) . '</div>',
                    ],
                    'answer' => [
                        '#markup' => '<div class="faq-answer"><strong>A:</strong> ' . htmlspecialchars($faq['answer']) . '</div>',
                    ],
                    'category' => [
                        '#markup' => '<div class="faq-category"><span class="badge">' . htmlspecialchars($faq['category'] ?? 'general') . '</span></div>',
                    ],
                ];
            }

            $form['results']['faqs'] = $faqItems;

            // Botón para guardar
            $form['actions']['save'] = [
                '#type' => 'submit',
                '#value' => $this->t('💾 Guardar como Nodos FAQ'),
                '#submit' => ['::saveFaqs'],
                '#attributes' => ['class' => ['button', 'button--action']],
            ];
        }

        // Estilos inline
        $form['#attached']['html_head'][] = [
            [
                '#tag' => 'style',
                '#value' => '
          .faq-generator-form { max-width: 900px; margin: 0 auto; }
          .faq-stats-grid { display: flex; gap: 2rem; margin: 1rem 0; }
          .faq-stat { text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 8px; }
          .faq-stat strong { display: block; font-size: 2rem; color: #233D63; }
          .faq-stat span { color: #666; font-size: 0.875rem; }
          .faq-preview-item { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
          .faq-question { font-size: 1.1rem; margin-bottom: 0.5rem; color: #1e293b; }
          .faq-answer { color: #475569; margin-bottom: 0.5rem; }
          .faq-category .badge { background: #FF8C42; color: #fff; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; }
          .faq-results-box { background: linear-gradient(135deg, #f0f9ff, #e0f2fe); border-radius: 12px; }
        ',
            ],
            'faq_generator_styles',
        ];

        return $form;
    }

    /**
     * Preview FAQs usando el servicio.
     */
    public function previewFaqs(array &$form, FormStateInterface $form_state): void
    {
        $days = (int) $form_state->getValue('days', 30);
        $limit = (int) $form_state->getValue('limit', 5);
        $source = $form_state->getValue('source', 'all');

        $result = $this->faqGenerator->generateFaqs($days, $limit, $source);

        if ($result['success'] && !empty($result['faqs'])) {
            $form_state->set('generated_faqs', $result['faqs']);
            $this->messenger()->addStatus($this->t('Se generaron @count FAQs a partir de @source preguntas frecuentes (segmento: @segment).', [
                '@count' => count($result['faqs']),
                '@source' => $result['source_questions'] ?? 0,
                '@segment' => $source,
            ]));
        } else {
            $this->messenger()->addWarning($result['message'] ?? $this->t('No se pudieron generar FAQs.'));
        }

        $form_state->setRebuild();
    }


    /**
     * Guarda las FAQs como nodos.
     */
    public function saveFaqs(array &$form, FormStateInterface $form_state): void
    {
        $faqs = $form_state->get('generated_faqs');

        if (empty($faqs)) {
            $this->messenger()->addError($this->t('No hay FAQs para guardar.'));
            return;
        }

        $result = $this->faqGenerator->saveFaqsAsNodes($faqs);

        if ($result['success']) {
            $this->messenger()->addStatus($result['message']);
            // Limpiar estado
            $form_state->set('generated_faqs', NULL);
        } else {
            $this->messenger()->addWarning($result['message']);
        }

        $form_state->setRebuild();
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        // El submit principal no hace nada, usamos callbacks específicos
    }

}
