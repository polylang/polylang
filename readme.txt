=== Polylang ===
Contributors: Chouby, manooweb, raaaahman, marianne38, sebastienserre, greglone, hugod
Donate link: https://polylang.pro
Tags: multilingual, translate, translation, language, localization
Requires at least: 6.2
Tested up to: 6.7
Requires PHP: 7.0
Stable tag: 3.6.5
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Go multilingual in a simple and efficient way. Keep writing posts and taxonomy terms as usual while defining their languages all at once.

== Description ==

With Polylang fully integrated to WordPress and using only its built-in core features (taxonomies), keep steady performances on your site and create a multilingual site featuring from just one extra language to 10 or more depending on your needs. There is no limit in the number of languages added and WordPress’ language packs are automatically downloaded when ready.

= Features =

Depending on the type of site you have built or are planning to build, a combination of plugins from the list below might be of interest.
All plugins include a wizard allowing to setup them in just a few clicks.

### Polylang

Polylang and [Polylang Pro](https://polylang.pro) share the same core providing features such as:

* Translating posts, pages, media, categories, post tags, custom post types and taxonomies, RSS feeds; RTL scripts are supported.
* The language is either set by the language code in URL, or you can use a different sub-domain or domain per language.
* Automatic copy of categories, post tags and other metas when creating a new post or page translation.
* Translating classic menus and classic widgets. Also accessible with [Site Editor Classic Features](https://wordpress.org/plugins/fse-classic/) in block themes.
* Customizable language switcher available as a classic widget or a classic navigation menu item.
* Compatibility with Yoast SEO.

### Polylang Pro

Helps optimizing the time spent translating your site with some very useful extra features such as:

* Better integration in the new Block Editor.
* Language switcher available as a block.
* Language options available in the widget block editor.
* Template parts translatable in the site editor (FSE).
* Duplicate and/or synchronize content across post translations.
* Improved compatibility with other plugins such as [ACF Pro](https://polylang.pro/doc/working-with-acf-pro/).
* Share the same URL slug for posts or terms across languages.
* [Translate URL slugs](https://polylang.pro/doc/translating-urls-slugs/) for categories, author bases, custom post types and more...
* Machine translation with DeepL.
* Export and import of content in XLIFF format for outsourced professional translation.
* **Access to a Premium Support for personalized assistance.**

### Polylang for WooCommerce

[Add-on](https://polylang.pro/downloads/polylang-for-woocommerce/) for the compatibility with WooCommerce which provides features such as:

* Translating WooCommerce pages (shop, check-out, cart, my account), product categories and global attribute terms directly in the WooCommerce interface.
* Translating WooCommerce e-mails and sending them to customers in their language.
* Products metadata synchronization.
* Compatibility with the native WooCommerce CSV import & export tool.
* Compatibility with popular plugins such as WooCommerce Subscriptions, Product Bundles, WooCommerce Bookings, Shipment Tracking and more.
* Ability to use the WooCommerce REST API (available with Polylang Pro).
* **Access to a Premium Support for personalized assistance.**

Neither of them will allow to do automated translation.

= Our other free plugins =

* [WPML to Polylang](https://wordpress.org/plugins/wpml-to-polylang/) allows migrating from WPML to Polylang.
* [DynaMo](https://wordpress.org/plugins/dynamo/) speeds up the translation of WordPress for all non-English sites.
* [Site Editor Classic Features](https://wordpress.org/plugins/fse-classic/) allows to use classic widgets (including the Polylang language switcher) and menus in the site editor (FSE).

= Credits =

Thanks a lot to all translators who [help translating Polylang](https://translate.wordpress.org/projects/wp-plugins/polylang).
Thanks a lot to [Alex Lopez](http://www.alexlopez.rocks/) for the design of the logo.
Most of the flags included with Polylang are coming from [famfamfam](http://famfamfam.com/) and are public domain.
Wherever third party code has been used, credit has been given in the code’s comments.

== Installation ==

1. Make sure you are using WordPress 6.2 or later and that your server is running PHP 7.0 or later (same requirement as WordPress itself).
1. If you tried other multilingual plugins, deactivate them before activating Polylang, otherwise, you may get unexpected results!
1. Install and activate the plugin as usual from the 'Plugins' menu in WordPress.
1. The [setup wizard](https://polylang.pro/doc/setup-wizard/) is automatically launched to help you get started more easily with Polylang by configuring the main features.

== Frequently Asked Questions ==

= Where to find help ? =

* First time users should read [Polylang - Getting started](https://polylang.pro/doc-category/getting-started/), which explains the basics and includes a lot of screenshots.
* Read the [documentation](https://polylang.pro/doc/). It includes a [FAQ](https://polylang.pro/doc-category/faq/) and the [documentation for developers](https://polylang.pro/doc-category/developers/).
* Search the [community support forum](https://wordpress.org/search/). You will probably find your answers here.
* Read the sticky posts in the [community support forum](http://wordpress.org/support/plugin/polylang).
* If you still have a problem, open a new thread in the [community support forum](http://wordpress.org/support/plugin/polylang).
* [Polylang Pro and Polylang for WooCommerce](https://polylang.pro) users have access to our premium support through helpdesk.

= Is Polylang compatible with WooCommerce? =

* You need [Polylang for WooCommerce](https://polylang.pro/downloads/polylang-for-woocommerce/), premium addon described above, which will make both plugins work together.

== Screenshots ==

1. The Polylang languages admin panel
2. The Strings translations admin panel
3. Multilingual media library
4. The Edit Post screen with the Languages metabox

== Changelog ==

= 3.6.5 (2024-11-05) =

* Add compatibility with WP 6.7
* Pro: Prevent infinite loop when the locale fallbacks reference each other
* Pro: Set canResegment attribute to no in XLIFF files
* Fix empty notice displayed if the plugin upgrade notice is set but empty

= 3.6.4 (2024-07-29) =

* Pro: Fix infinite loop with WP 6.6 when the locale fallbacks include the main locale of a language
* Pro: Prevent saving the main locale among the locale fallbacks of a language
* Pro: Hide the characters consumption graph when the DeepL cost control is deactivated
* Add Yoast SEO social title and social description to the strings translations
* Fix incorrect page on front and page for posts translations when the option is saved with admin language filter active

= 3.6.3 (2024-06-18) =

* Pro: Fix locale fallback for translations loaded just in time (requires WP 6.6)
* Allow to pass an array as context to icl_register_string() #1497
* Fix admin bar search menu in WP 6.6 #1496
* Fix a regression in the usage of the filter pll_flag #1489

= 3.6.2 (2024-06-03) =

* Pro: Fix XLIFF files not correctly imported when exported from older version than 3.6
* Pro: Fix translated categories not assigned to translated post when using machine translation
* Pro: Fix 'lang' param not applied for secondary queries during a REST request
* Pro: Fix newlines for content edited in classic editor and translated with DeepL
* Pro: Fix a conflict with the Stream plugin on multisite

= 3.6.1 (2024-04-09) =

* Pro: Fix ACF fields not shown after a post was translated with DeepL
* Remove rewrite when registering the language taxonomy #1457
* Fix search block not filtered when displayed as button only #1459
* Fix current language not kept when using switch_to_blog() in multisite #1458

= 3.6 (2024-03-18) =

* Requires WP 6.2 as minimum version
* Add compatibility with WP 6.5
* Pro: Add DeepL machine translation for posts
* Pro: Add export and import in XLIFF 2.0/2.1 formats
* Pro: Improve translator comments in exported PO files
* Pro: Allow to export JSON encoded post and term metas in XLIFF files
* Pro: Allow to export block sub-attributes in XLIFF files
* Pro: Add footer notes block to XLIFF files
* Pro: Single files are now exported directly instead of inside a zip
* Pro: Reworked the language switcher navigation block
* Pro: Fix language switcher navigation block justification not aligned with core settings in overlay menu (requires WP 6.5)
* Pro: Fix a race condition which could lead to display a notice to the wrong user
* Pro: Fix a conflict with ACF when rewrite rules are flushed with WP-CLI on a multisite
* Pro: Fix import of several metas with same sources but different translations
* Add filter `pll_cookie_args` to filter the Polylang cookie arguments #1406
* Fix wrong translated post types and taxononies after a `switch_to_blog()` #1415
* Fix a minor performance issue for the page for posts #1412
* Fix a JS errors after quick edit. Props @mcguffin #1435, #1444
* Fix a possible warning in view-translations-post.php #1439

See [changelog.txt](https://plugins.svn.wordpress.org/polylang/trunk/changelog.txt) for older changelog
