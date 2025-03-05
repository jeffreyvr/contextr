<?php

namespace Contextr;

use Contextr\Checks\Ai;
use Contextr\Checks\Check;
use Contextr\Checks\Moderation;
use Contextr\Checks\Sentiment;
use Contextr\Checks\Spam;
use Contextr\Providers\Provider;

class Contextr
{
    public function __construct(public Provider $provider) {}

    /**
     * @param  class-string<Check>  $check  The check class to instantiate
     */
    public function run(string $check, string $text, array $context, bool $withReason = false, ?string $language = null, ?array $extraParams = null): Response
    {
        $instance = new $check($this->provider, $text, $context);

        return $instance
            ->when($withReason, fn ($t) => $t->withReason($language))
            ->when($extraParams, fn ($t) => $t instanceof Moderation && isset($extraParams['rules']) ? $t->rules($extraParams['rules'])->withViolations() : $t)
            ->buildPrompt()
            ->execute();
    }

    public function spam(string $text, array $context, bool $withReason = false, ?string $language = null): Response
    {
        return $this->run(Spam::class, $text, $context, $withReason, $language);
    }

    public function sentiment(string $text, array $context, bool $withReason = false, ?string $language = null): Response
    {
        return $this->run(Sentiment::class, $text, $context, $withReason, $language);
    }

    public function moderation(string $text, array $context, bool $withReason = false, ?string $language = null, ?array $rules = null): Response
    {
        return $this->run(Moderation::class, $text, $context, $withReason, $language, $rules ? ['rules' => $rules] : null);
    }

    public function ai(string $text, array $context, bool $withReason = false, ?string $language = null): Response
    {
        return $this->run(Ai::class, $text, $context, $withReason, $language);
    }
}
