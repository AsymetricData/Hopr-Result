<?php

declare(strict_types=1);

namespace Hopr\Result;

/**
 * Class LazyOk
 *
 * Represents a successful result with a lazily evaluated value.
 *
 * @template T The type of the success value
 * @template E The type of the error value (not used in Ok, but needed for interface compatibility)
 * @implements Result<T, E>
 */
class LazyOk implements Result
{
    /**
     * @var callable(): T The lazy success value
     */
    private $value;

    /**
     * @var ?T The evaluated value
     */
    private $evaluatedValue = null;

    /**
     * @var bool Whether the value has been evaluated
     */
    private bool $isEvaluated = false;

    /**
     * @var array<string, mixed> Contextual values accumulated via `use`
     */
    private array $context = [];

    /**
     * LazyOk constructor
     *
     * @param callable(): T $value The lazy success value
     * @param array<string, mixed> $context Optional context
     */
    private function __construct(callable $value, array $context = [])
    {
        $this->value = $value;
        $this->context = $context;
    }

    /**
     * Creates a new LazyOk instance
     *
     * @template U
     * @param callable(): U $value The lazy success value
     * @return LazyOk<U, mixed> A new LazyOk instance
     */
    public static function of(callable $value): LazyOk
    {
        return new self($value);
    }

    /**
     * {@inheritdoc}
     *
     * @template U
     * @param callable(T): U $fn
     * @return LazyOk<U, E>
     */
    #[\Override]
    public function map(callable $fn): Result
    {
        return new self(fn() => $fn($this->unwrap()), $this->context);
    }

    /**
     * {@inheritdoc}
     *
     * @template U
     * @template F
     * @param callable(T): Result<U, F> $fn
     * @return Result<U, F>
     */
    #[\Override]
    public function bind(callable $fn): Result
    {
        return $fn($this->unwrap());
    }

    /**
     * {@inheritdoc}
     *
     * @param string $field Name under which the value is stored
     * @param callable(T, mixed ...): Result<mixed, E> $fn Callback producing the new value
     * @return Result<T, E>
     */
    #[\Override]
    public function use(string $field, callable $fn): Result
    {
        $result = $fn($this->unwrap(), ...array_values($this->context));

        if ($result instanceof Error) {
            return $result;
        }

        $newContext = $this->context;
        $newContext[$field] = $result->unwrap();

        return new self(fn() => $this->unwrap(), $newContext);
    }

    /**
     * {@inheritdoc}
     *
     * @template U
     * @param callable(T, mixed ...): U $fn
     * @return LazyOk<U, E>
     */
    #[\Override]
    public function mapWith(callable $fn): Result
    {
        return new self(fn() => $fn($this->unwrap(), ...array_values($this->context)), $this->context);
    }

    /**
     * {@inheritdoc}
     *
     * @template F
     * @param callable(E): F $fn
     * @return LazyOk<T, F>
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
        if (!$this->isEvaluated) {
            $this->evaluatedValue = ($this->value)();
            $this->isEvaluated = true;
        }
        return $this->evaluatedValue;
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
        return $this->unwrap();
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    #[\Override]
    public function getError(): mixed
    {
        // Nothing
    }

    /**
     * Returns the string representation of the LazyOk value
     *
     * @return string
     */
    #[\Override]
    public function __toString(): string
    {
        return sprintf('LazyOk(%s)', var_export($this->unwrap(), true));
    }

    /**
     * {@inheritdoc}
     *
     * @param callable(T): void $fn The function to execute, that doesn't modify the inner value
     * @return Result<T, E>
     */
    public function tap(callable $fn): Result
    {
        $fn($this->unwrap());
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function flatten(): Result
    {
        return $this->value instanceof Ok || $this->value instanceof LazyOk ? $this->unwrap() : $this;
    }
}
