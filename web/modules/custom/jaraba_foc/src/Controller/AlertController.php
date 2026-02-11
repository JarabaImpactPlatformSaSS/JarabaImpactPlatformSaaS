<?php

declare(strict_types=1);

namespace Drupal\jaraba_foc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\jaraba_foc\Entity\FocAlert;
use Drupal\jaraba_foc\Service\AlertService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controlador para la gestión de alertas FOC.
 */
class AlertController extends ControllerBase implements ContainerInjectionInterface
{

    /**
     * Constructor.
     */
    public function __construct(
        protected AlertService $alertService
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_foc.alerts')
        );
    }

    /**
     * Lista las alertas abiertas.
     */
    public function list(): array
    {
        $alerts = $this->alertService->getOpenAlerts();

        $header = [
            $this->t('Severidad'),
            $this->t('Tipo'),
            $this->t('Título'),
            $this->t('Tenant'),
            $this->t('Valor'),
            $this->t('Creada'),
            $this->t('Acciones'),
        ];

        $rows = [];
        foreach ($alerts as $alert) {
            $severity = $alert->get('severity')->value;
            $severityClass = match ($severity) {
                FocAlert::SEVERITY_CRITICAL => 'foc-severity--critical',
                FocAlert::SEVERITY_WARNING => 'foc-severity--warning',
                default => 'foc-severity--info',
            };

            $tenantLabel = '-';
            if ($tenantRef = $alert->get('related_tenant')->entity) {
                $tenantLabel = $tenantRef->label();
            }

            $rows[] = [
                [
                    'data' => $alert->get('severity')->value,
                    'class' => [$severityClass],
                ],
                $alert->get('alert_type')->value,
                $alert->get('title')->value,
                $tenantLabel,
                $alert->get('metric_value')->value,
                \Drupal::service('date.formatter')->format($alert->get('created')->value, 'short'),
                [
                    'data' => [
                        '#type' => 'operations',
                        '#links' => [
                            'view' => [
                                'title' => $this->t('Ver'),
                                'url' => $alert->toUrl('canonical'),
                            ],
                            'resolve' => [
                                'title' => $this->t('Resolver'),
                                'url' => Url::fromRoute('jaraba_foc.alert.resolve', ['foc_alert' => $alert->id()]),
                            ],
                        ],
                    ],
                ],
            ];
        }

        return [
            '#type' => 'container',
            'header' => [
                '#type' => 'html_tag',
                '#tag' => 'div',
                '#attributes' => ['class' => ['foc-alerts-header']],
                'title' => [
                    '#type' => 'html_tag',
                    '#tag' => 'h2',
                    '#value' => $this->t('Alertas Activas'),
                ],
                'actions' => [
                    '#type' => 'link',
                    '#title' => $this->t('Evaluar Ahora'),
                    '#url' => Url::fromRoute('jaraba_foc.alerts.evaluate'),
                    '#attributes' => ['class' => ['button', 'button--primary']],
                ],
            ],
            'table' => [
                '#type' => 'table',
                '#header' => $header,
                '#rows' => $rows,
                '#empty' => $this->t('No hay alertas activas. ¡Excelente salud financiera!'),
                '#attributes' => ['class' => ['foc-table', 'foc-table--alerts']],
            ],
            '#attached' => [
                'library' => ['jaraba_foc/dashboard'],
            ],
        ];
    }

    /**
     * Evalúa todas las alertas manualmente.
     */
    public function evaluate(): RedirectResponse
    {
        $alerts = $this->alertService->evaluateAllAlerts();
        $count = count($alerts);

        if ($count > 0) {
            $this->messenger()->addStatus($this->t('@count alerta(s) generada(s).', ['@count' => $count]));
        } else {
            $this->messenger()->addStatus($this->t('No se detectaron nuevas condiciones de alerta.'));
        }

        return new RedirectResponse(Url::fromRoute('jaraba_foc.alerts')->toString());
    }

    /**
     * Resuelve una alerta.
     */
    public function resolve(FocAlert $foc_alert): RedirectResponse
    {
        $this->alertService->resolveAlert((int) $foc_alert->id());
        $this->messenger()->addStatus($this->t('Alerta resuelta correctamente.'));

        return new RedirectResponse(Url::fromRoute('jaraba_foc.alerts')->toString());
    }

}
