=== Polylang ===
Contributors: Chouby, manooweb, raaaahman, marianne38, sebastienserre, greglone, hugod
Donate link: https://polylang.pro
Tags: multilingual, bilingual, translate, translation, language, multilanguage, international, localization
Requires at least: 5.8
Tested up to: 6.2
Requires PHP: 7.0
Stable tag: 3.4.5
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Go multilingual in a simple and efficient way. Keep writing posts, creating categories and post tags as usual while defining the languages all at once.

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
* Translating menus and widgets.
* Customizable language switcher available as a widget or a navigation menu item.
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
* Compatibility with popular plugins such as WooCommerce Subscriptions, Product Bundles, WooCommerce Bookings, Shipment tracking and more.
* Ability to use the WooCommerce REST API (available with Polylang Pro).
* **Access to a Premium Support for personalized assistance.**

Neither of them will allow to do automated translation.

= Our other free plugins =

* [WPML to Polylang](https://wordpress.org/plugins/wpml-to-polylang/) allows migrating from WPML to Polylang.
* [DynaMo](https://wordpress.org/plugins/dynamo/) speeds up the translation of WordPress for all non-English sites.
* [Site Editor Classic Features](https://wordpress.org/plugins/fse-classic/) allows to use legacy widgets (including the Polylang language switcher) and menus in the site editor (FSE).

= Credits =

Thanks a lot to all translators who [help translating Polylang](https://translate.wordpress.org/projects/wp-plugins/polylang).
Thanks a lot to [Alex Lopez](http://www.alexlopez.rocks/) for the design of the logo.
Most of the flags included with Polylang are coming from [famfamfam](http://famfamfam.com/) and are public domain.
Wherever third party code has been used, credit has been given in the code’s comments.

== Installation ==

1. Make sure you are using WordPress 5.8 or later and that your server is running PHP 7.0 or later (same requirement as WordPress itself).
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

= 3.4.5 (2023-08-07) =

* Requires PHP 7.0 as minimum version
* Pro: Fix error in site editor with WP 6.3
* Pro: Remove usage of block_core_navigation_submenu_build_css_colors() deprecated in WP 6.3
* Pro: Fix categories and tags kept in old language after the language of a post has been changed
* Add 'pll_admin_ajax_params' filter #1326
* Fix error when changing the language of a post and the post type doesn't support excerpts #1323

= 3.4.4 (2023-07-18) =

* Pro: Register a default (empty) value for the "lang" param when listing posts and terms in REST API
* Pro: Fix categories list refresh when the language of a post is changed in the block editor
* Pro: Fix store "pll/metabox" is already registered
* Add Kirghiz to the predefined list of languages #1308
* Fix incorrect flag url when WordPress is installed in a subfolder #1296
* Fix wrong home page url in multisite #1300

= 3.4.3 (2023-06-13) =

* Adapt the language filter for `get_pages()` for WP 6.3 #1268
* Fix static front page displaying latest posts when it is not translated #1295
* Fix a database error in ANSI mode #1297
* Fix a database error when accessing posts from another site in multisite #1301

= 3.4.2 (2023-05-30) =

* Fix empty languages displayed when Falang data are remaining in the database #1286
* Fix PHP warning on term_props #1288
* Fix blog page displayed in the customizer instead of the static front page when changing a setting #1289

= 3.4.1 (2023-05-25) =

* Fix incorrect site titles in My Site admin bar menu on multisites #1284
* Fix incorrect home url when using multiple domains or subdomain and a static front page #1285

= 3.4 (2023-05-23) =

* Requires WP 5.8 as minimum version
* Pro: Language fallbacks are now stored in language description instead of a term meta.
* Pro: Add more error messages when doing wrong when importing or exporting translations
* Pro: Avoid to check for translations files existence if no language fallbacks are defined.
* Pro: Reduce the number of DB queries when exporting posts for translation
* Pro: Fix incorrect post slug after XLIFF import
* Pro: Fix a performance issue with the autocomplete field in the block editor languages panel
* Pro: Fix translations not refreshed when switching the language in the block editor sidebar
* Pro: Fix a performance issue in Site editor
* Pro: Fix a possible bug in Site editor when language term_id and term_taxonomy_id are different
* Pro: Fix deactivated language re-activated when it is edited.
* Pro: Fix language switcher in legacy widget menu not correctly rendered in widget block editor
* Pro: Fix error 404 for untranslated attached attachement
* Pro: Fix a deprecated notice in ACF integration
* Pro: Fix update compatibility with WP Umbrella
* Refactor core to allow to easily translate contents stored in custom tables
* Strings translations are now stored in a language term meta instead of post meta of specific post type #1209
* Deprecate the filters `pll_languages_list` and `pll_after_languages_cache` #1210
* Add a new property `PLL_Language::$is_default` #1228
* Add a custom admin body class `pll-lang-{$language_code}` #1190
* Add support for new WPML API filters #1266
* Fix languages metabox autocomplete field not always returning expected results #1187
* Fix language not displayed if the transient has been saved with an empty array #1247
* Fix a PHP warning `Attempt to read property "home_url" on bool` #1206
* Fix a conflict leading to a performance issue when translating the theme Astra options #1196
* Fix related translations resetted when updating Yoast SEO titles settings #1111
* Fix a fatal error in case the registered strings option is corrupted #1264
* Fix the language extraction from the URL in plain permalinks #1270
* Fix content cleared when switching the language of a new post in the block editor #1272
* Fix: Prevent saving strings translations with an empty source #1273

See [changelog.txt](https://plugins.svn.wordpress.org/polylang/trunk/changelog.txt) for older changelog
