<?php

namespace Contextr\Checks;

class Sentiment extends Check
{
    public string $subject = 'sentiment';

    public string $baseInstruction = 'Determine the sentiment (positive, negative, neutral) of the text.';

    public string $responseInstruction = 'Return JSON object with: \"sentiment\" (string), \"confidence\" (float 0.00-1.00).';
}
