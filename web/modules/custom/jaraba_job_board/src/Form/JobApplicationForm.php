<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\jaraba_job_board\Entity\JobPostingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Job application form.
 *
 * Allows candidates to apply to a job posting.
 */
class JobApplicationForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'jaraba_job_application_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, ?JobPostingInterface $job_posting = NULL): array
    {
        if (!$job_posting) {
            $form['error'] = [
                '#markup' => $this->t('Job posting not found.'),
            ];
            return $form;
        }

        $form_state->set('job_posting', $job_posting);

        $form['#attributes']['class'][] = 'job-application-form';

        $form['job_info'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['job-application__info']],
        ];

        $form['job_info']['title'] = [
            '#markup' => '<h2 class="job-application__title">' . $job_posting->getTitle() . '</h2>',
        ];

        $form['cover_letter'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Carta de presentación'),
            '#description' => $this->t('Explica por qué eres el candidato ideal para este puesto.'),
            '#rows' => 8,
            '#attributes' => [
                'placeholder' => $this->t('Escribe tu carta de presentación...'),
            ],
        ];

        $form['resume'] = [
            '#type' => 'managed_file',
            '#title' => $this->t('CV / Currículum'),
            '#description' => $this->t('Sube tu CV en formato PDF, DOC o DOCX (máx. 5MB).'),
            '#upload_location' => 'private://job_applications/',
            '#upload_validators' => [
                'file_validate_extensions' => ['pdf doc docx'],
                'file_validate_size' => [5 * 1024 * 1024],
            ],
        ];

        $form['expected_salary'] = [
            '#type' => 'number',
            '#title' => $this->t('Expectativa salarial anual (€)'),
            '#description' => $this->t('Opcional. Indica tu expectativa salarial bruta anual.'),
            '#min' => 0,
            '#step' => 1000,
        ];

        $form['availability'] = [
            '#type' => 'select',
            '#title' => $this->t('Disponibilidad'),
            '#options' => [
                '' => $this->t('- Selecciona -'),
                'immediate' => $this->t('Inmediata'),
                '2_weeks' => $this->t('2 semanas'),
                '1_month' => $this->t('1 mes'),
                '2_months' => $this->t('2 meses'),
                'other' => $this->t('Otra'),
            ],
        ];

        $form['notes'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Notas adicionales'),
            '#description' => $this->t('Cualquier información adicional que quieras compartir.'),
            '#rows' => 3,
        ];

        $form['actions'] = ['#type' => 'actions'];
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Enviar candidatura'),
            '#attributes' => [
                'class' => ['jaraba-btn', 'jaraba-btn--primary', 'jaraba-btn--lg'],
            ],
        ];

        $form['actions']['cancel'] = [
            '#type' => 'link',
            '#title' => $this->t('Cancelar'),
            '#url' => Url::fromRoute('jaraba_job_board.job_detail', [
                'job_posting' => $job_posting->id(),
            ]),
            '#attributes' => [
                'class' => ['jaraba-btn', 'jaraba-btn--ghost'],
            ],
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $job_posting = $form_state->get('job_posting');
        $user = $this->currentUser();

        try {
            $entity_type_manager = \Drupal::entityTypeManager();
            $application = $entity_type_manager->getStorage('job_application')->create([
                'job_posting_id' => $job_posting->id(),
                'user_id' => $user->id(),
                'status' => 'submitted',
                'cover_letter' => $form_state->getValue('cover_letter'),
                'expected_salary' => $form_state->getValue('expected_salary'),
                'availability' => $form_state->getValue('availability'),
                'notes' => $form_state->getValue('notes'),
            ]);

            // Attach resume file if uploaded.
            $resume_fids = $form_state->getValue('resume');
            if (!empty($resume_fids)) {
                $fid = reset($resume_fids);
                $file = $entity_type_manager->getStorage('file')->load($fid);
                if ($file) {
                    $file->setPermanent();
                    $file->save();
                    $application->set('resume', $fid);
                }
            }

            $application->save();

            $this->messenger()->addStatus(
                $this->t('¡Tu candidatura ha sido enviada correctamente! Te notificaremos sobre el estado.')
            );

            $form_state->setRedirectUrl(
                Url::fromRoute('jaraba_job_board.my_applications')
            );
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_job_board')->error(
                'Error submitting application: @msg',
                ['@msg' => $e->getMessage()]
            );
            $this->messenger()->addError(
                $this->t('Ha ocurrido un error al enviar tu candidatura. Por favor, inténtalo de nuevo.')
            );
        }
    }

}
