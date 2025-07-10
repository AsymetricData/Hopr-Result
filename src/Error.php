<?php

declare(strict_types=1);

namespace Hopr\Result;

/**
 * Class Error
 *
 * Represents a failed result with an error.
 *
 * @template T The type of the success value (not used in Error, but needed for interface compatibility)
 * @template E The type of the error value
 * @implements Result<T, E>
 */
class Error implements Result
{
    /**
     * @var E The error value
     */
    private $error;

    /**
     * Error constructor
     *
     * @param E $error The error value
     */
    private function __construct(mixed $error)
    {
        $this->error = $error;
    }

    /**
     * Creates a new Error instance
     *
     * @template F
     * @param F $error The error value
     * @return Error<mixed, F> A new Error instance
     */
    public static function of(mixed $error): Error
    {
        return new self($error);
    }

    /**
     * {@inheritdoc}
     *
     * @template U
     * @param callable(T): U $fn
     * @return Error<U, E>
     */
    #[\Override]
    public function map(callable $fn): Result
    {
        // No success value to map, so return unchanged
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @template U
     * @template F
     * @param callable(T): Result<U, F> $fn
     * @return Error<U, E>
     */
    #[\Override]
    public function bind(callable $fn): Result
    {
        // No success value to bind, so return unchanged
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @template F
     * @param callable(E): F $fn
     * @return Error<T, F>
     */
    #[\Override]
    public function mapErr(callable $fn): Result
    {
        return new self($fn($this->error));
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function isOk(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function isErr(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return T
     * @throws \RuntimeException
     */
    #[\Override]
    public function unwrap(): mixed
    {
        $message = is_string($this->error)
            ? $this->error
            : 'Error value encountered in unwrap(). You should check if the Result is ok with isOk() before calling unwrap().';
        throw new \RuntimeException($message);
    }

    /**
     * {@inheritdoc}
     *
     * @template D
     * @param D $default
     * @return D The default value
     */
    #[\Override]
    public function unwrapOr($default): mixed
    {
        return $default;
    }

    /**
     * Gets the error value
     *
     * @return E The error value
     */
    public function getError(): mixed
    {
        return $this->error;
    }

    /**
     * Returns the string representation of the Error value
     *
     * @return string
     */
    #[\Override]
    public function __toString(): string
    {
        return sprintf('Error(%s)', var_export($this->error, true));
    }

    /**
     * {@inheritdoc}
     *
     * @param string $field
     * @param callable(T, mixed ...): Result<mixed, E> $fn
     * @return Error<T, E>
     */
    #[\Override]
    public function use(string $field, callable $fn): Result
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @template U
     * @param callable(T, mixed ...): U $fn
     * @return Error<T, E>
     */
    #[\Override]
    public function mapWith(callable $fn): Result
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     * Executes the function with the error value for side effects
     *
     * @param callable(T): void $fn The function to execute, that doesn't modify the inner value
     * @return Result<T, E>
     */
    public function tap(callable $fn): Result
    {
        $fn($this->error);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function flatten(): Result
    {
        return ($this->value instanceof Error) ? $this->unwrap() : $this;
    }
}
