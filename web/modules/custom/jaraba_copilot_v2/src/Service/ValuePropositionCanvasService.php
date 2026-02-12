<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Servicio para gestionar el Value Proposition Canvas (Osterwalder).
 *
 * Implementa la metodología de Value Proposition Design para ayudar
 * a los emprendedores a diseñar propuestas de valor ajustadas al
 * perfil del cliente objetivo.
 *
 * El VPC consta de dos partes:
 * - Customer Profile (Jobs, Pains, Gains)
 * - Value Map (Products/Services, Pain Relievers, Gain Creators)
 *
 * @see https://www.strategyzer.com/library/the-value-proposition-canvas
 */
class ValuePropositionCanvasService
{

    /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * The current user.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected AccountProxyInterface $currentUser;

    /**
     * The logger channel.
     *
     * @var \Drupal\Core\Logger\LoggerChannelInterface
     */
    protected LoggerChannelInterface $logger;

    /**
     * Constructor del servicio.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        AccountProxyInterface $current_user,
        LoggerChannelInterface $logger
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->currentUser = $current_user;
        $this->logger = $logger;
    }

    /**
     * Obtiene el VPC completo de un emprendedor.
     *
     * @param int|null $userId
     *   ID del usuario.
     *
     * @return array
     *   VPC estructurado con Customer Profile y Value Map.
     */
    public function getVpc(?int $userId = NULL): array
    {
        $userId = $userId ?? (int) $this->currentUser->id();

        if (!$userId) {
            return $this->getEmptyVpc();
        }

        try {
            // Intentar cargar desde entity entrepreneur_profile
            $storage = $this->entityTypeManager->getStorage('entrepreneur_profile');
            $profiles = $storage->loadByProperties(['user_id' => $userId]);

            if (!empty($profiles)) {
                $profile = reset($profiles);
                return $this->extractVpcFromProfile($profile);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error getting VPC: @message', ['@message' => $e->getMessage()]);
        }

        return $this->getEmptyVpc();
    }

    /**
     * Extrae los datos de VPC del perfil del emprendedor.
     *
     * @param object $profile
     *   Entidad de perfil.
     *
     * @return array
     *   VPC estructurado.
     */
    protected function extractVpcFromProfile($profile): array
    {
        return [
            'customer_profile' => [
                'customer_jobs' => $this->parseJsonField($profile, 'customer_jobs'),
                'customer_pains' => $this->parseJsonField($profile, 'customer_pains'),
                'customer_gains' => $this->parseJsonField($profile, 'customer_gains'),
            ],
            'value_map' => [
                'products_services' => $this->parseJsonField($profile, 'products_services'),
                'pain_relievers' => $this->parseJsonField($profile, 'pain_relievers'),
                'gain_creators' => $this->parseJsonField($profile, 'gain_creators'),
            ],
            'fit_score' => $this->calculateFitScore($profile),
            'validation_status' => $this->getValidationStatus($profile),
        ];
    }

    /**
     * Parsea un campo JSON del perfil.
     */
    protected function parseJsonField($profile, string $fieldName): array
    {
        try {
            if ($profile->hasField($fieldName)) {
                $value = $profile->get($fieldName)->value ?? '';
                if (!empty($value)) {
                    return json_decode($value, TRUE) ?? [];
                }
            }
        } catch (\Exception $e) {
            // Field doesn't exist
        }
        return [];
    }

    /**
     * Calcula el Fit Score entre Customer Profile y Value Map.
     *
     * Un alto fit indica que los Pain Relievers y Gain Creators
     * se alinean bien con los Pains y Gains del cliente.
     *
     * @param object $profile
     *   Entidad de perfil.
     *
     * @return int
     *   Score de 0 a 100.
     */
    protected function calculateFitScore($profile): int
    {
        $customerProfile = [
            'pains' => $this->parseJsonField($profile, 'customer_pains'),
            'gains' => $this->parseJsonField($profile, 'customer_gains'),
        ];

        $valueMap = [
            'pain_relievers' => $this->parseJsonField($profile, 'pain_relievers'),
            'gain_creators' => $this->parseJsonField($profile, 'gain_creators'),
        ];

        // Calcular cobertura
        $painsCovered = count(array_intersect_key($valueMap['pain_relievers'], $customerProfile['pains']));
        $gainsCovered = count(array_intersect_key($valueMap['gain_creators'], $customerProfile['gains']));

        $totalCustomerItems = count($customerProfile['pains']) + count($customerProfile['gains']);
        $totalCovered = $painsCovered + $gainsCovered;

        if ($totalCustomerItems === 0) {
            return 0;
        }

        return (int) round(($totalCovered / $totalCustomerItems) * 100);
    }

    /**
     * Obtiene el estado de validación del VPC.
     */
    protected function getValidationStatus($profile): array
    {
        return [
            'customer_profile' => [
                'jobs_validated' => (bool) ($profile->get('jobs_validated')->value ?? FALSE),
                'pains_validated' => (bool) ($profile->get('pains_validated')->value ?? FALSE),
                'gains_validated' => (bool) ($profile->get('gains_validated')->value ?? FALSE),
            ],
            'value_map' => [
                'products_validated' => (bool) ($profile->get('products_validated')->value ?? FALSE),
                'pain_relievers_validated' => (bool) ($profile->get('pain_relievers_validated')->value ?? FALSE),
                'gain_creators_validated' => (bool) ($profile->get('gain_creators_validated')->value ?? FALSE),
            ],
        ];
    }

    /**
     * Devuelve un VPC vacío.
     */
    public function getEmptyVpc(): array
    {
        return [
            'customer_profile' => [
                'customer_jobs' => [],
                'customer_pains' => [],
                'customer_gains' => [],
            ],
            'value_map' => [
                'products_services' => [],
                'pain_relievers' => [],
                'gain_creators' => [],
            ],
            'fit_score' => 0,
            'validation_status' => [
                'customer_profile' => [
                    'jobs_validated' => FALSE,
                    'pains_validated' => FALSE,
                    'gains_validated' => FALSE,
                ],
                'value_map' => [
                    'products_validated' => FALSE,
                    'pain_relievers_validated' => FALSE,
                    'gain_creators_validated' => FALSE,
                ],
            ],
        ];
    }

    /**
     * Genera sugerencias para mejorar el VPC basadas en gaps.
     *
     * @param int|null $userId
     *   ID del usuario.
     *
     * @return array
     *   Lista de sugerencias.
     */
    public function getSuggestions(?int $userId = NULL): array
    {
        $vpc = $this->getVpc($userId);
        $suggestions = [];

        // Verificar Customer Profile
        if (empty($vpc['customer_profile']['customer_jobs'])) {
            $suggestions[] = [
                'area' => 'customer_jobs',
                'priority' => 'high',
                'message' => '¿Qué trabajos intenta realizar tu cliente? Identifica tareas funcionales, sociales y emocionales.',
            ];
        }

        if (empty($vpc['customer_profile']['customer_pains'])) {
            $suggestions[] = [
                'area' => 'customer_pains',
                'priority' => 'high',
                'message' => '¿Qué dolores tiene tu cliente? Identifica obstáculos, riesgos y frustraciones.',
            ];
        }

        if (empty($vpc['customer_profile']['customer_gains'])) {
            $suggestions[] = [
                'area' => 'customer_gains',
                'priority' => 'medium',
                'message' => '¿Qué ganancias espera tu cliente? Identifica beneficios funcionales, sociales y emocionales.',
            ];
        }

        // Verificar Value Map
        if (empty($vpc['value_map']['pain_relievers'])) {
            $suggestions[] = [
                'area' => 'pain_relievers',
                'priority' => 'high',
                'message' => '¿Cómo alivia tu producto/servicio los dolores del cliente?',
            ];
        }

        if (empty($vpc['value_map']['gain_creators'])) {
            $suggestions[] = [
                'area' => 'gain_creators',
                'priority' => 'medium',
                'message' => '¿Cómo genera tu producto/servicio las ganancias que esperan los clientes?',
            ];
        }

        // Verificar Fit Score
        if ($vpc['fit_score'] < 50) {
            $suggestions[] = [
                'area' => 'fit',
                'priority' => 'high',
                'message' => 'Tu Fit Score es bajo. Revisa que cada Pain tenga un Pain Reliever y cada Gain tenga un Gain Creator.',
            ];
        }

        return $suggestions;
    }

    /**
     * Genera un resumen textual del VPC para el Copiloto.
     *
     * @param int|null $userId
     *   ID del usuario.
     *
     * @return string
     *   Resumen textual.
     */
    public function getVpcSummaryForPrompt(?int $userId = NULL): string
    {
        $vpc = $this->getVpc($userId);

        $parts = [];

        // Customer Profile
        $jobsCount = count($vpc['customer_profile']['customer_jobs']);
        $painsCount = count($vpc['customer_profile']['customer_pains']);
        $gainsCount = count($vpc['customer_profile']['customer_gains']);

        $parts[] = "VPC: {$jobsCount} jobs, {$painsCount} pains, {$gainsCount} gains identificados.";

        // Value Map
        $productsCount = count($vpc['value_map']['products_services']);
        $relieversCount = count($vpc['value_map']['pain_relievers']);
        $creatorsCount = count($vpc['value_map']['gain_creators']);

        $parts[] = "Value Map: {$productsCount} productos, {$relieversCount} pain relievers, {$creatorsCount} gain creators.";

        // Fit Score
        $parts[] = "Fit Score: {$vpc['fit_score']}%.";

        // Suggestions
        $suggestions = $this->getSuggestions($userId);
        if (!empty($suggestions)) {
            $highPriority = array_filter($suggestions, fn($s) => $s['priority'] === 'high');
            if (!empty($highPriority)) {
                $parts[] = "Gaps prioritarios: " . count($highPriority) . " áreas sin completar.";
            }
        }

        return implode(' ', $parts);
    }

}
