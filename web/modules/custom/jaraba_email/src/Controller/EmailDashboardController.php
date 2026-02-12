<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Controlador del dashboard de email marketing.
 *
 * PROPÓSITO:
 * Presenta un panel de resumen del sistema de email marketing
 * con estadísticas rápidas, acciones frecuentes y campañas recientes.
 *
 * SECCIONES DEL DASHBOARD:
 * 1. Tarjetas de estadísticas:
 *    - Total de listas
 *    - Total de suscriptores activos
 *    - Total de campañas
 *    - Total de secuencias
 *
 * 2. Acciones rápidas:
 *    - Crear campaña
 *    - Agregar suscriptor
 *    - Crear plantilla
 *    - Crear secuencia
 *
 * 3. Campañas recientes:
 *    - Tabla con nombre, estado, enviados y tasa de apertura
 *
 * ESPECIFICACIÓN: Doc 139 - Email_Marketing_Technical_Guide
 */
class EmailDashboardController extends ControllerBase
{

    /**
     * Muestra el dashboard de email marketing.
     *
     * Construye un render array con estadísticas generales,
     * enlaces de acciones rápidas y tabla de campañas recientes.
     *
     * @return array
     *   Render array con la estructura del dashboard.
     */
    public function dashboard(): array
    {
        // Obtener estadísticas rápidas.
        $listCount = $this->getEntityCount('email_list');
        $subscriberCount = $this->getEntityCount('email_subscriber', ['status' => 'subscribed']);
        $campaignCount = $this->getEntityCount('email_campaign');
        $sequenceCount = $this->getEntityCount('email_sequence');

        $build = [
            '#type' => 'container',
            '#attributes' => ['class' => ['email-dashboard']],
        ];

        // Tarjetas de estadísticas.
        $build['stats'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['email-dashboard__stats']],
        ];

        $stats = [
            ['label' => $this->t('Listas'), 'value' => $listCount, 'link' => 'entity.email_list.collection'],
            ['label' => $this->t('Suscriptores'), 'value' => $subscriberCount, 'link' => 'entity.email_subscriber.collection'],
            ['label' => $this->t('Campañas'), 'value' => $campaignCount, 'link' => 'entity.email_campaign.collection'],
            ['label' => $this->t('Secuencias'), 'value' => $sequenceCount, 'link' => 'entity.email_sequence.collection'],
        ];

        foreach ($stats as $index => $stat) {
            $build['stats']['card_' . $index] = [
                '#type' => 'container',
                '#attributes' => ['class' => ['email-dashboard__card']],
                'value' => [
                    '#type' => 'html_tag',
                    '#tag' => 'div',
                    '#value' => number_format($stat['value']),
                    '#attributes' => ['class' => ['email-dashboard__card-value']],
                ],
                'label' => [
                    '#type' => 'link',
                    '#title' => $stat['label'],
                    '#url' => Url::fromRoute($stat['link']),
                    '#attributes' => ['class' => ['email-dashboard__card-label']],
                ],
            ];
        }

        // Acciones rápidas.
        $build['actions'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['email-dashboard__actions']],
            'title' => [
                '#type' => 'html_tag',
                '#tag' => 'h3',
                '#value' => $this->t('Acciones Rápidas'),
            ],
            'links' => [
                '#theme' => 'item_list',
                '#items' => [
                    [
                        '#type' => 'link',
                        '#title' => $this->t('Crear Campaña'),
                        '#url' => Url::fromRoute('entity.email_campaign.add_form'),
                    ],
                    [
                        '#type' => 'link',
                        '#title' => $this->t('Agregar Suscriptor'),
                        '#url' => Url::fromRoute('entity.email_subscriber.add_form'),
                    ],
                    [
                        '#type' => 'link',
                        '#title' => $this->t('Crear Plantilla'),
                        '#url' => Url::fromRoute('entity.email_template.add_form'),
                    ],
                    [
                        '#type' => 'link',
                        '#title' => $this->t('Crear Secuencia'),
                        '#url' => Url::fromRoute('entity.email_sequence.add_form'),
                    ],
                ],
            ],
        ];

        // Campañas recientes.
        $build['recent'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['email-dashboard__recent']],
            'title' => [
                '#type' => 'html_tag',
                '#tag' => 'h3',
                '#value' => $this->t('Campañas Recientes'),
            ],
        ];

        $recentCampaigns = $this->getRecentCampaigns(5);
        if (!empty($recentCampaigns)) {
            $rows = [];
            foreach ($recentCampaigns as $campaign) {
                $rows[] = [
                    $campaign->toLink($campaign->getName()),
                    $campaign->get('status')->value,
                    $campaign->get('total_sent')->value ?? 0,
                    sprintf('%.1f%%', $campaign->getOpenRate()),
                ];
            }

            $build['recent']['table'] = [
                '#type' => 'table',
                '#header' => [
                    $this->t('Campaña'),
                    $this->t('Estado'),
                    $this->t('Enviados'),
                    $this->t('Tasa de Apertura'),
                ],
                '#rows' => $rows,
                '#empty' => $this->t('Aún no hay campañas.'),
            ];
        } else {
            $build['recent']['empty'] = [
                '#markup' => '<p>' . $this->t('Aún no hay campañas. <a href="@url">Crea tu primera campaña</a>.', [
                    '@url' => Url::fromRoute('entity.email_campaign.add_form')->toString(),
                ]) . '</p>',
            ];
        }

        return $build;
    }

    /**
     * Obtiene el conteo de entidades.
     *
     * @param string $entityType
     *   El tipo de entidad a contar.
     * @param array $conditions
     *   Condiciones opcionales para filtrar.
     *
     * @return int
     *   El número de entidades que coinciden.
     */
    protected function getEntityCount(string $entityType, array $conditions = []): int
    {
        try {
            $storage = $this->entityTypeManager()->getStorage($entityType);
            $query = $storage->getQuery()->accessCheck(FALSE);

            foreach ($conditions as $field => $value) {
                $query->condition($field, $value);
            }

            return (int) $query->count()->execute();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obtiene las campañas más recientes.
     *
     * @param int $limit
     *   Número máximo de campañas a retornar.
     *
     * @return array
     *   Array de entidades EmailCampaign.
     */
    protected function getRecentCampaigns(int $limit): array
    {
        try {
            $storage = $this->entityTypeManager()->getStorage('email_campaign');
            $ids = $storage->getQuery()
                ->sort('created', 'DESC')
                ->range(0, $limit)
                ->accessCheck(FALSE)
                ->execute();

            return $storage->loadMultiple($ids);
        } catch (\Exception $e) {
            return [];
        }
    }

}
