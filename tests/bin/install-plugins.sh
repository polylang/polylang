#!/usr/bin/env bash

TMPDIR=${TMPDIR-/tmp}
TMPDIR=$(echo $TMPDIR | sed -e "s/\/$//")


WORKING_DIR="$PWD/tmp"
WP_CORE_DIR=$WORKING_DIR/wordpress

if [[ -d $WP_TESTS_DIR ]]; then
	# We're in CI.
	WORKING_DIR=$WP_TESTS_DIR/..
	WP_CORE_DIR=$WORKING_DIR/wordpress
fi

download() {
    if [ `which curl` ]; then
        curl -s "$1" > "$2";
    elif [ `which wget` ]; then
        wget -nv -O "$2" "$1"
    fi
}

mkdir -p $TMPDIR/downloads

# Install Twenty Fourteen
if [[ ! -f "$WP_CORE_DIR/wp-content/themes/twentyfourteen/style.css" ]]; then
	rm -rf $TMPDIR/downloads/twentyfourteen
	download https://downloads.wordpress.org/theme/twentyfourteen.zip $TMPDIR/downloads/twentyfourteen.zip
	unzip -q $TMPDIR/downloads/twentyfourteen.zip -d $WP_CORE_DIR/wp-content/themes
fi

if [[ -d "$WP_CORE_DIR/wp-content/themes/twentyfourteen/" ]]; then
	echo "ℹ︎ Twenty Fourteen theme has been installed succesfully."
else
	echo "ℹ︎ Twenty Fourteen theme has not been installed."
fi

# Install Twenty Seventeen
if [[ ! -f "$WP_CORE_DIR/wp-content/themes/twentyseventeen/style.css" ]]; then
	rm -rf $TMPDIR/downloads/twentyseventeen
	download https://downloads.wordpress.org/theme/twentyseventeen.zip $TMPDIR/downloads/twentyseventeen.zip
	unzip -q $TMPDIR/downloads/twentyseventeen.zip -d $WP_CORE_DIR/wp-content/themes
fi

if [[ -d "$WP_CORE_DIR/wp-content/themes/twentyseventeen/" ]]; then
	echo "ℹ︎ Twenty Seventeen theme has been installed succesfully."
else
	echo "ℹ︎ Twenty Seventeen theme has not been installed."
fi

# Install WordPress Importer
download https://downloads.wordpress.org/plugin/wordpress-importer.zip $TMPDIR/downloads/wordpress-importer.zip
rm -rf $TMPDIR/downloads/wordpress-importer
unzip -q $TMPDIR/downloads/wordpress-importer.zip -d  $TMPDIR/downloads/
mkdir -p $WORKING_DIR/wordpress-importer
mv $TMPDIR/downloads/wordpress-importer/* $WORKING_DIR/wordpress-importer/

if [[ -d "$WORKING_DIR/wordpress-importer/" ]]; then
	echo "ℹ︎ WordPress Importer plugin has been installed succesfully."
else
	echo "ℹ︎ WordPress Importer plugin has not been installed."
fi

# Install Jetpack
download https://downloads.wordpress.org/plugin/jetpack.zip $TMPDIR/downloads/jetpack.zip
rm -rf $TMPDIR/downloads/jetpack
unzip -q $TMPDIR/downloads/jetpack.zip -d  $TMPDIR/downloads/
mkdir -p $WORKING_DIR/jetpack
mv $TMPDIR/downloads/jetpack/* $WORKING_DIR/jetpack/

if [[ -d "$WORKING_DIR/jetpack/" ]]; then
	echo "ℹ︎ Jetpack plugin has been installed succesfully."
else
	echo "ℹ︎ Jetpack plugin has not been installed."
fi

#Install Yoast SEO
download https://downloads.wordpress.org/plugin/wordpress-seo.zip $TMPDIR/downloads/wordpress-seo.zip
rm -rf $TMPDIR/downloads/wordpress-seo
unzip -q $TMPDIR/downloads/wordpress-seo.zip -d  $TMPDIR/downloads/
mkdir -p $WORKING_DIR/wordpress-seo
mv $TMPDIR/downloads/wordpress-seo/* $WORKING_DIR/wordpress-seo/

if [[ -d "$WORKING_DIR/wordpress-seo/" ]]; then
	echo "ℹ︎ Yoast SEO plugin has been installed succesfully."
else
	echo "ℹ︎ Yoast SEO plugin has not been installed."
fi

#Install Duplicate Post
download https://downloads.wordpress.org/plugin/duplicate-post.zip $TMPDIR/downloads/duplicate-post.zip
rm -rf $TMPDIR/downloads/duplicate-post
unzip -q $TMPDIR/downloads/duplicate-post.zip -d  $TMPDIR/downloads/
mkdir -p $WORKING_DIR/duplicate-post
mv $TMPDIR/downloads/duplicate-post/* $WORKING_DIR/duplicate-post/

if [[ -d "$WORKING_DIR/duplicate-post/" ]]; then
	echo "ℹ︎ Duplicate Post plugin has been installed succesfully."
else
	echo "ℹ︎ Duplicate Post plugin has not been installed."
fi
