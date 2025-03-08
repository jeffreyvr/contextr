<?php

namespace Contextr\Response\Types;

class ArrayType extends ResponseType
{
    public function normalize(mixed $value): array
    {
        if (isset($this->constraints['in']) && is_array($value)) {
            $value = array_intersect($value, $this->constraints['in']);
        }

        return is_array($value) ? $value : (array) $value;
    }

    public function toInstruction(): string
    {
        if ($this->constraints['in'] ?? false) {
            return 'array containing only the values: '.implode(', ', $this->constraints['in']);
        }

        return 'array';
    }
}
