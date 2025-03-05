<?php

namespace Contextr\Tasks;

use Contextr\Providers\Provider;

class Moderation extends Task
{
    public string $baseInstruction = 'Check for inappropriate content.';

    public string $responseInstruction = 'Return JSON object with: \"violation\" (boolean), \"confidence\" (float 0.00-1.00).';

    public ?string $prompt;

    public ?string $rules;

    public function __construct(public Provider $provider, public string $text, public array $context)
    {
        //
    }

    public function rules(string|array $rules)
    {
        $this->rules = implode(', ', $rules);

        $this->baseInstruction = $this->baseInstruction.'Look for: '.$this->rules;

        return $this;
    }

    public function withViolations()
    {
        if ($this->rules) {
            $this->responseInstruction = $this->responseInstruction.' Also include the violations in \"violations\" (array using values '.$this->rules.').';
        }

        return $this;
    }

    public function buildPrompt()
    {
        $context = $this->contextString();

        $this->prompt = "Analyze for sentiment: \"{$this->text}\". {$context} {$this->baseInstruction} {$this->responseInstruction}";

        return $this;
    }

    public function execute()
    {
        return $this->provider->analyze($this->prompt);
    }
}
