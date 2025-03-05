<?php

namespace Contextr\Tasks;

use Contextr\Providers\Provider;

class Sentiment extends Task
{
    public string $baseInstruction = 'Determine the sentiment (positive, negative, neutral) of the text.';

    public string $responseInstruction = 'Return JSON object with: \"sentiment\" (string), \"confidence\" (float 0.00-1.00).';

    public ?string $prompt;

    public function __construct(public Provider $provider, public string $text, public array $context)
    {
        //
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
