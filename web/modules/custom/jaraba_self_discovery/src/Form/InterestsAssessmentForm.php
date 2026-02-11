<?php

namespace Drupal\jaraba_self_discovery\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * Formulario de evaluación RIASEC (Intereses Vocacionales).
 *
 * Test basado en el modelo de Holland que identifica 6 tipos de intereses:
 * - Realista (R): Práctico, técnico, mecánico
 * - Investigador (I): Analítico, científico, curioso
 * - Artístico (A): Creativo, expresivo, imaginativo
 * - Social (S): Ayudar, enseñar, comunicar
 * - Emprendedor (E): Liderar, persuadir, vender
 * - Convencional (C): Organizar, estructurar, administrar
 */
class InterestsAssessmentForm extends FormBase
{

    /**
     * Las 6 categorías RIASEC con sus preguntas.
     */
    protected array $riasecQuestions = [
        'R' => [
            'label' => 'Realista',
            'description' => 'Trabajo práctico y técnico',
            'color' => '#10B981',
            'questions' => [
                'r1' => 'Trabajar con herramientas y maquinaria',
                'r2' => 'Reparar objetos o equipos',
                'r3' => 'Construir o fabricar cosas con las manos',
                'r4' => 'Trabajar al aire libre',
                'r5' => 'Operar vehículos o equipos pesados',
                'r6' => 'Resolver problemas técnicos prácticos',
            ],
        ],
        'I' => [
            'label' => 'Investigador',
            'description' => 'Análisis y descubrimiento',
            'color' => '#3B82F6',
            'questions' => [
                'i1' => 'Investigar y analizar datos',
                'i2' => 'Resolver problemas complejos',
                'i3' => 'Realizar experimentos científicos',
                'i4' => 'Leer y estudiar temas en profundidad',
                'i5' => 'Desarrollar teorías e hipótesis',
                'i6' => 'Usar la lógica para tomar decisiones',
            ],
        ],
        'A' => [
            'label' => 'Artístico',
            'description' => 'Creatividad y expresión',
            'color' => '#8B5CF6',
            'questions' => [
                'a1' => 'Expresarme a través del arte o la música',
                'a2' => 'Diseñar o crear cosas nuevas',
                'a3' => 'Escribir historias, poesía o contenido',
                'a4' => 'Actuar o presentar ante un público',
                'a5' => 'Trabajar en ambientes poco estructurados',
                'a6' => 'Usar mi imaginación para resolver problemas',
            ],
        ],
        'S' => [
            'label' => 'Social',
            'description' => 'Ayudar y enseñar',
            'color' => '#F59E0B',
            'questions' => [
                's1' => 'Ayudar a otros con sus problemas',
                's2' => 'Enseñar o capacitar a personas',
                's3' => 'Trabajar en equipo colaborativo',
                's4' => 'Escuchar y dar consejos',
                's5' => 'Organizar actividades grupales',
                's6' => 'Cuidar el bienestar de otros',
            ],
        ],
        'E' => [
            'label' => 'Emprendedor',
            'description' => 'Liderazgo y persuasión',
            'color' => '#EF4444',
            'questions' => [
                'e1' => 'Liderar equipos o proyectos',
                'e2' => 'Persuadir o negociar con otros',
                'e3' => 'Tomar riesgos calculados',
                'e4' => 'Vender productos o ideas',
                'e5' => 'Competir para alcanzar metas',
                'e6' => 'Tomar decisiones importantes',
            ],
        ],
        'C' => [
            'label' => 'Convencional',
            'description' => 'Organización y estructura',
            'color' => '#6B7280',
            'questions' => [
                'c1' => 'Organizar información y archivos',
                'c2' => 'Seguir procedimientos establecidos',
                'c3' => 'Trabajar con números y estadísticas',
                'c4' => 'Prestar atención a los detalles',
                'c5' => 'Mantener registros precisos',
                'c6' => 'Trabajar en ambientes estructurados',
            ],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'jaraba_interests_assessment_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['#prefix'] = '<div id="riasec-form-wrapper" class="riasec-assessment">';
        $form['#suffix'] = '</div>';

        $form['#attached']['library'][] = 'jaraba_self_discovery/riasec';

        // Instrucciones.
        $form['instructions'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['riasec-instructions']],
            'title' => [
                '#markup' => '<h3>' . $this->t('¿Cómo completar el test?') . '</h3>',
            ],
            'text' => [
                '#markup' => '<p>' . $this->t('Valora cada actividad del 1 (nada interesante) al 5 (muy interesante). No hay respuestas correctas o incorrectas, responde según tus preferencias genuinas.') . '</p>',
            ],
        ];

        // Contenedor de preguntas por categoría.
        $form['questions'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['riasec-questions']],
        ];

        foreach ($this->riasecQuestions as $code => $category) {
            $form['questions'][$code] = [
                '#type' => 'fieldset',
                '#title' => $category['label'],
                '#attributes' => [
                    'class' => ['riasec-category', 'riasec-category--' . strtolower($code)],
                    'data-category' => $code,
                    'style' => '--category-color: ' . $category['color'],
                ],
                '#description' => $category['description'],
            ];

            foreach ($category['questions'] as $qid => $question) {
                $form['questions'][$code][$qid . '_wrapper'] = [
                    '#type' => 'container',
                    '#attributes' => ['class' => ['riasec-question-wrapper']],
                ];

                $form['questions'][$code][$qid . '_wrapper'][$qid] = [
                    '#type' => 'range',
                    '#title' => $question,
                    '#min' => 1,
                    '#max' => 5,
                    '#default_value' => 3,
                    '#attributes' => [
                        'class' => ['riasec-slider'],
                        'data-question' => $qid,
                        'oninput' => "this.nextElementSibling.querySelector('.riasec-value').textContent = this.value",
                    ],
                ];

                // Escala numérica y valor actual.
                $form['questions'][$code][$qid . '_wrapper']['scale'] = [
                    '#markup' => '
                        <div class="riasec-scale">
                            <span class="riasec-value" aria-live="polite">3</span>
                            <div class="riasec-scale__numbers">
                                <span>1</span><span>2</span><span>3</span><span>4</span><span>5</span>
                            </div>
                            <div class="riasec-scale__labels">
                                <span>Nada</span><span>Mucho</span>
                            </div>
                        </div>',
                ];
            }
        }

        // Botón de envío.
        $form['actions'] = [
            '#type' => 'actions',
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Ver mis resultados'),
            '#attributes' => [
                'class' => ['btn', 'btn--primary', 'btn--lg'],
            ],
            '#ajax' => [
                'callback' => '::submitFormAjax',
                'wrapper' => 'riasec-form-wrapper',
                'effect' => 'fade',
            ],
        ];

        return $form;
    }

    /**
     * AJAX callback para mostrar resultados.
     */
    public function submitFormAjax(array &$form, FormStateInterface $form_state): AjaxResponse
    {
        $response = new AjaxResponse();

        // Calcular puntuaciones.
        $scores = $this->calculateScores($form_state);
        $code = $this->generateCode($scores);
        $suggestions = $this->getCareerSuggestions($code);

        // Generar HTML de resultados.
        $resultsHtml = $this->buildResultsHtml($scores, $code, $suggestions);

        $response->addCommand(new ReplaceCommand('#riasec-form-wrapper', $resultsHtml));
        $response->addCommand(new InvokeCommand(NULL, 'initRiasecChart', [$scores]));

        // Guardar resultados en user data.
        $this->saveResults($scores, $code);

        return $response;
    }

    /**
     * Calcula las puntuaciones por categoría.
     */
    protected function calculateScores(FormStateInterface $form_state): array
    {
        $scores = [];

        foreach ($this->riasecQuestions as $code => $category) {
            $total = 0;
            foreach (array_keys($category['questions']) as $qid) {
                $total += (int) $form_state->getValue($qid) ?: 3;
            }
            // Normalizar a escala 0-100.
            $scores[$code] = round(($total / 30) * 100);
        }

        return $scores;
    }

    /**
     * Genera el código de 3 letras RIASEC.
     */
    protected function generateCode(array $scores): string
    {
        arsort($scores);
        return implode('', array_slice(array_keys($scores), 0, 3));
    }

    /**
     * Obtiene sugerencias de carreras según el código.
     */
    protected function getCareerSuggestions(string $code): array
    {
        $suggestions = [
            'RIA' => ['Ingeniero/a Mecánico', 'Arquitecto/a', 'Diseñador/a Industrial'],
            'RIS' => ['Fisioterapeuta', 'Enfermero/a', 'Técnico/a de Laboratorio'],
            'RIE' => ['Gerente de Operaciones', 'Director/a Técnico', 'Emprendedor/a Tech'],
            'RIC' => ['Ingeniero/a de Calidad', 'Técnico/a de Datos', 'Analista de Sistemas'],
            'RAS' => ['Terapeuta Ocupacional', 'Instructor/a de Artes', 'Coach Deportivo'],
            'RAE' => ['Director/a Creativo', 'Productor/a', 'Diseñador/a de Experiencias'],
            'RSE' => ['Bombero/a', 'Policía', 'Instructor/a de Fitness'],
            'RSC' => ['Técnico/a Administrativo', 'Asistente de Logística', 'Coordinador/a'],
            'IAS' => ['Psicólogo/a', 'Investigador/a Social', 'Escritor/a Científico'],
            'IAE' => ['Consultor/a de Innovación', 'Director/a de I+D', 'Emprendedor/a'],
            'IAC' => ['Analista de Datos', 'Científico/a de Datos', 'Investigador/a'],
            'ISE' => ['Médico/a', 'Profesor/a Universitario', 'Consultor/a'],
            'ISC' => ['Contador/a', 'Auditor/a', 'Economista'],
            'IEC' => ['Abogado/a Corporativo', 'Consultor/a de Gestión', 'Analista Financiero'],
            'ASE' => ['Maestro/a de Arte', 'Terapeuta de Arte', 'Comunicador/a Social'],
            'ASC' => ['Bibliotecario/a', 'Curador/a de Museo', 'Archivista'],
            'AEC' => ['Publicista', 'Diseñador/a Gráfico', 'Community Manager'],
            'SEC' => ['Administrador/a de RRHH', 'Gerente de Ventas', 'Director/a Comercial'],
            'SEI' => ['Médico/a', 'Profesor/a', 'Psicólogo/a Organizacional'],
            'SCI' => ['Enfermero/a', 'Asistente Social', 'Terapeuta'],
            'ECS' => ['Gerente General', 'Director/a Ejecutivo', 'Emprendedor/a'],
            'ECI' => ['CEO', 'Director/a Financiero', 'Inversor/a'],
            'CEI' => ['Abogado/a', 'Juez/a', 'Notario/a'],
            'CES' => ['Administrador/a', 'Gerente de Oficina', 'Coordinador/a'],
        ];

        // Buscar coincidencia exacta o similar.
        if (isset($suggestions[$code])) {
            return $suggestions[$code];
        }

        // Buscar por primeras 2 letras.
        $prefix = substr($code, 0, 2);
        foreach ($suggestions as $key => $careers) {
            if (strpos($key, $prefix) === 0) {
                return $careers;
            }
        }

        // Fallback genérico.
        return ['Explorar más opciones según tu perfil', 'Consultar con orientador vocacional', 'Investigar carreras relacionadas'];
    }

    /**
     * Construye el HTML de resultados.
     */
    protected function buildResultsHtml(array $scores, string $code, array $suggestions): string
    {
        $labels = [];
        $colors = [];
        foreach ($this->riasecQuestions as $c => $cat) {
            $labels[$c] = $cat['label'];
            $colors[$c] = $cat['color'];
        }

        $suggestionsHtml = '';
        foreach ($suggestions as $career) {
            $suggestionsHtml .= '<li class="riasec-suggestion">' . htmlspecialchars($career) . '</li>';
        }

        $scoresJson = json_encode($scores);
        $labelsJson = json_encode($labels);
        $colorsJson = json_encode($colors);

        return <<<HTML
<div id="riasec-form-wrapper" class="riasec-results">
  <div class="riasec-results__header">
    <h2>Tu Perfil RIASEC</h2>
    <div class="riasec-code">
      <span class="riasec-code__label">Tu código:</span>
      <span class="riasec-code__value">{$code}</span>
    </div>
  </div>
  
  <div class="riasec-results__chart">
    <canvas id="riasec-hexagon-chart" width="400" height="400"></canvas>
  </div>
  
  <div class="riasec-results__scores">
    <h3>Puntuaciones por área</h3>
    <div class="riasec-scores-grid" id="riasec-scores" 
         data-scores='{$scoresJson}' 
         data-labels='{$labelsJson}'
         data-colors='{$colorsJson}'>
    </div>
  </div>
  
  <div class="riasec-results__suggestions">
    <h3>Carreras sugeridas para tu perfil</h3>
    <ul class="riasec-suggestions-list">
      {$suggestionsHtml}
    </ul>
  </div>
  
  <div class="riasec-results__actions">
    <a href="/my-profile/self-discovery" class="btn btn--secondary">← Volver al Dashboard</a>
  </div>
</div>
HTML;
    }

    /**
     * Guarda los resultados en user data.
     */
    protected function saveResults(array $scores, string $code): void
    {
        $user = \Drupal::currentUser();
        if ($user->isAuthenticated()) {
            $userData = \Drupal::service('user.data');
            $userData->set('jaraba_self_discovery', $user->id(), 'riasec_scores', $scores);
            $userData->set('jaraba_self_discovery', $user->id(), 'riasec_code', $code);
            $userData->set('jaraba_self_discovery', $user->id(), 'riasec_completed', time());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        // El submit se maneja via AJAX.
    }

}
