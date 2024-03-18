=== Polylang ===
Contributors: Chouby, manooweb, raaaahman, marianne38, sebastienserre, greglone, hugod
Donate link: https://polylang.pro
Tags: multilingual, translate, translation, language, localization
Requires at least: 6.2
Tested up to: 6.5
Requires PHP: 7.0
Stable tag: 3.6
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

= 3.5.4 (2024-02-06) =

* Pro: Fix an accessibility issue int the navigation language switcher block
* Pro: Fix featured image not exported for posts with blocks
* Pro: Fix a conflict with the Flatsome builder
* Fix a notice when using system CRON. Props arielruminski #1397
* Fix an edge case where a wrong post tag may be assigned to a post #1418

= 3.5.3 (2023-12-11) =

* Pro: Fix fatal error with The Events Calendar when rewrite param of event category is set to false
* Remove flag alt text in the language switcher when both the flag and language name are displayed #1393
* Fix incorrect string translations when 2 languages are sharing the same locale in a multisite #1378
* Fix posts lists not filtered by the current language when editing a post in the block editor #1386
* Fix error when a tax query is filled with unexpected data #1396

= 3.5.2 (2023-10-25) =

* Pro: Fix terms not filtered by the current language in the block editor custom taxonomy component panel
* Fix incorrect rewrite rules leading to error 404 for the main site on mutisite #1375

= 3.5.1 (2023-10-17) =

* Pro: Fix terms not filtered by the current language in the block editor custom taxonomy component panel
* Pro: Fix fatal error when using plain permalinks on multisite
* Pro: Fix rewrite rules incorrectly refreshed when saving strings translations
* Fix incorrect rewrite rules leading to error 404 on mutisite #1366
* Fix fatal error when using symlinked MU plugins that are not in open_basedir #1368

= 3.5 (2023-10-09) =

* Requires WordPress 5.9 as minimum version
* Pro: Manage navigation blocks translations in the site editor (requires WP 6.3)
* Pro: Manage pages translations in the site editor (requires WP 6.3)
* Pro: Manage patterns translations in the site editor (requires WP 6.3)
* Pro: Remove compatibility with the navigation screen removed from Gütenberg 15.1
* Pro: Add filter 'pll_export_post_fields' to control post fields exported to XLIFF files
* Pro: Do not set default translation option to "translate" for ACF fields created before Polylang Pro is activated
* Pro: Fix Polylang not set as recently active when automatically deactivated by Polylang Pro
* Don't output javascript type for themes supporting html5 #1332
* Hook WP_Query automatic translation to 'parse_query' instead of 'pre_get_posts' #1339
* Improve preload paths management for the block editor #1341
* Fix rewrite rules in WP 6.4 #1345
* Fix: always assign the default language to new posts and terms if no language is specified #1351
* Fix 'polylang' option not correctly created when a new site is created on a multisite #1319
* Fix front page display switched to "Your latest posts" when deleting a static home page translation #1311
* Fix wrong language assigned to terms #1336
* Fix error when updating a translated option while the blog is switched on a multisite #1342

See [changelog.txt](https://plugins.svn.wordpress.org/polylang/trunk/changelog.txt) for older changelog
