<?php

namespace Contextr\Providers;

use Contextr\Response;
use Exception;
use OpenAI as OpenAIClient;

class OpenAi implements Provider
{
    public function __construct(
        public string $apiKey,
        public string $model = 'gpt-4o-mini',
        public float $temperature = 0.1,
        public ?string $organization = null,
        public ?string $project = null,
        public bool $throwExceptions = true
    ) {
        //
    }

    public function analyze(string $prompt): Response
    {
        $client = OpenAIClient::client($this->apiKey, $this->organization, $this->project);

        $messages = [['role' => 'system', 'content' => $prompt]];

        try {
            $result = $client->chat()->create([
                'model' => $this->model,
                'messages' => $messages,
                'response_format' => ['type' => 'json_object'],
                'temperature' => $this->temperature,
            ]);

            return new Response(data: $result->choices[0]->message->content, sourceResponse: $result, success: true);
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
