<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * List builder para solicitudes Andalucia +ei.
 *
 * Pestanas por estado con contadores + filtros secundarios
 * (busqueda, provincia, colectivo, triaje IA).
 */
class SolicitudEiListBuilder extends EntityListBuilder
{

    /**
     * Status tabs configuration.
     */
    private const STATUS_TABS = [
        '' => 'Todas',
        'pendiente' => 'Pendientes',
        'contactado' => 'Contactados',
        'admitido' => 'Admitidos',
        'rechazado' => 'Rechazados',
        'lista_espera' => 'Lista espera',
    ];

    protected Request $currentRequest;

    protected DateFormatterInterface $dateFormatter;

    /**
     * {@inheritdoc}
     */
    public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static
    {
        $instance = parent::createInstance($container, $entity_type);
        $instance->currentRequest = $container->get('request_stack')->getCurrentRequest();
        $instance->dateFormatter = $container->get('date.formatter');
        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function render(): array
    {
        $build['tabs'] = $this->buildStatusTabs();
        $build['filters'] = $this->buildFilters();
        $build += parent::render();
        $build['table']['#empty'] = $this->t('No se encontraron solicitudes con los filtros seleccionados.');
        return $build;
    }

    /**
     * Build status tabs with counters.
     */
    protected function buildStatusTabs(): array
    {
        $counts = $this->getStatusCounts();
        $activeEstado = (string) $this->currentRequest->query->get('estado', '');
        $baseUrl = $this->getCollectionUrl();

        $items = [];
        foreach (self::STATUS_TABS as $key => $label) {
            $count = $key === '' ? array_sum($counts) : ($counts[$key] ?? 0);

            // Preserve other active filters when switching tabs.
            $params = array_filter([
                'search' => $this->currentRequest->query->get('search', ''),
                'provincia' => $this->currentRequest->query->get('provincia', ''),
                'colectivo' => $this->currentRequest->query->get('colectivo', ''),
                'ai_rec' => $this->currentRequest->query->get('ai_rec', ''),
            ]);
            if ($key !== '') {
                $params['estado'] = $key;
            }

            $options = !empty($params) ? ['query' => $params] : [];
            $url = Url::fromRoute('entity.solicitud_ei.collection', [], $options);

            $isActive = $activeEstado === $key;
            $text = $this->t('@label (@count)', [
                '@label' => $label,
                '@count' => $count,
            ]);

            $items[] = [
                '#type' => 'link',
                '#title' => $text,
                '#url' => $url,
                '#attributes' => [
                    'class' => array_filter([
                        'solicitud-ei-tab',
                        $isActive ? 'is-active' : '',
                    ]),
                ],
            ];
        }

        return [
            '#theme' => 'item_list',
            '#items' => $items,
            '#attributes' => ['class' => ['solicitud-ei-tabs', 'tabs', 'tabs--secondary']],
            '#attached' => [
                'library' => ['core/drupal.functional'],
            ],
        ];
    }

    /**
     * Build secondary filter form (search, provincia, colectivo, triaje IA).
     */
    protected function buildFilters(): array
    {
        $search = $this->currentRequest->query->get('search', '');
        $provincia = $this->currentRequest->query->get('provincia', '');
        $estado = $this->currentRequest->query->get('estado', '');
        $colectivo = $this->currentRequest->query->get('colectivo', '');
        $ai_rec = $this->currentRequest->query->get('ai_rec', '');

        $form = [
            '#type' => 'container',
            '#attributes' => ['class' => ['solicitud-ei-filters', 'clearfix']],
        ];

        // Hidden field to preserve active tab.
        if ($estado !== '' && $estado !== NULL) {
            $form['estado_hidden'] = [
                '#type' => 'hidden',
                '#name' => 'estado',
                '#value' => $estado,
            ];
        }

        $form['search'] = [
            '#type' => 'search',
            '#title' => $this->t('Buscar'),
            '#name' => 'search',
            '#default_value' => $search,
            '#placeholder' => $this->t('Nombre, email o telefono...'),
            '#size' => 30,
            '#attributes' => ['class' => ['solicitud-ei-filter-search']],
        ];

        $form['provincia'] = [
            '#type' => 'select',
            '#title' => $this->t('Provincia'),
            '#name' => 'provincia',
            '#default_value' => $provincia,
            '#options' => ['' => $this->t('- Todas -')] + [
                'almeria' => $this->t('Almeria'),
                'cadiz' => $this->t('Cadiz'),
                'cordoba' => $this->t('Cordoba'),
                'granada' => $this->t('Granada'),
                'huelva' => $this->t('Huelva'),
                'jaen' => $this->t('Jaen'),
                'malaga' => $this->t('Malaga'),
                'sevilla' => $this->t('Sevilla'),
            ],
            '#attributes' => ['class' => ['solicitud-ei-filter-select']],
        ];

        $form['colectivo'] = [
            '#type' => 'select',
            '#title' => $this->t('Colectivo'),
            '#name' => 'colectivo',
            '#default_value' => $colectivo,
            '#options' => ['' => $this->t('- Todos -')] + [
                'larga_duracion' => $this->t('Larga duracion'),
                'mayores_45' => $this->t('Mayores 45'),
                'migrantes' => $this->t('Migrantes'),
                'perceptores_prestaciones' => $this->t('Perceptores'),
                'otros' => $this->t('Otros'),
            ],
            '#attributes' => ['class' => ['solicitud-ei-filter-select']],
        ];

        $form['ai_rec'] = [
            '#type' => 'select',
            '#title' => $this->t('Triaje IA'),
            '#name' => 'ai_rec',
            '#default_value' => $ai_rec,
            '#options' => ['' => $this->t('- Todos -')] + [
                'admitir' => $this->t('Admitir'),
                'revisar' => $this->t('Revisar'),
                'rechazar' => $this->t('Rechazar'),
                'sin_triaje' => $this->t('Sin triaje'),
            ],
            '#attributes' => ['class' => ['solicitud-ei-filter-select']],
        ];

        $form['actions'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['solicitud-ei-filter-actions']],
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Filtrar'),
            '#attributes' => ['class' => ['button', 'button--primary']],
        ];

        $form['actions']['reset'] = [
            '#type' => 'link',
            '#title' => $this->t('Limpiar'),
            '#url' => $this->getCollectionUrl(),
            '#attributes' => ['class' => ['button']],
        ];

        return [
            '#type' => 'html_tag',
            '#tag' => 'form',
            '#attributes' => [
                'method' => 'get',
                'action' => $this->currentRequest->getPathInfo(),
                'class' => ['solicitud-ei-filter-form'],
            ],
            'filters' => $form,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['nombre'] = $this->t('Nombre');
        $header['email'] = $this->t('Email');
        $header['provincia'] = $this->t('Provincia');
        $header['colectivo_inferido'] = $this->t('Colectivo');
        $header['ai_triage'] = $this->t('Triaje IA');
        $header['estado'] = $this->t('Estado');
        $header['created'] = $this->t('Fecha');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_andalucia_ei\Entity\SolicitudEiInterface $entity */
        $provincias = [
            'almeria' => 'Almeria',
            'cadiz' => 'Cadiz',
            'cordoba' => 'Cordoba',
            'granada' => 'Granada',
            'huelva' => 'Huelva',
            'jaen' => 'Jaen',
            'malaga' => 'Malaga',
            'sevilla' => 'Sevilla',
        ];

        $colectivos = [
            'larga_duracion' => "\xF0\x9F\x9F\xA0 Larga duracion",
            'mayores_45' => "\xF0\x9F\x9F\xA1 Mayores 45",
            'migrantes' => "\xF0\x9F\x8C\x8D Migrantes",
            'perceptores_prestaciones' => "\xF0\x9F\x94\xB5 Perceptores",
            'otros' => "\xE2\xAC\x9C Otros",
        ];

        $estados = [
            'pendiente' => "\xE2\x8F\xB3 Pendiente",
            'contactado' => "\xF0\x9F\x93\x9E Contactado",
            'admitido' => "\xE2\x9C\x85 Admitido",
            'rechazado' => "\xE2\x9D\x8C Rechazado",
            'lista_espera' => "\xF0\x9F\x93\x8B Lista espera",
        ];

        $provincia = $entity->get('provincia')->value;
        $colectivo = $entity->getColectivoInferido();
        $estado = $entity->getEstado();

        // Nombre as direct link to canonical view.
        $row['nombre'] = Link::fromTextAndUrl(
            $entity->getNombre(),
            $entity->toUrl('canonical')
        )->toString();

        $row['email'] = $entity->getEmail();
        $row['provincia'] = $provincias[$provincia] ?? $provincia;
        $row['colectivo_inferido'] = $colectivos[$colectivo] ?? $colectivo;

        // AI triage: show score with color semaphore.
        $aiScore = $entity->get('ai_score')->value;
        $aiRec = $entity->get('ai_recomendacion')->value ?? '';
        if ($aiScore !== NULL) {
            $recEmojis = [
                'admitir' => "\xF0\x9F\x9F\xA2",
                'revisar' => "\xF0\x9F\x9F\xA1",
                'rechazar' => "\xF0\x9F\x94\xB4",
            ];
            $emoji = $recEmojis[$aiRec] ?? "\xE2\xAC\x9C";
            $row['ai_triage'] = $emoji . ' ' . $aiScore . '/100';
        } else {
            $row['ai_triage'] = "\xE2\x80\x94";
        }

        $row['estado'] = $estados[$estado] ?? $estado;
        $row['created'] = $this->dateFormatter->format(
            (int) $entity->get('created')->value,
            'short'
        );

        return $row + parent::buildRow($entity);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityIds(): array
    {
        $query = $this->getStorage()->getQuery()
            ->accessCheck(TRUE)
            ->sort('created', 'DESC');

        // Tab filter (estado).
        $estado = $this->currentRequest->query->get('estado', '');
        if ($estado !== '' && $estado !== NULL) {
            $query->condition('estado', $estado);
        }

        // Text search.
        $search = trim((string) $this->currentRequest->query->get('search', ''));
        if ($search !== '') {
            $group = $query->orConditionGroup()
                ->condition('nombre', '%' . $search . '%', 'LIKE')
                ->condition('email', '%' . $search . '%', 'LIKE')
                ->condition('telefono', '%' . $search . '%', 'LIKE');
            $query->condition($group);
        }

        $provincia = $this->currentRequest->query->get('provincia', '');
        if ($provincia !== '' && $provincia !== NULL) {
            $query->condition('provincia', $provincia);
        }

        $colectivo = $this->currentRequest->query->get('colectivo', '');
        if ($colectivo !== '' && $colectivo !== NULL) {
            $query->condition('colectivo_inferido', $colectivo);
        }

        $aiRec = $this->currentRequest->query->get('ai_rec', '');
        if ($aiRec === 'sin_triaje') {
            $query->notExists('ai_recomendacion');
        } elseif ($aiRec !== '' && $aiRec !== NULL) {
            $query->condition('ai_recomendacion', $aiRec);
        }

        if ($this->limit) {
            $query->pager($this->limit);
        }

        return $query->execute();
    }

    /**
     * Get counts per status for tab badges.
     *
     * @return array<string, int>
     *   Keyed by status machine name.
     */
    protected function getStatusCounts(): array
    {
        $storage = $this->getStorage();
        $counts = [];
        $statuses = ['pendiente', 'contactado', 'admitido', 'rechazado', 'lista_espera'];

        foreach ($statuses as $status) {
            $counts[$status] = (int) $storage->getQuery()
                ->accessCheck(TRUE)
                ->condition('estado', $status)
                ->count()
                ->execute();
        }

        return $counts;
    }

    /**
     * Get the collection URL for the reset link.
     */
    protected function getCollectionUrl(): Url
    {
        return Url::fromRoute('entity.solicitud_ei.collection');
    }

}
