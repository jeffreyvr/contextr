<?php

namespace Contextr\Checks;

use Contextr\Response\Types\ArrayType;
use Contextr\Response\Types\BoolType;
use Exception;

class Moderation extends Check
{
    public string $subject = 'moderation';

    public string $baseInstruction = 'Check for inappropriate content.';

    public string $rules = 'profanity, hate speech, violence';

    public bool $violations = false;

    protected function finishResponseMap(): void
    {
        $this->responseMap = array_merge([new BoolType(name: 'violates', default: '')], $this->responseMap);
    }

    public function rules(string|array $rules, bool $merge = false): self
    {
        if ($merge && is_string($this->rules)) {
            $existingRules = explode(', ', $this->rules);
            $rules = array_merge($existingRules, (array) $rules);
        }

        $this->rules = implode(', ', (array) $rules);

        $ruleCount = count($rules);
        $this->withAdditionalInstruction('Evaluate only these '.$ruleCount.' rules: ' . $this->rules);

        return $this;
    }

    public function withViolations()
    {
        if (! $this->rules) {
            throw new Exception('Violations can only be returned if rules are set.');
        }

        $this->responseMap[] = new ArrayType(name: 'violations', default: [], constraints: ['in' => explode(', ', $this->rules)]);

        return $this;
    }
}
