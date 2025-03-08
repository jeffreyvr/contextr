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
    public function init(string $check, ?string $text, ?array $context): Check
    {
        return new $check($this->provider, $text, $context);
    }

    public function spam(?string $text = null, ?array $context = null): Spam
    {
        return $this->init(Spam::class, $text, $context);
    }

    public function sentiment(?string $text = null, ?array $context = null): Sentiment
    {
        return $this->init(Sentiment::class, $text, $context);
    }

    public function moderation(?string $text = null, ?array $context = null): Moderation
    {
        return $this->init(Moderation::class, $text, $context);
    }

    public function ai(?string $text = null, ?array $context = null): Ai
    {
        return $this->init(Ai::class, $text, $context);
    }
}
