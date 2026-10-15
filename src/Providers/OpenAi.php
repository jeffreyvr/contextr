<?php

namespace Contextr\Providers;

use Contextr\Checks\Check;
use Contextr\Response\Response;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use OpenAI as OpenAIClient;
use OpenAI\Contracts\ClientContract;
use OpenAI\Exceptions\ErrorException;

class OpenAi implements Provider
{
    /**
     * Recommended replacement for the GPT-4o, GPT-4o mini, GPT-4.1, and
     * GPT-4.1 mini models OpenAI removes on 2027-01-14.
     *
     * @see https://developers.openai.com/api/docs/deprecations
     * @see https://help.openai.com/en/articles/20001600-migrating-from-gpt-4x-to-gpt-6-luna
     */
    public const REPLACEMENT_MODEL = 'gpt-6-luna';

    /**
     * UTC instant when RETIRED_MODELS start being sent as REPLACEMENT_MODEL.
     * OpenAI's shutdown date has no time of day; midnight UTC is the start of that date.
     */
    public const RETIRED_AT = '2027-01-14 00:00:00 UTC';

    /**
     * Exact ids => replacement. Stable aliases are included because callers
     * (and this provider's previous default) pass those, and they point at the
     * dated snapshots named on OpenAI's deprecations page.
     *
     * @var array<string, string>
     */
    public const RETIRED_MODELS = [
        'gpt-4o' => self::REPLACEMENT_MODEL,
        'gpt-4o-2024-08-06' => self::REPLACEMENT_MODEL,
        'gpt-4o-2024-11-20' => self::REPLACEMENT_MODEL,
        'gpt-4o-mini' => self::REPLACEMENT_MODEL,
        'gpt-4o-mini-2024-07-18' => self::REPLACEMENT_MODEL,
        'gpt-4.1' => self::REPLACEMENT_MODEL,
        'gpt-4.1-2025-04-14' => self::REPLACEMENT_MODEL,
        'gpt-4.1-mini' => self::REPLACEMENT_MODEL,
        'gpt-4.1-mini-2025-04-14' => self::REPLACEMENT_MODEL,
    ];

    public function __construct(
        public string $apiKey,
        public string $model = self::REPLACEMENT_MODEL,
        public float $temperature = 0.1,
        public ?string $organization = null,
        public ?string $project = null,
        public bool $throwExceptions = true
    ) {
        //
    }

    public function analyze(Check $check): Response
    {
        $client = $this->client();
        $model = $this->requestModel();

        try {
            return $this->complete($client, $check, $model);
        } catch (Exception $exception) {
            $replacement = $this->replacementFor($model);

            if ($replacement === null || ! $this->isModelNotFound($exception)) {
                return $this->failure($exception);
            }

            try {
                return $this->complete($client, $check, $replacement);
            } catch (Exception $retryException) {
                return $this->failure($retryException);
            }
        }
    }

    protected function client(): ClientContract
    {
        return OpenAIClient::client($this->apiKey, $this->organization, $this->project);
    }

    protected function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    private function requestModel(): string
    {
        $replacement = $this->replacementFor($this->model);

        if ($replacement === null || $this->now() < $this->shutdownAt()) {
            return $this->model;
        }

        return $replacement;
    }

    private function shutdownAt(): DateTimeImmutable
    {
        return new DateTimeImmutable(self::RETIRED_AT);
    }

    private function replacementFor(string $model): ?string
    {
        $replacement = self::RETIRED_MODELS[$model] ?? null;

        if ($replacement === null || $replacement === $model) {
            return null;
        }

        return $replacement;
    }

    private function complete(ClientContract $client, Check $check, string $model): Response
    {
        $result = $client->chat()->create($this->parameters($check, $model));

        return new Response(data: $result->choices[0]->message->content, sourceResponse: $result, success: true, responseMap: $check->responseMap);
    }

    /**
     * @return array<string, mixed>
     */
    private function parameters(Check $check, string $model): array
    {
        $parameters = [
            'model' => $model,
            'messages' => [['role' => 'system', 'content' => $check->prompt]],
            'response_format' => ['type' => 'json_object'],
            'temperature' => $this->temperature,
        ];

        // Luna defaults to reasoning effort "medium", which rejects temperature.
        // OpenAI's GPT-4.x migration guide says to start at "none", the only
        // effort that still accepts temperature.
        if ($model === self::REPLACEMENT_MODEL) {
            $parameters['reasoning_effort'] = 'none';
        }

        return $parameters;
    }

    private function isModelNotFound(Exception $exception): bool
    {
        if (! $exception instanceof ErrorException) {
            return false;
        }

        if ($exception->getErrorCode() === 'model_not_found') {
            return true;
        }

        if ($exception->getStatusCode() !== 404) {
            return false;
        }

        $message = strtolower($exception->getMessage());

        return str_contains($message, 'model')
            && (str_contains($message, 'does not exist')
                || str_contains($message, 'not found')
                || str_contains($message, 'do not have access'));
    }

    private function failure(Exception $exception): Response
    {
        if ($this->throwExceptions) {
            throw $exception;
        }

        return new Response(data: null, sourceResponse: null, success: false, error: [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
        ]);
    }
}
