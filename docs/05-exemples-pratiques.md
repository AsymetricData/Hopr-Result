# Exemples Pratiques

Cette section présente des exemples concrets d'utilisation de `Hopr\Result`, `LazyOk`, et des fonctions associées pour résoudre des problèmes courants en développement PHP. Nous mettrons l'accent sur la gestion des erreurs et la composition des opérations.

## 1. Interaction avec une Base de Données (SQLite)

Interagir avec une base de données implique souvent des opérations qui peuvent échouer (connexion, requêtes invalides, données manquantes). `Result` est parfait pour gérer ces scénarios de manière explicite.

Nous allons simuler une base de données SQLite en mémoire pour cet exemple.

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use function Hopr\Result\ok;
use function Hopr\Result\err;
use function Hopr\Result\tryTo;
use function Hopr\Result\lazyOk;
use Hopr\Result\Result;

class DatabaseService
{
    private ?\PDO $pdo = null;

    public function __construct(private string $dbPath = ':memory:')
    {
    }

    public function connect(): Result
    {
        return tryTo(function () {
            $this->pdo = new \PDO("sqlite:{$this->dbPath}");
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)");
            return $this->pdo;
        }, fn(\Throwable $e) => err("Erreur de connexion à la DB: " . $e->getMessage()));
    }

    public function insertUser(string $name, string $email): Result
    {
        return $this->ensureConnected()
            ->bind(function (\PDO $pdo) use ($name, $email): Result {
                return tryTo(function () use ($pdo, $name, $email) {
                    $stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (:name, :email)");
                    $stmt->execute(['name' => $name, 'email' => $email]);
                    return $pdo->lastInsertId();
                }, fn(\Throwable $e) => err("Erreur d'insertion utilisateur: " . $e->getMessage()));
            });
    }

    public function getUserById(int $id): Result
    {
        return $this->ensureConnected()
            ->bind(function (\PDO $pdo) use ($id): Result {
                // Utilisation de lazyOk car la récupération des données est une opération I/O
                return lazyOk(function () use ($pdo, $id) {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
                    $stmt->execute(['id' => $id]);
                    $user = $stmt->fetch(\PDO::FETCH_ASSOC);
                    return $user ?: null; // Retourne null si non trouvé
                })
                ->map(fn($user) => $user ?: err("Utilisateur avec ID {$id} non trouvé")) // Transforme null en Error
                ->bind(fn($user) => $user instanceof Result ? $user : ok($user)); // Gère le cas où map retourne un Error
            });
    }

    private function ensureConnected(): Result
    {
        if ($this->pdo === null) {
            return $this->connect();
        }
        return ok($this->pdo);
    }
}

// --- Utilisation du service ---

$dbService = new DatabaseService();

// Cas 1: Insertion réussie et récupération
$dbService->insertUser("Alice", "alice@example.com")
    ->bind(fn($id) => $dbService->getUserById($id))
    ->tap(fn($user) => echo "Utilisateur inséré et récupéré : " . json_encode($user) . "\n")
    ->mapErr(fn($e) => echo "Erreur lors de l'insertion/récupération : " . $e . "\n");

// Cas 2: Tentative de récupération d'un utilisateur inexistant
$dbService->getUserById(999)
    ->tap(fn($user) => echo "Utilisateur 999 trouvé : " . json_encode($user) . "\n") // Ne s'exécutera pas
    ->mapErr(fn($e) => echo "Erreur lors de la récupération de l'utilisateur 999 : " . $e . "\n");

// Cas 3: Erreur de connexion (simulée en changeant le chemin de la DB pour un non-accessible)
// $dbService = new DatabaseService('/non/existent/path/db.sqlite');
// $dbService->connect()
//     ->mapErr(fn($e) => echo "Erreur de connexion simulée : " . $e . "\n");

?>
```

## 2. Appel à une API Externe

Les appels réseau sont intrinsèquement incertains. Ils peuvent échouer pour de multiples raisons (réseau, timeout, réponse invalide). `Result` et `LazyOk` sont des outils idéaux pour gérer cette incertitude.

Nous allons simuler un appel à une API de conversion de devises.

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use function Hopr\Result\ok;
use function Hopr\Result\err;
use function Hopr\Result\tryTo;
use function Hopr\Result\lazyTryTo;
use Hopr\Result\Result;

class CurrencyConverter
{
    private string $apiUrl = 'https://api.exchangerate-api.com/v4/latest/USD'; // Exemple d'API publique

    public function getExchangeRates(): Result
    {
        // lazyTryTo est utilisé car file_get_contents est une opération I/O et peut lancer une exception
        return lazyTryTo(function () {
            $json = file_get_contents($this->apiUrl);
            if ($json === false) {
                throw new \Exception("Impossible de récupérer les taux de change.");
            }
            return $json;
        }, fn(\Throwable $e) => err("Erreur réseau/API: " . $e->getMessage()))
        ->bind(function (string $json): Result {
            // map pour décoder le JSON, puis bind pour gérer l'erreur de décodage
            return tryTo(function () use ($json) {
                $data = json_decode($json, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception("JSON invalide: " . json_last_error_msg());
                }
                return $data;
            }, fn(\Throwable $e) => err("Erreur de décodage JSON: " . $e->getMessage()));
        })
        ->bind(function (array $data): Result {
            if (!isset($data['rates']) || !is_array($data['rates'])) {
                return err("Structure de réponse API invalide: 'rates' manquant.");
            }
            return ok($data['rates']);
        });
    }

    public function convert(float $amount, string $fromCurrency, string $toCurrency): Result
    {
        return $this->getExchangeRates()
            ->bind(function (array $rates) use ($amount, $fromCurrency, $toCurrency): Result {
                if (!isset($rates[$fromCurrency])) {
                    return err("Devise source '{$fromCurrency}' non supportée.");
                }
                if (!isset($rates[$toCurrency])) {
                    return err("Devise cible '{$toCurrency}' non supportée.");
                }

                $amountInUSD = $amount / $rates[$fromCurrency];
                $convertedAmount = $amountInUSD * $rates[$toCurrency];

                return ok($convertedAmount);
            });
    }
}

// --- Utilisation du service ---

$converter = new CurrencyConverter();

// Cas 1: Conversion réussie
$converter->convert(100, "USD", "EUR")
    ->tap(fn($converted) => echo "100 USD en EUR : " . sprintf("%.2f", $converted) . "\n")
    ->mapErr(fn($e) => echo "Erreur de conversion : " . $e . "\n");

// Cas 2: Devise non supportée
$converter->convert(50, "USD", "XYZ")
    ->tap(fn($converted) => echo "50 USD en XYZ : " . sprintf("%.2f", $converted) . "\n")
    ->mapErr(fn($e) => echo "Erreur de conversion : " . $e . "\n");

// Cas 3: Erreur de l'API (simulée en changeant l'URL pour une non-existante)
// $converter = new CurrencyConverter();
// $converter->apiUrl = 'http://invalid.api.url/v4/latest/USD';
// $converter->convert(10, "USD", "JPY")
//     ->mapErr(fn($e) => echo "Erreur API simulée : " . $e . "\n");

?>
```

## 3. Validation de Données Utilisateur avec `use()` et `mapWith()`

La validation de formulaires ou de données d'entrée est un cas d'usage parfait pour `Result`, surtout lorsque plusieurs champs doivent être validés et que les résultats intermédiaires sont nécessaires pour la transformation finale. `use()` et `mapWith()` brillent ici.

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use function Hopr\Result\ok;
use function Hopr\Result\err;
use Hopr\Result\Result;

function validateUsername(string $username): Result
{
    if (strlen($username) < 3) {
        return err("Le nom d'utilisateur doit avoir au moins 3 caractères.");
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return err("Le nom d'utilisateur ne peut contenir que des lettres, chiffres et underscores.");
    }
    return ok($username);
}

function validatePassword(string $password): Result
{
    if (strlen($password) < 8) {
        return err("Le mot de passe doit avoir au moins 8 caractères.");
    }
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        return err("Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre.");
    }
    return ok(password_hash($password, PASSWORD_DEFAULT)); // Hacher le mot de passe
}

function validateEmail(string $email): Result
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return err("L'adresse email est invalide.");
    }
    return ok($email);
}

// --- Données d'entrée ---
$formData = [
    'username' => 'john_doe',
    'password' => 'MySecureP4ss',
    'email' => 'john.doe@example.com',
    'age' => 25
];

// --- Processus de validation et de transformation ---
$processedFormData = ok($formData)
    ->use('username', fn($data) => validateUsername($data['username']))
    ->use('hashed_password', fn($data) => validatePassword($data['password']))
    ->use('email', fn($data) => validateEmail($data['email']))
    // mapWith pour combiner les résultats validés et transformés
    ->mapWith(fn($original, $username, $hashed_password, $email) => [
        'id' => uniqid('user_'), // Générer un ID unique
        'username' => $username,
        'password_hash' => $hashed_password,
        'email' => $email,
        'age' => $original['age'] // L'âge n'a pas été validé ici, juste passé
    ]);

// --- Affichage des résultats ---
if ($processedFormData->isOk()) {
    echo "Données utilisateur validées et traitées :\n";
    print_r($processedFormData->unwrap());
} else {
    echo "Erreur de validation : " . $processedFormData->getError() . "\n";
}

echo "\n---\n";

// --- Cas d'erreur : Données invalides ---
$invalidFormData = [
    'username' => 'jo',
    'password' => 'short',
    'email' => 'invalid-email',
    'age' => 17
];

$processedInvalidFormData = ok($invalidFormData)
    ->use('username', fn($data) => validateUsername($data['username']))
    ->use('hashed_password', fn($data) => validatePassword($data['password']))
    ->use('email', fn($data) => validateEmail($data['email']));

if ($processedInvalidFormData->isOk()) {
    echo "Données utilisateur validées et traitées :\n";
    print_r($processedInvalidFormData->unwrap());
} else {
    echo "Erreur de validation : " . $processedInvalidFormData->getError() . "\n";
    // Affiche la première erreur rencontrée, par exemple "Le nom d'utilisateur doit avoir au moins 3 caractères."
}

?>
```

Ces exemples démontrent la puissance et la flexibilité de `Hopr\Result` pour gérer les flux de données complexes et les erreurs de manière élégante et prévisible. Dans la dernière section, nous aborderons quelques bonnes pratiques et pièges à éviter.
