<?php

namespace Contextr\Response;

class Response
{
    public ?array $decodedData;

    public bool $success;

    public ?array $error;

    public function __construct(
        public ?string $data,
        public mixed $sourceResponse,
        bool $success = true,
        ?array $error = null,
        protected ?array $responseMap = null
    ) {
        $this->success = $success;
        $this->error = $error;
        $this->decodedData = ($data && $success) ? json_decode($data, true) : null;

        if ($success && $this->decodedData) {
            $this->decodedData = $this->normalizeResponse($this->decodedData);
        } elseif ($success && $this->decodedData === null && json_last_error() !== JSON_ERROR_NONE) {
            $this->success = false;

            $this->error = ['message' => 'Failed to decode JSON', 'code' => json_last_error()];
        }
    }

    protected function normalizeResponse(array $data): array
    {
        if (! $this->responseMap) {
            return $data;
        }

        $normalized = [];

        foreach ($this->responseMap as $type) {
            $normalized[$type->name] = $type->normalize($data[$type->name] ?? $type->default);
        }

        return $normalized;
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
