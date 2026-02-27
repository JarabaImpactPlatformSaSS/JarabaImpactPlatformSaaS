<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\group\GroupMembershipLoaderInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestión de Brand Voice por tenant.
 *
 * PROPÓSITO:
 * Proporciona prompts de IA personalizados según la configuración
 * de marca de cada tenant (grupo). Permite que el contenido
 * generado mantenga consistencia con la identidad de la marca.
 *
 * CARACTERÍSTICAS:
 * - Arquetipos de marca: professional, artisan, innovative, etc.
 * - Personalidad configurable: formalidad, calidez, humor, etc.
 * - Ejemplos de estilo: good/bad responses
 * - Términos permitidos/prohibidos
 * - Instrucciones custom adicionales
 *
 * ARQUETIPOS DISPONIBLES:
 * - professional: Corporativo pero accesible
 * - artisan: Tradicional y auténtico
 * - innovative: Tecnológico y futurista
 * - friendly: Cercano como un vecino
 * - expert: Autoritativo con conocimiento profundo
 * - playful: Juvenil y dinámico
 * - luxury: Premium y sofisticado
 * - eco: Sostenible y ético
 *
 * ESPECIFICACIÓN: Doc 156 - World_Class_AI_Elevation_v3
 */
class TenantBrandVoiceService
{

    /**
     * La factoría de configuración.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * El cargador de membresías de grupo.
     *
     * @var \Drupal\group\GroupMembershipLoaderInterface
     */
    protected GroupMembershipLoaderInterface $membershipLoader;

    /**
     * El usuario actual.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected AccountProxyInterface $currentUser;

    /**
     * El gestor de tipos de entidad.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * El logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Construye un TenantBrandVoiceService.
     *
     * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
     *   La factoría de configuración.
     * @param \Drupal\group\GroupMembershipLoaderInterface $membershipLoader
     *   El cargador de membresías de grupo.
     * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
     *   El usuario actual.
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   El gestor de tipos de entidad.
     * @param \Psr\Log\LoggerInterface $logger
     *   El logger.
     */
    public function __construct(
        ConfigFactoryInterface $configFactory,
        GroupMembershipLoaderInterface $membershipLoader,
        AccountProxyInterface $currentUser,
        EntityTypeManagerInterface $entityTypeManager,
        LoggerInterface $logger,
    ) {
        $this->configFactory = $configFactory;
        $this->membershipLoader = $membershipLoader;
        $this->currentUser = $currentUser;
        $this->entityTypeManager = $entityTypeManager;
        $this->logger = $logger;
    }

    /**
     * Obtiene el prompt de Brand Voice para un tenant específico.
     *
     * Carga la configuración del tenant y construye un prompt
     * de sistema con las directrices de marca.
     *
     * @param string $tenantId
     *   El ID del tenant/grupo.
     *
     * @return string
     *   El prompt de sistema de Brand Voice.
     */
    public function getPromptForTenant(string $tenantId): string
    {
        // S5-06: Try entity-based profile first (HAL-AI-30).
        $entityProfile = $this->loadEntityProfile($tenantId);
        if ($entityProfile) {
            return $this->buildPromptFromProfile($entityProfile);
        }

        $config = $this->configFactory->get("jaraba_ai_agents.brand_voice.{$tenantId}");

        if ($config->isNew()) {
            return $this->getDefaultBrandVoice();
        }

        $archetype = $config->get('archetype') ?? 'professional';
        $personality = $config->get('personality') ?? [];
        $examples = $config->get('examples') ?? [];
        $forbiddenTerms = $config->get('forbidden_terms') ?? [];
        $preferredTerms = $config->get('preferred_terms') ?? [];
        $customInstructions = $config->get('custom_instructions') ?? '';

        return $this->buildPromptFromConfig(
            $archetype,
            $personality,
            $examples,
            $forbiddenTerms,
            $preferredTerms,
            $customInstructions
        );
    }


    /**
     * Obtiene el Brand Voice para el tenant del usuario actual.
     *
     * Detecta automáticamente el grupo del usuario y retorna
     * la configuración de marca correspondiente.
     *
     * @return string
     *   El prompt de sistema de Brand Voice.
     */
    public function getCurrentUserBrandVoice(): string
    {
        $user = $this->currentUser->getAccount();
        $memberships = $this->membershipLoader->loadByUser($user);

        if (empty($memberships)) {
            return $this->getDefaultBrandVoice();
        }

        // Usar el primer grupo del usuario como tenant.
        $membership = reset($memberships);
        $group = $membership->getGroup();

        return $this->getPromptForTenant((string) $group->id());
    }

    /**
     * Guarda la configuración de Brand Voice para un tenant.
     *
     * @param string $tenantId
     *   El ID del tenant/grupo.
     * @param array $settings
     *   Configuración de Brand Voice:
     *   - archetype: string - Arquetipo de marca.
     *   - personality: array - Rasgos con puntuación 1-10.
     *   - examples: array - Ejemplos good/bad.
     *   - forbidden_terms: array - Términos a evitar.
     *   - preferred_terms: array - Terminología preferida.
     *   - custom_instructions: string - Instrucciones adicionales.
     */
    public function saveBrandVoice(string $tenantId, array $settings): void
    {
        $config = $this->configFactory->getEditable("jaraba_ai_agents.brand_voice.{$tenantId}");

        if (isset($settings['archetype'])) {
            $config->set('archetype', $settings['archetype']);
        }
        if (isset($settings['personality'])) {
            $config->set('personality', $settings['personality']);
        }
        if (isset($settings['examples'])) {
            $config->set('examples', $settings['examples']);
        }
        if (isset($settings['forbidden_terms'])) {
            $config->set('forbidden_terms', $settings['forbidden_terms']);
        }
        if (isset($settings['preferred_terms'])) {
            $config->set('preferred_terms', $settings['preferred_terms']);
        }

        $config->save();
    }

    /**
     * Construye el prompt a partir de la configuración.
     *
     * @param string $archetype
     *   El arquetipo de marca.
     * @param array $personality
     *   Rasgos de personalidad con puntuaciones.
     * @param array $examples
     *   Ejemplos de respuestas correctas/incorrectas.
     * @param array $forbiddenTerms
     *   Términos a evitar.
     * @param array $preferredTerms
     *   Terminología preferida.
     * @param string $customInstructions
     *   Instrucciones adicionales personalizadas.
     *
     * @return string
     *   El prompt construido.
     */
    protected function buildPromptFromConfig(
        string $archetype,
        array $personality,
        array $examples,
        array $forbiddenTerms,
        array $preferredTerms,
        string $customInstructions = '',
    ): string {
        $prompt = "Eres un asistente de IA para una marca con las siguientes características:\n\n";

        // Arquetipo de marca - lista expandida.
        $archetypes = [
            'professional' => 'Profesional y confiable, con tono corporativo pero accesible.',
            'artisan' => 'Artesanal y auténtico, enfatizando tradición y calidad.',
            'innovative' => 'Innovador y moderno, orientado a tecnología y futuro.',
            'friendly' => 'Cercano y amigable, como un vecino de confianza.',
            'expert' => 'Experto y autoritativo, transmitiendo conocimiento profundo.',
            'playful' => 'Divertido y energético, con tono juvenil y dinámico.',
            'luxury' => 'Premium y exclusivo, con sofisticación y elegancia.',
            'eco' => 'Sostenible y ético, comprometido con el medio ambiente.',
        ];

        $prompt .= "ARQUETIPO: " . ($archetypes[$archetype] ?? $archetypes['professional']) . "\n\n";

        // Rasgos de personalidad con etiquetas significativas.
        if (!empty($personality)) {
            $traitLabels = [
                'formality' => 'Formalidad',
                'warmth' => 'Calidez',
                'confidence' => 'Confianza',
                'humor' => 'Humor',
                'technical' => 'Nivel técnico',
            ];

            $prompt .= "PERSONALIDAD:\n";
            foreach ($personality as $trait => $score) {
                $label = $traitLabels[$trait] ?? ucfirst($trait);
                $prompt .= "- {$label}: {$score}/10\n";
            }
            $prompt .= "\n";
        }

        // Ejemplos de estilo con contexto.
        if (!empty($examples)) {
            $prompt .= "EJEMPLOS DE ESTILO:\n";
            foreach ($examples as $example) {
                if (!empty($example['context'])) {
                    $prompt .= "Situación: {$example['context']}\n";
                }
                if (!empty($example['good'])) {
                    $prompt .= "✅ CORRECTO: \"{$example['good']}\"\n";
                }
                if (!empty($example['bad'])) {
                    $prompt .= "❌ INCORRECTO: \"{$example['bad']}\"\n";
                }
                $prompt .= "\n";
            }
        }

        // Terminología.
        if (!empty($forbiddenTerms)) {
            $prompt .= "TÉRMINOS A EVITAR: " . implode(', ', $forbiddenTerms) . "\n";
        }
        if (!empty($preferredTerms)) {
            $prompt .= "TÉRMINOS PREFERIDOS: " . implode(', ', $preferredTerms) . "\n";
        }

        // Instrucciones personalizadas.
        if (!empty($customInstructions)) {
            $prompt .= "\nINSTRUCCIONES ADICIONALES:\n{$customInstructions}\n";
        }

        return $prompt;
    }


    /**
     * Gets a structured brand voice profile for a tenant (S5-06: HAL-AI-30).
     *
     * Tries entity-based BrandVoiceProfile first, then falls back to
     * config-based approach.
     *
     * @param string $tenantId
     *   The tenant/group ID.
     *
     * @return array|null
     *   Structured profile array with archetype, personality, terms,
     *   and example_phrases keys, or NULL if no profile exists.
     */
    public function getProfileForTenant(string $tenantId): ?array {
        // Try entity-based profile first.
        $entityProfile = $this->loadEntityProfile($tenantId);
        if ($entityProfile) {
            return $entityProfile;
        }

        // Fallback to config-based approach.
        $config = $this->configFactory->get("jaraba_ai_agents.brand_voice.{$tenantId}");
        if ($config->isNew()) {
            return NULL;
        }

        return [
            'archetype' => $config->get('archetype') ?? 'professional',
            'personality' => $config->get('personality') ?? [],
            'forbidden_terms' => $config->get('forbidden_terms') ?? [],
            'preferred_terms' => $config->get('preferred_terms') ?? [],
            'example_phrases' => $config->get('examples') ?? [],
        ];
    }

    /**
     * Loads a BrandVoiceProfile entity for a tenant.
     *
     * @param string $tenantId
     *   The tenant/group ID.
     *
     * @return array|null
     *   Structured profile data or NULL if not found.
     */
    protected function loadEntityProfile(string $tenantId): ?array {
        try {
            $storage = $this->entityTypeManager->getStorage('brand_voice_profile');
            $ids = $storage->getQuery()
                ->accessCheck(FALSE)
                ->condition('tenant_id', $tenantId)
                ->condition('is_active', TRUE)
                ->sort('changed', 'DESC')
                ->range(0, 1)
                ->execute();

            if (empty($ids)) {
                return NULL;
            }

            /** @var \Drupal\jaraba_ai_agents\Entity\BrandVoiceProfile $profile */
            $profile = $storage->load(reset($ids));
            if (!$profile) {
                return NULL;
            }

            return [
                'archetype' => $profile->get('archetype')->value ?? 'professional',
                'personality' => [
                    'formality' => (int) ($profile->get('formality')->value ?? 5),
                    'warmth' => (int) ($profile->get('warmth')->value ?? 5),
                    'confidence' => (int) ($profile->get('confidence')->value ?? 7),
                    'humor' => (int) ($profile->get('humor')->value ?? 3),
                    'technical' => (int) ($profile->get('technical')->value ?? 5),
                ],
                'forbidden_terms' => $profile->getForbiddenTerms(),
                'preferred_terms' => $profile->getPreferredTerms(),
                'example_phrases' => $profile->getExamplePhrases(),
            ];
        }
        catch (\Exception $e) {
            $this->logger->warning('Failed to load BrandVoiceProfile for tenant @tid: @msg', [
                '@tid' => $tenantId,
                '@msg' => $e->getMessage(),
            ]);
            return NULL;
        }
    }

    /**
     * Builds a prompt string from a structured entity profile.
     *
     * @param array $profile
     *   Profile data with archetype, personality, terms, etc.
     *
     * @return string
     *   The constructed brand voice prompt.
     */
    protected function buildPromptFromProfile(array $profile): string {
        $archetypes = [
            'professional' => 'Profesional y confiable, con tono corporativo pero accesible.',
            'artisan' => 'Artesanal y auténtico, enfatizando tradición y calidad.',
            'innovative' => 'Innovador y moderno, orientado a tecnología y futuro.',
            'friendly' => 'Cercano y amigable, como un vecino de confianza.',
            'expert' => 'Experto y autoritativo, transmitiendo conocimiento profundo.',
            'playful' => 'Divertido y energético, con tono juvenil y dinámico.',
            'luxury' => 'Premium y exclusivo, con sofisticación y elegancia.',
            'eco' => 'Sostenible y ético, comprometido con el medio ambiente.',
        ];

        $archetype = $profile['archetype'] ?? 'professional';
        $prompt = "Eres un asistente de IA para una marca con las siguientes características:\n\n";
        $prompt .= "ARQUETIPO: " . ($archetypes[$archetype] ?? $archetypes['professional']) . "\n\n";

        // Personality traits.
        $personality = $profile['personality'] ?? [];
        if (!empty($personality)) {
            $traitLabels = [
                'formality' => 'Formalidad',
                'warmth' => 'Calidez',
                'confidence' => 'Confianza',
                'humor' => 'Humor',
                'technical' => 'Nivel técnico',
            ];

            $prompt .= "PERSONALIDAD:\n";
            foreach ($personality as $trait => $score) {
                $label = $traitLabels[$trait] ?? ucfirst($trait);
                $prompt .= "- {$label}: {$score}/10\n";
            }
            $prompt .= "\n";
        }

        // Example phrases.
        $examplePhrases = $profile['example_phrases'] ?? [];
        if (!empty($examplePhrases)) {
            $prompt .= "FRASES DE EJEMPLO (on-brand):\n";
            foreach ($examplePhrases as $phrase) {
                if (is_string($phrase)) {
                    $prompt .= "- \"{$phrase}\"\n";
                }
            }
            $prompt .= "\n";
        }

        // Terminology.
        $forbiddenTerms = $profile['forbidden_terms'] ?? [];
        if (!empty($forbiddenTerms)) {
            $prompt .= "TÉRMINOS A EVITAR: " . implode(', ', $forbiddenTerms) . "\n";
        }
        $preferredTerms = $profile['preferred_terms'] ?? [];
        if (!empty($preferredTerms)) {
            $prompt .= "TÉRMINOS PREFERIDOS: " . implode(', ', $preferredTerms) . "\n";
        }

        return $prompt;
    }

    /**
     * Retorna el Brand Voice por defecto.
     *
     * Usado cuando el tenant no tiene configuración específica.
     *
     * @return string
     *   Prompt de Brand Voice por defecto.
     */
    protected function getDefaultBrandVoice(): string
    {
        return "Eres un asistente de IA profesional y amable. Responde de forma clara, útil y respetuosa. Adapta tu tono al contexto pero mantén siempre profesionalismo.";
    }

}
