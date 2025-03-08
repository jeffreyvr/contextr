<div align="center">
    <picture>
    <source
    srcset="./art/logo-light.svg"
    media="(prefers-color-scheme: dark)"
    height="50"
    />
    <img
    src="./art/logo-dark.svg"
    alt="contextr logo"
    height="50"
    />
    </picture>

  <h3 align="center">contextr</h3>
</div>

Use AI as your intelligent assistant to analyze text for spam, sentiment, and moderation, delivering precise insights with confidence scores and detailed reasoning.

## Usage

### Installation

```bash
composer require contextr-dev/contextr
```

### Setup

First, you need to setup your instance of Contextr.

You can use [OpenAI](http://platform.openai.com/) or [Grok](https://x.ai/api) as AI providers.

```php
$contextr = new Contextr\Contextr(provider: new Contextr\Providers\OpenAi(apiKey: 'API_KEY'));

// Or for Grok:

$contextr = new Contextr\Contextr(provider: new Contextr\Providers\Grok(apiKey: 'API_KEY'));
```

### Examples

##### Spam

```php
$check = $contextr->spam()
    ->text('Buy cheap viagra now!!! Click here: shady.link')
    ->context([
        'topic' => 'Health Forum Discussion',
        'user_history' => 'First time poster'
    ])
    ->withReasoning()
    ->analyze();

$check->data();         // full result array
$check->spam();         // true (boolean)
$check->confidence();   // 0.95 (float)
$check->reasoning();    // "Contains promotional content and suspicious link"
```

##### Sentiment

Determine if the sentiment is `positive`, `negative` or `neutral`.

```php
$check = $contextr->sentiment()
    ->text('This blu ray was great, too bad it did not include Project A.')
    ->context([
        'product' => 'Jackie Chan Collection Vol 1983',
        'category' => 'Blu ray'
    ])
    ->withReasoning(language: 'Cantonese')
    ->analyze();

$check->data();         // full result array
$check->sentiment();    // neutral (string)
$check->confidence();   // 0.75 (float)
$check->reasoning();    // "Expresses enjoyment of the blu ray but also disappointment about the absence of a specific content." (string)
```

##### Moderation

Determine if user comments violate moderation rules.

```php
$check = $contextr->moderation()
    ->text('These morons don’t even know how to kick a ball properly!')
    ->rules(['hate speech', 'profanity', 'civility'])
    ->context([
        'platform' => 'sports news website',
        'topic' => 'Premier League match review'
    ])
    ->withReasoning()
    ->withViolations()
    ->analyze();

$check->data();         // full result array
$check->violates();     // true (boolean)
$check->confidence();   // 0.75 (float)
$check->violations();   // ['profanity', 'civility'] (array)
$check->reasoning();    // "Contains insulting language and lacks respectful tone"
```

##### AI

Determine the likelyhood that a text is AI-generated.

```php
$check = $contextr->ai()
    ->text('The strategic intricacies of modern football necessitate a comprehensive understanding of player positioning, tactical adaptability, and cohesive team synergy to achieve superior performance outcomes.')
    ->context([
        'platform' => 'football fan forum',
        'topic' => 'Post-match discussion: Manchester United vs. Liverpool',
        'user_history' => 'New account, posted 5 similar analyses in 24 hours'
    ])
    ->withReasoning()
    >analyze();

$check->data();         // full result array
$check->ai();           // true (boolean)
$check->confidence();   // 0.92 (float)
$check->reasoning();    // "Overly polished language and generic analysis typical of AI-generated text, especially given the user's pattern of similar posts."
```

## Contributors
* [Jeffrey van Rossum](https://github.com/jeffreyvr)
* [All contributors](https://github.com/contextr-dev/contextr/graphs/contributors)

## License
MIT. Please see the [License File](/LICENSE) for more information.
