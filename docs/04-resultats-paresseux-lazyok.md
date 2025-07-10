# Résultats Paresseux : `LazyOk`

Dans le monde de la programmation, certaines opérations sont coûteuses en ressources ou en temps. Pensez aux requêtes de base de données, aux appels d'API externes, ou à des calculs complexes. Exécuter ces opérations inutilement peut dégrader les performances de votre application.

C'est là qu'intervient `LazyOk` dans `Hopr\Result`. Contrairement à `Ok` qui contient une valeur déjà évaluée, `LazyOk` contient une fonction (un `callable`) qui, lorsqu'elle est appelée, produit la valeur. La valeur n'est donc évaluée que lorsque cela est strictement nécessaire, c'est-à-dire lors de l'appel à `unwrap()`.

## 1. Qu'est-ce que `LazyOk` ?

`LazyOk` est une implémentation de `Result` qui encapsule une opération dont le résultat n'est pas immédiatement disponible ou dont l'évaluation est coûteuse. La valeur n'est calculée qu'une seule fois, lors du premier appel à `unwrap()`, puis mise en cache pour les appels ultérieurs.

Cela est particulièrement utile pour :
*   **Optimisation des performances** : Éviter des calculs ou des requêtes réseau si le résultat n'est finalement pas utilisé.
*   **Gestion des effets de bord** : Retarder l'exécution d'opérations qui ont des effets de bord (comme l'écriture dans un fichier ou l'envoi d'un email) jusqu'à ce que leur résultat soit réellement nécessaire.

## 2. Création d'un `LazyOk`

Vous pouvez créer un `LazyOk` en utilisant la fonction d'aide `lazyOk()` qui prend un `callable` (généralement une fonction anonyme ou une flèche fonction) en argument.

```php
<?php

use function Hopr\Result\lazyOk;
use function Hopr\Result\err;

// Cette fonction simule une opération coûteuse (par exemple, une requête DB ou API)
function fetchUserData(int $userId): \Hopr\Result\Result
{
    echo "[LOG] Récupération des données utilisateur pour l'ID : " . $userId . "\n";
    if ($userId === 123) {
        return lazyOk(fn() => ['id' => 123, 'name' => 'Alice', 'email' => 'alice@example.com']);
    } else {
        return err("Utilisateur non trouvé");
    }
}

echo "Début du script\n";
$userResult = fetchUserData(123);
echo "Après l'appel à fetchUserData, mais avant unwrap()\n";

// La fonction encapsulée dans LazyOk n'est exécutée qu'ici
if ($userResult->isOk()) {
    $user = $userResult->unwrap();
    echo "Données utilisateur : " . json_encode($user) . "\n";
} else {
    echo "Erreur : " . $userResult->getError() . "\n";
}
echo "Fin du script\n";
?>
```

**Sortie attendue :**

```
Début du script
[LOG] Récupération des données utilisateur pour l'ID : 123
Après l'appel à fetchUserData, mais avant unwrap()
Données utilisateur : {"id":123,"name":"Alice","email":"alice@example.com"}
Fin du script
```

Remarquez que le message `[LOG] Récupération des données utilisateur...` apparaît *après* `Après l'appel à fetchUserData, mais avant unwrap()`, prouvant que l'évaluation est différée.

## 3. `LazyOk` et les Opérations de Chaînage

Les méthodes `map()`, `bind()`, `use()`, `mapWith()`, `mapErr()` et `tap()` fonctionnent de manière similaire avec `LazyOk` qu'avec `Ok`. La différence clé est que l'évaluation de la valeur sous-jacente est toujours différée jusqu'à ce que `unwrap()` soit appelé.

```php
<?php

use function Hopr\Result\lazyOk;
use function Hopr\Result\err;
use Hopr\Result\Result;

function getProductPrice(int $productId): Result
{
    echo "[LOG] Récupération du prix pour le produit : " . $productId . "\n";
    if ($productId === 456) {
        return lazyOk(fn() => 99.99); // Simule une requête DB/API
    } else {
        return err("Produit non trouvé");
    }
}

function calculateDiscount(float $price): Result
{
    echo "[LOG] Calcul de la remise pour le prix : " . $price . "\n";
    return lazyOk(fn() => $price * 0.9); // 10% de remise
}

function formatCurrency(float $amount): Result
{
    echo "[LOG] Formatage du montant : " . $amount . "\n";
    return lazyOk(fn() => sprintf("%.2f EUR", $amount));
}

echo "Début du processus de commande\n";

$finalPriceResult = getProductPrice(456)
    ->bind(fn($price) => calculateDiscount($price))
    ->map(fn($discountedPrice) => $discountedPrice + 5.00) // Ajout de frais de port
    ->bind(fn($finalAmount) => formatCurrency($finalAmount));

echo "Chaîne d'opérations définie, mais pas encore évaluée...\n";

if ($finalPriceResult->isOk()) {
    echo "Prix final : " . $finalPriceResult->unwrap() . "\n";
} else {
    echo "Erreur de commande : " . $finalPriceResult->getError() . "\n";
}
echo "Fin du processus de commande\n";
?>
```

**Sortie attendue :**

```
Début du processus de commande
Chaîne d'opérations définie, mais pas encore évaluée...
[LOG] Récupération du prix pour le produit : 456
[LOG] Calcul de la remise pour le prix : 99.99
[LOG] Formatage du montant : 94.991
Prix final : 94.99 EUR
Fin du processus de commande
```

Comme vous pouvez le voir, toutes les opérations coûteuses (simulées par les `[LOG]`) ne sont exécutées qu'au moment où `unwrap()` est appelé, et dans l'ordre de la chaîne. Si une erreur était survenue plus tôt dans la chaîne, aucune des fonctions coûteuses suivantes n'aurait été exécutée.

## 4. `lazyTryTo()` : Gérer les Exceptions de Manière Paresseuse

Similaire à `tryTo()`, la fonction `lazyTryTo()` encapsule une fonction potentiellement génératrice d'exceptions dans un `LazyOk`. L'exception n'est capturée et le `Result` transformé en `Error` que si l'opération est effectivement évaluée via `unwrap()`.

```php
<?php

use function Hopr\Result\lazyTryTo;
use function Hopr\Result\err;

function riskyOperation(bool $shouldThrow): string
{
    echo "[LOG] Exécution de l'opération risquée...\n";
    if ($shouldThrow) {
        throw new \Exception("Quelque chose a mal tourné dans l'opération risquée");
    }
    return "Opération réussie !";
}

echo "Début du script\n";
$resultSuccess = lazyTryTo(fn() => riskyOperation(false));
$resultFailure = lazyTryTo(fn() => riskyOperation(true));

echo "Opérations définies, mais pas encore évaluées.\n";

// Évaluation du succès
if ($resultSuccess->isOk()) {
    echo "Succès : " . $resultSuccess->unwrap() . "\n";
} else {
    echo "Erreur : " . $resultSuccess->getError()->getMessage() . "\n";
}

// Évaluation de l'échec
if ($resultFailure->isOk()) {
    echo "Succès : " . $resultFailure->unwrap() . "\n";
} else {
    echo "Erreur : " . $resultFailure->getError()->getMessage() . "\n";
}
echo "Fin du script\n";
?>
```

**Sortie attendue :**

```
Début du script
Opérations définies, mais pas encore évaluées.
[LOG] Exécution de l'opération risquée...
Succès : Opération réussie !
[LOG] Exécution de l'opération risquée...
Erreur : Quelque chose a mal tourné dans l'opération risquée
Fin du script
```

`LazyOk` et `lazyTryTo()` sont des outils puissants pour construire des applications plus performantes et réactives, en vous donnant un contrôle fin sur le moment où les opérations coûteuses sont réellement exécutées. Dans la prochaine section, nous mettrons en pratique ces concepts avec des exemples concrets.
