#!/usr/bin/env bash

. "$PWD/vendor/wpsyntex/wp-phpunit/bin/wp-download-tools.sh"

mkdir -p $WP_PLUGINS_DIR
mkdir -p $WP_THEMES_DIR

# Install TwentyFourteen.
downloadThemeFromRepository twentyfourteen

# Install TwentySeventeen.
downloadThemeFromRepository twentyseventeen

# Install WordPress Importer.
downloadPluginFromRepository wordpress-importer

# Install Jetpack.
downloadPluginFromRepository jetpack

# Install Yoast SEO.
downloadPluginFromRepository wordpress-seo

# Install Duplicate Post.
downloadPluginFromRepository duplicate-post
