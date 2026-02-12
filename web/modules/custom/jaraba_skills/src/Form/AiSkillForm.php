<?php

declare(strict_types=1);

namespace Drupal\jaraba_skills\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Formulario para crear/editar habilidades IA.
 *
 * Implementa el patrón slide-panel con:
 * - Clases CSS premium para estilos dentro del panel.
 * - Redirect a la misma ruta AJAX para mantener compatibilidad
 *   con el flujo AJAX del slide-panel.js.
 *
 * @see .agent/workflows/slide-panel-modales.md
 */
class AiSkillForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form = parent::buildForm($form, $form_state);

        // Añadir clases para slide-panel styling premium.
        $form['#attributes']['class'][] = 'jaraba-premium-form';
        $form['#attributes']['class'][] = 'slide-panel__form';

        // Añadir Monaco Editor para el campo content.
        // El behavior skill-prompt-editor.js transforma el textarea
        // en un editor con syntax highlighting y autocompletado.
        $form['#attached']['library'][] = 'jaraba_skills/skill.prompt-editor';

        // NOTA: NO cambiar el form action.
        // Drupal usará las rutas nativas entity.ai_skill.add_form/edit_form.
        // El slide-panel.js intercepta el submit y maneja la respuesta.

        return $form;
    }


    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $entity = $this->getEntity();
        $status = parent::save($form, $form_state);

        // Mensaje de confirmación.
        $messenger = \Drupal::messenger();
        if ($status === SAVED_NEW) {
            $messenger->addStatus($this->t('Habilidad "@title" creada correctamente.', [
                '@title' => $entity->label(),
            ]));
        } else {
            $messenger->addStatus($this->t('Habilidad "@title" actualizada correctamente.', [
                '@title' => $entity->label(),
            ]));
        }

        // Redirect al dashboard frontend (no a collection admin).
        // El slide-panel.js detectará este redirect y cerrará el panel.
        $form_state->setRedirectUrl(Url::fromRoute('jaraba_skills.dashboard'));

        return $status;
    }

}
