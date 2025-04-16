<?php

declare(strict_types=1);

namespace Hopr\Result;

/**
 * Creates a new Ok result
 *
 * @template T
 * @param T $value The success value
 * @return Ok<T, mixed> A new Ok instance
 *
 * @example
 * ```php
 * $result = ok(42);
 * ```
 */
function ok($value): Ok
{
    return Ok::of($value);
}

/**
 * Creates a new Error result
 *
 * @template E
 * @param E $error The error value
 * @return Error<mixed, E> A new Error instance
 *
 * @example
 * ```php
 * $result = err("something went wrong");
 * ```
 */
function err($error): Error
{
    return Error::of($error);
}
