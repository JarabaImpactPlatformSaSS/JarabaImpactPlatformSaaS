<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Premium form handler for CandidateProfile entity.
 *
 * Used inside slide-panel and modal dialogs for editing personal info,
 * professional summary, location, preferences, etc.
 */
class CandidateProfileForm extends ContentEntityForm
{

    /**
     * Fields to hide from the form (internal/auto-set).
     */
    private const HIDDEN_FIELDS = [
        'user_id', 'uid', 'created', 'changed', 'completion_percent',
        'is_verified', 'source',
    ];

    /**
     * Section definitions with Spanish labels and field groupings.
     */
    private const SECTIONS = [
        'personal' => [
            'label' => 'Información Personal',
            'icon_cat' => 'user',
            'icon_name' => 'user',
            'fields' => ['first_name', 'last_name', 'email', 'phone', 'photo'],
            'open' => TRUE,
        ],
        'professional' => [
            'label' => 'Perfil Profesional',
            'icon_cat' => 'business',
            'icon_name' => 'briefcase',
            'fields' => ['headline', 'summary', 'experience_years', 'experience_level', 'education_level'],
            'open' => TRUE,
        ],
        'location' => [
            'label' => 'Ubicación',
            'icon_cat' => 'ui',
            'icon_name' => 'map-pin',
            'fields' => ['city', 'province', 'country', 'postal_code', 'willing_to_relocate', 'relocation_countries'],
            'open' => FALSE,
        ],
        'preferences' => [
            'label' => 'Preferencias Laborales',
            'icon_cat' => 'business',
            'icon_name' => 'target',
            'fields' => ['availability_status', 'available_from', 'salary_expectation', 'salary_currency'],
            'open' => FALSE,
        ],
        'online' => [
            'label' => 'Presencia Online',
            'icon_cat' => 'ui',
            'icon_name' => 'globe',
            'fields' => ['linkedin_url', 'github_url', 'portfolio_url', 'website_url'],
            'open' => FALSE,
        ],
        'privacy' => [
            'label' => 'Privacidad',
            'icon_cat' => 'ui',
            'icon_name' => 'shield',
            'fields' => ['is_public', 'show_photo', 'show_contact'],
            'open' => FALSE,
        ],
    ];

    /**
     * Spanish labels for fields.
     */
    private const FIELD_LABELS = [
        'first_name' => 'Nombre',
        'last_name' => 'Apellidos',
        'email' => 'Correo electrónico',
        'phone' => 'Teléfono',
        'photo' => 'Foto de perfil',
        'headline' => 'Titular profesional',
        'summary' => 'Resumen profesional',
        'experience_years' => 'Años de experiencia',
        'experience_level' => 'Nivel de experiencia',
        'education_level' => 'Nivel de formación',
        'city' => 'Ciudad',
        'province' => 'Provincia',
        'country' => 'País',
        'postal_code' => 'Código postal',
        'willing_to_relocate' => 'Dispuesto a reubicarse',
        'relocation_countries' => 'Países de interés para reubicación',
        'availability_status' => 'Estado de disponibilidad',
        'available_from' => 'Disponible desde',
        'salary_expectation' => 'Expectativa salarial',
        'salary_currency' => 'Moneda del salario',
        'linkedin_url' => 'LinkedIn',
        'github_url' => 'GitHub',
        'portfolio_url' => 'Portfolio',
        'website_url' => 'Sitio web',
        'is_public' => 'Perfil público',
        'show_photo' => 'Mostrar foto',
        'show_contact' => 'Mostrar contacto',
    ];

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form = parent::buildForm($form, $form_state);

        // ── Attach premium forms library ─────────────────────────────────
        $form['#attached']['library'][] = 'ecosistema_jaraba_core/premium-forms';

        // ── CSS classes ──────────────────────────────────────────────────
        $form['#attributes']['class'][] = 'premium-entity-form';
        $form['#attributes']['class'][] = 'premium-entity-form--candidate-profile';
        $form['#attributes']['class'][] = 'profile-section-form';

        // ── Premium header ───────────────────────────────────────────────
        $form['section_header'] = [
            '#type' => 'inline_template',
            '#template' => '<div class="premium-form__header">
              <span class="premium-form__header-icon">{{ jaraba_icon("user", "user", { variant: "duotone", size: "28px", color: "impulse" }) }}</span>
              <div class="premium-form__header-text">
                <h2 class="premium-form__title">{{ title }}</h2>
                <p class="premium-form__subtitle">{{ subtitle }}</p>
              </div>
            </div>',
            '#context' => [
                'title' => $this->t('Editar Perfil'),
                'subtitle' => $this->t('Actualiza tu información profesional para mejorar tu visibilidad.'),
            ],
            '#weight' => -1000,
        ];

        // ── Hide internal fields ─────────────────────────────────────────
        foreach (self::HIDDEN_FIELDS as $field_name) {
            if (isset($form[$field_name])) {
                $form[$field_name]['#access'] = FALSE;
            }
        }

        // ── Apply Spanish labels ─────────────────────────────────────────
        foreach (self::FIELD_LABELS as $field_name => $label) {
            if (!isset($form[$field_name])) {
                continue;
            }
            if (isset($form[$field_name]['widget'][0]['value']['#title'])) {
                $form[$field_name]['widget'][0]['value']['#title'] = $label;
            }
            elseif (isset($form[$field_name]['widget']['#title'])) {
                $form[$field_name]['widget']['#title'] = $label;
            }
            elseif (isset($form[$field_name]['widget'][0]['#title'])) {
                $form[$field_name]['widget'][0]['#title'] = $label;
            }
        }

        // ── Build glass-card sections ────────────────────────────────────
        foreach (self::SECTIONS as $section_id => $section) {
            $form['section_' . $section_id] = [
                '#type' => 'container',
                '#attributes' => [
                    'class' => ['premium-form__section', 'glass-card'],
                ],
            ];

            // Section label.
            $form['section_' . $section_id]['label'] = [
                '#type' => 'inline_template',
                '#template' => '<h3 class="premium-form__section-title">{{ jaraba_icon(icon_cat, icon_name, { variant: "duotone", size: "18px", color: "impulse" }) }} {{ label }}</h3>',
                '#context' => [
                    'icon_cat' => $section['icon_cat'],
                    'icon_name' => $section['icon_name'],
                    'label' => $section['label'],
                ],
                '#weight' => -100,
            ];

            // Move fields into section.
            foreach ($section['fields'] as $field_name) {
                if (isset($form[$field_name])) {
                    unset($form[$field_name]['#group']);
                    $form['section_' . $section_id][$field_name] = $form[$field_name];
                    unset($form[$field_name]);
                }
            }
        }

        // ── Actions bar — premium styling ────────────────────────────────
        if (isset($form['actions'])) {
            $form['actions']['#weight'] = 9999;
            $form['actions']['#attributes']['class'][] = 'premium-form__actions';

            if (isset($form['actions']['submit'])) {
                $form['actions']['submit']['#value'] = $this->t('Guardar cambios');
                $form['actions']['submit']['#attributes']['class'][] = 'button--primary';
            }

            unset($form['actions']['delete']);
        }

        // Remove vertical-tabs artifacts.
        unset($form['tabs'], $form['advanced']);

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $result = parent::save($form, $form_state);

        if ($result === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Perfil creado correctamente.'));
        }
        else {
            $this->messenger()->addStatus($this->t('Perfil actualizado correctamente.'));
        }

        // Redirect to profile section route for slide-panel reload.
        $form_state->setRedirect('jaraba_candidate.my_profile.edit');

        return $result;
    }

}
