<?php

namespace Contextr\Tests\Support;

use Contextr\Providers\OpenAi;
use DateTimeImmutable;
use OpenAI\Contracts\ClientContract;

class ClockedOpenAi extends OpenAi
{
    public function __construct(
        public ClientContract $fakeClient,
        public DateTimeImmutable $clock,
        string $model = OpenAi::REPLACEMENT_MODEL,
        float $temperature = 0.1,
        bool $throwExceptions = true,
    ) {
        parent::__construct(
            apiKey: 'test-key',
            model: $model,
            temperature: $temperature,
            throwExceptions: $throwExceptions,
        );
    }

    protected function client(): ClientContract
    {
        return $this->fakeClient;
    }

    protected function now(): DateTimeImmutable
    {
        return $this->clock;
    }
}
