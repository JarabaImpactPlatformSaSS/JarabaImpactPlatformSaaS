<?php

namespace Drupal\ai_provider_google_vertex\Plugin\AiProvider;

use Drupal\ai\Exception\AiMissingFeatureException;
use Drupal\ai\Exception\AiResponseErrorException;
use Drupal\ai\OperationType\Chat\Tools\ToolsFunctionOutput;
use Drupal\ai_provider_google_vertex\TranslationModels;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ai\Attribute\AiProvider;
use Drupal\ai\Base\AiProviderClientBase;
use Drupal\ai\Exception\AiBadRequestException;
use Drupal\ai\Exception\AiSetupFailureException;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatInterface;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\ai\OperationType\Chat\ChatOutput;
use Drupal\ai\OperationType\Embeddings\EmbeddingsInput;
use Drupal\ai\OperationType\Embeddings\EmbeddingsInterface;
use Drupal\ai\OperationType\Embeddings\EmbeddingsOutput;
use Drupal\ai\OperationType\TranslateText\TranslateTextInput;
use Drupal\ai\OperationType\TranslateText\TranslateTextInterface;
use Drupal\ai\OperationType\TranslateText\TranslateTextOutput;
use Drupal\ai\Traits\OperationType\ChatTrait;
use Symfony\Component\Yaml\Yaml;
use GuzzleHttp\Client;
use Google\Auth\Credentials\ServiceAccountCredentials;

/**
 * Plugin implementation of the 'google vertex' provider.
 */
#[AiProvider(
  id: 'google_vertex',
  label: new TranslatableMarkup('Google Vertex'),
)]
class VertexProvider extends AiProviderClientBase implements
  ContainerFactoryPluginInterface,
  ChatInterface,
  EmbeddingsInterface,
  TranslateTextInterface {

  use StringTranslationTrait;
  use ChatTrait;

  /**
   * The HTTP client.
   *
   * (Originally this was a PredictionServiceClient;)
   *
   * @var \GuzzleHttp\Client|null
   */
  protected $client;

  /**
   * Credential file.
   *
   * @var string
   */
  protected string $credentialFile = '';

  /**
   * We want to add models to the provider dynamically.
   *
   * @var bool
   */
  protected bool $hasPredefinedModels = FALSE;

  /**
   * {@inheritdoc}
   */
  public function isUsable(?string $operation_type = NULL, array $capabilities = []): bool {
    // If it's not configured, it is not usable.
    if (!$this->getConfig()->get('general_credential_file')) {
      return FALSE;
    }
    // If it's one of the bundles that Vertex supports its usable.
    if ($operation_type) {
      return in_array($operation_type, $this->getSupportedOperationTypes());
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedOperationTypes(): array {
    return [
      'chat',
      'embeddings',
      'translate_text',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(): ImmutableConfig {
    return $this->configFactory->get('ai_provider_google_vertex.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getApiDefinition(): array {
    // Load the configuration.
    $definition = Yaml::parseFile($this->moduleHandler->getModule('ai_provider_google_vertex')->getPath() . '/definitions/api_defaults.yml');
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getModelSettings(string $model_id, array $generalConfig = []): array {
    return $generalConfig;
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthentication(mixed $file_location): void {
    // Set the new file credential location.
    $this->credentialFile = $file_location;
    $this->client = NULL;
  }

  /**
   * Gets the raw chat client.
   *
   * @return \GuzzleHttp\Client
   *   The HTTP client.
   */
  public function getClient(): Client {
    $this->loadClient();
    return $this->client;
  }

  /**
   * Loads the HTTP client.
   */
  protected function loadClient(): void {
    if (!$this->client) {
      $this->client = new Client();
    }
  }

  /**
   * Retrieves an OAuth2 access token using the service account credentials.
   *
   * @return string
   *   The access token.
   *
   * @throws \Exception
   *   Thrown if unable to retrieve an access token.
   */
  protected function getAccessToken(): string {
    $credentialsJson = $this->loadCredentials();

    if (!$credentialsJson) {
      throw new \Exception('Invalid credentials JSON.');
    }
    $sc = new ServiceAccountCredentials(
      ['https://www.googleapis.com/auth/cloud-platform'],
      $credentialsJson
    );
    $token = $sc->fetchAuthToken();
    if (!isset($token['access_token'])) {
      throw new \Exception('Unable to retrieve access token.');
    }
    return $token['access_token'];
  }

  /**
   * Returns the authentication headers.
   *
   * @return array
   *   An array containing the Authorization header.
   */
  protected function getAuthHeaders(): array {
    return [
      'Authorization' => 'Bearer ' . $this->getAccessToken(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function chat(array|string|ChatInput $input, string $model_id, array $tags = []): ChatOutput {
    $info = $this->getModelInfo('chat', $model_id);
    if (!isset($info['vertex_model_id'], $info['project_id'], $info['location'])) {
      throw new AiBadRequestException('The model does not exist.');
    }
    $this->loadClient();

    $groundingDatastore = $info['datastore'] ?? '';
    $useGrounding = !empty($groundingDatastore);

    $url = sprintf('projects/%s/locations/%s/publishers/google/models/%s', $info['project_id'], $info['location'], $info['vertex_model_id']);
    $endpoint = 'https://' . $info['location'] . '-aiplatform.googleapis.com/v1/' . $url . ':generateContent';

    $payload = ['model' => $url];
    if ($input instanceof ChatInput) {
      if ($this->chatSystemRole) {
        $payload['systemInstruction'] = [
          'role' => 'system',
          'parts' => [
            ['text' => $this->chatSystemRole],
          ],
        ];
      }
      $payload['contents'] = [];
      foreach ($input->getMessages() as $message) {
        $content = $this->getMessageContent($message);
        if (count($message->getImages())) {
          $useGrounding = FALSE;
        }
        $payload['contents'][] = $content;
      }
      // Add grounding to a datastore via the tools key.
      if ($useGrounding === TRUE) {
        $payload['tools']['retrieval']['vertexAiSearch'] = [
          'datastore' => $groundingDatastore,
        ];
      }
    }
    else {
      $payload['contents'] = $input;
    }

    if (method_exists($input, 'getChatTools') && $input->getChatTools()) {
      $this->addTools($input, $payload);
    }

    $raw_output = [];
    try {
      $options = [
        'json' => $payload,
        'headers' => $this->getAuthHeaders(),
      ];

      $response = $this->client->post($endpoint, $options);
      $body = $response->getBody()->getContents();
      $json = json_decode($body, TRUE);
      if (!empty($json['candidates']) && is_array($json['candidates'])) {
        $candidate = reset($json['candidates']);
        foreach ($candidate['content']['parts'] as $part) {
          // Part is a function call.
          if (!empty($part['functionCall'])) {
            $tool = $part['functionCall'];
            $functionInput = $input->getChatTools()->getFunctionByName($tool['name']);
            $functionOutput = new ToolsFunctionOutput($functionInput, $tool['name'], $tool['args']);
            $message = (new ChatMessage($candidate['content']['role']));
            $message->setTools([$functionOutput]);
            $raw_output['candidates'][] = [
              'text' => json_encode($tool, JSON_PRETTY_PRINT),
              'role' => $candidate['content']['role'],
            ];
          }
          elseif (isset($part['text'])) {
            $raw_output['candidates'][] = [
              'text' => $candidate['content']['parts'][0]['text'],
              'role' => $candidate['content']['role'],
            ];
            $message = new ChatMessage($candidate['content']['role'], $candidate['content']['parts'][0]['text']);
          }
          else {
            throw new AiBadRequestException('Could not get a text response from the model.');
          }
        }
      }
      else {
        throw new AiBadRequestException('Could not get a text response from the model.');
      }
      $raw_output['model_version'] = $json['modelVersion'] ?? '';
      if (isset($json['promptFeedback'])) {
        $raw_output['prompt_feedback'] = [
          'block_reason' => $json['promptFeedback']['blockReason'] ?? '',
          'block_reason_message' => $json['promptFeedback']['blockReasonMessage'] ?? '',
          'safety_rating' => $json['promptFeedback']['safetyRatings'] ?? [],
        ];
      }
      if (isset($json['usageMetadata'])) {
        $raw_output['usage_metadata'] = [
          'prompt_token_count' => $json['usageMetadata']['promptTokenCount'] ?? 0,
          'candidates_token_count' => $json['usageMetadata']['candidatesTokenCount'] ?? 0,
          'total_token_count' => $json['usageMetadata']['totalTokenCount'] ?? 0,
        ];
      }
    }
    catch (\Exception $e) {
      $this->throwError($e->getMessage());
    }
    if (!isset($message)) {
      throw new AiBadRequestException('Could not get a return from the model.');
    }
    return new ChatOutput($message, $raw_output, $raw_output['usage_metadata'] ?? []);
  }

  /**
   * {@inheritdoc}
   */
  public function embeddings(string|EmbeddingsInput $input, string $model_id, array $tags = []): EmbeddingsOutput {
    $info = $this->getModelInfo('embeddings', $model_id);
    if (!isset($info['vertex_model_id'], $info['project_id'], $info['location'])) {
      throw new AiBadRequestException('The model does not exist.');
    }
    $this->loadClient();

    // Calculate the url.
    $url = sprintf('projects/%s/locations/%s/publishers/google/models/%s', $info['project_id'], $info['location'], $info['vertex_model_id']);
    $endpoint = 'https://' . $info['location'] . '-aiplatform.googleapis.com/v1/' . $url . ':predict';
    $text = ($input instanceof EmbeddingsInput) ? $input->getPrompt() : $input;
    $payload = [
      'instances' => [
        ['content' => $text],
      ],
    ];

    try {
      $options = [
        'json' => $payload,
        'headers' => $this->getAuthHeaders(),
      ];
      $response = $this->client->post($endpoint, $options);
      $body = $response->getBody()->getContents();
      $json = json_decode($body, TRUE);
      $embedding_values = [];
      if (!empty($json['predictions']) && is_array($json['predictions'])) {
        foreach ($json['predictions'] as $prediction) {
          if (isset($prediction['embedding']) && is_array($prediction['embedding'])) {
            $embedding_values = array_merge($embedding_values, $prediction['embedding']);
          }
          else {
            foreach ($prediction as $field) {
              if (is_array($field)) {
                $embedding_values = array_merge($embedding_values, $field);
              }
            }
          }
        }
      }
    }
    catch (\Exception $e) {
      $this->throwError($e->getMessage());
    }

    if (empty($embedding_values)) {
      throw new AiBadRequestException('Could not get a return from the model.');
    }
    $raw_output['model_version'] = $json['modelDisplayName'] ?? '';
    $raw_output['prediction'] = $embedding_values;
    return new EmbeddingsOutput($embedding_values['values'], $raw_output, []);
  }

  /**
   * {@inheritdoc}
   */
  public function maxEmbeddingsInput(string $model_id = ''): int {
    return 4096;
  }

  /**
   * {@inheritdoc}
   */
  public function embeddingsVectorSize(string $model_id): int {
    return 768;
  }

  /**
   * {@inheritdoc}
   */
  public function loadModelsForm(array $form, $form_state, string $operation_type, ?string $model_id = NULL): array {
    $form = parent::loadModelsForm($form, $form_state, $operation_type, $model_id);
    $config = $this->loadModelConfig($operation_type, $model_id);
    $form['model_data']['project_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Project ID'),
      '#description' => $this->t('The Google Vertex project id needed to access the model.'),
      '#default_value' => $config['project_id'] ?? '',
      '#required' => TRUE,
    ];
    $form['model_data']['location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Location'),
      '#description' => $this->t('The Google Vertex location needed to access the model. Example: us-central1'),
      '#default_value' => $config['location'] ?? '',
      '#required' => TRUE,
    ];
    // Text translation models are hardcoded.
    // @see https://cloud.google.com/vertex-ai/generative-ai/docs/translate/translate-text#translation-llm
    if ('translate_text' === $operation_type) {
      $form['model_data']['model_id']['#type'] = 'select';
      $form['model_data']['model_id']['#description'] = $this->t(
        'Comparison of translation models supported by Vertex API can be found at <a href="@link" target="_blank">@link</a>.',
        ['@link' => 'https://cloud.google.com/vertex-ai/generative-ai/docs/translate/translate-text#translation-llm']);

      $options = [];
      foreach (TranslationModels::cases() as $model) {
        $options[$model->value] = $model->getLabel();
      }
      $form['model_data']['model_id']['#options'] = $options;
    }
    else {
      $form['model_data']['vertex_model_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Vertex Model ID'),
        '#description' => $this->t('The Google Vertex model id. Example: gemini-1.0-pro'),
        '#default_value' => $config['vertex_model_id'] ?? '',
        '#required' => TRUE,
      ];
    }
    // Inject the Datastore textfield under model_data.
    $form['model_data']['datastore'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Data Store'),
      '#description' => $this->t('Optional data store identifier (BigQuery, Firestore, etc.). <a href="@link" target="_blank">Learn more about Google Data Stores</a>.', [
        '@link' => 'https://cloud.google.com/generative-ai-app-builder/docs/create-datastore-ingest',
      ]),
      '#default_value' => $config['datastore'] ?? '',
      '#weight' => 4,
      '#required' => FALSE,
    ];
    return $form;
  }

  /**
   * Throw errors.
   *
   * @param string $message
   *   The message that got thrown.
   *
   * @throws \Drupal\ai\Exception\AiBadRequestException
   * @throws \Drupal\ai\Exception\AiResponseErrorException
   * @throws \Drupal\ai\Exception\AiMissingFeatureException
   */
  protected function throwError(string $message): void {
    $data = Json::decode($message);
    if (is_array($data)) {
      if (isset($data['reason'])) {
        if ($data['reason'] == 'CONSUMER_INVALID') {
          throw new AiMissingFeatureException('You do not have access to the model.');
        }
        else {
          throw new AiResponseErrorException($data['reason']);
        }
      }
    }
    throw new AiBadRequestException($message);
  }

  /**
   * Get the location of credentials file from the key module.
   *
   * @return array
   *   Decoded contents of credentials file.
   */
  protected function loadCredentials(): array {
    $key_id = $this->getConfig()->get('general_credential_file');
    $key = $this->keyRepository->getKey($key_id);
    if (!$key || !($file = $key->getKeyValue())) {
      throw new AiSetupFailureException(sprintf('Could not load the %s credential file or its not available, please check your environment settings or your setup.', $this->getPluginDefinition()['label']));
    }
    return Json::decode($file);
  }

  /**
   * Add tools section to the chat request payload.
   *
   * @param mixed $input
   *   Chat input passed to chat() method.
   * @param array $payload
   *   Request payload.
   */
  protected function addTools(&$input, array &$payload) {
    $renderedTools = $input->getChatTools()->renderToolsArray();
    foreach ($renderedTools as $tool) {
      if ($tool['type'] !== 'function') {
        continue;
      }

      $function = [
        'name' => $tool['function']['name'],
        'description' => $tool['function']['description'],
      ];

      $parameters = [];
      if (!empty($tool['function']['parameters']['properties'])) {
        $parameters = ['type' => 'object'];
        foreach ($tool['function']['parameters']['properties'] as $name => $parameter) {
          $parameters['properties'][$name] = [
            'type' => 'string',
            'description' => $tool['function']['parameters']['properties'][$name]['description'] ?? '',
          ];
        }
        $parameters['required'] = ($tool['function']['parameters']['required']) ?? [];
      }
      if ($parameters) {
        $function['parameters'] = $parameters;
      }
      $payload['tools']['function_declarations'][] = $function;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function translateText(TranslateTextInput $input, string $model_id, array $options = []): TranslateTextOutput {
    $info = $this->getModelInfo('translate_text', $model_id);
    if (!isset($info['project_id'], $info['location'])) {
      throw new AiBadRequestException('The model does not exist.');
    }
    $this->loadClient();
    $model = TranslationModels::tryFrom($model_id);
    if (!$model) {
      throw new AiBadRequestException('The model does not exist.');
    }

    $text = $input->getText();
    $source_language = $input->getSourceLanguage();
    $target_language = $input->getTargetLanguage();

    // Calculate the url.
    $endpoint = $model->getUrl($info);
    $info['source_language'] = $source_language;
    $info['target_language'] = $target_language;
    $info['text'] = $text;
    $payload = $model->buildPayload($info);

    $translated_text = '';
    $raw_output = [];

    try {
      $options = [
        'json' => $payload,
        'headers' => $this->getAuthHeaders(),
      ];

      $response = $this->client->post($endpoint, $options);
      $body = $response->getBody()->getContents();
      $json = json_decode($body, TRUE);

      $translated_text = $model->extractTranslation($json);
      if (empty($translated_text)) {
        throw new AiBadRequestException('Could not extract translated text from the response.');
      }

      // Prepare raw output for metadata.
      $raw_output['source_language_code'] = $source_language;
      $raw_output['target_language_code'] = $target_language;
      $raw_output['predictions'] = $json['predictions'] ?? [];
    }
    catch (\Exception $e) {
      $this->throwError($e->getMessage());
    }

    return new TranslateTextOutput($translated_text, $raw_output, []);
  }

  /**
   * Extract content from the message.
   *
   * @param \Drupal\ai\OperationType\Chat\ChatMessage $message
   *   The message to extract content from.
   *
   * @return string[]
   *   An array of extracted content.
   *
   * @see https://cloud.google.com/vertex-ai/docs/reference/rest/v1/Content
   * @see https://cloud.google.com/vertex-ai/docs/reference/rest/v1/projects.locations.endpoints/generateContent
   */
  protected function getMessageContent(ChatMessage $message): array {
    $content = [
      'role' => ($message->getRole() === 'user') ? 'user' : 'model',
    ];
    $parts = [];
    if ($message->getToolsId()) {
      $parts[]['functionResponse'] = [
        'name' => $message->getToolsId(),
        'response' => [
          'text' => $message->getText(),
        ],
      ];
    }
    elseif ($tools = $message->getTools()) {
      foreach ($tools as $tool) {
        $output = $tool->getOutputRenderArray();
        $name = $output['function']['name'];
        $args = Json::decode($output['function']['arguments']);
        $parts[]['functionCall'] = [
          'name' => $name,
          'args' => $args,
        ];
      }
    }
    else {
      $parts[]['text'] = $message->getText();
    }
    if (count($message->getImages())) {
      foreach ($message->getImages() as $image) {
        $parts[] = [
          'inlineData' => [
            'data' => base64_encode($image->getAsBinary()),
            'mime_type' => $image->getMimeType(),
          ],
        ];
      }
    }
    $content['parts'] = $parts;

    return $content;
  }

}
