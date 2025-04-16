<?php

use Hopr\Result\Ok;
use Hopr\Result\Error;
use Hopr\Result\Result;

// Tests pour la création et les propriétés de base de Ok
test('Ok::of crée correctement une instance de Ok', function () {
    $ok = Ok::of(42);
    expect($ok)->toBeInstanceOf(Ok::class)
        ->and($ok)->toBeInstanceOf(Result::class);
});

test('Ok::isOk retourne true', function () {
    $ok = Ok::of(42);
    expect($ok->isOk())->toBeTrue();
});

test('Ok::isErr retourne false', function () {
    $ok = Ok::of(42);
    expect($ok->isErr())->toBeFalse();
});

test('Ok::unwrap retourne la valeur', function () {
    $ok = Ok::of(42);
    expect($ok->unwrap())->toBe(42);
});

test('Ok::unwrapOr retourne la valeur et ignore la valeur par défaut', function () {
    $ok = Ok::of(42);
    expect($ok->unwrapOr(0))->toBe(42);
});

test('Ok::__toString produit une représentation lisible', function () {
    $ok = Ok::of(42);
    expect((string)$ok)->toBe('Ok(42)');
});

// Tests pour différents types de valeurs dans Ok
test('Ok peut contenir des types scalaires', function () {
    expect(Ok::of(42)->unwrap())->toBe(42);
    expect(Ok::of('hello')->unwrap())->toBe('hello');
    expect(Ok::of(3.14)->unwrap())->toBe(3.14);
    expect(Ok::of(true)->unwrap())->toBeTrue();
    expect(Ok::of(null)->unwrap())->toBeNull();
});

test('Ok peut contenir des objets', function () {
    $obj = new \stdClass();
    $obj->id = 123;

    $ok = Ok::of($obj);
    expect($ok->unwrap())->toBe($obj)
        ->and($ok->unwrap()->id)->toBe(123);
});

test('Ok peut contenir des tableaux', function () {
    $arr = [1, 2, 3];
    $ok = Ok::of($arr);
    expect($ok->unwrap())->toBe($arr);
});

// Tests pour la méthode map de Ok
test('Ok::map applique la fonction et retourne un nouveau Ok', function () {
    $ok = Ok::of(5);
    $result = $ok->map(fn ($x) => $x * 2);

    expect($result)->toBeInstanceOf(Ok::class)
        ->and($result->unwrap())->toBe(10);
});

test('Ok::map préserve le typage avec différents types', function () {
    // int => string
    $result = Ok::of(5)->map(fn ($x) => "Number: $x");
    expect($result->unwrap())->toBe('Number: 5');

    // string => array
    $result = Ok::of('a,b,c')->map(fn ($s) => explode(',', $s));
    expect($result->unwrap())->toBe(['a', 'b', 'c']);

    // array => int
    $result = Ok::of([1, 2, 3])->map(fn ($arr) => count($arr));
    expect($result->unwrap())->toBe(3);
});

// Tests pour la méthode bind de Ok
test('Ok::bind applique la fonction et retourne son résultat', function () {
    $ok = Ok::of(5);

    // bind renvoie un Ok
    $result1 = $ok->bind(fn ($x) => Ok::of($x * 2));
    expect($result1)->toBeInstanceOf(Ok::class)
        ->and($result1->unwrap())->toBe(10);

    // bind renvoie un Error
    $result2 = $ok->bind(fn ($x) => Error::of('erreur'));
    expect($result2)->toBeInstanceOf(Error::class)
        ->and($result2->getError())->toBe('erreur');
});

test('Ok::bind peut enchaîner des opérations', function () {
    $parseNumber = function (string $input): Result {
        if (is_numeric($input)) {
            return Ok::of((int)$input);
        }
        return Error::of("Pas un nombre");
    };

    $multiplyByTwo = function (int $number): Result {
        return Ok::of($number * 2);
    };

    // Cas réussi: "5" => 5 => 10
    $result1 = Ok::of("5")
        ->bind($parseNumber)
        ->bind($multiplyByTwo);

    expect($result1->isOk())->toBeTrue()
        ->and($result1->unwrap())->toBe(10);

    // Cas d'erreur: "abc" => Error
    $result2 = Ok::of("abc")
        ->bind($parseNumber)
        ->bind($multiplyByTwo);

    expect($result2->isErr())->toBeTrue()
        ->and($result2->getError())->toBe("Pas un nombre");
});

// Tests pour la méthode mapErr de Ok
test('Ok::mapErr ne fait rien et retourne l\'Ok inchangé', function () {
    $ok = Ok::of(5);
    $result = $ok->mapErr(fn ($e) => "Nouvelle erreur: $e");

    expect($result)->toBe($ok)
        ->and($result->unwrap())->toBe(5);
});

