#!/bin/sh
echo "Installing PHP packages..."
composer install

echo "Installing javascript packages..."
npm install

echo "Running Polylang build..."
npm run build

echo "Build done!"
