# Skribblio

Outil communautaire pour préparer des parties de Skribbl.io. Il permet de créer des utilisateurs, d'organiser des mots en groupes, de voter pour les mots préférés et d'exporter la liste d'un groupe. L'export est formaté pour être copié directement dans Skribbl.io au début d'une partie.

L'application utilise Laravel 13, PHP 8.5 et Vite pour compiler les fichiers JavaScript et Sass.

## Environnement de développement (Docker)

Tout tourne dans Docker : rien d'autre que Docker n'est à installer sur la machine.

| Service   | Rôle                                                  | Adresse                 |
|-----------|-------------------------------------------------------|-------------------------|
| `app`     | Laravel servi par FrankenPHP (PHP 8.5), Composer, Node 24 | <http://localhost:8000> |
| `mysql`   | MySQL 8.4 LTS, données dans le volume `mysql_data`    | `127.0.0.1:3306`        |
| `mailpit` | Capture les e-mails envoyés par l'application         | <http://localhost:8025> |

Le code du projet est monté dans le conteneur `app` : toute modification faite dans l'éditeur est visible immédiatement, sans reconstruire l'image.

### Première installation

```sh
cp .env.example .env              # puis changez DB_PASSWORD si vous le souhaitez
docker compose up -d --build      # construit l'image et démarre les services
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app npm install
docker compose exec app npm run build
```

Les identifiants `DB_*` du fichier `.env` servent à la fois à Laravel et à la création de la base par le conteneur MySQL. Changez-les **avant** le premier démarrage : MySQL ne les lit qu'à la création du volume.

### Au quotidien

```sh
docker compose up -d                          # démarrer
docker compose exec app npm run dev           # Vite avec rechargement à chaud (Ctrl+C pour arrêter)
docker compose exec app php artisan <cmd>     # commande Artisan
docker compose exec app composer <cmd>        # commande Composer
docker compose exec app vendor/bin/phpunit   # tests (SQLite en mémoire)
docker compose logs -f app                    # suivre les logs
docker compose down                           # arrêter (les données sont conservées)
```

Pour ne pas tout préfixer, ouvrez un shell dans le conteneur avec `docker compose exec app bash`.

### Données

La base est stockée dans le volume Docker `mysql_data` : elle survit à `docker compose down` et à la reconstruction des images. `docker compose down -v` supprime ce volume et **efface définitivement** la base ; recréez-la ensuite avec `docker compose exec app php artisan migrate --seed`.

### Permissions

Le conteneur `app` utilise par défaut l'UID/GID `1000`, pour que les fichiers créés (`vendor/`, `node_modules/`, logs) vous appartiennent. Si `id -u` affiche une autre valeur, ajoutez `UID=…` et `GID=…` dans `.env` puis relancez `docker compose up -d --build`.
