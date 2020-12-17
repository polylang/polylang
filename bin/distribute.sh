#!/bin/bash

rm -rf vendor/ # Make sure to remove all traces of development dependencies

composer install --no-dev

npm install && npm run build # minify js and css files

rsync -rc --exclude-from=.distignore . polylang/ --delete --delete-excluded
zip -r polylang.zip polylang/*
rm -rf polylang/
