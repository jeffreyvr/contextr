<?php

namespace Contextr\Providers;

use Contextr\Checks\Check;
use Contextr\Response\Response;

interface Provider
{
    public function analyze(Check $check): Response;
}
