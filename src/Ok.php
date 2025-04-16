<?php

namespace Hopr\Result;

/**
 * Class Ok
 *
 * Represents a successful result with a value.
 *
 * @template T The type of the success value
 * @template E The type of the error value (not used in Ok, but needed for interface compatibility)
 * @implements Result<T, E>
 */
class Ok implements Result
{
    /**
     * @var T The success value
     */
    private $value;

    /**
     * Ok constructor
     *
     * @param T $value The success value
     */
    private function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Creates a new Ok instance
     *
     * @template U
     * @param U $value The success value
     * @return Ok<U, mixed> A new Ok instance
     */
    public static function of($value): Ok
    {
        return new self($value);
    }

    /**
     * {@inheritdoc}
     *
     * @template U
     * @param callable(T): U $fn
     * @return Ok<U, E>
     */
    public function map(callable $fn): Result
    {
        return new self($fn($this->value));
    }

    /**
     * {@inheritdoc}
     *
     * @template U
     * @param callable(T): Result<U, E> $fn
     * @return Result<U, E>
     */
    public function bind(callable $fn): Result
    {
        return $fn($this->value);
    }

    /**
     * {@inheritdoc}
     *
     * @template F
     * @param callable(E): F $fn
     * @return Ok<T, F>
     */
    public function mapErr(callable $fn): Result
    {
        // No error to map, so return unchanged
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isOk(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isErr(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @return T
     */
    public function unwrap()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     *
     * @template D
     * @param D $default
     * @return T The success value (default is ignored)
     */
    public function unwrapOr($default)
    {
        return $this->value;
    }

    /**
     * Returns the string representation of the Ok value
     *
     * @return string
     */
    public function __toString(): string
    {
        return sprintf('Ok(%s)', $this->value);
    }
}
