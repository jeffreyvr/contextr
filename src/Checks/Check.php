<?php

namespace Contextr\Checks;

use Contextr\Providers\Provider;

abstract class Check
{
    public string $subject;

    public string $responseInstruction;

    public string $baseInstruction;

    public bool $reason = false;

    public ?string $prompt;

    public function __construct(public Provider $provider, public string $text, public array $context)
    {
        //
    }

    public function buildPrompt()
    {
        $context = $this->contextString();

        $this->prompt = "Analyze for {$this->subject}}: \"{$this->text}\". {$context} {$this->baseInstruction} {$this->responseInstruction}";

        return $this;
    }

    public function execute()
    {
        return $this->provider->analyze($this->prompt);
    }

    protected function contextString(): string
    {
        if (empty($this->context)) {
            return '';
        }

        $parts = array_map(fn ($key, $value) => ucfirst($key).': '.$value, array_keys($this->context), $this->context);

        return 'Context: '.implode(', ', $parts).'. ';
    }

    public function when($condition, $callback)
    {
        if ($condition) {
            $callback($this);
        }

        return $this;
    }

    public function withReason($language = null)
    {
        $this->reason = true;

        if ($language) {
            $this->responseInstruction = $this->responseInstruction.' Include a very short \"reason\" (string) in '.$language.'.';
        } else {
            $this->responseInstruction = $this->responseInstruction.' Include a very short \"reason\" (string).';
        }

        return $this;
    }
}
