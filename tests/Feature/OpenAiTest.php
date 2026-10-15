<?php

use Contextr\Checks\Spam;
use Contextr\Providers\OpenAi;
use Contextr\Tests\Support\ClockedOpenAi;
use OpenAI\Exceptions\ErrorException;
use OpenAI\Resources\Chat;
use OpenAI\Responses\Chat\CreateResponse;
use OpenAI\Testing\ClientFake;
use OpenAI\Testing\Requests\TestRequest;

function openAiChat(string $json = '{"spam":false,"confidence":0.42}'): CreateResponse
{
    return CreateResponse::fake([
        'choices' => [
            [
                'message' => [
                    'content' => $json,
                ],
            ],
        ],
    ]);
}

function openAiError(string $message, string|int|null $code, int $status): ErrorException
{
    return new ErrorException([
        'message' => $message,
        'type' => 'invalid_request_error',
        'code' => $code,
    ], $status);
}

function openAiModelNotFound(string $model): ErrorException
{
    return openAiError(
        "The model `{$model}` does not exist or you do not have access to it.",
        'model_not_found',
        404,
    );
}

function openAiProvider(
    string $model,
    array $responses,
    string $at,
    string $timezone = 'UTC',
    bool $throwExceptions = true,
    float $temperature = 0.1,
): array {
    $client = new ClientFake($responses);
    $provider = new ClockedOpenAi(
        fakeClient: $client,
        clock: new DateTimeImmutable($at, new DateTimeZone($timezone)),
        model: $model,
        temperature: $temperature,
        throwExceptions: $throwExceptions,
    );

    return [$provider, $client];
}

function openAiSent(ClientFake $client): array
{
    $requests = (new ReflectionProperty(ClientFake::class, 'requests'))->getValue($client);

    return array_values(array_map(
        fn (TestRequest $request): array => $request->args()[0],
        $requests,
    ));
}

it('defaults to gpt-6-luna', function () {
    $provider = new OpenAi(apiKey: 'test-key');

    expect($provider->model)->toBe('gpt-6-luna')
        ->and(OpenAi::REPLACEMENT_MODEL)->toBe('gpt-6-luna');
});

it('sends gpt-6-luna with reasoning effort none and the configured temperature', function () {
    [$provider, $client] = openAiProvider(
        model: OpenAi::REPLACEMENT_MODEL,
        responses: [openAiChat()],
        at: '2026-10-15 12:00:00',
        temperature: 0.4,
    );

    $response = (new Spam($provider, 'hello world', []))->analyze();

    expect($response->success())->toBeTrue()
        ->and($response->spam())->toBeFalse()
        ->and($response->confidence())->toBe(0.42);

    $sent = openAiSent($client);

    expect($sent)->toHaveCount(1)
        ->and($sent[0]['model'])->toBe('gpt-6-luna')
        ->and($sent[0]['reasoning_effort'])->toBe('none')
        ->and($sent[0]['temperature'])->toBe(0.4)
        ->and($sent[0]['response_format'])->toBe(['type' => 'json_object'])
        ->and($sent[0]['messages'][0]['content'])->toContain('hello world');

    $client->assertSent(Chat::class, 1);
});

it('keeps an explicit retired model until the shutdown instant', function (string $model) {
    [$provider, $client] = openAiProvider(
        model: $model,
        responses: [openAiChat()],
        at: '2027-01-13 23:59:59',
    );

    expect((new Spam($provider, 'hello', []))->analyze()->success())->toBeTrue();

    $sent = openAiSent($client);

    expect($sent)->toHaveCount(1)
        ->and($sent[0]['model'])->toBe($model)
        ->and($sent[0])->not->toHaveKey('reasoning_effort')
        ->and($sent[0]['temperature'])->toBe(0.1);
})->with('retired openai models');

it('sends gpt-6-luna for a retired model from the shutdown instant', function (string $model) {
    [$provider, $client] = openAiProvider(
        model: $model,
        responses: [openAiChat()],
        at: '2027-01-14 00:00:00',
    );

    expect((new Spam($provider, 'hello', []))->analyze()->success())->toBeTrue();

    $sent = openAiSent($client);

    expect($sent)->toHaveCount(1)
        ->and($sent[0]['model'])->toBe('gpt-6-luna')
        ->and($sent[0]['reasoning_effort'])->toBe('none');
})->with('retired openai models');

it('compares the shutdown against UTC rather than the clock timezone', function (string $at, string $timezone, string $expected) {
    [$provider, $client] = openAiProvider(
        model: 'gpt-4o-mini',
        responses: [openAiChat()],
        at: $at,
        timezone: $timezone,
    );

    (new Spam($provider, 'hello', []))->analyze();

    expect(openAiSent($client)[0]['model'])->toBe($expected);
})->with([
    'los angeles evening is already january 14 utc' => ['2027-01-13 16:30:00', 'America/Los_Angeles', 'gpt-6-luna'],
    'tokyo morning is still january 13 utc' => ['2027-01-14 08:00:00', 'Asia/Tokyo', 'gpt-4o-mini'],
]);

it('retries a retired model once when the api says it does not exist', function () {
    [$provider, $client] = openAiProvider(
        model: 'gpt-4o-mini',
        responses: [
            openAiModelNotFound('gpt-4o-mini'),
            openAiChat('{"spam":true,"confidence":0.91}'),
        ],
        at: '2026-12-01 00:00:00',
    );

    $response = (new Spam($provider, 'buy now', []))->analyze();

    expect($response->success())->toBeTrue()
        ->and($response->spam())->toBeTrue()
        ->and($response->confidence())->toBe(0.91);

    $sent = openAiSent($client);

    expect($sent)->toHaveCount(2)
        ->and($sent[0]['model'])->toBe('gpt-4o-mini')
        ->and($sent[0])->not->toHaveKey('reasoning_effort')
        ->and($sent[1]['model'])->toBe('gpt-6-luna')
        ->and($sent[1]['reasoning_effort'])->toBe('none');
});

it('retries when the missing-model response has no error code', function () {
    [$provider, $client] = openAiProvider(
        model: 'gpt-4.1',
        responses: [
            openAiError('The model `gpt-4.1` does not exist or you do not have access to it.', null, 404),
            openAiChat(),
        ],
        at: '2026-11-01 00:00:00',
        throwExceptions: false,
    );

    expect((new Spam($provider, 'hello', []))->analyze()->success())->toBeTrue();

    expect(openAiSent($client))->toHaveCount(2)
        ->and(openAiSent($client)[1]['model'])->toBe('gpt-6-luna');
});

it('does not retry a retired model for other api errors', function () {
    [$provider, $client] = openAiProvider(
        model: 'gpt-4o',
        responses: [
            openAiError('Rate limit reached for model gpt-4o', 'rate_limit_exceeded', 429),
        ],
        at: '2026-10-15 00:00:00',
        throwExceptions: false,
    );

    $response = (new Spam($provider, 'hello', []))->analyze();

    expect($response->success())->toBeFalse()
        ->and($response->error()['message'])->toBe('Rate limit reached for model gpt-4o');

    expect(openAiSent($client))->toHaveCount(1)
        ->and(openAiSent($client)[0]['model'])->toBe('gpt-4o');
});

it('does not treat an unrelated not found error as a missing model', function () {
    [$provider, $client] = openAiProvider(
        model: 'gpt-4o-mini',
        responses: [
            openAiError('Not found', null, 404),
        ],
        at: '2026-10-15 00:00:00',
    );

    expect(fn () => (new Spam($provider, 'hello', []))->analyze())
        ->toThrow(ErrorException::class, 'Not found');

    expect(openAiSent($client))->toHaveCount(1);
});

it('retries a missing retired model only once', function () {
    [$provider, $client] = openAiProvider(
        model: 'gpt-4.1-mini',
        responses: [
            openAiModelNotFound('gpt-4.1-mini'),
            openAiModelNotFound('gpt-6-luna'),
        ],
        at: '2026-10-15 00:00:00',
        throwExceptions: false,
    );

    $response = (new Spam($provider, 'hello', []))->analyze();

    expect($response->success())->toBeFalse()
        ->and($response->error()['message'])->toContain('gpt-6-luna');

    $sent = openAiSent($client);

    expect($sent)->toHaveCount(2)
        ->and($sent[0]['model'])->toBe('gpt-4.1-mini')
        ->and($sent[1]['model'])->toBe('gpt-6-luna');
});

it('throws the replacement error when exceptions are enabled and the retry fails', function () {
    [$provider, $client] = openAiProvider(
        model: 'gpt-4o-2024-11-20',
        responses: [
            openAiModelNotFound('gpt-4o-2024-11-20'),
            openAiModelNotFound('gpt-6-luna'),
        ],
        at: '2026-10-15 00:00:00',
    );

    expect(fn () => (new Spam($provider, 'hello', []))->analyze())
        ->toThrow(ErrorException::class, 'The model `gpt-6-luna` does not exist');

    expect(openAiSent($client))->toHaveCount(2);
});

it('does not alias or retry models that are not in the retirement map', function (string $model) {
    [$provider, $client] = openAiProvider(
        model: $model,
        responses: [openAiModelNotFound($model)],
        at: '2027-06-01 00:00:00',
        throwExceptions: false,
    );

    $response = (new Spam($provider, 'hello', []))->analyze();

    expect($response->success())->toBeFalse();

    $sent = openAiSent($client);

    expect($sent)->toHaveCount(1)
        ->and($sent[0]['model'])->toBe($model)
        ->and($sent[0])->not->toHaveKey('reasoning_effort');
})->with('unrelated openai models');

it('leaves a current non-luna model request unchanged', function () {
    [$provider, $client] = openAiProvider(
        model: 'gpt-4-turbo',
        responses: [openAiChat()],
        at: '2027-02-01 00:00:00',
        temperature: 0.2,
    );

    expect((new Spam($provider, 'hello', []))->analyze()->success())->toBeTrue();

    $sent = openAiSent($client);

    expect($sent)->toHaveCount(1)
        ->and($sent[0]['model'])->toBe('gpt-4-turbo')
        ->and($sent[0]['temperature'])->toBe(0.2)
        ->and($sent[0])->not->toHaveKey('reasoning_effort')
        ->and($sent[0]['response_format'])->toBe(['type' => 'json_object']);
});

it('does not retry gpt-6-luna when that model itself is missing', function () {
    [$provider, $client] = openAiProvider(
        model: 'gpt-6-luna',
        responses: [openAiModelNotFound('gpt-6-luna')],
        at: '2027-02-01 00:00:00',
    );

    expect(fn () => (new Spam($provider, 'hello', []))->analyze())
        ->toThrow(ErrorException::class, 'gpt-6-luna');

    expect(openAiSent($client))->toHaveCount(1);
});

dataset('retired openai models', [
    'gpt-4o',
    'gpt-4o-2024-08-06',
    'gpt-4o-2024-11-20',
    'gpt-4o-mini',
    'gpt-4o-mini-2024-07-18',
    'gpt-4.1',
    'gpt-4.1-2025-04-14',
    'gpt-4.1-mini',
    'gpt-4.1-mini-2025-04-14',
]);

dataset('unrelated openai models', [
    'gpt-4-turbo',
    'gpt-4.1-nano',
    'gpt-4o-2024-05-13',
    'gpt-4o-mini-tts-2025-03-20',
    'ft:gpt-4o-mini-2024-07-18:acme:spam:abc123',
]);
