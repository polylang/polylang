=== Polylang ===
Contributors: Chouby, manooweb, raaaahman, marianne38, sebastienserre, greglone, hugod
Donate link: https://polylang.pro
Tags: multilingual, bilingual, translate, translation, language, multilanguage, international, localization
Requires at least: 5.7
Tested up to: 6.2
Requires PHP: 5.6
Stable tag: 3.3.3
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Go multilingual in a simple and efficient way. Keep writing posts, creating categories and post tags as usual while defining the languages all at once.

== Description ==

With Polylang fully integrated to WordPress and using only its built-in core features (taxonomies), keep steady performances on your site and create a multilingual site featuring from just one extra language to 10 or more depending on your needs. There is no limit in the number of languages added and WordPress’ language packs are automatically downloaded when ready.

= Features =

Depending on the type of site you have built or are planning to build, a combination of plugins from the list below might be of interest.
All plugins include a wizard allowing to install them in just a few clicks.

### Polylang

Polylang and [Polylang Pro](https://polylang.pro) share the same core providing features such as:

* Translating posts, pages, media, categories, post tags, custom post types and taxonomies, RSS feeds; RTL scripts are supported.
* The language is either set by the language code in URL, or you can use a different sub-domain or domain per language.
* Automatic copy of categories, post tags and other metas when creating a new post or page translation.
* Translating menus and widgets.
* Customizable language switcher available as a widget or a navigation menu item.
* Compatibility with Yoast SEO

### Polylang Pro

Helps optimizing the time spent translating your site with some very useful extra features such as:

* Better integration in the new Block Editor.
* Language switcher available as a block.
* Language options available in the widget block editor.
* Template parts translatable in the site editor (FSE).
* Duplicate and/or synchronize content across post translations.
* Improved compatibilities with other plugins such as [ACF Pro](https://polylang.pro/doc/working-with-acf-pro/).
* Share the same URL slug for posts or terms across languages.
* [Translate URLs slugs](https://polylang.pro/doc/translating-urls-slugs/) for categories, author bases, custom post types and more...
* **Access to a Premium Support for personalized assistance.**

### Polylang for WooCommerce

[Add-on](https://polylang.pro/downloads/polylang-for-woocommerce/) for the compatibility with WooCommerce which provides features such as:

* Translating WooCommerce pages (shop, check-out, cart, my account), product categories and global attribute terms directly in the WooCommerce interface.
* Translating WooCommerce e-mails and sending them to customers in their language.
* Products metadata synchronization.
* Compatibility with the native WooCommerce CSV import & export tool.
* Compatibility with popular plugins such as WooCommerce Subscriptions, Product Bundles, WooCommerce Bookings, Shipment tracking and more.
* Ability to use the WooCommerce REST API (available with Polylang Pro).
* **Access to a Premium Support for personalized assistance**

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

1. Make sure you are using WordPress 5.7 or later and that your server is running PHP 5.6 or later (same requirement as WordPress itself).
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

= 3.3.3 (2023-04-11) =

* Pro: Adapt the submenu colors of the navigation language switcher block to WP 6.2
* Pro: Fix the dropdown setting in the navigation language switcher block
* Add Amharic, Aragonese and Spanish from Dominican Republic to the list of predefined languages #1248
* Fix a deprecated notice in WP 6.2 when using multiple domains without the Internationalization PHP extension (intl) #1245

= 3.3.2 (2023-03-06) =

* Pro: Add compatibility with FSE changes introduced by WP 6.2
* Pro: Adapt the navigation language switcher block for consistency with WP 6.2
* Only store term ids in taxonomy relationships cache for WP 6.0+. Props @ocean90 #1154
* Remove usage of `get_page_by_title()` deprecated in WP 6.2 #1213
* Fix fatal error if the mu-plugins folder is not readable #1217
* Fix a compatibility issue with plugins not expecting a null 'update_plugins' transient #1224

= 3.3.1 (2023-01-09) =

* Pro: Allow to translate Oembed, URL and Email ACF fields
* Pro: Fix ACF REST API mixing fields
* Pro: Fix ACF compatibility loaded when no language exist
* Pro: Fix headers of exported PO files.
* Pro: Fix spacing in language switcher navigation block preview
* Work around a bug in Sendinblue for WooCommerce causing a fatal error. #1156
* Fix a regression with WooCommerce Product Add-Ons Ultimate. #1186

= 3.3 (2022-11-28) =

* Requires WP 5.7 as minimum version
* Pro: Allow to export and import XLIFF files for posts
* Pro: Honor the provided context for the navigation language switcher block.
* Pro: Remove the parent hyperlink in the navigation language switcher block.
* Pro: Add spacing between flag and name in the navigation language switcher block.
* Pro: Disallow some special characters in translated slugs to avoid 404 errors.
* Pro: Fix string translation not imported when the original is registered but has never been saved in database.
* Pro: Fix string translation not imported when it includes an html entity.
* Pro: Fix navigation language switcher block rendering in block editor.
* Pro: Fix navigation language switcher may be displayed wrong color.
* Translate the post pages in get_post_type_archive_link() on admin side too. #1000
* Enable the block editor in page for posts translations to match the WordPress behavior since version 5.8 #1002
* Improve the site health report #1062 #1076
* Set the current language when saving a post #1065
* The search block is now filtered by language #1081
* Display slug of CPT and taxonomies in Custom post types and Taxonomies settings. Props @nicomollet #1112
* Add support for wpml-config.xml to MU plugins #1140 Props Jeremy Simkins
* Fix some deprecated notices fired by PHP 8.1 #975
* Fix some missing canonical redirect taxonomies #1074
* Fix redirect when permalink structure has no trailing slash #1080
* Fix language switcher in legacy navigation menu widget not rendered in widgets block editor #1083
* Fix language in tax query when an OR relation is used #1098
* Fix parent of translated category removed when assigning an untranslated parent #1105
* Fix is_front_page() when a static front page is not translated #1123
* Yoast SEO: Fix posts without language displayed in the sitemap #1103
* Yoast SEO: Avoid syncing robots meta. #1118

See [changelog.txt](https://plugins.svn.wordpress.org/polylang/trunk/changelog.txt) for older changelog
