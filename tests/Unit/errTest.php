<?php

use Hopr\Result\Result;
use Hopr\Result\Error;

// Tests pour la création et les propriétés de base de Error
test('Error::of crée correctement une instance de Error', function () {
    $err = Error::of('erreur');
    expect($err)->toBeInstanceOf(Error::class)
        ->and($err)->toBeInstanceOf(Result::class);
});

test('Error::isOk retourne false', function () {
    $err = Error::of('erreur');
    expect($err->isOk())->toBeFalse();
});

test('Error::isErr retourne true', function () {
    $err = Error::of('erreur');
    expect($err->isErr())->toBeTrue();
});

test('Error::unwrap lève une exception', function () {
    $err = Error::of('erreur test');
    expect(fn () => $err->unwrap())->toThrow(\RuntimeException::class, 'erreur test');
});

test('Error::unwrapOr retourne la valeur par défaut', function () {
    $err = Error::of('erreur');
    expect($err->unwrapOr(42))->toBe(42);
});

test('Error::getError retourne la valeur d\'erreur', function () {
    $err = Error::of('erreur');
    expect($err->getError())->toBe('erreur');
});

test('Error::__toString produit une représentation lisible', function () {
    $err = Error::of('erreur');
    expect((string) $err)->toBe('Error(\'erreur\')');
});

// Tests pour différents types d'erreurs dans Error
test('Error peut contenir des types scalaires comme erreurs', function () {
    expect(Error::of(42)->getError())->toBe(42);
    expect(Error::of('erreur')->getError())->toBe('erreur');
    expect(Error::of(3.14)->getError())->toBe(3.14);
    expect(Error::of(true)->getError())->toBeTrue();
});

test('Error peut contenir des objets comme erreurs', function () {
    $exception = new \Exception('test');
    $err = Error::of($exception);
    expect($err->getError())->toBe($exception)
        ->and($err->getError()->getMessage())->toBe('test');
});
