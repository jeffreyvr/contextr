<?php

namespace Contextr\Response\Types;

abstract class ResponseType
{
    public function __construct(
        public string $name,
        public mixed $default,
        public ?array $constraints = null
    ) {
        //
    }

    abstract public function normalize(mixed $value): mixed;

    abstract public function toInstruction(): string;
}
