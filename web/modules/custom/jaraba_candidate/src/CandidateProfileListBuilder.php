<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * List builder for CandidateProfile entities with filters.
 */
class CandidateProfileListBuilder extends EntityListBuilder implements FormInterface
{

    /**
     * The form builder.
     *
     * @var \Drupal\Core\Form\FormBuilderInterface
     */
    protected $formBuilder;

    /**
     * {@inheritdoc}
     */
    public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type)
    {
        $instance = parent::createInstance($container, $entity_type);
        $instance->formBuilder = $container->get('form_builder');
        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'candidate_profile_filter_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $request = \Drupal::request();

        $form['filters'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['form--inline', 'clearfix']],
        ];

        $form['filters']['completion'] = [
            '#type' => 'select',
            '#title' => $this->t('Completitud'),
            '#options' => [
                '' => $this->t('- Todos -'),
                'incomplete' => $this->t('Incompleto (< 50%)'),
                'partial' => $this->t('Parcial (50-80%)'),
                'complete' => $this->t('Completo (> 80%)'),
            ],
            '#default_value' => $request->query->get('completion', ''),
        ];

        $form['filters']['search'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Buscar'),
            '#size' => 20,
            '#default_value' => $request->query->get('search', ''),
            '#placeholder' => $this->t('Nombre o titular...'),
        ];

        $form['filters']['actions'] = [
            '#type' => 'actions',
        ];

        $form['filters']['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Filtrar'),
        ];

        $form['filters']['actions']['reset'] = [
            '#type' => 'submit',
            '#value' => $this->t('Limpiar'),
            '#submit' => ['::resetForm'],
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state): void
    {
        // No validation needed.
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $query = [];

        $completion = $form_state->getValue('completion');
        if (!empty($completion)) {
            $query['completion'] = $completion;
        }

        $search = $form_state->getValue('search');
        if (!empty($search)) {
            $query['search'] = $search;
        }

        $form_state->setRedirect('entity.candidate_profile.collection', [], ['query' => $query]);
    }

    /**
     * Reset form handler.
     */
    public function resetForm(array &$form, FormStateInterface $form_state): void
    {
        $form_state->setRedirect('entity.candidate_profile.collection');
    }

    /**
     * {@inheritdoc}
     */
    public function render(): array
    {
        $build['form'] = $this->formBuilder->getForm($this);
        $build['table'] = parent::render();
        return $build;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityIds(): array
    {
        $query = $this->getStorage()->getQuery()
            ->accessCheck(TRUE)
            ->sort('changed', 'DESC');

        $request = \Drupal::request();

        // Apply completion filter.
        $completion = $request->query->get('completion');
        if (!empty($completion)) {
            switch ($completion) {
                case 'incomplete':
                    $query->condition('completion_percent', 50, '<');
                    break;
                case 'partial':
                    $query->condition('completion_percent', 50, '>=');
                    $query->condition('completion_percent', 80, '<=');
                    break;
                case 'complete':
                    $query->condition('completion_percent', 80, '>');
                    break;
            }
        }

        // Apply search filter (searches in headline).
        $search = $request->query->get('search');
        if (!empty($search)) {
            $query->condition('headline', '%' . $search . '%', 'LIKE');
        }

        if ($this->limit) {
            $query->pager($this->limit);
        }

        return $query->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['name'] = $this->t('Candidato');
        $header['headline'] = $this->t('Titular');
        $header['completion'] = $this->t('Completitud');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        $row['id'] = $entity->id();

        // Obtener nombre del usuario vinculado o usar el titular
        $name = $this->t('Sin nombre');
        if ($entity->hasField('user_id') && $entity->get('user_id')->entity) {
            $name = $entity->get('user_id')->entity->getDisplayName();
        } elseif ($entity->hasField('headline') && $entity->get('headline')->value) {
            $name = $entity->get('headline')->value;
        } elseif ($entity->label()) {
            $name = $entity->label();
        }

        $row['name'] = Link::createFromRoute(
            $name,
            'entity.candidate_profile.canonical',
            ['candidate_profile' => $entity->id()]
        );
        $row['headline'] = $entity->get('headline')->value ?? '-';

        // Badge de completitud con colores
        $percent = (int) ($entity->get('completion_percent')->value ?? 0);
        $row['completion'] = [
            'data' => [
                '#markup' => '<span class="completion-badge completion-' . ($percent >= 80 ? 'complete' : ($percent >= 50 ? 'partial' : 'incomplete')) . '">' . $percent . '%</span>',
            ],
        ];

        return $row + parent::buildRow($entity);
    }

}
