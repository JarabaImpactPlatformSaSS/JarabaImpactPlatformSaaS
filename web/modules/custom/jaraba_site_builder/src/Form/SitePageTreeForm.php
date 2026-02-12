<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para editar un nodo del árbol de páginas.
 */
class SitePageTreeForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state): array
    {
        $form = parent::form($form, $form_state);

        // Configuración de jerarquía.
        $form['hierarchy'] = [
            '#type' => 'details',
            '#title' => $this->t('Posición en el Árbol'),
            '#open' => TRUE,
            '#weight' => 0,
        ];
        $form['hierarchy']['page_id'] = $form['page_id'];
        $form['hierarchy']['parent_id'] = $form['parent_id'];
        $form['hierarchy']['weight'] = $form['weight'];
        unset($form['page_id'], $form['parent_id'], $form['weight']);

        // Visibilidad.
        $form['visibility'] = [
            '#type' => 'details',
            '#title' => $this->t('Visibilidad'),
            '#open' => TRUE,
            '#weight' => 10,
        ];
        $form['visibility']['show_in_navigation'] = $form['show_in_navigation'];
        $form['visibility']['show_in_sitemap'] = $form['show_in_sitemap'];
        $form['visibility']['show_in_footer'] = $form['show_in_footer'];
        $form['visibility']['show_in_breadcrumbs'] = $form['show_in_breadcrumbs'];
        unset($form['show_in_navigation'], $form['show_in_sitemap'], $form['show_in_footer'], $form['show_in_breadcrumbs']);

        // Override de navegación.
        $form['nav_override'] = [
            '#type' => 'details',
            '#title' => $this->t('Personalización del Menú'),
            '#open' => FALSE,
            '#weight' => 20,
        ];
        $form['nav_override']['nav_title'] = $form['nav_title'];
        $form['nav_override']['nav_icon'] = $form['nav_icon'];
        $form['nav_override']['nav_highlight'] = $form['nav_highlight'];
        $form['nav_override']['nav_external_url'] = $form['nav_external_url'];
        unset($form['nav_title'], $form['nav_icon'], $form['nav_highlight'], $form['nav_external_url']);

        // Estado de publicación.
        $form['publishing'] = [
            '#type' => 'details',
            '#title' => $this->t('Estado'),
            '#open' => FALSE,
            '#weight' => 30,
        ];
        $form['publishing']['status'] = $form['status'];
        $form['publishing']['published_at'] = $form['published_at'];
        unset($form['status'], $form['published_at']);

        // Ocultar campos técnicos.
        if (isset($form['depth'])) {
            $form['depth']['#access'] = FALSE;
        }
        if (isset($form['path'])) {
            $form['path']['#access'] = FALSE;
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $entity = $this->entity;
        $result = parent::save($form, $form_state);

        $pageTitle = $entity->getNavTitle();
        $this->messenger()->addStatus($this->t('Página "%title" guardada en el árbol.', [
            '%title' => $pageTitle,
        ]));

        $form_state->setRedirect('jaraba_site_builder.tree');

        return $result;
    }

}
