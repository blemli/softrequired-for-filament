<?php

namespace Blemli\SoftRequired\Exceptions;

use RuntimeException;
use Throwable;

class IntrospectionFailedException extends RuntimeException
{
    public static function for(string $modelClass, Throwable $previous): self
    {
        if ($previous instanceof self) {
            return $previous;
        }

        return new self(
            "softrequired-for-filament could not introspect the Filament form for {$modelClass}: "
            . "{$previous->getMessage()} "
            . "Declare `protected array \$completable = ['attribute_a', 'attribute_b'];` on the model "
            . 'to bypass form introspection.',
            previous: $previous,
        );
    }
}
