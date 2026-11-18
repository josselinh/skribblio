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
- Export d'une liste dans un format prêt à coller dans Skribbl.io

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

## Architecture

L'application est découpée en deux parties qui vivent dans le même dépôt :

- **Le front-end** est une application monopage (SPA) Vue 3 écrite en TypeScript, dans `resources/js`. Toute l'interface est rendue côté navigateur ; Vue Router gère la navigation.
- **Le back-end** Laravel est une API JSON. Il ne génère pas de pages : pour n'importe quelle URL du front, il renvoie la même vue `resources/views/app.blade.php`, qui ne fait que charger la SPA.

```
Navigateur ──► Laravel sert app.blade.php ──► la SPA Vue démarre
     │
     └── appels axios ──► /login, /register… (Fortify)
                     └──► /api/...           (API de l'application)
```

### Accès à l'application

Tout est privé : pour voir quoi que ce soit, il faut être **inscrit, connecté et avoir validé son adresse e-mail** (elle sert à envoyer des notifications). Une fois connecté, l'utilisateur arrive sur `/dashboard`.

- Les routes de l'API (`routes/api.php`) sont protégées par les middlewares `auth:sanctum` et `verified`. Seule `GET /api/user` est accessible avant la validation de l'e-mail, pour que la SPA sache où rediriger l'utilisateur.
- Côté front, le routeur (`resources/js/router/index.ts`) fait les mêmes vérifications avant chaque page et redirige vers la connexion ou vers la page « confirmez votre e-mail ».

### Authentification

- **Sanctum en mode SPA** : l'authentification repose sur le cookie de session de Laravel et sa protection CSRF, pas sur des tokens. Le front et l'API partagent le même domaine.
- **Fortify** fournit les routes d'inscription, de connexion, de déconnexion, de mot de passe oublié et de validation d'e-mail. Ses vues sont désactivées : les écrans correspondants sont des pages Vue.
- Laravel génère des redirections et des liens dans les e-mails vers les routes nommées `login`, `dashboard`, `password.reset` et `verification.notice`. Elles sont donc déclarées dans `routes/web.php` et pointent vers la SPA.

### Base de données

Toutes les clés primaires sont des **UUID** (v7, triables) plutôt que des entiers auto-incrémentés : trait `HasUuids` sur les modèles, `uuid('id')->primary()` et `foreignUuid()` dans les migrations.

### Modèle de données

```
User ──crée──► Word, WordGroup, GroupCollection

WordGroup 1 ──── n Word              un mot appartient à un seul groupe
GroupCollection n ──── n WordGroup   table pivot groups_collections
User n ──── n WordGroup              favoris : favorite_word_groups
User n ──── n GroupCollection        favoris : favorite_group_collections
```

- **Mot** (`words`) : un nom, unique dans son groupe. Un mot n'a pas de visibilité propre : il est visible par ceux qui voient son groupe.
- **Groupe** (`word_groups`) et **collection** (`group_collections`, modèle `GroupCollection`) : un nom et une visibilité, `public` (visible par tous les utilisateurs connectés) ou `private` (visible par son créateur uniquement).
- Une collection contient **au moins un groupe**. Un groupe peut appartenir à plusieurs collections.

Règles d'accès (définies dans `app/Policies`) :

- Seul le créateur peut modifier ou supprimer un groupe ou une collection.
- Tout le monde peut ajouter des mots à un groupe public. Un mot peut être modifié ou supprimé par son auteur ou par le créateur du groupe. Ajouter, renommer ou supprimer un mot met à jour la date de modification du groupe.
- Un groupe privé auquel on n'a pas accès répond « introuvable » (404), pour ne pas révéler son existence.
- Le créateur d'une collection peut y ajouter ses groupes et les groupes publics des autres utilisateurs. On ne peut pas retirer le dernier groupe d'une collection. Ajouter ou retirer un groupe met à jour la date de modification de la collection.
- Une collection privée, comme un groupe privé, répond « introuvable » (404) aux autres utilisateurs.
- Une collection publique peut contenir des groupes privés. Les autres utilisateurs ne les voient pas, mais un message leur indique que certains groupes leur sont masqués.
- On peut mettre en favori tout groupe ou toute collection que l'on peut voir. Les favoris sont listés sur le tableau de bord. Si un favori devient privé, il y reste affiché mais grisé et non cliquable ; on peut toujours le retirer des favoris.
- Supprimer un utilisateur supprime tout ce qu'il a créé. Supprimer un groupe supprime ses mots et le retire des collections. Supprimer une collection ne supprime pas ses groupes.

### Partage des groupes et collections privés

1. Sur un groupe ou une collection **privé**, le créateur clique sur « Partager » et copie le lien (`/share/…`). Il peut régénérer ce lien : l'ancien ne fonctionne alors plus.
2. La personne qui ouvre le lien (après connexion ou inscription) envoie une demande d'accès. Le créateur est prévenu par e-mail.
3. Le créateur accepte ou refuse la demande depuis la page de l'élément ou son tableau de bord. Le demandeur est prévenu par e-mail. Le créateur peut retirer un accès à tout moment.

Règles :

- Une personne autorisée sur un groupe privé peut le voir et y ajouter des mots.
- Une personne autorisée sur une collection privée voit aussi les groupes privés qu'elle contient, **mais uniquement ceux du créateur de la collection** : partager une collection ne donne jamais accès aux groupes d'autres personnes.
- Sur une collection, un bouton permet de demander en une fois l'accès à tous les groupes privés masqués ; leurs noms ne sont pas révélés au demandeur.
- Les e-mails partent par la file d'attente : lancez `./vendor/bin/sail artisan queue:work` en local.

### Organisation du front-end

| Dossier                    | Contenu                                                              |
| -------------------------- | -------------------------------------------------------------------- |
| `resources/js/pages`       | Les pages, une par route (`pages/auth` pour l'authentification)      |
| `resources/js/components`  | Les composants réutilisables (champs de formulaire, boutons…)        |
| `resources/js/api`         | Les fonctions d'appel à l'API, un fichier par ressource (`wordGroups.ts`…) |
| `resources/js/stores`      | Les stores Pinia (utilisateur connecté et actions d'authentification) |
| `resources/js/composables` | La logique réutilisable, dont `useForm` pour les formulaires          |
| `resources/js/router`      | Les routes et leurs protections (`requiresVerified`, `guestOnly`…)    |
| `resources/js/lib`         | Le client HTTP partagé (axios configuré pour Sanctum)                 |
| `resources/js/types`       | Les types TypeScript qui reflètent les réponses de l'API             |

Chaque réponse de l'API passe par une API Resource Laravel (`app/Http/Resources`) et a son interface TypeScript équivalente dans `resources/js/types`.

### Traductions

L'application est en français (`APP_LOCALE=fr`). Les traductions de Laravel (validation, e-mails, mots de passe…) sont dans `lang/` ; elles proviennent de Laravel Lang et se mettent à jour avec `./vendor/bin/sail artisan lang:update`.

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
./vendor/bin/sail npm run type-check  # Vérifier les types TypeScript
./vendor/bin/sail npm run build    # Vérifier les types puis compiler le front-end
./vendor/bin/sail shell            # Ouvrir un shell dans le conteneur
```

Astuce : ajoutez un alias pour taper simplement `sail` au lieu de `./vendor/bin/sail` :

```sh
alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'
```
