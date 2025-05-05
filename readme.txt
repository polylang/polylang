=== Polylang ===
Contributors: Chouby, manooweb, raaaahman, marianne38, sebastienserre, greglone, hugod
Donate link: https://polylang.pro
Tags: multilingual, translate, translation, language, localization
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 7.2
Stable tag: 3.7.1
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

= Our other free plugins =

* [WPML to Polylang](https://wordpress.org/plugins/wpml-to-polylang/) allows migrating from WPML to Polylang.
* [Site Editor Classic Features](https://wordpress.org/plugins/fse-classic/) allows to use classic widgets (including the Polylang language switcher) and menus in the site editor (FSE).

= Credits =

Thanks a lot to all translators who [help translating Polylang](https://translate.wordpress.org/projects/wp-plugins/polylang).
Thanks a lot to [Alex Lopez](http://www.alexlopez.rocks/) for the design of the logo.
Most of the flags included with Polylang are coming from [famfamfam](http://famfamfam.com/) and are public domain.
Wherever third party code has been used, credit has been given in the code’s comments.

== Installation ==

1. Make sure you are using WordPress 6.2 or later and that your server is running PHP 7.2 or later (same requirement as WordPress itself).
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

= 3.7.1 (2025-05-05) =

* Pro: Do not display ACF fileds translations settings when language location is set.
* Pro: Fix ACF taxonomy field terms not synchronized when "Save Terms" and "load terms" settings are activated.
* Pro: Fix duplicate options when using numeric keys for ACF choice fields.

= 3.7 (2025-04-22) =

* Requires PHP 7.2 as minimum version
* Pro: Add DeepL machine translation for strings
* Pro: Consider Polylang Pro as equivalent to Polylang for plugin dependencies
* Pro: Add the details block and several labels in other blocks to XLIFF files and machine translation
* Pro: Allow to translate metas stored as objects
* Pro: Enhanced multilingual support of archive template hierarchy
* Pro: Wrap the language switcher block in a nav tag
* Pro: Support automatic IDs translation in blocks with new filters `pll_sync_block_rules_for_attributes` and `pll_sync_blocks_xpath_rules`
* Pro: Complete rewrite of ACF integration
* Pro: Add support for ACF blocks, post types and taxonomies.
* Pro: Remove the possibility to translate ACF field groups
* Pro: Add languages in ACF locations
* Pro: Add translation of ACF labels in the strings translations page
* Pro: Fix incorrect count of translated strings when importing strings translations
* Pro: Fix incorrect translation when an XLIFF import updates a term sharing its slug
* Pro: Fix term hierarchy with machine translation
* Pro: Fix indented items of a list block not translated with machine translation
* Pro: Fix navigation block inserted in the wrong language
* Update plugin updater to 1.9.4
* Add translation of widgets custom html in strings translations #1423
* Refactor core to manage the plugin options in an object #1451
* Refactor core to give access to languages management in all contexts #1503
* Remove the language set from the content option for new installs #1517
* Allow numbers in language codes #1546
* Display empty fields in the translations table for untranslated strings (instead of duplicating the original) #1574
* Add REST API endpoints to manage options and languages #1505 #1569
* Improve performance by registering the language taxonomy only once #1359
* Add new API functions to insert and update posts and terms in a given language #1500 #1520
* Add compatibility with jQuery 4 (planned in core for WP 6.8) #1612
* Fix translations not loaded when the language is set from the content #1395
* Fix possible term duplication #1490
* Fix sanitization of translated options that may impact other strings #1571
* Fix home link block not translated #1647
* Fix a conflict with WooCommerce Price Based on Country #1638

See [changelog.txt](https://plugins.svn.wordpress.org/polylang/trunk/changelog.txt) for older changelog
