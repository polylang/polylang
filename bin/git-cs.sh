#!/bin/bash

# If there is none unstaged or uncommitted files, 'MODIFIED_FILES' will be empty (i.e. PHPCS will analyze all files).
MODIFIED_FILES=$(git ls-files -om --exclude-standard)

if [ ! -z "$MODIFIED_FILES" ]; then
	echo -e "Running PHPCS on files:\n$MODIFIED_FILES"
else
	echo "Running PHPCS on all files."
fi

vendor/bin/phpcs $MODIFIED_FILES
