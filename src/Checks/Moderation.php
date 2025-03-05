<?php

namespace Contextr\Checks;

class Moderation extends Check
{
    public string $subject = 'moderation';

    public string $baseInstruction = 'Check for inappropriate content.';

    public string $responseInstruction = 'Return JSON object with: \"violation\" (boolean), \"confidence\" (float 0.00-1.00).';

    public ?string $rules;

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
}
