FROM node:26-bookworm-slim

ARG UID=1000
ARG GID=1000

# Le volume nommé "node_modules" est monté sur ce chemin. Docker initialise un
# volume vide à partir du contenu de l'image : en créant le dossier ici avec le
# bon propriétaire, le volume n'appartient plus à root et npm peut y écrire.
RUN mkdir -p /var/www/html/node_modules \
    && chown -R ${UID}:${GID} /var/www/html

WORKDIR /var/www/html
