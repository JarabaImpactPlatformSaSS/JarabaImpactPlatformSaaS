<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\jaraba_customer_success\Service\NpsSurveyService;
use Psr\Log\LoggerInterface;

/**
 * Formulario de envio de respuesta NPS.
 *
 * PROPOSITO:
 * Permite a los usuarios enviar su puntuacion NPS (0-10) con
 * comentario opcional y checkbox de recomendacion.
 *
 * LOGICA:
 * - Score: radio buttons 0-10 con agrupacion visual.
 * - Comment: textarea opcional para feedback cualitativo.
 * - Would recommend: checkbox de confirmacion.
 * - Validacion estricta del rango 0-10.
 * - Submit via NpsSurveyService.
 */
class NpsSurveySubmissionForm extends FormBase {

  public function __construct(
    protected NpsSurveyService $npsSurveyService,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_customer_success.nps_survey'),
      $container->get('logger.channel.jaraba_customer_success'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_cs_nps_survey_submission_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#attributes']['class'][] = 'cs-nps-survey__form';

    // Hidden tenant_id field (set by JS or context).
    $form['tenant_id'] = [
      '#type' => 'hidden',
      '#default_value' => '',
    ];

    // Score: radio buttons 0-10.
    $options = [];
    for ($i = 0; $i <= 10; $i++) {
      $options[$i] = (string) $i;
    }

    $form['score'] = [
      '#type' => 'radios',
      '#title' => $this->t('Score'),
      '#description' => $this->t('0 = Not at all likely, 10 = Extremely likely'),
      '#options' => $options,
      '#required' => TRUE,
      '#attributes' => [
        'class' => ['cs-nps-survey__score-radios'],
      ],
    ];

    // Comment: textarea optional.
    $form['comment'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Comment'),
      '#description' => $this->t('Optional: Tell us more about your experience.'),
      '#rows' => 3,
      '#attributes' => [
        'class' => ['cs-nps-survey__comment'],
        'placeholder' => $this->t('What is the primary reason for your score?'),
      ],
    ];

    // Would recommend: checkbox.
    $form['would_recommend'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I would recommend this platform to others'),
      '#attributes' => [
        'class' => ['cs-nps-survey__recommend'],
      ],
    ];

    // Submit button.
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit Response'),
      '#attributes' => [
        'class' => ['cs-nps-survey__submit'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $score = $form_state->getValue('score');

    if ($score === NULL || $score === '') {
      $form_state->setErrorByName('score', $this->t('Please select a score.'));
      return;
    }

    $score = (int) $score;
    if ($score < 0 || $score > 10) {
      $form_state->setErrorByName('score', $this->t('Score must be between 0 and 10.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    try {
      $tenant_id = (string) $form_state->getValue('tenant_id');
      $score = (int) $form_state->getValue('score');
      $comment = (string) $form_state->getValue('comment');

      if (empty($tenant_id)) {
        $tenant_id = 'global';
      }

      $this->npsSurveyService->collectResponse($tenant_id, $score, $comment);
      $this->npsSurveyService->markSurveySent($tenant_id);

      $this->logger->info('NPS form submission: tenant=@tenant score=@score', [
        '@tenant' => $tenant_id,
        '@score' => $score,
      ]);

      $this->messenger()->addStatus($this->t('Thank you for your feedback! Your response has been recorded.'));
    }
    catch (\Exception $e) {
      $this->logger->error('NPS form submission error: @msg', [
        '@msg' => $e->getMessage(),
      ]);

      $this->messenger()->addError($this->t('An error occurred while submitting your response. Please try again.'));
    }
  }

}
