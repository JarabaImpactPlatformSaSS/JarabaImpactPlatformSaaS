<?php

declare(strict_types=1);

namespace Drupal\jaraba_self_discovery\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\jaraba_self_discovery\Service\TimelineAnalysisService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario Phase 3 del Timeline: identificar patrones recurrentes.
 *
 * Presenta al usuario un analisis de sus eventos del timeline,
 * permite identificar patrones y temas recurrentes.
 */
class TimelinePhase3Form extends FormBase
{

    /**
     * Timeline analysis service.
     */
    protected TimelineAnalysisService $timelineAnalysis;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        $instance = parent::create($container);
        $instance->timelineAnalysis = $container->get('jaraba_self_discovery.timeline_analysis');
        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'jaraba_timeline_phase3_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['#prefix'] = '<div id="timeline-phase3-wrapper" class="timeline-phase3">';
        $form['#suffix'] = '</div>';

        $form['#attached']['library'][] = 'jaraba_self_discovery/global';

        $uid = (int) $this->currentUser()->id();

        // Obtener analisis automatico.
        $patterns = $this->timelineAnalysis->getIdentifiedPatterns($uid);
        $topSkills = $this->timelineAnalysis->getTopSkills($uid);
        $topValues = $this->timelineAnalysis->getTopValues($uid);
        $topFactors = $this->timelineAnalysis->getTopSatisfactionFactors($uid);

        // Seccion de insights generados automaticamente.
        $form['ai_insights'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['timeline-phase3__insights']],
        ];

        $form['ai_insights']['title'] = [
            '#markup' => '<h3>' . $this->t('Analisis de tu Timeline') . '</h3>',
        ];

        if (!empty($topSkills)) {
            $skillsList = implode(', ', $topSkills);
            $form['ai_insights']['skills'] = [
                '#markup' => '<div class="timeline-phase3__insight"><strong>' . $this->t('Habilidades recurrentes:') . '</strong> ' . htmlspecialchars($skillsList) . '</div>',
            ];
        }

        if (!empty($topValues)) {
            $valuesList = implode(', ', $topValues);
            $form['ai_insights']['values'] = [
                '#markup' => '<div class="timeline-phase3__insight"><strong>' . $this->t('Valores recurrentes:') . '</strong> ' . htmlspecialchars($valuesList) . '</div>',
            ];
        }

        if (!empty($topFactors)) {
            $factorsList = implode(', ', array_keys($topFactors));
            $form['ai_insights']['factors'] = [
                '#markup' => '<div class="timeline-phase3__insight"><strong>' . $this->t('Factores de satisfaccion:') . '</strong> ' . htmlspecialchars($factorsList) . '</div>',
            ];
        }

        if (empty($topSkills) && empty($topValues) && empty($topFactors)) {
            $form['ai_insights']['empty'] = [
                '#markup' => '<p>' . $this->t('Completa la Phase 2 de tus eventos para ver patrones. Necesitas al menos 2 eventos descritos.') . '</p>',
            ];
        }

        // Campo para patrones identificados por el usuario.
        $form['patterns_identified'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Patrones que identificas'),
            '#description' => $this->t('Basandote en el analisis anterior y tu reflexion personal, que patrones ves en tu trayectoria.'),
            '#rows' => 5,
        ];

        // Temas recurrentes.
        $form['recurring_themes'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Temas recurrentes'),
            '#description' => $this->t('Temas que se repiten en tu historia. Separa con comas.'),
            '#maxlength' => 512,
        ];

        $form['actions'] = ['#type' => 'actions'];
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Guardar reflexion'),
            '#attributes' => ['class' => ['btn', 'btn--primary']],
        ];

        $form['actions']['back'] = [
            '#type' => 'link',
            '#title' => $this->t('Volver al Timeline'),
            '#url' => \Drupal\Core\Url::fromRoute('jaraba_self_discovery.timeline'),
            '#attributes' => ['class' => ['btn', 'btn--secondary']],
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $uid = (int) $this->currentUser()->id();

        try {
            // Guardar patrones en el ultimo evento del timeline del usuario.
            $storage = \Drupal::entityTypeManager()->getStorage('life_timeline');
            $ids = $storage->getQuery()
                ->accessCheck(FALSE)
                ->condition('user_id', $uid)
                ->sort('created', 'DESC')
                ->range(0, 1)
                ->execute();

            if (!empty($ids)) {
                $entity = $storage->load(reset($ids));
                $patternsText = $form_state->getValue('patterns_identified') ?? '';
                $themes = $form_state->getValue('recurring_themes') ?? '';

                if ($themes) {
                    $patternsText .= "\n\nTemas recurrentes: " . $themes;
                }

                $entity->set('patterns', $patternsText);
                $entity->save();
            }

            $this->messenger()->addStatus($this->t('Reflexion guardada correctamente.'));
        }
        catch (\Exception $e) {
            $this->messenger()->addError($this->t('Error al guardar: @error', [
                '@error' => $e->getMessage(),
            ]));
        }

        $form_state->setRedirectUrl(\Drupal\Core\Url::fromRoute('jaraba_self_discovery.timeline'));
    }

}
