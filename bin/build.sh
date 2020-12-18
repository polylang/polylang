#!/bin/sh
COMPOSER_COMMAND='install'

if [ "$1" = "-u" ] || [ "$1" = "-update" ]; then
    COMPOSER_COMMAND='update'
fi

echo "Installing PHP packages..."
composer $COMPOSER_COMMAND # Need update to ensure to have the latest version of Polylang dependencies.

echo "Running Polylang build..."
npm install && npm run build

echo "Build done!"

echo "Cleanup" # Discard composer.lock and package-lock.json changings to ensure they'll never be pushed on repository.
git checkout -- composer.lock package-lock.json
