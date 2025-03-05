<?php

use Contextr\Contextr;
use Contextr\Tests\Support\MockProvider;

beforeEach(function () {
    $this->provider = new MockProvider();
    $this->contextr = new Contextr($this->provider);
});

it('detects spam with promotional content', function () {
    $this->provider->mockResponses = [['spam' => true, 'confidence' => 0.95, 'reason' => 'Promotional link detected']];

    $response = $this->contextr->spam(
        text: 'Buy cheap viagra now!!! Click here: shady.link',
        context: ['topic' => 'Health Forum'],
        withReason: true
    );

    expect($response->spam())->toBeTrue();
    expect($response->confidence())->toBe(0.95);
    expect($response->reason())->toBe('Promotional link detected');
});

it('analyzes neutral sentiment', function () {
    $this->provider->mockResponses = [[
        'sentiment' => 'neutral',
        'confidence' => 0.75,
        'reason' => 'Mixed positive and negative elements'
    ]];

    $response = $this->contextr->sentiment(
        text: 'This game was fun but the lag was annoying.',
        context: ['product' => 'Video Game'],
        withReason: true
    );

    expect($response->sentiment())->toBe('neutral');
    expect($response->confidence())->toBe(0.75);
    expect($response->reason())->toBe('Mixed positive and negative elements');
});

it('flags moderation violations', function () {
    $this->provider->mockResponses = [[
        'violates' => true,
        'confidence' => 0.85,
        'violations' => ['profanity', 'civility'],
        'reason' => 'Contains insults and rude tone'
    ]];

    $response = $this->contextr->moderation(
        text: 'These idiots can’t play worth a damn!',
        context: ['platform' => 'forum'],
        withReason: true,
        rules: ['hate speech', 'profanity', 'civility']
    );

    expect($response->violates())->toBeTrue();
    expect($response->confidence())->toBe(0.85);
    expect($response->violations())->toBe(['profanity', 'civility']);
    expect($response->reason())->toBe('Contains insults and rude tone');
});

it('detects AI-generated text', function () {
    $this->provider->mockResponses = [[
        'ai_generated' => true,
        'confidence' => 0.92,
        'reason' => 'Text is overly formal for a casual forum'
    ]];

    $response = $this->contextr->ai(
        text: 'The strategic intricacies of modern football necessitate a comprehensive understanding of player positioning.',
        context: ['platform' => 'football fan forum'],
        withReason: true
    );

    expect($response->ai_generated())->toBeTrue();
    expect($response->confidence())->toBe(0.92);
    expect($response->reason())->toBe('Text is overly formal for a casual forum');
});

it('returns null for missing response fields', function () {
    $this->provider->mockResponses = [[
        'ai' => true,
        'confidence' => 0.92
    ]];

    $response = $this->contextr->ai(
        text: 'Some text',
        context: [],
        withReason: true
    );

    expect($response->ai())->toBeTrue();
    expect($response->confidence())->toBe(0.92);
    expect($response->reason())->toBeNull();
});

it('handles empty response gracefully', function () {
    $this->provider->mockResponses = [[]];

    $response = $this->contextr->spam(
        text: 'Hello world',
        context: []
    );

    expect($response->spam())->toBeNull();
    expect($response->confidence())->toBeNull();
});
