<?php

namespace Contextr;

use Contextr\Providers\Provider;
use Contextr\Tasks\Moderation;
use Contextr\Tasks\Sentiment;
use Contextr\Tasks\Spam;

class Contextr
{
    public function __construct(public Provider $provider) {}

    public function spam(string $text, array $context, bool $withReason = false, ?string $language = null): Response
    {
        return (new Spam(provider: $this->provider, text: $text, context: $context))
            ->when($withReason, function (Spam $task) use ($language) {
                $task->withReason($language);
            })
            ->buildPrompt()
            ->execute();
    }

    public function sentiment(string $text, array $context, bool $withReason = false, ?string $language = null): Response
    {
        return (new Sentiment(provider: $this->provider, text: $text, context: $context))
            ->when($withReason, function (Sentiment $task) use ($language) {
                $task->withReason($language);
            })
            ->buildPrompt()
            ->execute();
    }

    public function moderation(string $text, array $context, bool $withReason = false, ?string $language = null, ?array $rules = null): Response
    {
        return (new Moderation(provider: $this->provider, text: $text, context: $context))
            ->when($withReason, function ($task) use ($language) {
                $task->withReason($language);
            })
            ->when($rules, function (Moderation $task) use ($rules) {
                $task->rules($rules)
                    ->withViolations();
            })
            ->buildPrompt()
            ->execute();
    }
}
