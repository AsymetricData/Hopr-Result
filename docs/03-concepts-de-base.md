# Concepts de Base : Ok, Error et les Opérations Fondamentales

Au cœur de `Hopr\Result` se trouvent deux états fondamentaux : `Ok` pour le succès et `Error` pour l'échec. Ces deux types implémentent l'interface `Result`, garantissant une API cohérente pour la manipulation des valeurs.

## 1. Les Types `Ok` et `Error`

*   **`Ok<T>`** : Représente une opération réussie et contient une valeur de type `T`.
*   **`Error<E>`** : Représente une opération échouée et contient une valeur d'erreur de type `E`.

Pour créer facilement ces instances, vous pouvez utiliser les fonctions d'aide globales `ok()` et `err()` :

```php
<?php

use function Hopr\Result\ok;
use function Hopr\Result\err;

$success = ok("Données récupérées");
$failure = err("Erreur de connexion");

// Vérifier le type
var_dump($success->isOk());   // true
var_dump($failure->isErr());  // true

var_dump($success->isErr());  // false
var_dump($failure->isOk());   // false

?>
```

## 2. Accéder aux Valeurs : `unwrap()` et `unwrapOr()`

### `unwrap()`

La méthode `unwrap()` vous permet d'extraire la valeur contenue dans un `Ok`. **Attention** : si vous appelez `unwrap()` sur une instance de `Error`, une `RuntimeException` sera levée. Utilisez-la uniquement lorsque vous êtes certain que le `Result` est un `Ok` (par exemple, après une vérification avec `isOk()`).

```php
<?php

use function Hopr\Result\ok;
use function Hopr\Result\err;

$success = ok("Hello");
echo $success->unwrap(); // Affiche "Hello"

$failure = err("Quelque chose a mal tourné");
// echo $failure->unwrap(); // Lève une RuntimeException

?>
```

### `unwrapOr(defaultValue)`

Pour éviter les exceptions, `unwrapOr()` est une méthode plus sûre. Elle retourne la valeur si le `Result` est un `Ok`, sinon elle retourne la `defaultValue` fournie.

```php
<?php

use function Hopr\Result\ok;
use function Hopr\Result\err;

$success = ok("Données");
echo $success->unwrapOr("Valeur par défaut"); // Affiche "Données"

$failure = err("Erreur");
echo $failure->unwrapOr("Valeur par défaut"); // Affiche "Valeur par défaut"

?>
```

## 3. Transformer les Valeurs : `map()` et `mapErr()`

### `map(callable $fn)`

La méthode `map()` applique une fonction au succès (`Ok`) contenu dans le `Result` et retourne un nouveau `Result` avec la valeur transformée. Si le `Result` est un `Error`, la fonction n'est pas appelée et l'`Error` est simplement propagée.

Ceci est extrêmement utile pour transformer des données sans se soucier de l'état d'erreur.

```php
<?php

use function Hopr\Result\ok;
use function Hopr\Result\err;

$length = ok("Hello World")
    ->map(fn($s) => strlen($s));

var_dump($length->unwrap()); // int(11)

$errorLength = err("Pas de chaîne")
    ->map(fn($s) => strlen($s));

var_dump($errorLength->isErr()); // bool(true)

?>
```

### `mapErr(callable $fn)`

Similaire à `map()`, mais `mapErr()` applique une fonction à la valeur d'erreur (`Error`) si le `Result` est un `Error`. Si c'est un `Ok`, la fonction n'est pas appelée et l'`Ok` est propagé.

```php
<?php

use function Hopr\Result\ok;
use function Hopr\Result\err;

$formattedError = err("DB_CONN_FAILED")
    ->mapErr(fn($e) => "Erreur système: " . $e);

var_dump($formattedError->getError()); // string(22) "Erreur système: DB_CONN_FAILED"

$okResult = ok("Success")
    ->mapErr(fn($e) => "Cette erreur ne sera pas vue");

var_dump($okResult->isOk()); // bool(true)

?>
```

## 4. Chaîner les Opérations : `bind()`

La méthode `bind()` (souvent appelée `flatMap` dans d'autres contextes) est utilisée pour chaîner des opérations qui *elles-mêmes* retournent un `Result`. Si l'opération précédente est un `Ok`, la fonction fournie à `bind()` est exécutée. Si elle retourne un `Ok`, la chaîne continue. Si elle retourne un `Error`, la chaîne s'arrête et l'`Error` est propagée.

Ceci est la pierre angulaire de la composition fonctionnelle avec `Result`, permettant d'éviter les imbrications de `if-else`.

**Exemple sans `Result` (approche traditionnelle) :**

```php
<?php

function parseAndDoubleTraditional(string $input): int|string // int ou string pour l'erreur
{
    if (!is_numeric($input)) {
        return "Entrée non numérique";
    }
    $number = (int)$input;
    if ($number < 0) {
        return "Nombre négatif non autorisé";
    }
    return $number * 2;
}

$result = parseAndDoubleTraditional("10");
if (is_int($result)) {
    echo "Résultat: " . $result . "\n"; // Affiche 20
} else {
    echo "Erreur: " . $result . "\n";
}

$result = parseAndDoubleTraditional("abc");
if (is_int($result)) {
    echo "Résultat: " . $result . "\n";
} else {
    echo "Erreur: " . $result . "\n"; // Affiche "Erreur: Entrée non numérique"
}

?>
```

**Exemple avec `Result` et `bind()` :**

```php
<?php

use function Hopr\Result\ok;
use function Hopr\Result\err;
use Hopr\Result\Result;

function parseNumber(string $input): Result
{
    if (!is_numeric($input)) {
        return err("Entrée non numérique");
    }
    return ok((int)$input);
}

function validatePositive(int $number): Result
{
    if ($number < 0) {
        return err("Nombre négatif non autorisé");
    }
    return ok($number);
}

function doubleNumber(int $number): Result
{
    return ok($number * 2);
}

$process = function (string $input): Result {
    return parseNumber($input)
        ->bind(fn($num) => validatePositive($num))
        ->bind(fn($num) => doubleNumber($num));
};

$result1 = $process("10");
if ($result1->isOk()) {
    echo "Résultat: " . $result1->unwrap() . "\n"; // Affiche 20
} else {
    echo "Erreur: " . $result1->getError() . "\n";
}

$result2 = $process("abc");
if ($result2->isOk()) {
    echo "Résultat: " . $result2->unwrap() . "\n";
} else {
    echo "Erreur: " . $result2->getError() . "\n"; // Affiche "Erreur: Entrée non numérique"
}

$result3 = $process("-5");
if ($result3->isOk()) {
    echo "Résultat: " . $result3->unwrap() . "\n";
} else {
    echo "Erreur: " . $result3->getError() . "\n"; // Affiche "Erreur: Nombre négatif non autorisé"
}

?>
```

Comme vous pouvez le voir, `bind()` permet un flux de données clair et linéaire, où les erreurs sont gérées automatiquement sans interrompre la chaîne d'opérations.

## 5. Accumuler du Contexte : `use()` et `mapWith()`

Ces deux méthodes travaillent de concert pour vous permettre d'accumuler des valeurs intermédiaires (du "contexte") au fur et à mesure que vous chaînez des opérations, puis d'utiliser ce contexte pour une transformation finale.

### `use(string $field, callable $fn)`

La méthode `use()` est conçue pour extraire ou calculer une valeur et la stocker dans un contexte interne sous un nom de champ donné. La fonction `$fn` reçoit la valeur actuelle du `Result` (si `Ok`) et toutes les valeurs de contexte précédemment accumulées. Elle doit retourner un `Result`.

Si la fonction `$fn` retourne un `Error`, la chaîne s'arrête et cet `Error` est propagé.

### `mapWith(callable $fn)`

Une fois que vous avez accumulé toutes les données nécessaires avec `use()`, `mapWith()` vous permet d'appliquer une fonction qui reçoit la valeur principale du `Result` *ainsi que toutes les valeurs de contexte accumulées* comme arguments. Cela est très puissant pour des transformations finales complexes.

**Exemple avec `use()` et `mapWith()` :**

Imaginez que vous traitez des données utilisateur et que vous avez besoin de valider plusieurs champs, puis de les combiner.

```php
<?php

use function Hopr\Result\ok;
use function Hopr\Result\err;
use Hopr\Result\Result;

function validateName(string $name): Result
{
    return strlen($name) > 2 ? ok($name) : err("Nom trop court");
}

function validateEmail(string $email): Result
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? ok($email) : err("Email invalide");
}

function validateAge(int $age): Result
{
    return $age >= 18 ? ok($age) : err("Doit être majeur");
}

$userData = [
    'name' => 'Alice',
    'email' => 'alice@example.com',
    'age' => 30
];

$processedUser = ok($userData) // Commence avec les données brutes
    ->use('validName', fn($data) => validateName($data['name']))
    ->use('validEmail', fn($data, $validName) => validateEmail($data['email'])) // $validName est disponible ici
    ->use('validAge', fn($data, $validName, $validEmail) => validateAge($data['age'])) // $validName et $validEmail sont disponibles
    ->mapWith(fn($originalData, $validName, $validEmail, $validAge) => [
        'full_name' => strtoupper($validName),
        'contact_email' => $validEmail,
        'age_in_years' => $validAge
    ]);

if ($processedUser->isOk()) {
    print_r($processedUser->unwrap());
    /* Affiche :
    Array
    (
        [full_name] => ALICE
        [contact_email] => alice@example.com
        [age_in_years] => 30
    )
    */
} else {
    echo "Erreur de traitement : " . $processedUser->getError() . "\n";
}

// Cas d'erreur
$invalidUserData = [
    'name' => 'Al',
    'email' => 'invalid-email',
    'age' => 16
];

$processedInvalidUser = ok($invalidUserData)
    ->use('validName', fn($data) => validateName($data['name']))
    ->use('validEmail', fn($data, $validName) => validateEmail($data['email']))
    ->use('validAge', fn($data, $validName, $validEmail) => validateAge($data['age']));

if ($processedInvalidUser->isOk()) {
    print_r($processedInvalidUser->unwrap());
} else {
    echo "Erreur de traitement : " . $processedInvalidUser->getError() . "\n";
    // Affiche "Erreur de traitement : Nom trop court" (s'arrête à la première erreur)
}

?>
```

`use()` et `mapWith()` sont particulièrement puissants pour construire des pipelines de validation et de transformation de données complexes, où chaque étape peut potentiellement échouer, et où les résultats des étapes précédentes sont nécessaires pour les étapes suivantes.

## 6. Effets de Bord : `tap()`

La méthode `tap()` vous permet d'exécuter une fonction pour des effets de bord (comme la journalisation, le débogage, etc.) sans modifier la valeur du `Result`. Elle est appelée uniquement si le `Result` est un `Ok` et retourne le `Result` original inchangé.

```php
<?php

use function Hopr\Result\ok;
use function Hopr\Result\err;

ok("Données importantes")
    ->tap(function ($data) { echo "Log: Données traitées : " . $data . "\n"; })
    ->map(fn($data) => strtoupper($data));
// Affiche "Log: Données traitées : Données importantes"

err("Erreur critique")
    ->tap(function ($error) { echo "Log: Cette ligne ne sera pas affichée car c'est une erreur\n"; });
// N'affiche rien

?>
```

Ces méthodes constituent la base de la manipulation des `Result`. Dans la prochaine section, nous explorerons `LazyOk` pour les opérations coûteuses ou asynchrones.
