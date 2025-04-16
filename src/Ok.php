<?php

declare(strict_types=1);

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
     * @var array<string, mixed> Contextual values accumulated via `use`
     */
    private array $context = [];

    /**
     * Ok constructor
     *
     * @param T $value The success value
     * @param array<string, mixed> $context Optional context
     */
    private function __construct(mixed $value, array $context = [])
    {
        $this->value = $value;
        $this->context = $context;
    }

    /**
     * Creates a new Ok instance
     *
     * @template U
     * @param U $value The success value
     * @return Ok<U, mixed> A new Ok instance
     */
    public static function of(mixed $value): Ok
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
    #[\Override]
    public function map(callable $fn): Result
    {
        return new self($fn($this->value), $this->context);
    }

    /**
     * {@inheritdoc}
     *
     * @template U
     * @param callable(T): Result<U, E> $fn
     * @return Result<U, E>
     */
    #[\Override]
    public function bind(callable $fn): Result
    {
        return $fn($this->value);
    }

    /**
     * Chains a function that extracts a value from the current value/context.
     * The function must return an Ok with a named array of new context values.
     *
     * @param callable(mixed ...$args): Result<array<string, mixed>, E> $fn
     * @return Result<T, E>
     */
    #[\Override]
    public function use(string $field, callable $fn): Result
    {
        $result = $fn($this->value, ...array_values($this->context));

        if ($result instanceof Error) {
            return $result;
        }

        $newContext = $this->context;
        $newContext[$field] = $result->unwrap();

        return new self($this->value, $newContext);
    }

    /**
     * Redefines the main value using the current context and initial value.
     *
     * @template U
     * @param callable(mixed ...$args): U $fn
     * @return Ok<U, E>
     */
    #[\Override]
    public function mapWith(callable $fn): Result
    {
        $args = [$this->value, ...array_values($this->context)];
        return new self($fn(...$args), $this->context);
    }

    /**
     * {@inheritdoc}
     *
     * @template F
     * @param callable(E): F $fn
     * @return Ok<T, F>
     */
    #[\Override]
    public function mapErr(callable $fn): Result
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function isOk(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function isErr(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @return T
     */
    #[\Override]
    public function unwrap(): mixed
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     *
     * @template D
     * @param D $default
     * @return T
     */
    #[\Override]
    public function unwrapOr($default): mixed
    {
        return $this->value;
    }

    /**
     * Returns the string representation of the Ok value
     *
     * @return string
     */
    #[\Override]
    public function __toString(): string
    {
        return sprintf('Ok(%s)', var_export($this->value, true));
    }
}
