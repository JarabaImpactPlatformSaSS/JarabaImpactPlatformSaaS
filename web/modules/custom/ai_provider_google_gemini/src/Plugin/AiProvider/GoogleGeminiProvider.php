<?php

declare(strict_types=1);

namespace Drupal\ai_provider_google_gemini\Plugin\AiProvider;

use Drupal\ai\Attribute\AiProvider;
use Drupal\ai\Base\AiProviderClientBase;
use Drupal\ai\Exception\AiBadRequestException;
use Drupal\ai\Exception\AiResponseErrorException;
use Drupal\ai\Exception\AiSetupFailureException;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatInterface;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\ai\OperationType\Chat\ChatOutput;
use Drupal\ai\Traits\OperationType\ChatTrait;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Yaml\Yaml;

/**
 * Plugin implementation of the 'google_gemini' provider.
 *
 * Integrates with Google AI Studio (Gemini API) using simple API key auth.
 */
#[AiProvider(
    id: 'google_gemini',
    label: new TranslatableMarkup('Google Gemini (AI Studio)')
)]
class GoogleGeminiProvider extends AiProviderClientBase implements
    ContainerFactoryPluginInterface,
    ChatInterface
{

    use StringTranslationTrait;
    use ChatTrait;

    /**
     * The HTTP client.
     *
     * @var \GuzzleHttp\Client|null
     */
    protected ?Client $client = NULL;

    /**
     * The API key.
     *
     * @var string
     */
    protected string $apiKey = '';

    /**
     * API endpoint base URL.
     */
    const API_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models';

    /**
     * Available models.
     */
    const MODELS = [
        // Gemini 3 Series (Newest - Preview)
        'gemini-3-pro-preview' => 'Gemini 3 Pro Preview (Most Powerful)',
        // Gemini 2.5 Series (Current Stable)
        'gemini-2.5-pro' => 'Gemini 2.5 Pro (Reasoning & Complex Tasks)',
        'gemini-2.5-flash' => 'Gemini 2.5 Flash (Best Price/Performance)',
        'gemini-2.5-flash-lite' => 'Gemini 2.5 Flash-Lite (Cost Optimized)',
        // Gemini 2.0 Series (Previous Generation)
        'gemini-2.0-flash' => 'Gemini 2.0 Flash',
        'gemini-2.0-flash-lite' => 'Gemini 2.0 Flash-Lite',
    ];

    /**
     * {@inheritdoc}
     */
    public function isUsable(?string $operation_type = NULL, array $capabilities = []): bool
    {
        // Check if API key is configured.
        if (!$this->getConfig()->get('api_key')) {
            return FALSE;
        }

        // Check if operation type is supported.
        if ($operation_type) {
            return in_array($operation_type, $this->getSupportedOperationTypes());
        }

        return TRUE;
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedOperationTypes(): array
    {
        return ['chat'];
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(): ImmutableConfig
    {
        return $this->configFactory->get('ai_provider_google_gemini.settings');
    }

    /**
     * {@inheritdoc}
     */
    public function getApiDefinition(): array
    {
        try {
            $module = $this->moduleHandler->getModule('ai_provider_google_gemini');
            $path = $module->getPath() . '/definitions/api_defaults.yml';
            if (file_exists($path)) {
                return Yaml::parseFile($path);
            }
        } catch (\Exception $e) {
            // Fallback to inline definition if file not found.
        }

        // Inline fallback definition.
        return [
            'chat' => [
                'input' => [
                    'description' => 'Input provided to the model.',
                    'type' => 'array',
                    'default' => [['role' => 'user', 'content' => 'Hello!']],
                    'required' => TRUE,
                ],
                'authentication' => [
                    'description' => 'API Key from Google AI Studio',
                    'type' => 'string',
                    'default' => '',
                    'required' => TRUE,
                ],
                'configuration' => [
                    'max_tokens' => [
                        'label' => 'Max Output Tokens',
                        'description' => 'Maximum tokens in response.',
                        'type' => 'integer',
                        'default' => 8192,
                        'required' => FALSE,
                    ],
                    'temperature' => [
                        'label' => 'Temperature',
                        'description' => 'Controls randomness (0.0-2.0).',
                        'type' => 'float',
                        'default' => 0.7,
                        'required' => FALSE,
                    ],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getModelSettings(string $model_id, array $generalConfig = []): array
    {
        return $generalConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function setAuthentication(mixed $authentication): void
    {
        $this->apiKey = $authentication;
        $this->client = NULL;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguredModels(?string $operation_type = NULL, array $capabilities = []): array
    {
        // Return predefined models for chat.
        if ($operation_type === 'chat' || $operation_type === NULL) {
            return self::MODELS;
        }
        return [];
    }

    /**
     * Gets the HTTP client.
     *
     * @return \GuzzleHttp\Client
     *   The HTTP client.
     */
    public function getClient(): Client
    {
        $this->loadClient();
        return $this->client;
    }

    /**
     * Loads the HTTP client.
     */
    protected function loadClient(): void
    {
        if (!$this->client) {
            $this->client = new Client([
                'timeout' => 60,
                'connect_timeout' => 10,
            ]);
        }
    }

    /**
     * Loads the API key from the Key module.
     *
     * @return string
     *   The API key value.
     *
     * @throws \Drupal\ai\Exception\AiSetupFailureException
     *   If the API key cannot be loaded.
     */
    protected function loadApiKey(): string
    {
        if ($this->apiKey) {
            return $this->apiKey;
        }

        $keyId = $this->getConfig()->get('api_key');
        if (empty($keyId)) {
            throw new AiSetupFailureException('Google Gemini API key is not configured.');
        }

        $key = $this->keyRepository->getKey($keyId);
        if (!$key || !($apiKey = $key->getKeyValue())) {
            throw new AiSetupFailureException('Could not load Google Gemini API key from the Key module.');
        }

        $this->apiKey = $apiKey;
        return $this->apiKey;
    }

    /**
     * {@inheritdoc}
     */
    public function chat(array|string|ChatInput $input, string $model_id, array $tags = []): ChatOutput
    {
        $this->loadClient();
        $apiKey = $this->loadApiKey();

        // Build the endpoint URL.
        $endpoint = self::API_ENDPOINT . '/' . $model_id . ':generateContent?key=' . $apiKey;

        // Build the request payload.
        $payload = $this->buildChatPayload($input);

        $rawOutput = [];

        try {
            $response = $this->client->post($endpoint, [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);

            $body = $response->getBody()->getContents();
            $json = json_decode($body, TRUE);

            if (empty($json['candidates']) || !is_array($json['candidates'])) {
                throw new AiBadRequestException('No candidates returned from Google Gemini API.');
            }

            $candidate = reset($json['candidates']);

            // Check for blocked responses.
            if (!empty($candidate['finishReason']) && $candidate['finishReason'] === 'SAFETY') {
                throw new AiResponseErrorException('Response blocked due to safety filters.');
            }

            // Extract text from response.
            $text = '';
            if (!empty($candidate['content']['parts'])) {
                foreach ($candidate['content']['parts'] as $part) {
                    if (isset($part['text'])) {
                        $text .= $part['text'];
                    }
                }
            }

            if (empty($text)) {
                throw new AiBadRequestException('Could not extract text from response.');
            }

            $role = $candidate['content']['role'] ?? 'model';
            $message = new ChatMessage($role, $text);

            // Build raw output for debugging/logging.
            $rawOutput = [
                'candidates' => [
                    [
                        'text' => $text,
                        'role' => $role,
                    ],
                ],
                'model_version' => $json['modelVersion'] ?? $model_id,
            ];

            // Add usage metadata if available.
            if (isset($json['usageMetadata'])) {
                $rawOutput['usage_metadata'] = [
                    'prompt_token_count' => $json['usageMetadata']['promptTokenCount'] ?? 0,
                    'candidates_token_count' => $json['usageMetadata']['candidatesTokenCount'] ?? 0,
                    'total_token_count' => $json['usageMetadata']['totalTokenCount'] ?? 0,
                ];
            }

        } catch (GuzzleException $e) {
            $this->handleApiError($e);
        }

        return new ChatOutput($message, $rawOutput, $rawOutput['usage_metadata'] ?? []);
    }

    /**
     * Builds the chat payload for the API request.
     *
     * @param array|string|\Drupal\ai\OperationType\Chat\ChatInput $input
     *   The input messages.
     *
     * @return array
     *   The formatted payload.
     */
    protected function buildChatPayload(array|string|ChatInput $input): array
    {
        $payload = [
            'contents' => [],
            'generationConfig' => [
                'maxOutputTokens' => 8192,
                'temperature' => 0.7,
            ],
        ];

        // Add system instruction if set.
        if ($this->chatSystemRole) {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => $this->chatSystemRole],
                ],
            ];
        }

        if ($input instanceof ChatInput) {
            foreach ($input->getMessages() as $message) {
                $content = $this->formatMessage($message);
                $payload['contents'][] = $content;
            }
        } elseif (is_string($input)) {
            $payload['contents'][] = [
                'role' => 'user',
                'parts' => [
                    ['text' => $input],
                ],
            ];
        } else {
            $payload['contents'] = $input;
        }

        return $payload;
    }

    /**
     * Formats a ChatMessage for the Gemini API.
     *
     * @param \Drupal\ai\OperationType\Chat\ChatMessage $message
     *   The chat message.
     *
     * @return array
     *   The formatted message.
     */
    protected function formatMessage(ChatMessage $message): array
    {
        // Gemini uses 'user' and 'model' roles.
        $role = ($message->getRole() === 'user') ? 'user' : 'model';

        $parts = [];

        // Add text content.
        if ($text = $message->getText()) {
            $parts[] = ['text' => $text];
        }

        // Add images if present.
        if (count($message->getImages())) {
            foreach ($message->getImages() as $image) {
                $parts[] = [
                    'inlineData' => [
                        'mimeType' => $image->getMimeType(),
                        'data' => base64_encode($image->getAsBinary()),
                    ],
                ];
            }
        }

        return [
            'role' => $role,
            'parts' => $parts,
        ];
    }

    /**
     * Handles API errors and throws appropriate exceptions.
     *
     * @param \GuzzleHttp\Exception\GuzzleException $e
     *   The Guzzle exception.
     *
     * @throws \Drupal\ai\Exception\AiBadRequestException
     * @throws \Drupal\ai\Exception\AiResponseErrorException
     */
    protected function handleApiError(GuzzleException $e): void
    {
        $message = $e->getMessage();

        // Try to extract error details from response body.
        if (method_exists($e, 'getResponse') && $response = $e->getResponse()) {
            $body = $response->getBody()->getContents();
            $json = json_decode($body, TRUE);
            if (isset($json['error']['message'])) {
                $message = $json['error']['message'];
            }
        }

        // Check for specific error types.
        if (str_contains($message, 'API key')) {
            throw new AiSetupFailureException('Invalid or missing Google Gemini API key: ' . $message);
        }

        if (str_contains($message, 'quota') || str_contains($message, 'rate limit')) {
            throw new AiResponseErrorException('Google Gemini API quota exceeded: ' . $message);
        }

        throw new AiBadRequestException('Google Gemini API error: ' . $message);
    }

    /**
     * {@inheritdoc}
     */
    public function loadModelsForm(array $form, $form_state, string $operation_type, ?string $model_id = NULL): array
    {
        $form = parent::loadModelsForm($form, $form_state, $operation_type, $model_id);
        $config = $this->loadModelConfig($operation_type, $model_id);

        // Replace model_id textfield with select from predefined models.
        $form['model_data']['model_id'] = [
            '#type' => 'select',
            '#title' => $this->t('Model'),
            '#description' => $this->t('Select the Gemini model to use.'),
            '#options' => self::MODELS,
            '#default_value' => $config['model_id'] ?? 'gemini-2.0-flash',
            '#required' => TRUE,
        ];

        return $form;
    }

}
