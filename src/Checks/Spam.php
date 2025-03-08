<?php

namespace Contextr\Checks;

use Contextr\Response\Types\BoolType;

class Spam extends Check
{
    public string $subject = 'spam';

    public string $baseInstruction = 'Classify it as spam or not. Flag if you suspect promotional intent, off-topic content, or links unless relevant to the context.';

    protected function finishResponseMap(): void
    {
        $this->responseMap = array_merge([new BoolType(name: 'spam', default: false)], $this->responseMap);
    }
}
