<?php

namespace Contextr\Checks;

use Contextr\Response\Types\StringType;

class Sentiment extends Check
{
    public string $subject = 'sentiment';

    public string $baseInstruction = 'Determine the sentiment of the text.';

    public array $indicators = ['positive', 'negative', 'neutral'];

    protected function finishResponseMap(): void
    {
        $this->responseMap = array_merge([new StringType(name: 'sentiment', default: '', constraints: ['in' => $this->indicators])], $this->responseMap);
    }

    public function indicators(array $indicators, bool $merge = false)
    {
        if ($merge) {
            $this->indicators = array_merge($this->indicators, $indicators);
        } else {
            $this->indicators = $indicators;
        }

        return $this;
    }
}
