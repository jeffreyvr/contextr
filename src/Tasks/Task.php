<?php

namespace Contextr\Tasks;

abstract class Task
{
    public array $context;

    public string $responseInstruction;

    public string $baseInstruction;

    public bool $reason = false;

    protected function contextString(): string
    {
        if (empty($this->context)) {
            return '';
        }

        $parts = array_map(fn ($key, $value) => ucfirst($key).': '.$value, array_keys($this->context), $this->context);

        return 'Context: '.implode(', ', $parts).'. ';
    }

    public function when($condition, $callback)
    {
        if ($condition) {
            $callback($this);
        }

        return $this;
    }

    public function withReason($language = null)
    {
        $this->reason = true;

        if ($language) {
            $this->responseInstruction = $this->responseInstruction.' Also include a short one sentence \"reason\" (string) in '.$language.'.';
        } else {
            $this->responseInstruction = $this->responseInstruction.' Also include a short one sentence \"reason\" (string).';
        }

        return $this;
    }
}
