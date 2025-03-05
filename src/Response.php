<?php

namespace Contextr;

class Response
{
    public ?array $decodedData;

    public bool $success;

    public ?array $error;

    public function __construct(
        public ?string $data,
        public mixed $sourceResponse,
        bool $success = true,
        ?array $error = null
    ) {
        $this->success = $success;
        $this->error = $error;

        $this->decodedData = ($data && $success) ? json_decode($data, true) : null;

        if ($success && $data && $this->decodedData === null && json_last_error() !== JSON_ERROR_NONE) {
            $this->success = false;
            $this->error = [
                'message' => 'Failed to decode JSON response: '.json_last_error_msg(),
                'code' => json_last_error(),
                'type' => 'JsonDecodeException',
            ];
        }
    }

    public function success(): bool
    {
        return $this->success;
    }

    public function data(): ?array
    {
        return $this->decodedData;
    }

    public function error(): ?array
    {
        return $this->error;
    }

    public function sourceResponse(): mixed
    {
        return $this->sourceResponse;
    }

    public function __call($name, $arguments)
    {
        if ($this->decodedData && array_key_exists($name, $this->decodedData)) {
            return $this->decodedData[$name];
        }

        return null;
    }
}
