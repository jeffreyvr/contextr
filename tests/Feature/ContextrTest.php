<?php

use Contextr\Contextr;
use Contextr\Response\Types\StringType;
use Contextr\Tests\Support\MockProvider;

beforeEach(function () {
    $this->provider = new MockProvider();
    $this->contextr = new Contextr($this->provider);
});

it('detects spam with promotional content', function () {
    $this->provider->mockResponses = [['spam' => true, 'confidence' => 0.95, 'reasoning' => 'Promotional link detected']];

    $response = $this->contextr->spam(
        text: 'Buy cheap viagra now!!! Click here: shady.link',
        context: ['topic' => 'Health Forum']
    )
    ->withReasoning()
    ->analyze();

    expect($response->spam())->toBeTrue();
    expect($response->confidence())->toBe(0.95);
    expect($response->reasoning())->toBe('Promotional link detected');
});

it('can have additional response types', function () {
    $this->provider->mockResponses = [['spam' => true, 'foo' => 'bar', 'confidence' => 0.95, 'reasoning' => 'Promotional link detected']];

    $response = $this->contextr->spam(
        text: 'Buy cheap viagra now!!! Click here: shady.link',
        context: ['topic' => 'Health Forum']
    )
    ->responseMap([
        new StringType(name: 'foo', default: 'baz')
    ])
    ->withReasoning()
    ->analyze();

    expect($response->spam())->toBeTrue();
    expect($response->confidence())->toBe(0.95);
    expect($response->foo())->toBe('bar');
    expect($response->reasoning())->toBe('Promotional link detected');
});

it('analyzes neutral sentiment', function () {
    $this->provider->mockResponses = [[
        'sentiment' => 'neutral',
        'confidence' => 0.75,
        'reasoning' => 'Mixed positive and negative elements'
    ]];

    $response = $this->contextr->sentiment(
        text: 'This game was fun but the lag was annoying.',
        context: ['product' => 'Video Game']
    )
    ->withReasoning()
    ->analyze();

    expect($response->sentiment())->toBe('neutral');
    expect($response->confidence())->toBe(0.75);
    expect($response->reasoning())->toBe('Mixed positive and negative elements');
});

it('flags moderation violations', function () {
    $this->provider->mockResponses = [[
        'violates' => true,
        'confidence' => 0.85,
        'violations' => ['profanity', 'civility'],
        'reasoning' => 'Contains insults and rude tone'
    ]];

    $check = $this->contextr->moderation(
        text: 'These idiots cant play worth a damn!',
        context: ['platform' => 'forum']
    )
        ->rules(['hate speech', 'profanity', 'civility'])
        ->withReasoning()
        ->withViolations();

    $response = $check->analyze();

    expect($check->prompt)
        ->toContain('Evaluate only these', 'hate speech', 'profanity', 'civility');

    expect($response->violates())->toBeTrue();
    expect($response->confidence())->toBe(0.85);
    expect($response->violations())->toBe(['profanity', 'civility']);
    expect($response->reasoning())->toBe('Contains insults and rude tone');
});

it('detects AI-generated text', function () {
    $this->provider->mockResponses = [[
        'ai' => true,
        'confidence' => 0.92,
        'reasoning' => 'Text is overly formal for a casual forum'
    ]];

    $response = $this->contextr->ai(
        text: 'The strategic intricacies of modern football necessitate a comprehensive understanding of player positioning.',
        context: ['platform' => 'football fan forum']
    )
    ->withReasoning()
    ->analyze();

    expect($response->ai())->toBeTrue();
    expect($response->confidence())->toBe(0.92);
    expect($response->reasoning())->toBe('Text is overly formal for a casual forum');
});

it('returns null for missing response fields', function () {
    $this->provider->mockResponses = [[
        'ai' => true,
        'confidence' => 0.92
    ]];

    $response = $this->contextr->ai(
        text: 'Some text',
        context: []
    )
    ->withReasoning()
    ->analyze();

    expect($response->ai())->toBeTrue();
    expect($response->confidence())->toBe(0.92);
    expect($response->reasoning())->toBeEmpty();
});

it('handles empty response gracefully', function () {
    $this->provider->mockResponses = [[]];

    $response = $this->contextr->spam(
        text: 'Hello world',
        context: []
    )
    ->analyze();

    expect($response->spam())->toBeNull();
    expect($response->confidence())->toBeNull();
});
