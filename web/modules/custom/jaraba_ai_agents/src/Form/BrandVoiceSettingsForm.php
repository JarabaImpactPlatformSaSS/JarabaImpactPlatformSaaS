<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\GroupMembershipLoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for Brand Voice settings per tenant.
 */
class BrandVoiceSettingsForm extends ConfigFormBase
{

    /**
     * The group membership loader.
     *
     * @var \Drupal\group\GroupMembershipLoaderInterface
     */
    protected GroupMembershipLoaderInterface $membershipLoader;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        $instance = parent::create($container);
        $instance->membershipLoader = $container->get('group.membership_loader');
        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'jaraba_ai_agents_brand_voice_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames(): array
    {
        $tenantId = $this->getTenantId();
        return ["jaraba_ai_agents.brand_voice.{$tenantId}"];
    }

    /**
     * Gets the current tenant ID.
     */
    protected function getTenantId(): string
    {
        $user = $this->currentUser();
        $memberships = $this->membershipLoader->loadByUser($user);

        if (!empty($memberships)) {
            $membership = reset($memberships);
            return (string) $membership->getGroup()->id();
        }

        return 'global';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $tenantId = $this->getTenantId();
        $config = $this->config("jaraba_ai_agents.brand_voice.{$tenantId}");

        $form['brand_voice'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Brand Voice Configuration'),
            '#description' => $this->t('Configure how AI agents communicate using your brand personality.'),
        ];

        // Archetype selection.
        $form['brand_voice']['archetype'] = [
            '#type' => 'select',
            '#title' => $this->t('Brand Archetype'),
            '#description' => $this->t('Select the personality archetype that best represents your brand.'),
            '#options' => [
                'professional' => $this->t('🏢 Professional - Corporate, trustworthy, formal'),
                'artisan' => $this->t('🎨 Artisan - Authentic, traditional, quality-focused'),
                'innovative' => $this->t('🚀 Innovative - Modern, tech-forward, cutting-edge'),
                'friendly' => $this->t('😊 Friendly - Warm, approachable, neighborly'),
                'expert' => $this->t('📚 Expert - Authoritative, knowledgeable, educational'),
                'playful' => $this->t('🎉 Playful - Fun, energetic, youthful'),
                'luxury' => $this->t('💎 Luxury - Premium, exclusive, sophisticated'),
                'eco' => $this->t('🌱 Eco-Conscious - Sustainable, ethical, green'),
            ],
            '#default_value' => $config->get('archetype') ?? 'professional',
            '#required' => TRUE,
        ];

        // Personality sliders.
        $form['brand_voice']['personality'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Personality Traits'),
            '#description' => $this->t('Adjust each trait on a scale of 1-10.'),
        ];

        $personality = $config->get('personality') ?? [];

        $traits = [
            'formality' => ['Formality', 'Casual ↔ Formal'],
            'warmth' => ['Warmth', 'Distant ↔ Warm'],
            'confidence' => ['Confidence', 'Humble ↔ Bold'],
            'humor' => ['Humor', 'Serious ↔ Playful'],
            'technical' => ['Technical', 'Simple ↔ Expert'],
        ];

        foreach ($traits as $key => [$label, $range]) {
            $form['brand_voice']['personality'][$key] = [
                '#type' => 'range',
                '#title' => $this->t('@label (@range)', ['@label' => $label, '@range' => $range]),
                '#min' => 1,
                '#max' => 10,
                '#default_value' => $personality[$key] ?? 5,
                '#attributes' => ['class' => ['brand-voice-slider']],
            ];
        }

        // Examples.
        $form['brand_voice']['examples'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Style Examples'),
            '#description' => $this->t('Provide examples of good and bad communication style.'),
        ];

        $examples = $config->get('examples') ?? [];
        $numExamples = $form_state->get('num_examples') ?? max(1, count($examples));
        $form_state->set('num_examples', $numExamples);

        for ($i = 0; $i < $numExamples; $i++) {
            $form['brand_voice']['examples'][$i] = [
                '#type' => 'fieldset',
                '#title' => $this->t('Example @num', ['@num' => $i + 1]),
            ];

            $form['brand_voice']['examples'][$i]['context'] = [
                '#type' => 'textfield',
                '#title' => $this->t('Context/Situation'),
                '#default_value' => $examples[$i]['context'] ?? '',
                '#placeholder' => $this->t('e.g., Responding to a customer complaint'),
            ];

            $form['brand_voice']['examples'][$i]['good'] = [
                '#type' => 'textarea',
                '#title' => $this->t('✅ Good Example'),
                '#default_value' => $examples[$i]['good'] ?? '',
                '#rows' => 2,
                '#placeholder' => $this->t('How you WANT the AI to respond'),
            ];

            $form['brand_voice']['examples'][$i]['bad'] = [
                '#type' => 'textarea',
                '#title' => $this->t('❌ Bad Example'),
                '#default_value' => $examples[$i]['bad'] ?? '',
                '#rows' => 2,
                '#placeholder' => $this->t('How you DO NOT want the AI to respond'),
            ];
        }

        $form['brand_voice']['examples']['add_more'] = [
            '#type' => 'submit',
            '#value' => $this->t('Add Another Example'),
            '#submit' => ['::addMoreExamples'],
            '#ajax' => [
                'callback' => '::ajaxRefreshExamples',
                'wrapper' => 'brand-voice-examples-wrapper',
            ],
            '#limit_validation_errors' => [],
        ];

        $form['brand_voice']['examples']['#prefix'] = '<div id="brand-voice-examples-wrapper">';
        $form['brand_voice']['examples']['#suffix'] = '</div>';

        // Terms.
        $form['brand_voice']['terms'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Terminology'),
        ];

        $form['brand_voice']['terms']['preferred_terms'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Preferred Terms'),
            '#description' => $this->t('Words and phrases to use. One per line.'),
            '#default_value' => implode("\n", $config->get('preferred_terms') ?? []),
            '#rows' => 3,
            '#placeholder' => "clients (not customers)\npartners (not vendors)\nsolutions (not products)",
        ];

        $form['brand_voice']['terms']['forbidden_terms'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Forbidden Terms'),
            '#description' => $this->t('Words and phrases to avoid. One per line.'),
            '#default_value' => implode("\n", $config->get('forbidden_terms') ?? []),
            '#rows' => 3,
            '#placeholder' => "cheap\nproblem\nunfortunately",
        ];

        // Custom instructions.
        $form['brand_voice']['custom_instructions'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Additional Instructions'),
            '#description' => $this->t('Any extra instructions for the AI about your brand voice.'),
            '#default_value' => $config->get('custom_instructions') ?? '',
            '#rows' => 4,
            '#placeholder' => $this->t("Always mention our commitment to sustainability.\nNever use aggressive sales language.\nInclude our tagline when appropriate."),
        ];

        // Preview.
        $form['preview'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Preview'),
        ];

        $form['preview']['test_prompt'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Test your Brand Voice'),
            '#description' => $this->t('Enter a test scenario to see how the AI would respond.'),
            '#rows' => 2,
            '#placeholder' => $this->t('Customer asks about product availability'),
        ];

        $form['preview']['generate_preview'] = [
            '#type' => 'submit',
            '#value' => $this->t('🔮 Generate Preview'),
            '#submit' => ['::generatePreview'],
            '#ajax' => [
                'callback' => '::ajaxPreviewResponse',
                'wrapper' => 'brand-voice-preview-result',
            ],
        ];

        $form['preview']['result'] = [
            '#type' => 'container',
            '#attributes' => ['id' => 'brand-voice-preview-result'],
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * Ajax callback for adding more examples.
     */
    public function addMoreExamples(array &$form, FormStateInterface $form_state): void
    {
        $numExamples = $form_state->get('num_examples') ?? 1;
        $form_state->set('num_examples', $numExamples + 1);
        $form_state->setRebuild();
    }

    /**
     * Ajax callback to refresh examples.
     */
    public function ajaxRefreshExamples(array &$form, FormStateInterface $form_state): array
    {
        return $form['brand_voice']['examples'];
    }

    /**
     * Generate preview callback.
     */
    public function generatePreview(array &$form, FormStateInterface $form_state): void
    {
        $form_state->setRebuild();
    }

    /**
     * Ajax callback for preview response.
     */
    public function ajaxPreviewResponse(array &$form, FormStateInterface $form_state): array
    {
        $testPrompt = $form_state->getValue('test_prompt');

        if (empty($testPrompt)) {
            return [
                '#markup' => '<div class="messages messages--warning">' . $this->t('Please enter a test scenario.') . '</div>',
            ];
        }

        // Build preview system prompt from current form values.
        $archetype = $form_state->getValue('archetype');
        $personality = [];
        foreach (['formality', 'warmth', 'confidence', 'humor', 'technical'] as $trait) {
            $personality[$trait] = $form_state->getValue($trait);
        }

        return [
            '#markup' => '<div class="messages messages--status"><strong>' . $this->t('Preview:') . '</strong><br>' .
                $this->t('Based on archetype "@archetype" with formality=@formality, warmth=@warmth', [
                    '@archetype' => $archetype,
                    '@formality' => $personality['formality'] ?? 5,
                    '@warmth' => $personality['warmth'] ?? 5,
                ]) . '<br><em>' . $this->t('(Full AI preview requires saving and testing via API)') . '</em></div>',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $tenantId = $this->getTenantId();
        $config = $this->config("jaraba_ai_agents.brand_voice.{$tenantId}");

        // Save archetype.
        $config->set('archetype', $form_state->getValue('archetype'));

        // Save personality traits.
        $personality = [];
        foreach (['formality', 'warmth', 'confidence', 'humor', 'technical'] as $trait) {
            $personality[$trait] = (int) $form_state->getValue($trait);
        }
        $config->set('personality', $personality);

        // Save examples.
        $examples = [];
        $numExamples = $form_state->get('num_examples') ?? 1;
        for ($i = 0; $i < $numExamples; $i++) {
            $context = $form_state->getValue([$i, 'context']);
            $good = $form_state->getValue([$i, 'good']);
            $bad = $form_state->getValue([$i, 'bad']);

            if (!empty($good) || !empty($bad)) {
                $examples[] = [
                    'context' => $context ?? '',
                    'good' => $good ?? '',
                    'bad' => $bad ?? '',
                ];
            }
        }
        $config->set('examples', $examples);

        // Save terms.
        $preferredTerms = array_filter(array_map('trim', explode("\n", $form_state->getValue('preferred_terms') ?? '')));
        $forbiddenTerms = array_filter(array_map('trim', explode("\n", $form_state->getValue('forbidden_terms') ?? '')));
        $config->set('preferred_terms', $preferredTerms);
        $config->set('forbidden_terms', $forbiddenTerms);

        // Save custom instructions.
        $config->set('custom_instructions', $form_state->getValue('custom_instructions'));

        $config->save();

        parent::submitForm($form, $form_state);
        $this->messenger()->addStatus($this->t('Brand Voice settings saved for tenant @tenant.', ['@tenant' => $tenantId]));
    }

}
