# Skribblio Tools

**Skribblio Tools** est une application web qui aide à préparer des parties de [Skribbl.io](https://skribbl.io).

Elle permet de constituer des listes de mots personnalisés, seul ou à plusieurs, puis de les exporter pour les utiliser directement comme mots personnalisés lors d'une partie Skribbl.io.

## Fonctionnalités

- Création de listes de mots personnalisés
- Édition des listes seul ou en collaboration avec d'autres utilisateurs
- Export d'une liste dans un format prêt à coller dans Skribbl.io

## Stack technique

- [Laravel](https://laravel.com) (PHP 8.5)
- [Laravel Sail](https://laravel.com/docs/sail) pour l'environnement de développement Docker
- MySQL 8.4 pour la base de données
- Redis pour le cache et les files d'attente (jobs)
- Mailpit pour intercepter les e-mails en local
- Vite et Tailwind CSS pour le front-end

## Démarrer le projet avec Sail

### Prérequis

- [Docker](https://docs.docker.com/get-docker/) avec Docker Compose
- Git

PHP, Composer et Node n'ont pas besoin d'être installés sur votre machine : tout tourne dans les conteneurs Sail.

### Installation

1. Cloner le dépôt :

   ```sh
   git clone git@github.com:josselinh/skribblio2.git
   cd skribblio2
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
       laravelsail/php85-composer:latest \
       composer install --ignore-platform-reqs
   ```

4. Démarrer les conteneurs :

   ```sh
   ./vendor/bin/sail up -d
   ```

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
./vendor/bin/sail shell            # Ouvrir un shell dans le conteneur
```

Astuce : ajoutez un alias pour taper simplement `sail` au lieu de `./vendor/bin/sail` :

```sh
alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'
```
