#!/bin/sh
set -e

echo ">>> Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction

echo ">>> Checking if exercises need seeding..."
COUNT=$(php bin/console doctrine:query:dql "SELECT COUNT(e.id) FROM App\\Entity\\Exercise e" 2>/dev/null | grep -Eo '[0-9]+' | tail -1)
if [ -z "$COUNT" ] || [ "$COUNT" = "0" ]; then
    echo ">>> Seeding exercises..."
    php bin/console doctrine:fixtures:load --no-interaction
else
    echo ">>> Exercises already seeded ($COUNT found), skipping."
fi

echo ">>> Starting PHP server on port ${PORT:-8080}..."
exec php -d variables_order=EGPCS -S 0.0.0.0:${PORT:-8080} public/index.php
