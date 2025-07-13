<?php

declare(strict_types=1);

namespace Hopr\Result;

/**
 * Creates a new Ok result
 *
 * @template T
 * @param callable(): T $value The success value
 * @return LazyOk<T, mixed> A new Ok instance
 */
function lazyOk(callable $value): LazyOk
{
    return LazyOk::of($value);
}

/**
 * Creates a new Ok result
 *
 * @template T
 * @param T $value The success value
 * @return Ok<T, mixed> A new Ok instance
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
 */
function err($error): Error
{
    return Error::of($error);
}

/**
 * Wraps a potentially throwing function in a Result
 *
 * @template T
 * @param callable(): T $fn The function to execute
 * @param (callable(\Throwable): Result<mixed, mixed>)|null $or Optional error handler
 * @return Result<T, \Throwable> The Result of the operation
 */
function tryTo(callable $fn, ?callable $or = null): Result
{
    try {
        return ok($fn());
    } catch (\Throwable $e) {
        return $or ? $or($e) : err($e);
    }
}

/**
 * Wraps a potentially throwing function in a Result
 *
 * @template T
 * @param callable(): T $fn The function to execute
 * @param (callable(\Throwable): Result<mixed, mixed>)|null $or Optional error handler
 * @return Result<T, \Throwable> The Result of the operation
 */
function lazyTryTo(callable $fn, null|callable $or = null): Result
{
    return lazyOk(function () use ($fn, $or) {
        try {
            return $fn();
        } catch (\Throwable $e) {
            return $or ? $or($e) : err($e);
        }
    });
}
