# Bonnes Pratiques et Pièges à Éviter

`Hopr\Result` est un outil puissant, mais comme tout outil, son efficacité dépend de la manière dont il est utilisé. Voici quelques bonnes pratiques et pièges courants à éviter pour tirer le meilleur parti de cette bibliothèque.

## Bonnes Pratiques

### 1. Retournez Toujours un `Result` pour les Opérations Faillibles

Si une fonction peut échouer de manière prévisible (par exemple, une validation, un appel réseau, une requête DB), son type de retour devrait être `Result`. Cela rend l'intention de la fonction explicite et force l'appelant à gérer les deux chemins possibles (succès ou échec).

```php
// Mauvaise pratique : le type de retour ne dit rien sur l'échec
function findUserById(int $id): ?array { /* ... */ }

// Bonne pratique : le type de retour est clair
function findUserById(int $id): \Hopr\Result\Result { /* ... */ }
```

### 2. Utilisez `map()` pour les Transformations Pures

Si votre opération ne peut pas échouer *après* avoir reçu une valeur `Ok`, utilisez `map()`. C'est idéal pour les transformations de données qui ne génèrent pas de nouvelles erreurs (par exemple, `strlen`, `strtoupper`, calculs mathématiques simples).

```php
$result = ok("hello")
    ->map(fn($s) => strtoupper($s)); // Transformation pure
```

### 3. Utilisez `bind()` pour les Opérations Chaînées Faillibles

Lorsque vous chaînez des opérations qui *elles-mêmes* retournent un `Result`, utilisez `bind()`. C'est le cœur de la composition fonctionnelle avec `Result`, permettant de propager automatiquement les erreurs.

```php
$result = fetchUserData($id)
    ->bind(fn($data) => validateUserData($data))
    ->bind(fn($validatedData) => saveToDatabase($validatedData));
```

### 4. Utilisez `use()` et `mapWith()` pour Accumuler du Contexte

Pour les workflows complexes où vous avez besoin de valider ou de calculer plusieurs valeurs intermédiaires et de les utiliser ensemble à la fin, `use()` et `mapWith()` sont vos meilleurs amis. Ils permettent de construire un contexte riche et de le passer à la fonction finale.

```php
$processedOrder = ok($rawOrder)
    ->use('validatedItems', fn($order) => validateOrderItems($order['items']))
    ->use('shippingCost', fn($order, $items) => calculateShipping($items))
    ->mapWith(fn($original, $items, $shipping) => /* ... combine tout ... */);
```

### 5. Utilisez `lazyOk()` et `lazyTryTo()` pour les Opérations Coûteuses

Pour les opérations qui impliquent des I/O (réseau, disque) ou des calculs intensifs, utilisez `lazyOk()` ou `lazyTryTo()`. Cela garantit que l'opération n'est exécutée que si et quand sa valeur est réellement nécessaire (lors de l'appel à `unwrap()`), améliorant ainsi les performances.

### 6. Gérez les Erreurs à la Fin de la Chaîne

L'un des avantages de `Result` est de pouvoir chaîner de nombreuses opérations. Idéalement, vous ne devriez gérer l'état `Error` qu'une seule fois, à la fin de votre chaîne de traitement, en utilisant `isOk()`, `isErr()`, `unwrapOr()` ou `mapErr()`.

```php
$finalResult = someOperation()
    ->bind(fn($x) => anotherOperation($x))
    ->map(fn($y) => transform($y));

if ($finalResult->isOk()) {
    // Traitement du succès
} else {
    // Traitement de l'erreur
}
```

### 7. Utilisez `tap()` pour les Effets de Bord Non Modifiants

Si vous avez besoin d'exécuter une fonction pour des effets de bord (logging, débogage) sans modifier la valeur du `Result` ou son état, `tap()` est parfait. Il ne s'exécute que sur un `Ok` et retourne le `Result` original.

```php
$result = fetchData()
    ->tap(fn($data) => log("Données récupérées : " . json_encode($data)))
    ->map(fn($data) => process($data));
```

## Pièges à Éviter

### 1. Appeler `unwrap()` sans Vérification

C'est le piège le plus courant. Appeler `unwrap()` sur un `Error` lèvera une `RuntimeException`, ce qui annule l'avantage de la gestion explicite des erreurs. Vérifiez toujours avec `isOk()` ou utilisez `unwrapOr()` si vous n'êtes pas absolument certain du succès.

```php
// À éviter !
$result = someRiskyOperation();
echo $result->unwrap(); // Peut planter !

// Préférable
if ($result->isOk()) {
    echo $result->unwrap();
} else {
    echo "Erreur : " . $result->getError();
}

// Ou
echo $result->unwrapOr("Valeur par défaut en cas d'erreur");
```

### 2. Utiliser `map()` là où `bind()` est Nécessaire

Si la fonction que vous passez à `map()` retourne déjà un `Result`, vous vous retrouverez avec un `Result<Result<T, E>, E>`. C'est un signe que vous auriez dû utiliser `bind()`.

```php
// À éviter : Result<Result<string, string>, string>
$nestedResult = ok("input")
    ->map(fn($s) => someFunctionReturningResult($s));

// Correct : Result<string, string>
$flatResult = ok("input")
    ->bind(fn($s) => someFunctionReturningResult($s));
```

### 3. Ignorer les Erreurs

L'objectif de `Result` est de vous forcer à gérer les erreurs. Ne pas vérifier `isErr()` ou ne pas utiliser `mapErr()` ou `unwrapOr()` revient à ignorer les erreurs, ce qui annule l'intérêt de la bibliothèque.

### 4. Abuser de `tryTo()` pour des Erreurs Métier

`tryTo()` est excellent pour encapsuler des fonctions qui peuvent lancer des exceptions *techniques* (par exemple, `PDOException`, `RuntimeException`). Cependant, pour les erreurs *métier* (par exemple, "utilisateur non trouvé", "mot de passe invalide"), il est souvent plus clair de retourner directement un `err()`.

```php
// Moins idéal pour une erreur métier
function findUser(int $id): Result
{
    return tryTo(function () use ($id) {
        if ($id === 0) {
            throw new \Exception("ID utilisateur invalide");
        }
        return ['id' => $id, 'name' => 'Test'];
    });
}

// Plus clair pour une erreur métier
function findUser(int $id): Result
{
    if ($id === 0) {
        return err("ID utilisateur invalide");
    }
    return ok(['id' => $id, 'name' => 'Test']);
}
```

En suivant ces lignes directrices, vous pourrez écrire du code PHP plus robuste, plus maintenable et plus agréable à développer avec `Hopr\Result`.
