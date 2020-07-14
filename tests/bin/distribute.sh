#!/bin/bash

rm -rf vendor/ # Make sure to remove all traces of development dependencies

if [[ -e "composer.lock" ]]; then
	composer update --no-dev
else
	composer install --no-dev
fi

rsync -rc --exclude-from=.distignore . polylang/ --delete --delete-excluded
zip -r polylang.zip polylang/*
rm -rf polylang/
