=== Polylang ===
Contributors: Chouby, manooweb, raaaahman, marianne38, sebastienserre, greglone, hugod
Donate link: https://polylang.pro
Tags: multilingual, translate, translation, language, localization
Requires at least: 6.5
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 3.8.3
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
* Customizable language switchers available as blocks, classic widget or classic navigation menu item.
* Compatibility with Yoast SEO.

### Polylang Pro

Helps optimizing the time spent translating your site with some very useful extra features such as:

* Better integration in the new Block Editor.
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

1. Make sure you are using WordPress 6.5 or later and that your server is running PHP 7.4 or later (same requirement as WordPress itself).
1. If you tried other multilingual plugins, deactivate them before activating Polylang, otherwise, you may get unexpected results!
1. Install and activate the plugin as usual from the 'Plugins' menu in WordPress.
1. The [setup wizard](https://polylang.pro/documentation/support/getting-started/setup-wizard/) is automatically launched to help you get started more easily with Polylang by configuring the main features.

== Frequently Asked Questions ==

= Where to find help ? =

* First time users should read [Polylang - Getting started](https://polylang.pro/documentation/support/getting-started/), which explains the basics and includes a lot of screenshots.
* Read the [documentation](https://polylang.pro/documentation/support/). It includes a [FAQ](https://polylang.pro/documentation/support/faq/) and the [documentation for developers](https://polylang.pro/documentation/support/developers/).
* Search the [community support forum](https://wordpress.org/search/). You will probably find your answers here.
* Read the sticky posts in the [community support forum](http://wordpress.org/support/plugin/polylang).
* If you still have a problem, open a new thread in the [community support forum](http://wordpress.org/support/plugin/polylang).
* [Polylang Pro and Polylang for WooCommerce](https://polylang.pro) users have access to our premium support through helpdesk.

= Is Polylang compatible with WooCommerce? =

* You need [Polylang for WooCommerce](https://polylang.pro/pricing/polylang-for-woocommerce/), premium addon described above, which will make both plugins work together.

= Where do I report security bugs found in this plugin? =

* Please report security bugs found in the source code of the Polylang plugin through the [Patchstack Vulnerability Disclosure Program](https://patchstack.com/database/vdp/d83984d2-d748-43e3-88e2-6dd3bd2c881d). The Patchstack team will assist you with verification, CVE assignment, and notify the developers of this plugin.

== Screenshots ==

1. The Polylang languages admin panel
2. The Strings translations admin panel
3. Multilingual media library
4. The Edit Post screen with the Languages metabox

== Changelog ==

= 3.8.3 (2026-04-27) =

* Pro: Improve performance of the languages sidebar in the block editor #2989
* Pro: Fix impossibility to add more than one translation at once from the block editor sidebar #2985
* Pro: Fix HTML entities displayed in translation inputs in the block editor sidebar #2984
* Pro: Fix error when editing a pattern from the post editor #2990
* Pro: Fix sync icon incorrectly shown as active on new posts #2995
* Pro: Fix `pll_sync_post` REST API field returning an empty array instead of an ampty object #2995
* Pro: Fix ACF fields in a repeater nested in a flexible content overwritten with wrong values #2975
* Fix language switcher block error in Widget screen #1856
* Fix PHP warning in site health #1862
* Fix wrong cookie sent in some cases #1863
* Fix error with WordPress Importer when the imported file has synchronized posts #1853

= 3.8.2 (2026-04-07) =

* Pro: Fix refresh issues in block editor languages panel #2965
* Pro: Fix error when unlinking 2 translations in block editor #2970
* Pro: Fix fatal error when a repeater is previously created in ACF Pro but ACF is active #2972
* Fix settings redirect in Playground #1840
* Fix possible fatal error when the default language has been corrupted in DB #1843

= 3.8.1 (2026-03-19) =

* Fix fatal error when the cache is suspended #1837 #1839

= 3.8 (2026-03-17) =

* Requires PHP 7.4 and WP 6.5 as minimum version
* Pro: Use autonomous updater #2475
* Pro: Add capabilities allowing to control permissions per language
* Pro: Add capabilities to control access to languages and strings translations
* Pro: Allow to manage languages and settings with WP CLI #2653
* Pro: Add smart duplication in site editor #2559
* Pro: Add support for DeepL glossaries #2687
* Pro: Add locale fallback support to machine translation #2685
* Pro: Add Support encoding for block attributes in wpml-config.xml #1683, #2660
* Pro: Improve extensibility of the block editor languages panel #2553
* Pro: Add support for new blocks introduced in WP 7.0 #2933 #2941
* Pro: Hide language selector on inner blocks in Widget Block Editor #2900
* Pro: Fix page template not copied when creating a new page translation in the block editor #2581
* Pro: Fix SVG icons lost when using machine translation
* Pro: Fix a performance issue with the translation of ACF labels #2670
* Pro: Fix product status wrongly modified when updating a product with the REST API #2540
* Pro: Fix inactive languages not displayed when they should in REST API #2791
* Pro: Fix translation of ACF repeaters with pagination #2674
* Pro: Fix ACF's layout fields instructions #2889
* Pro: Fix ACF's taxonomy fields not synchronized #2948
* Pro: Fix event dates of The Events Calendar not duplicated #2894
* Add language switcher block and navigation language switcher block #1811
* Add support for multiple encodings in wpml-config.xml #1679, #2655
* Enforce transients in DB and in object cache are synchronized #1653, #2598
* Enforce passing all translations when saving the translations of a post or term #1690
* Hide the admin language filter when editing posts #1698
* Improve performance by preventing DB queries to fetch the language terms #1650
* Fix edge cases where the translation group could be corrupted #1690
* Fix a fatal error on multisite when saving a post after having switched to a site without Polylang #1700
* Fix a fatal error when translating a custom table #1730
* Fix warning when requesting a non-existing language #1665
* Fix strings translations import with WP Importer #1637
* Fix edge cases leading to wrong languages order #1777
* Fix a fatal error with the plugin Groups #1834

See [changelog.txt](https://plugins.svn.wordpress.org/polylang/trunk/changelog.txt) for older changelog
