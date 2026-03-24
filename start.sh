#!/bin/bash
echo "Starting database..."
docker compose up -d database

echo "Starting Symfony server..."
symfony serve --allow-all-ip --no-tls
