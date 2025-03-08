<?php

namespace Contextr\Providers;

use Contextr\Response\Response;
use Exception;
use GrokPHP\Client\Clients\GrokClient;
use GrokPHP\Client\Config\ChatOptions;
use GrokPHP\Client\Config\GrokConfig;
use GrokPHP\Client\Enums\Model;

class Grok implements Provider
{
    public function __construct(
        public string $apiKey,
        public Model $model = Model::GROK_2_1212,
        public float $temperature = 0.1,
        public bool $throwExceptions = false
    ) {
        //
    }

    public function analyze(string $prompt): Response
    {
        $client = new GrokClient(new GrokConfig($this->apiKey));

        $messages = [['role' => 'system', 'content' => $prompt]];

        try {
            $options = new ChatOptions(model: $this->model, temperature: $this->temperature, stream: false);
            $result = $client->chat($messages, $options);
            $content = $result['choices'][0]['message']['content'] ?? '';

            // Cannot set 'response_format' => ['type' => 'json_object'] yet (https://github.com/grok-php/client/pull/3)
            // Currently need to use this dirty trick.
            $content = preg_replace('/^```json|```$/', '', $content);

            return new Response(data: $content, sourceResponse: $result, success: true);
        } catch (Exception $e) {
            if ($this->throwExceptions) {
                throw $e;
            }

            return new Response(data: null, sourceResponse: null, success: false, error: [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
        }
    }
}
