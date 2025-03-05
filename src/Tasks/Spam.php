<?php

namespace Contextr\Tasks;

use Contextr\Providers\Provider;

class Spam extends Task
{
    public string $baseInstruction = 'Classify it as spam or not. Flag any promotional intent, off-topic content, or links unless clearly relevant to the context. Be strict—err on the side of flagging.';

    public string $responseInstruction = 'Return JSON object with: \"spam\" (boolean), \"confidence\" (float 0.00-1.00).';

    public ?string $prompt;

    public function __construct(public Provider $provider, public string $text, public array $context)
    {
        //
    }

    public function buildPrompt()
    {
        $context = $this->contextString();

        $this->prompt = "Analyze for spam: \"{$this->text}\". {$context} {$this->baseInstruction} {$this->responseInstruction}";

        return $this;
    }

    public function execute()
    {
        return $this->provider->analyze($this->prompt);
    }
}
