<?php

declare(strict_types=1);

namespace Hopr\Result;

/**
 * Interface Result
 *
 * Represents the result of an operation that might fail.
 * It can be either an Ok value or an Error value.
 *
 * @template T The type of the success value
 * @template E The type of the error value
 */
interface Result
{
    /**
     * Maps the success value using the given function
     *
     * @template U
     * @param callable(T): U $fn The mapping function to apply to the success value
     * @return Result<U, E> A new Result with the mapped value
     *
     * @example
     * ```php
     * $result = Ok::of(5);
     * $mapped = $result->map(fn($x) => $x * 2); // Ok(10)
     * ```
     */
    public function map(callable $fn): Result;

    /**
     * Maps the success value using a function that returns a Result
     *
     * @template U
     * @param callable(T): Result<U, E> $fn The binding function
     * @return Result<U, E> The result of applying the function
     *
     * @example
     * ```php
     * $parseNumber = function(string $input): Result {
     *     if (is_numeric($input)) {
     *         return Ok::of((int)$input);
     *     }
     *     return Error::of("Not a number");
     * };
     *
     * $result = Ok::of("42");
     * $bound = $result->bind($parseNumber); // Ok(42)
     *
     * $result = Ok::of("not a number");
     * $bound = $result->bind($parseNumber); // Error("Not a number")
     * ```
     */
    public function bind(callable $fn): Result;

    /**
     * Maps the error value using the given function
     *
     * @template F
     * @param callable(E): F $fn The mapping function to apply to the error value
     * @return Result<T, F> A new Result with the mapped error
     *
     * @example
     * ```php
     * $result = Error::of("not found");
     * $mapped = $result->mapErr(fn($err) => "Error: $err"); // Error("Error: not found")
     * ```
     */
    public function mapErr(callable $fn): Result;

    /**
     * Checks if the Result is an Ok value
     *
     * @return bool True if the Result is Ok, false otherwise
     *
     * @example
     * ```php
     * $result = Ok::of(42);
     * if ($result->isOk()) {
     *     echo "Operation succeeded!";
     * }
     * ```
     */
    public function isOk(): bool;

    /**
     * Checks if the Result is an Error value
     *
     * @return bool True if the Result is Error, false otherwise
     *
     * @example
     * ```php
     * $result = Error::of("something went wrong");
     * if ($result->isErr()) {
     *     echo "Operation failed!";
     * }
     * ```
     */
    public function isErr(): bool;

    /**
     * Returns the success value or throws an exception if the Result is an Error
     *
     * @return T The success value
     * @throws \RuntimeException if the Result is an Error
     *
     * @example
     * ```php
     * $result = Ok::of(42);
     * $value = $result->unwrap(); // 42
     *
     * $result = Error::of("bad");
     * $value = $result->unwrap(); // Throws RuntimeException with message "bad"
     * ```
     */
    public function unwrap();

    /**
     * Returns the success value or a default value if the Result is an Error
     *
     * @template D
     * @param D $default The default value
     * @return T|D The success value or the default value
     *
     * @example
     * ```php
     * $result = Ok::of(42);
     * $value = $result->unwrapOr(0); // 42
     *
     * $result = Error::of("bad");
     * $value = $result->unwrapOr(0); // 0
     * ```
     */
    public function unwrapOr($default);
}
