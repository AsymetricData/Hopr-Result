# Installation et Démarrage

Pour commencer à utiliser `Hopr\Result` dans votre projet PHP, la méthode recommandée est d'utiliser Composer.

## 1. Installation avec Composer

Si vous n'avez pas encore Composer, vous pouvez le télécharger et l'installer en suivant les instructions sur [getcomposer.org](https://getcomposer.org/).

Une fois Composer installé, ouvrez votre terminal dans le répertoire de votre projet et exécutez la commande suivante :

```bash
composer require hopr/result
```

Cette commande téléchargera la bibliothèque et l'ajoutera à votre fichier `composer.json` et `composer.lock`. Elle créera également le dossier `vendor/` et le fichier `vendor/autoload.php`.

## 2. Premier Pas : "Hello Result!"

Maintenant que la bibliothèque est installée, vous pouvez l'utiliser dans votre code. N'oubliez pas d'inclure l'autoloader de Composer au début de votre script.

Créez un fichier, par exemple `index.php` :

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use function Hopr\Result\ok;
use function Hopr\Result\err;

// Une fonction qui peut réussir ou échouer
function divide(int $a, int $b): \Hopr\Result\Result
{
    if ($b === 0) {
        return err('Division par zéro impossible.');
    }
    return ok($a / $b);
}

// Cas de succès
$result1 = divide(10, 2);
if ($result1->isOk()) {
    echo "Résultat de la division (succès) : " . $result1->unwrap() . "\n"; // Affiche 5
} else {
    echo "Erreur (ne devrait pas arriver ici) : " . $result1->getError() . "\n";
}

// Cas d'échec
$result2 = divide(10, 0);
if ($result2->isErr()) {
    echo "Résultat de la division (échec) : " . $result2->getError() . "\n"; // Affiche "Division par zéro impossible."
} else {
    echo "Succès (ne devrait pas arriver ici) : " . $result2->unwrap() . "\n";
}

// Utilisation de unwrapOr pour une valeur par défaut
$valueOrDefault = divide(10, 0)->unwrapOr(0);
echo "Valeur ou défaut : " . $valueOrDefault . "\n"; // Affiche 0

?>
```

Exécutez ce fichier depuis votre terminal :

```bash
php index.php
```

Vous devriez voir la sortie suivante :

```
Résultat de la division (succès) : 5
Résultat de la division (échec) : Division par zéro impossible.
Valeur ou défaut : 0
```

Félicitations ! Vous avez installé et utilisé `Hopr\Result` pour la première fois. Dans la section suivante, nous allons explorer les concepts fondamentaux de `Ok`, `Error` et les méthodes de manipulation des `Result`.