<?php

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
    private function __construct($error)
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
    public static function of($error): Error
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
    public function map(callable $fn): Result
    {
        // No success value to map, so return unchanged
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @template U
     * @param callable(T): Result<U, E> $fn
     * @return Error<U, E>
     */
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
    public function mapErr(callable $fn): Result
    {
        return new self($fn($this->error));
    }

    /**
     * {@inheritdoc}
     */
    public function isOk(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
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
    public function unwrap()
    {
        $message = is_string($this->error) ? $this->error : 'Error value encountered in unwrap(). You should check if the Result is ok with isOk() before calling unwrap().';
        throw new \RuntimeException($message);
    }

    /**
     * {@inheritdoc}
     *
     * @template D
     * @param D $default
     * @return D The default value
     */
    public function unwrapOr($default)
    {
        return $default;
    }

    /**
     * Gets the error value
     *
     * @return E The error value
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Returns the string representation of the Error value
     *
     * @return string
     */
    public function __toString(): string
    {
        return sprintf('Error(%s)', $this->error);
    }
}
