<?php

namespace Contextr\Checks;

class Spam extends Check
{
    public string $subject = 'spam';

    public string $baseInstruction = 'Classify it as spam or not. Flag any promotional intent, off-topic content, or links unless clearly relevant to the context. Be strict—err on the side of flagging.';

    public string $responseInstruction = 'Return JSON object with: \"spam\" (boolean), \"confidence\" (float 0.00-1.00).';
}
