#!/bin/sh
set -e

# Render fournit le port d'écoute via $PORT (10000 par défaut).
PORT="${PORT:-10000}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Migrations de base de données (idempotentes). On ne bloque pas le démarrage si échec.
if php migrations/run.php; then
  echo "[entrypoint] Migrations OK."
else
  echo "[entrypoint] ATTENTION: migrations en échec (vérifier les variables DB)."
fi

exec apache2-foreground
