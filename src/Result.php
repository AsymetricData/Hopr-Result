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
     */
    public function map(callable $fn): Result;

    /**
     * Maps the success value using a function that returns a Result
     *
     * @template U
     * @template F
     * @param callable(T): Result<U, F> $fn The binding function
     * @return Result<U, E|F> The result of applying the function
     */
    public function bind(callable $fn): Result;

    /**
     * Maps the error value using the given function
     *
     * @template F
     * @param callable(E): F $fn The mapping function to apply to the error value
     * @return Result<T, F> A new Result with the mapped error
     */
    public function mapErr(callable $fn): Result;

    /**
     * Checks if the Result is an Ok value
     *
     * @return bool True if the Result is Ok, false otherwise
     */
    public function isOk(): bool;

    /**
     * Checks if the Result is an Error value
     *
     * @return bool True if the Result is Error, false otherwise
     */
    public function isErr(): bool;

    /**
     * Returns the success value or throws an exception if the Result is an Error
     *
     * @return T The success value
     * @throws \RuntimeException if the Result is an Error
     */
    public function unwrap();

    /**
     * Returns the success value or a default value if the Result is an Error
     *
     * @template D
     * @param D $default The default value
     * @return T|D The success value or the default value
     */
    public function unwrapOr($default);

    /**
     * Returns the error value.
     * On Ok, it do nothing.
     * @return E
     */
    public function getError(): mixed;

    /**
     * Accumulates contextual data or propagates an Error.
     *
     * @param string $field Key under which the produced value is stored.
     * @param callable(T, mixed ...): Result<mixed, E> $fn Receives the current success value plus any previously accumulated context values and returns a Result.
     * @return Result<T, E>
     */
    public function use(string $field, callable $fn): Result;

    /**
     * Redefines the main value using the current context and initial value.
     *
     * @template U
     * @param callable(T, mixed ...): U $fn
     * @return Result<U, E>
     */
    public function mapWith(callable $fn): Result;

    /**
     * Executes a function for side effects without modifying the Result
     *
     * @param callable(T): void $fn The function to execute on success value
     * @return Result<T, E>
     */
    public function tap(callable $fn): Result;
}
