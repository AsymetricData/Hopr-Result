# Introduction à Hopr\Result

Bienvenue dans la documentation de `Hopr\Result` ! Cette bibliothèque PHP est inspirée des types `Result` que l'on retrouve dans des langages comme Rust, Elm ou Swift. Son objectif principal est de vous aider à écrire du code plus robuste, plus lisible et plus prévisible en gérant explicitement les erreurs et les succès de vos opérations.

## Le Problème : La Gestion des Erreurs en PHP

Traditionnellement, PHP gère les erreurs de plusieurs manières :

1.  **Exceptions (`try-catch`)** : Excellentes pour les erreurs inattendues ou les situations exceptionnelles qui empêchent la poursuite normale du programme.
2.  **Valeurs de retour spéciales (`null`, `false`, `0`, `-1`)** : Souvent utilisées pour indiquer un échec, mais elles sont ambiguës et nécessitent une vérification manuelle à chaque appel, ce qui peut facilement être oublié.
3.  **Erreurs et Warnings PHP** : Des mécanismes internes qui peuvent interrompre l'exécution ou polluer les logs.

Ces approches, bien que fonctionnelles, peuvent rendre le code difficile à lire, à maintenir et à déboguer. Il est facile d'oublier de vérifier une valeur de retour `false`, ce qui peut entraîner des bugs silencieux ou des erreurs inattendues plus tard dans l'exécution.

## La Solution : Le Type `Result`

`Hopr\Result` introduit un concept simple mais puissant : une opération peut soit réussir et contenir une valeur (`Ok`), soit échouer et contenir une erreur (`Error`). Cette approche force le développeur à considérer explicitement les deux chemins possibles (succès et échec) à chaque étape de son code.

En utilisant `Result`, vous bénéficiez de :

*   **Clarté** : Le type de retour d'une fonction indique clairement qu'elle peut échouer.
*   **Prévisibilité** : Vous êtes encouragé à gérer les erreurs dès qu'elles se produisent, plutôt que de les laisser se propager.
*   **Composition** : Les opérations peuvent être chaînées de manière élégante, en propageant automatiquement les erreurs sans avoir besoin de multiples blocs `if-else` ou `try-catch` imbriqués.
*   **Sécurité** : Réduit les risques de `null` ou de valeurs inattendues qui peuvent causer des erreurs fatales.

## Quand Utiliser `Hopr\Result` ?

Utilisez `Result` pour toute opération qui peut raisonnablement échouer et dont l'échec est une partie attendue du flux de votre application. Par exemple :

*   Lecture/écriture de fichiers
*   Appels à des APIs externes ou des services web
*   Requêtes de base de données
*   Validation de données utilisateur
*   Parsing de chaînes de caractères ou de formats complexes

En adoptant `Hopr\Result`, vous écrirez du code plus expressif, plus sûr et plus agréable à manipuler. Plongeons maintenant dans l'installation et les concepts de base !