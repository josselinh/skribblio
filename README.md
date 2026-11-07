# Skribblio

Outil communautaire pour préparer des parties de Skribbl.io. Il permet de créer des utilisateurs, d'organiser des mots en groupes, de voter pour les mots préférés et d'exporter la liste d'un groupe. L'export est formaté pour être copié directement dans Skribbl.io au début d'une partie.

L'application utilise Laravel 13, PHP 8.5 et Vite pour compiler les fichiers JavaScript et Sass.

## Environnement de développement Docker

L'environnement Compose fournit PHP-FPM 8.5, MySQL 9.7 LTS, Nginx et Node.js 26. Le code source est monté dans les conteneurs. Nginx sert l'application depuis `public/`, MySQL conserve ses données dans un volume Docker, et Node conserve ses dépendances dans un volume séparé.

### Prérequis

- Docker Engine
- Docker Compose v2 (`docker compose`)

### Installation et démarrage

Depuis la racine du dépôt :

```sh
cp .env.docker.example .env.docker
```

Modifiez les mots de passe d'exemple dans `.env.docker` avant de démarrer. Gardez les valeurs `DB_DATABASE`, `DB_USERNAME` et `DB_PASSWORD` cohérentes avec `MYSQL_DATABASE`, `MYSQL_USER` et `MYSQL_PASSWORD`. `.env.docker` est local et ignoré par Git.

Construisez et démarrez les services :

```sh
docker compose up -d --build
docker compose exec app composer install
docker compose exec node npm install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec node npm run build
```

L'application est ensuite accessible à l'adresse <http://localhost:8080>. Pour les démarrages suivants, utilisez simplement `docker compose up -d`.

### Base de données et données de démonstration

La base est créée au premier démarrage avec les valeurs de `.env.docker`. Depuis le conteneur Laravel, les commandes suivantes permettent de gérer le schéma et les données :

```sh
# Appliquer les migrations
docker compose exec app php artisan migrate

# Afficher l'état des migrations
docker compose exec app php artisan migrate:status

# Ajouter les données de démonstration
docker compose exec app php artisan db:seed
```

`migrate --seed` applique les migrations puis exécute les seeders. Ils créent un compte administrateur, cinq utilisateurs Faker, ainsi que leurs groupes et phrases. Exécutez `db:seed` une seule fois sur une base vide : les seeders ne sont pas conçus pour être rejoués sur une base déjà remplie.

Les valeurs de connexion par défaut de `.env.docker.example` sont :

| Paramètre | Valeur |
| --- | --- |
| Hôte depuis les conteneurs | `db` |
| Port | `3306` |
| Base | `laravel` |
| Utilisateur | `laravel` |
| Mot de passe local d'exemple | `local-dev-password` |

Si ces valeurs sont modifiées dans `.env.docker`, mettez à jour les variables `DB_*` et `MYSQL_*` correspondantes.

Le compte administrateur de démonstration est :

| Identifiant | Valeur |
| --- | --- |
| E-mail | `josselin@skribblio.com` |
| Mot de passe | `josselin` |

Les cinq comptes Faker utilisent le mot de passe `1234`. Ces identifiants sont réservés au développement local ; ne les utilisez pas en production.

Pour ouvrir un client MySQL dans le conteneur :

```sh
docker compose exec db sh -c 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'
```

Le service MySQL n'expose pas de port sur l'hôte. Depuis l'application, son adresse est `db:3306`.

### Commandes courantes

```sh
# État et journaux des services
docker compose ps
docker compose logs -f app nginx db node

# Commandes Laravel et Composer
docker compose exec app php artisan <commande>
docker compose exec app composer <commande>

# Compilation et rechargement des assets avec Vite
docker compose exec node npm run dev
docker compose exec node npm run build
# Installer les dépendances JavaScript (package-lock.json versionné)
docker compose exec node npm install

# Arrêter les conteneurs en conservant la base de données
docker compose down
```

### Données et permissions

Le volume `mysql_data_97` conserve les données MySQL 9.7 après `docker compose down`. `docker compose down -v` supprime ce volume et efface définitivement cette base locale. L'ancien volume MySQL 8 est conservé séparément lors de la migration et n'est pas utilisé par la nouvelle stack.

Sur Linux, PHP-FPM et Node utilisent par défaut l'UID et le GID `1000` afin que les fichiers créés restent accessibles depuis l'hôte. Si votre utilisateur a d'autres identifiants, définissez-les dans le terminal avant de construire et démarrer les conteneurs :

```sh
export DOCKER_UID="$(id -u)"
export DOCKER_GID="$(id -g)"
docker compose up -d --build
```

Les répertoires `storage/` et `bootstrap/cache/` doivent rester accessibles en écriture par PHP-FPM. En cas de montée de version de MySQL, le volume de données existant peut nécessiter une recréation locale ; dans ce cas, réinitialisez le schéma avec `docker compose exec app php artisan migrate --seed` après avoir supprimé le volume avec `docker compose down -v`.
