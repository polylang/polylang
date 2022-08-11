#!/bin/bash

# If there is none unstaged or uncommitted files, 'MODIFIED_FILES' will be empty (i.e. PHPCS will analyze all files).
MODIFIED_FILES=$(git ls-files -om --exclude-standard)

vendor/bin/phpcs $MODIFIED_FILES
