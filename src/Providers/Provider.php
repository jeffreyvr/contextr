<?php

namespace Contextr\Providers;

use Contextr\Response;

interface Provider
{
    public function analyze(string $prompt): Response;
}
