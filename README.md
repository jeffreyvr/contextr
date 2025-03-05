# Contextr

Use AI as your intelligent assistant to analyze text for spam, sentiment, and moderation, delivering precise insights with confidence scores and detailed reasoning.

## Usage

### Installation

```bash
composer require contextr/contextr
```

### Setup

First, you need to setup your instance of Contextr.

You can use [OpenAI](http://platform.openai.com/) or [Grok](https://x.ai/api) for reasoning.

```php
$contextr = new Contextr\Contextr(provider: new Contextr\Providers\OpenAi(apiKey: 'API_KEY'));

// Or for Grok:

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
$check = $contextr->sentiment(
    text: 'This blu ray was great, too bad it did not include Project A.',
    withReason: true,
    context: [
        'product' => 'Jackie Chan Collection Vol 1983',
        'category' => 'Blu ray'
    ]
);

$check->sentiment();    // neutral (string)
$check->confidence();   // 0.75 (float)
$check->reason();       // "Expresses enjoyment of the blu ray but also disappointment about the absence of a specific content." (string)
```

##### Moderation

Determine if user comments violate moderation rules.

```php
$check = $contextr->moderation(
    text: 'These morons don’t even know how to kick a ball properly!',
    withReason: true,
    rules: ['hate speech', 'profanity', 'civility'],
    context: [
        'platform' => 'sports news website',
        'topic' => 'Premier League match review'
    ]
);

$check->violates();     // true (boolean)
$check->confidence();   // 0.75 (float)
$check->violations();   // ['profanity', 'civility'] (array)
$check->reason();       // "Contains insulting language and lacks respectful tone"
```

##### AI

```php
$check = $contextr->ai(
    text: 'The strategic intricacies of modern football necessitate a comprehensive understanding of player positioning, tactical adaptability, and cohesive team synergy to achieve superior performance outcomes.',
    withReason: true,
    context: [
        'platform' => 'football fan forum',
        'topic' => 'Post-match discussion: Manchester United vs. Liverpool',
        'user_history' => 'New account, posted 5 similar analyses in 24 hours'
    ]
);

$check->ai();           // true (boolean)
$check->confidence();   // 0.92 (float)
$check->reason();       // "Overly polished language and generic analysis typical of AI-generated text, especially given the user's pattern of similar posts."
```

## ✨ Hosted version ✨

Want a hosted version of Contextr? It’s in the works! Sign up for updates to be the first to know when it’s ready—no setup, just AI-powered analysis at your fingertips. [Join the waitlist](https://contextr.dev).

## Contributors
* [Jeffrey van Rossum](https://github.com/jeffreyvr)
* [All contributors](https://github.com/jeffreyvr/wp-settings/graphs/contributors)

## License
MIT. Please see the [License File](/LICENSE) for more information.
