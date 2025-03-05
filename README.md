# Contextr

Use AI as your intelligent assistant to analyze text for spam, sentiment, and moderation, delivering precise insights with confidence scores and detailed reasoning.

## Usage

### Installation

```bash
composer require contextr/contextr
```

### Setup

First, you need to setup your instance of Contextr.

If you like to use OpenAI for  reasoning, you can use:

```php
$contextr = new Contextr\Contextr(provider: new Contextr\Providers\OpenAi(apiKey: 'API_KEY'));
```

For Grok, use:

```php
$contextr = new Contextr\Contextr(provider: new Contextr\Providers\Grok(apiKey: 'API_KEY'));
```

### Examples

##### Spam

```php
$check = $contextr->spam(
    text: 'Buy cheap viagra now!!! Click here: shady.link',
    withReason: true,
    context: [
        'topic' => 'Health Forum Discussion',
        'user_history' => 'First time poster'
    ]
);

$check->data();         // Full result array
$check->spam();         // true (boolean)
$check->confidence();   // 0.95 (float)
$check->reason();       // "Contains promotional content and suspicious link"
```

##### Sentiment

Determine if the sentiment is `positive`, `negative` or `neutral`.

```php
$result = $contextr->sentiment(
    text: 'This blu ray was great, too bad it did not include Project A.',
    withReason: true,
    context: [
        'product' => 'Jackie Chan Collection Vol 1983',
        'category' => 'Blu ray'
    ]
);

$sentiment = $result->sentiment(); // neutral (string)
$confidence = $result->confidence(); // 0.75 (float)
$reason = $result->reason(); // "Expresses enjoyment of the blu ray but also disappointment about the absence of a specific content." (string)
```

##### Moderation

Determine if user comments violate moderation rules.

```php
$result = $contextr->moderation(
    text: 'These morons don’t even know how to kick a ball properly!',
    withReason: true,
    rules: ['hate speech', 'profanity', 'civility'],
    context: [
        'platform' => 'sports news website',
        'topic' => 'Premier League match review'
    ]
);

$violates = $result->violates();     // true (boolean)
$confidence = $result->confidence(); // 0.75 (float)
$violations = $result->violations(); // ['profanity', 'civility'] (array)
$reason = $result->reason();         // "Contains insulting language and lacks respectful tone"
```

## Contribute

TODO

## Hosted version

TODO

## License

TODO
