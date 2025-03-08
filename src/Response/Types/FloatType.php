<?php

namespace Contextr\Response\Types;

class FloatType extends ResponseType
{
    public function normalize(mixed $value): float
    {
        $value = (float) $value;

        if ($this->constraints['range'] ?? false) {
            [$min, $max] = $this->constraints['range'];

            return max($min, min($max, $value));
        }

        return $value;
    }

    public function toInstruction(): string
    {
        $instruction = 'float';

        if ($this->constraints['range'] ?? false) {
            [$min, $max] = $this->constraints['range'];

            $instruction .= " $min-$max";
        }

        return $instruction;
    }
}
