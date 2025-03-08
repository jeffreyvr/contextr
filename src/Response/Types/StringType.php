<?php

namespace Contextr\Response\Types;

class StringType extends ResponseType
{
    public function normalize(mixed $value): string
    {
        $value = (string) $value;

        return $value;
    }

    public function toInstruction(): string
    {
        $instruction = 'string';
        $parts = [];

        if (isset($this->constraints['language'])) {
            $parts[] = "in {$this->constraints['language']} language";
        }

        if (isset($this->constraints['length'])) {
            $parts[] = "length {$this->constraints['length']}";
        }

        if (isset($this->constraints['in'])) {
            $parts[] = 'accepted values: '.implode(', ', $this->constraints['in']);
        }

        if (! empty($parts)) {
            $instruction .= ' ('.implode(', ', $parts).')';
        }

        return $instruction;
    }
}
