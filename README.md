# Skribblio Tools

**Skribblio Tools** est une application web qui aide à préparer des parties de [Skribbl.io](https://skribbl.io).

Elle permet de constituer des listes de mots personnalisés, seul ou à plusieurs, puis de les exporter pour les utiliser directement comme mots personnalisés lors d'une partie Skribbl.io.

## Fonctionnalités

- Création de listes de mots personnalisés
- Édition des listes seul ou en collaboration avec d'autres utilisateurs
- Favoris : groupes et collections favoris sur le tableau de bord
- Partage des groupes et collections privés par lien de demande d'accès, avec validation par le créateur et notifications par e-mail
- Page de chaque utilisateur (en cliquant sur son nom) avec ses groupes et collections visibles
- Recherche par nom parmi ses groupes et collections et ceux, publics, des autres utilisateurs
- Export d'un groupe ou d'une collection dans un format prêt à coller dans Skribbl.io

## Stack technique

- [Laravel](https://laravel.com) 13 (PHP 8.5) : API JSON
- [Laravel Sanctum](https://laravel.com/docs/sanctum) (mode SPA) et [Laravel Fortify](https://laravel.com/docs/fortify) (sans vues) pour l'authentification
- [Vue 3](https://vuejs.org) + [TypeScript](https://www.typescriptlang.org), avec [Vue Router](https://router.vuejs.org), [Pinia](https://pinia.vuejs.org) et [axios](https://axios-http.com) : front-end (SPA)
- Vite et Tailwind CSS 4 pour le build et le style
- [Laravel Lang](https://laravel-lang.com) pour les traductions françaises
- [Laravel Sail](https://laravel.com/docs/sail) pour l'environnement de développement Docker
- MySQL 8.4 pour la base de données
- Redis pour le cache et les files d'attente (jobs)
- Mailpit pour intercepter les e-mails en local

## Démarrer le projet avec Sail

### Prérequis

- [Docker](https://docs.docker.com/get-docker/) avec Docker Compose
- Git

PHP, Composer et Node n'ont pas besoin d'être installés sur votre machine : tout tourne dans les conteneurs Sail.

### Installation

1. Cloner le dépôt :

   ```sh
   git clone git@github.com:josselinh/skribblio.git
   cd skribblio
   ```

2. Créer le fichier d'environnement :

   ```sh
   cp .env.example .env
   ```

3. Installer les dépendances PHP (Sail n'est pas encore disponible à cette étape, on passe donc par un conteneur Composer) :

   ```sh
   docker run --rm \
       -u "$(id -u):$(id -g)" \
       -v "$(pwd):/var/www/html" \
       -w /var/www/html \
       laravelsail/php84-composer:latest \
       composer install --ignore-platform-reqs
   ```

   Il n'existe pas encore d'image `laravelsail/php85-composer` : celle de PHP 8.4 suffit pour installer les dépendances. L'application tourne bien en PHP 8.5 dans le conteneur Sail.

4. Construire l'image de l'application, puis démarrer les conteneurs :

   ```sh
   ./vendor/bin/sail build
   ./vendor/bin/sail up -d
   ```

   L'image `sail-8.5/app` n'est publiée sur aucun registre : elle est construite localement à partir de `vendor/laravel/sail/runtimes/8.5`. Sans `sail build`, `sail up -d` tente d'abord de la télécharger et affiche `failed to resolve reference "sail-8.5/app:latest"`. Le premier build prend une dizaine de minutes. Relancez `./vendor/bin/sail build` après une mise à jour de `laravel/sail`.

5. Générer la clé de l'application et créer les tables :

   ```sh
   ./vendor/bin/sail artisan key:generate
   ./vendor/bin/sail artisan migrate
   ```

6. Installer les dépendances front-end et lancer Vite :

   ```sh
   ./vendor/bin/sail npm install
   ./vendor/bin/sail npm run dev
   ```

   `npm run dev` reste au premier plan : laissez ce terminal ouvert tant que vous travaillez sur l'application. Pour se passer du serveur Vite, compilez une fois le front-end avec `./vendor/bin/sail npm run build` (et supprimez `public/hot` s'il existe).

L'application est alors accessible sur <http://localhost:8000>.

### Traiter les jobs

Les jobs sont placés dans une file Redis. Pour qu'ils soient exécutés, lancez un worker dans un terminal dédié :

```sh
./vendor/bin/sail artisan queue:work
```

### Services disponibles

| Service             | URL / port                  |
| ------------------- | --------------------------- |
| Application         | <http://localhost:8000>     |
| Vite (dev server)   | <http://localhost:5173>     |
| Mailpit (web)       | <http://localhost:8025>     |
| MySQL               | `localhost:3306`            |
| Redis               | `localhost:6379`            |

Les ports sont modifiables dans le fichier `.env` (`APP_PORT`, `VITE_PORT`, `FORWARD_DB_PORT`, `FORWARD_REDIS_PORT`, `FORWARD_MAILPIT_DASHBOARD_PORT`).

### Commandes utiles

```sh
./vendor/bin/sail up -d            # Démarrer les conteneurs
./vendor/bin/sail stop             # Arrêter les conteneurs
./vendor/bin/sail artisan test     # Lancer les tests
./vendor/bin/sail bin pint         # Formater le code PHP
./vendor/bin/sail npm run type-check  # Vérifier les types TypeScript
./vendor/bin/sail npm run build    # Vérifier les types puis compiler le front-end
./vendor/bin/sail shell            # Ouvrir un shell dans le conteneur
```

## Licence

Skribblio Tools est un logiciel libre distribué sous [licence MIT](LICENSE). Projet indépendant, non affilié à Skribbl.io.
