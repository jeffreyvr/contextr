<?php

namespace Contextr\Response\Types;

class BoolType extends ResponseType
{
    public function normalize(mixed $value): bool
    {
        return (bool) $value;
    }

    public function toInstruction(): string
    {
        return 'boolean';
    }
}
