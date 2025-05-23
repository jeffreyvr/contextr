<?php

namespace Contextr\Tests\Support;

use Contextr\Checks\Check;
use Contextr\Response\Response;
use Contextr\Providers\Provider;

class MockProvider implements Provider
{
    public array $mockResponses;

    public function __construct(array $mockResponses = [])
    {
        $this->mockResponses = $mockResponses;
    }

    public function analyze(Check $check): Response
    {
        $data = array_shift($this->mockResponses);

        if(! $data) {
            return new Response(data: null, sourceResponse: null, success: false, error: ['code' => '1', 'message' => 'Something went wrong']);
        }

        return new Response(responseMap: $check->responseMap, data: json_encode($data), sourceResponse: null, success: true);
    }
}
