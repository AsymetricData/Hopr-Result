<?php

declare(strict_types=1);

use Hopr\Result\Error;
use Hopr\Result\LazyOk;
use Hopr\Result\Ok;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertTrue;

it('can be created from a value', function () {
    $lazyOk = LazyOk::of(fn () => 'test');
    assertInstanceOf(LazyOk::class, $lazyOk);
});

it('is ok', function () {
    $lazyOk = LazyOk::of(fn () => 'test');
    assertTrue($lazyOk->isOk());
});

it('is not err', function () {
    $lazyOk = LazyOk::of(fn () => 'test');
    assertFalse($lazyOk->isErr());
});

it('unwraps the value', function () {
    $lazyOk = LazyOk::of(fn () => 'test');
    assertEquals('test', $lazyOk->unwrap());
});

it('unwraps the value only once', function () {
    $counter = 0;
    $lazyOk = LazyOk::of(function () use (&$counter) {
        $counter++;
        return 'test';
    });

    assertEquals('test', $lazyOk->unwrap());
    assertEquals('test', $lazyOk->unwrap());
    assertEquals(1, $counter);
});

it('maps the value', function () {
    $lazyOk = LazyOk::of(fn () => 10);
    $mappedLazyOk = $lazyOk->map(fn ($value) => $value * 2);
    assertEquals(20, $mappedLazyOk->unwrap());
});

it('binds the value', function () {
    $lazyOk = LazyOk::of(fn () => 10);
    $boundResult = $lazyOk->bind(fn ($value) => Ok::of($value * 2));
    assertInstanceOf(Ok::class, $boundResult);
    assertEquals(20, $boundResult->unwrap());
});

it('binds to an error', function () {
    $lazyOk = LazyOk::of(fn () => 10);
    $boundResult = $lazyOk->bind(fn ($value) => Error::of('error'));
    assertInstanceOf(Error::class, $boundResult);
    assertEquals('error', $boundResult->getError());
});

it('uses context', function () {
    $lazyOk = LazyOk::of(fn () => 10);
    $resultWithContext = $lazyOk->use('multiplier', fn ($value) => Ok::of(2));
    $mappedResult = $resultWithContext->mapWith(fn ($value, $multiplier) => $value * $multiplier);
    assertEquals(20, $mappedResult->unwrap());
});

it('mapErr returns itself', function () {
    $lazyOk = LazyOk::of(fn () => 'test');
    $mappedLazyOk = $lazyOk->mapErr(fn ($error) => 'new error');
    assertInstanceOf(LazyOk::class, $mappedLazyOk);
    assertEquals('test', $mappedLazyOk->unwrap());
});

it('unwraps or returns default', function () {
    $lazyOk = LazyOk::of(fn () => 'test');
    assertEquals('test', $lazyOk->unwrapOr('default'));
});

it('has a string representation', function () {
    $lazyOk = LazyOk::of(fn () => 'test');
    assertEquals("LazyOk('test')", (string) $lazyOk);
});

it('taps the value', function () {
    $tappedValue = null;
    $lazyOk = LazyOk::of(fn () => 'original');
    $result = $lazyOk->tap(function ($value) use (&$tappedValue) {
        $tappedValue = $value;
    });

    assertEquals('original', $tappedValue);
    assertInstanceOf(LazyOk::class, $result);
    assertEquals('original', $result->unwrap());
});
