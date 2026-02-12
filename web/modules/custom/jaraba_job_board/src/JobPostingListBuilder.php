<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * List builder for JobPosting entities with filters.
 */
class JobPostingListBuilder extends EntityListBuilder implements FormInterface
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
        return 'job_posting_filter_form';
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

        $form['filters']['status'] = [
            '#type' => 'select',
            '#title' => $this->t('Estado'),
            '#options' => [
                '' => $this->t('- Todos -'),
                'published' => $this->t('Publicado'),
                'draft' => $this->t('Borrador'),
                'closed' => $this->t('Cerrado'),
                'expired' => $this->t('Expirado'),
            ],
            '#default_value' => $request->query->get('status', ''),
        ];

        $form['filters']['location'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Ubicación'),
            '#size' => 20,
            '#default_value' => $request->query->get('location', ''),
            '#placeholder' => $this->t('Ciudad...'),
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

        $status = $form_state->getValue('status');
        if ($status !== '' && $status !== NULL) {
            $query['status'] = $status;
        }

        $location = $form_state->getValue('location');
        if (!empty($location)) {
            $query['location'] = $location;
        }

        $form_state->setRedirect('entity.job_posting.collection', [], ['query' => $query]);
    }

    /**
     * Reset form handler.
     */
    public function resetForm(array &$form, FormStateInterface $form_state): void
    {
        $form_state->setRedirect('entity.job_posting.collection');
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
            ->sort('created', 'DESC');

        $request = \Drupal::request();

        // Apply status filter.
        $status = $request->query->get('status');
        if (!empty($status)) {
            $query->condition('status', $status);
        }

        // Apply location filter.
        $location = $request->query->get('location');
        if (!empty($location)) {
            $query->condition('location_city', '%' . $location . '%', 'LIKE');
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
        $header['title'] = $this->t('Título');
        $header['status'] = $this->t('Estado');
        $header['location'] = $this->t('Ubicación');
        $header['applications'] = $this->t('Candidaturas');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        $row['id'] = $entity->id();
        $row['title'] = Link::createFromRoute(
            $entity->label() ?? $this->t('Sin título'),
            'entity.job_posting.canonical',
            ['job_posting' => $entity->id()]
        );

        // Badge de estado con colores
        $status = $entity->get('status')->value ?? 'draft';
        $statusLabels = [
            'published' => $this->t('Publicado'),
            'draft' => $this->t('Borrador'),
            'closed' => $this->t('Cerrado'),
            'expired' => $this->t('Expirado'),
        ];
        $row['status'] = [
            'data' => [
                '#markup' => '<span class="status-badge status-' . $status . '">' . ($statusLabels[$status] ?? $status) . '</span>',
            ],
        ];

        $row['location'] = $entity->get('location_city')->value ?? '-';
        $row['applications'] = $entity->get('applications_count')->value ?? 0;
        return $row + parent::buildRow($entity);
    }

}
