#!/usr/bin/env bash

TMPDIR=${TMPDIR-/tmp}
TMPDIR=$(echo $TMPDIR | sed -e "s/\/$//")
WP_CORE_DIR=${WP_CORE_DIR-$TMPDIR/wordpress/}

WORKING_DIR="$PWD"

download() {
    if [ `which curl` ]; then
        curl -s "$1" > "$2";
    elif [ `which wget` ]; then
        wget -nv -O "$2" "$1"
    fi
}

mkdir -p $TMPDIR/downloads

# Install Twenty Fourteen
download https://downloads.wordpress.org/theme/twentyfourteen.zip $TMPDIR/downloads/twentyfourteen.zip
unzip -q $TMPDIR/downloads/twentyfourteen.zip -d $WP_CORE_DIR/wp-content/themes

# Install WordPress Importer
download https://downloads.wordpress.org/plugin/wordpress-importer.zip $TMPDIR/downloads/wordpress-importer.zip
unzip -q $TMPDIR/downloads/wordpress-importer.zip -d  $TMPDIR/downloads/
mkdir -p $TMPDIR/wordpress-importer
mv $TMPDIR/downloads/wordpress-importer/* $TMPDIR/wordpress-importer/

# Install Jetpack
download https://downloads.wordpress.org/plugin/jetpack.zip $TMPDIR/downloads/jetpack.zip
unzip -q $TMPDIR/downloads/jetpack.zip -d  $TMPDIR/downloads/
mkdir -p $TMPDIR/jetpack
mv $TMPDIR/downloads/jetpack/* $TMPDIR/jetpack/

#Install Yoast SEO
download https://downloads.wordpress.org/plugin/wordpress-seo.zip $TMPDIR/downloads/wordpress-seo.zip
unzip -q $TMPDIR/downloads/wordpress-seo.zip -d  $TMPDIR/downloads/
mkdir -p $TMPDIR/wordpress-seo
mv $TMPDIR/downloads/wordpress-seo/* $TMPDIR/wordpress-seo/

