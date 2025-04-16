<?php

namespace Tests\Unit;

use Hopr\Result\Error;
use Hopr\Result\Ok;
use Hopr\Result\Result;

// Tests pour des cas d'utilisation spécifiques
test('exemple de validation de formulaire', function () {
    // Simulation d'une fonction de validation
    $validateAge = function ($age): Result {
        if (!is_numeric($age)) {
            return Error::of("L'âge doit être un nombre");
        }
        $age = (int)$age;
        if ($age < 0) {
            return Error::of("L'âge ne peut pas être négatif");
        }
        if ($age > 150) {
            return Error::of("L'âge semble invalide");
        }
        return Ok::of($age);
    };

    expect($validateAge('abc')->isErr())->toBeTrue();
    expect($validateAge('-5')->isErr())->toBeTrue();
    expect($validateAge('200')->isErr())->toBeTrue();
    expect($validateAge('25')->isOk())->toBeTrue();
    expect($validateAge('25')->unwrap())->toBe(25);
});

test('exemple de chaîne d\'opérations de traitement de données', function () {
    // Simulons un workflow de:
    // 1. Récupérer des données JSON
    // 2. Valider la structure
    // 3. Transformer les données

    $parseJson = function (string $input): Result {
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return Error::of('JSON invalide: ' . json_last_error_msg());
        }
        return Ok::of($data);
    };

    $validateStructure = function (array $data): Result {
        if (!isset($data['name']) || !isset($data['age'])) {
            return Error::of('Structure invalide: name et age requis');
        }
        return Ok::of($data);
    };

    $transform = function (array $data): Result {
        return Ok::of([
            'fullName' => $data['name'],
            'ageInMonths' => $data['age'] * 12
        ]);
    };

    // Test de chaînage réussi
    $validJson = '{"name":"John Doe","age":30}';
    $result1 = Ok::of($validJson)
        ->bind($parseJson)
        ->bind($validateStructure)
        ->bind($transform);

    expect($result1->isOk())->toBeTrue();
    expect($result1->unwrap())->toBe([
        'fullName' => 'John Doe',
        'ageInMonths' => 360
    ]);

    // Test d'erreur JSON
    $invalidJson = '{name:"John Doe",age:30}';
    $result2 = Ok::of($invalidJson)
        ->bind($parseJson)
        ->bind($validateStructure)
        ->bind($transform);

    expect($result2->isErr())->toBeTrue();
    expect($result2->getError())->toContain('JSON invalide');

    // Test d'erreur de structure
    $incompletJson = '{"name":"John Doe"}';
    $result3 = Ok::of($incompletJson)
        ->bind($parseJson)
        ->bind($validateStructure)
        ->bind($transform);

    expect($result3->isErr())->toBeTrue();
    expect($result3->getError())->toContain('Structure invalide');
});

test('exemple de traitement d\'exception', function () {
    // Simulons une fonction qui peut lancer une exception
    $divideNumbers = function (int $a, int $b): Result {
        try {
            if ($b === 0) {
                throw new \DivisionByZeroError("Division par zéro");
            }
            return Ok::of($a / $b);
        } catch (\Throwable $e) {
            return Error::of($e->getMessage());
        }
    };

    expect($divideNumbers(10, 2)->unwrap())->toBe(5);
    expect($divideNumbers(10, 0)->isErr())->toBeTrue();
    expect($divideNumbers(10, 0)->getError())->toBe("Division par zéro");
});

test('utilisation d\'un pattern matching (pseudo) pour traiter les résultats', function () {
    $handleResult = function (Result $result) {
        if ($result->isOk()) {
            return "Succès: " . $result->unwrap();
        } else {
            return "Erreur: " . $result->getError();
        }
    };

    expect($handleResult(Ok::of(42)))->toBe("Succès: 42");
    expect($handleResult(Error::of("échec")))->toBe("Erreur: échec");
});

// Tests pour la composition de fonctions avec Result
test('composition fonctionnelle avec bind', function () {
    // Fonctions qui renvoient des Results
    $parse = function (string $s): Result {
        if (is_numeric($s)) {
            return Ok::of((int)$s);
        }
        return Error::of("Pas un nombre: $s");
    };

    $double = function (int $i): Result {
        return Ok::of($i * 2);
    };

    $toString = function (int $i): Result {
        return Ok::of("Résultat: $i");
    };

    // Compose plusieurs fonctions
    $process = function (string $input) use ($parse, $double, $toString): Result {
        return $parse($input)
            ->bind($double)
            ->bind($toString);
    };

    expect($process("42")->unwrap())->toBe("Résultat: 84");
    expect($process("abc")->isErr())->toBeTrue();
    expect($process("abc")->getError())->toBe("Pas un nombre: abc");
});



test('comportement avec des closures/callables', function () {
    // Ok et Error peuvent contenir des callables
    $okFn = Ok::of(fn ($x) => $x * 2);
    $fn = $okFn->unwrap();
    expect($fn(5))->toBe(10);

    // On peut mapper un callable
    $result = $okFn->map(fn ($f) => fn ($x) => $f($x) + 1);
    $newFn = $result->unwrap();
    expect($newFn(5))->toBe(11);
});

test('la vérification d\'immutabilité des objets Result', function () {
    $ok = Ok::of(42);
    $mapped = $ok->map(fn ($x) => $x + 1);

    // L'original ne doit pas être modifié
    expect($ok->unwrap())->toBe(42);
    expect($mapped->unwrap())->toBe(43);

    $err = Error::of("erreur");
    $mappedErr = $err->mapErr(fn ($e) => "$e!");

    // L'original ne doit pas être modifié
    expect($err->getError())->toBe("erreur");
    expect($mappedErr->getError())->toBe("erreur!");
});
