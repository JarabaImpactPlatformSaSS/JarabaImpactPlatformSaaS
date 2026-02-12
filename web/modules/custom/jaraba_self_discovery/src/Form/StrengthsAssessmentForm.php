<?php

namespace Drupal\jaraba_self_discovery\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de evaluación de Fortalezas (VIA-inspired).
 *
 * Identifica los 5 talentos principales del usuario mediante
 * comparación de pares de fortalezas.
 */
class StrengthsAssessmentForm extends FormBase
{

    /**
     * Catálogo de 24 fortalezas organizadas por virtud.
     */
    protected array $strengths = [
        // Sabiduría.
        'creativity' => ['name' => 'Creatividad', 'desc' => 'Generar ideas nuevas y originales'],
        'curiosity' => ['name' => 'Curiosidad', 'desc' => 'Interés por explorar y aprender'],
        'judgment' => ['name' => 'Criterio', 'desc' => 'Pensar de forma crítica y objetiva'],
        'love_learning' => ['name' => 'Amor por aprender', 'desc' => 'Dominar nuevas habilidades'],
        'perspective' => ['name' => 'Perspectiva', 'desc' => 'Dar consejos sabios a otros'],
        // Coraje.
        'bravery' => ['name' => 'Valentía', 'desc' => 'Actuar a pesar del miedo'],
        'perseverance' => ['name' => 'Perseverancia', 'desc' => 'Terminar lo que se empieza'],
        'honesty' => ['name' => 'Honestidad', 'desc' => 'Ser genuino y auténtico'],
        'zest' => ['name' => 'Vitalidad', 'desc' => 'Vivir con energía y entusiasmo'],
        // Humanidad.
        'love' => ['name' => 'Amor', 'desc' => 'Valorar relaciones cercanas'],
        'kindness' => ['name' => 'Amabilidad', 'desc' => 'Ayudar a los demás'],
        'social_intel' => ['name' => 'Inteligencia social', 'desc' => 'Entender motivaciones de otros'],
        // Justicia.
        'teamwork' => ['name' => 'Trabajo en equipo', 'desc' => 'Colaborar efectivamente'],
        'fairness' => ['name' => 'Equidad', 'desc' => 'Tratar a todos por igual'],
        'leadership' => ['name' => 'Liderazgo', 'desc' => 'Organizar e inspirar grupos'],
        // Templanza.
        'forgiveness' => ['name' => 'Perdón', 'desc' => 'Dejar ir el resentimiento'],
        'humility' => ['name' => 'Humildad', 'desc' => 'No buscar el centro de atención'],
        'prudence' => ['name' => 'Prudencia', 'desc' => 'Tomar decisiones cuidadosas'],
        'self_control' => ['name' => 'Autocontrol', 'desc' => 'Regular impulsos y emociones'],
        // Transcendencia.
        'appreciation' => ['name' => 'Apreciación', 'desc' => 'Notar la belleza y excelencia'],
        'gratitude' => ['name' => 'Gratitud', 'desc' => 'Ser consciente de lo bueno'],
        'hope' => ['name' => 'Esperanza', 'desc' => 'Esperar lo mejor del futuro'],
        'humor' => ['name' => 'Humor', 'desc' => 'Disfrutar la risa y alegría'],
        'spirituality' => ['name' => 'Espiritualidad', 'desc' => 'Conexión con propósito mayor'],
    ];

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'jaraba_strengths_assessment_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        // Inicializar storage en primera carga.
        $storage = $form_state->getStorage();

        if (!isset($storage['pairs'])) {
            $storage['pairs'] = $this->generatePairs();
            $storage['step'] = 0;
            $storage['selections'] = [];
            $form_state->setStorage($storage);
        }

        $step = $storage['step'];
        $pairs = $storage['pairs'];
        $totalPairs = count($pairs);

        $form['#prefix'] = '<div id="strengths-form-wrapper" class="strengths-assessment">';
        $form['#suffix'] = '</div>';
        $form['#attached']['library'][] = 'jaraba_self_discovery/global';

        // Progreso.
        $progress = min(100, round(($step / $totalPairs) * 100));

        $form['progress'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['strengths-progress']],
            'bar' => [
                '#markup' => '<div class="strengths-progress__bar" style="width: ' . $progress . '%"></div>',
            ],
            'text' => [
                '#markup' => '<div class="strengths-progress__text">' . ($step + 1) . ' de ' . $totalPairs . '</div>',
            ],
        ];

        if ($step < $totalPairs) {
            $pair = $pairs[$step];
            $strengthA = $this->strengths[$pair[0]];
            $strengthB = $this->strengths[$pair[1]];

            $form['instructions'] = [
                '#markup' => '<p class="strengths-instructions">' . $this->t('¿Cuál de estas fortalezas te representa mejor?') . '</p>',
            ];

            $form['comparison'] = [
                '#type' => 'container',
                '#attributes' => ['class' => ['strengths-comparison']],
            ];

            $form['comparison']['option_a'] = [
                '#type' => 'container',
                '#attributes' => [
                    'class' => ['strengths-option', 'strengths-option--a'],
                    'data-value' => $pair[0],
                ],
                'radio' => [
                    '#type' => 'radio',
                    '#title' => $strengthA['name'],
                    '#description' => $strengthA['desc'],
                    '#return_value' => $pair[0],
                    '#parents' => ['choice'],
                    '#attributes' => ['class' => ['strengths-radio']],
                ],
            ];

            $form['comparison']['vs'] = [
                '#markup' => '<div class="strengths-vs">VS</div>',
            ];

            $form['comparison']['option_b'] = [
                '#type' => 'container',
                '#attributes' => [
                    'class' => ['strengths-option', 'strengths-option--b'],
                    'data-value' => $pair[1],
                ],
                'radio' => [
                    '#type' => 'radio',
                    '#title' => $strengthB['name'],
                    '#description' => $strengthB['desc'],
                    '#return_value' => $pair[1],
                    '#parents' => ['choice'],
                    '#attributes' => ['class' => ['strengths-radio']],
                ],
            ];

            $form['actions'] = ['#type' => 'actions'];
            $form['actions']['next'] = [
                '#type' => 'submit',
                '#value' => $this->t('Siguiente'),
                '#attributes' => ['class' => ['btn', 'btn--primary']],
                '#ajax' => [
                    'wrapper' => 'strengths-form-wrapper',
                ],
            ];
        } else {
            // Mostrar resultados.
            $selections = $storage['selections'] ?? [];
            $results = $this->calculateResults($selections);

            $this->saveResults($results);

            // Limpiar storage para próxima vez.
            $form_state->setStorage([]);

            $form['results'] = [
                '#markup' => $this->buildResultsHtml($results),
            ];
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $choice = $form_state->getValue('choice');

        // Obtener storage.
        $storage = $form_state->getStorage();

        // Guardar selección.
        $storage['selections'][] = $choice;

        // Avanzar paso.
        $storage['step']++;

        // Guardar storage y rebuild.
        $form_state->setStorage($storage);
        $form_state->setRebuild(TRUE);
    }

    /**
     * Genera pares de fortalezas para comparación.
     */
    protected function generatePairs(): array
    {
        $keys = array_keys($this->strengths);
        $pairs = [];

        // 20 pares aleatorios pero sin repetir el mismo par.
        $used = [];
        while (count($pairs) < 20) {
            shuffle($keys);
            $a = $keys[0];
            $b = $keys[1];
            $pairKey = $a < $b ? "$a-$b" : "$b-$a";

            if (!isset($used[$pairKey])) {
                $pairs[] = [$a, $b];
                $used[$pairKey] = TRUE;
            }
        }

        return $pairs;
    }

    /**
     * Calcula los resultados basados en las selecciones.
     */
    protected function calculateResults(array $selections): array
    {
        $counts = array_count_values($selections);
        arsort($counts);

        $top5 = array_slice($counts, 0, 5, TRUE);
        $results = [];

        foreach ($top5 as $key => $count) {
            if (isset($this->strengths[$key])) {
                $results[$key] = [
                    'name' => $this->strengths[$key]['name'],
                    'desc' => $this->strengths[$key]['desc'],
                    'score' => $count,
                ];
            }
        }

        return $results;
    }

    /**
     * Construye el HTML de resultados.
     */
    protected function buildResultsHtml(array $results): string
    {
        $html = '<div class="strengths-results">';
        $html .= '<div class="strengths-results__header">';
        $html .= '<h2>' . $this->t('Tus 5 Fortalezas Principales') . '</h2>';
        $html .= '<p>' . $this->t('Estas son las fortalezas que más te representan') . '</p>';
        $html .= '</div>';

        $html .= '<div class="strengths-results__list">';
        $rank = 1;
        foreach ($results as $key => $strength) {
            $html .= '<div class="strength-card" style="--delay: ' . (($rank - 1) * 0.1) . 's">';
            $html .= '<div class="strength-card__rank">#' . $rank . '</div>';
            $html .= '<div class="strength-card__content">';
            $html .= '<h3 class="strength-card__name">' . $strength['name'] . '</h3>';
            $html .= '<p class="strength-card__desc">' . $strength['desc'] . '</p>';
            $html .= '</div>';
            $html .= '</div>';
            $rank++;
        }
        $html .= '</div>';

        $html .= '<div class="strengths-results__tips">';
        $html .= '<h3>' . $this->t('¿Cómo usar tus fortalezas?') . '</h3>';
        $html .= '<ul>';
        $html .= '<li>' . $this->t('Busca roles laborales que potencien tus talentos') . '</li>';
        $html .= '<li>' . $this->t('Usa tus fortalezas para superar desafíos') . '</li>';
        $html .= '<li>' . $this->t('Combina fortalezas para mayor impacto') . '</li>';
        $html .= '</ul>';
        $html .= '</div>';

        $html .= '<div class="strengths-results__actions">';
        $html .= '<a href="/my-profile/self-discovery" class="btn btn--secondary">← ' . $this->t('Volver al Dashboard') . '</a>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Guarda los resultados en user data y en entity StrengthAssessment.
     */
    protected function saveResults(array $results): void
    {
        $user = \Drupal::currentUser();
        if ($user->isAuthenticated()) {
            // Retrocompatibilidad: guardar en user.data.
            $userData = \Drupal::service('user.data');
            $userData->set('jaraba_self_discovery', $user->id(), 'strengths_top5', $results);
            $userData->set('jaraba_self_discovery', $user->id(), 'strengths_completed', time());

            // Guardar en Content Entity StrengthAssessment.
            try {
                $storage = \Drupal::entityTypeManager()->getStorage('strength_assessment');
                $entity = $storage->create([
                    'user_id' => $user->id(),
                    'top_strengths' => json_encode($results),
                    'all_scores' => json_encode($results),
                ]);
                $entity->save();
            }
            catch (\Exception $e) {
                \Drupal::logger('jaraba_self_discovery')->error('Error saving StrengthAssessment entity: @error', [
                    '@error' => $e->getMessage(),
                ]);
            }
        }
    }

}
