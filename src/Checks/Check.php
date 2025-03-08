<?php

namespace Contextr\Checks;

use Contextr\Providers\Provider;
use Contextr\Response\Response;
use Contextr\Response\Types\FloatType;
use Contextr\Response\Types\StringType;

abstract class Check
{
    public string $subject;

    public string $baseInstruction;

    public ?string $prompt;

    public array $responseMap;

    public function __construct(public Provider $provider, public ?string $text, public ?array $context)
    {
        $this->initResponseMap();
    }

    protected function initResponseMap(): void
    {
        $this->responseMap = [new FloatType(name: 'confidence', default: 0.0, constraints: ['range' => [number_format(0, 2), number_format(1, 2)]])];
    }

    abstract protected function finishResponseMap(): void;

    public function text(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function context(array $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function buildPrompt(): self
    {
        $context = $this->contextString();
        $responseInstruction = $this->responseInstructionString();

        $this->prompt = "Analyze for {$this->subject}: \"{$this->text}\". {$context} {$this->baseInstruction} {$responseInstruction}";

        return $this;
    }

    public function analyze(): Response
    {
        $this->buildPrompt();

        return $this->provider->analyze($this);
    }

    protected function contextString(): string
    {
        if (empty($this->context)) {
            return '';
        }

        $parts = array_map(fn ($key, $value) => ucfirst($key).': '.$value, array_keys($this->context), $this->context);

        return 'Context: '.implode(', ', $parts).'. ';
    }

    protected function responseInstructionString(): string
    {
        $this->finishResponseMap();

        $map = $this->responseMap;

        $parts = [];

        foreach ($map as $type) {
            $parts[] = "\"{$type->name}\" ({$type->toInstruction()})";
        }

        return 'Return a JSON object with: '.implode(', ', $parts).'.';
    }

    public function when(mixed $condition, callable $callback)
    {
        if ($condition) {
            $callback($this);
        }

        return $this;
    }

    public function withReasoning(?string $language = null)
    {
        $this->responseMap[] = new StringType(name: 'reasoning', default: '', constraints: ['length' => 'one short sentence', 'language' => $language]);

        return $this;
    }

    public function responseMap(array $map, bool $merge = true)
    {
        if (! $merge) {
            $this->responseMap = $map;
        }

        $this->responseMap = array_merge($this->responseMap, $map);

        return $this;
    }
}
