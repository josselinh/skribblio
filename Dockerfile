# Image de développement de Skribblio.
#
# Elle contient les outils nécessaires au projet, mais pas son code :
# le code est monté depuis l'hôte (voir compose.yaml), ce qui permet de
# voir ses modifications immédiatement, sans reconstruire l'image.
#
# - FrankenPHP : serveur web + PHP 8.5 en un seul binaire (pas de Nginx/PHP-FPM)
# - Composer   : dépendances PHP
# - Node.js    : dépendances JavaScript et Vite (Tailwind, Alpine.js)

FROM dunglas/frankenphp:1-php8.5

# Extensions PHP utilisées par Laravel et le projet.
# "install-php-extensions" est fourni par l'image de base et installe
# automatiquement les bibliothèques système dont chaque extension a besoin.
RUN install-php-extensions \
    bcmath \
    intl \
    opcache \
    pcntl \
    pdo_mysql \
    zip

# Réglages PHP adaptés au développement (affichage des erreurs, etc.).
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Composer et Node.js sont copiés depuis leurs images officielles :
# c'est plus rapide et plus fiable que de les installer à la main.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --from=node:24-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:24-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# Utilisateur non-root ayant le même UID/GID que vous sur l'hôte :
# les fichiers créés dans le conteneur (vendor/, node_modules/, logs…)
# vous appartiennent, sans problème de permissions.
# L'image de base définit XDG_CONFIG_HOME=/config et XDG_DATA_HOME=/data :
# Caddy, mais aussi Tinker (PsySH), y écrivent. L'utilisateur doit en être propriétaire.
ARG UID=1000
ARG GID=1000
RUN groupadd --gid "${GID}" app \
    && useradd --uid "${UID}" --gid app --create-home app \
    && chown -R app:app /config /data

USER app
WORKDIR /app

# FrankenPHP écoute sur le port 8000 en HTTP simple (pas de HTTPS en local).
ENV SERVER_NAME=":8000"
EXPOSE 8000 5173
