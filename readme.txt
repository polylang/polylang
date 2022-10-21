=== Polylang ===
Contributors: Chouby, manooweb, raaaahman, marianne38, sebastienserre, greglone, hugod
Donate link: https://polylang.pro
Tags: multilingual, bilingual, translate, translation, language, multilanguage, international, localization
Requires at least: 5.7
Tested up to: 6.1
Requires PHP: 5.6
Stable tag: 3.2.8
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
* Template parts translatable in the Full Site Editing (FSE).
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

Neither of them will allow to do automated translation. Nevertheless, can be isntalled alongside Polylang Pro or Polylang, a third party plugin such as [Lingotek Translation](https://wordpress.org/plugins/lingotek-translation/) which offers a complete translation management system and provides services such as a translation memory or semi-automated translation processes (e.g., machine translation => human translation => legal review).

Migrating from WPML is possible using the plugin [WPML to Polylang](https://wordpress.org/plugins/wpml-to-polylang/).

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

= Do you need translation services? =

* If you want to use professional or automatic translation services, install and activate the [Lingotek Translation](https://wordpress.org/plugins/lingotek-translation/) plugin.

== Screenshots ==

1. The Polylang languages admin panel
2. The Strings translations admin panel
3. Multilingual media library
4. The Edit Post screen with the Languages metabox

== Changelog ==

= 3.3 =

* Requires WP 5.7 as minimum version
* Pro: Allow to export and import XLIFF files for posts
* Pro: Honor the provided context for the navigation language switcher block.
* Pro: Remove the parent hyperlink in the navigation language switcher block.
* Pro: Add spacing between flag and name in the navigation language switcher block.
* Pro: Disallow some special characters in translated slugs to avoid 404 errors.
* Pro: Fix string translation not imported when the original is registered but has never been saved in database.
* Pro: Fix string translation not imported when it includes an html entity.
* Translate the post pages in get_post_type_archive_link() on admin side too. #1000
* Enable the block editor in page for posts translations to match the WordPress behavior since version 5.8 #1002
* Improve the site health report #1062 #1076
* Set the current language when saving a post #1065
* The search block is now filtered by language #1081
* Display slug of CPT and taxonomies in Custom post types and Taxonomies settings. Props @nicomollet #1112
* Fix some deprecated notices fired by PHP 8.1 #975
* Fix some missing canonical redirect taxonomies #1074
* Fix redirect when permalink structure has no trailing slash #1080
* Fix language switcher in legacy navigation menu widget not rendered in widgets block editor #1083
* Fix language in tax query when an OR relation is used #1098
* Fix parent of translated category removed when assigning an untranslated parent #1105
* Yoast SEO: Fix posts without language displayed in the sitemap #1103
* Yoast SEO: Avoid syncing robots meta. #1118

= 3.2.8 (2022-10-17) =

* Fix PHP warning when a filtered taxonomy has no query var #1124
* Fix SQL error when attempting to get objects without languages and no language exist #1126
* Fix error when term slugs are provided as array in WP_Query #1119, #1132 Props Susanna Häggblom
* Fix a CSS regression in the wizard causing the default language icon to be removed #1137

= 3.2.7 (2022-09-20) =

* Work around a WooCommerce 6.9.x bug causing a fatal error in the wizard. #1116

= 3.2.6 (2022-09-06) =

* Pro: Fix a conflict with Kadence blocks
* Pro: Fix a conflict with Flatsome builder
* Fix media translation setting having no effect

= 3.2.5 (2022-06-28) =

* Pro: Fix creation of WC product categories with shared slug via REST API
* Pro: Fix conflict with WooBuilder when editing a WC product
* Fix: Force empty string translation to empty string #1058
* Fix CSS conflict with Dynamic content for Elementor #1060

= 3.2.4 (2022-06-07) =

* Pro: Remove "Navigation menus" from the post type settings list
* Pro: Fix block editor languages panel missing in WordPress 5.6
* Pro: Fix wrongly indexed languages list returned by REST API when the first language is deactivated.
* Revert fix for category feed not redirected when the language code is wrong #1054
* Fix wrong redirect of category when the url includes a query string #1048
* Fix querying multiple categories failing

= 3.2.3 (2022-05-17) =

* Pro: Fix a fatal error when inserting a term
* Pro: Fix translation of the block cover when duplicating a post
* Pro: Fix a CSS issue in bulk tranlate form introduced by WP 6.0
* Pro: Fix a CSS issue in string import/export metaboxes.
* Prevent random languages order in WP 6.0 #1041
* Translate site title in retrieve password email #1042
* Fix 'lang' attribute in language widget dropdown #1039

= 3.2.2 (2022-04-25) =

* Pro: Fix redirect occuring for tags sharing the same slug as their translations
* Fix quick edit allowing to modify the language of the default category when it should not #1018

= 3.2.1 (2022-04-14) =

* Pro: Fix users with editor role not able to save or publish posts
* Pro: Fix FSE compatibility not loaded when the plugin Gütenberg is active
* Pro: Fix a fatal error occuring with Yoast SEO Premium
* Pro: Fix a fatal error with ACF when no language is defined

= 3.2 (2022-04-12) =

* Requires WP 5.6 as minimum version
* Pro: Add compatibility with the full site editing introduced in WP 5.9
* Pro: Add a language switcher block for the navigation block introduced in WP 5.9
* Pro: Add compatibility with the new gallery block introduced in WP 5.9
* Pro: Make the language switcher block available in the widget section of the customizer
* Pro: Fix wrong category when translating the latest posts block
* Pro: Fix the language switcher block when using the dropdown option
* Pro: Fix some edge cases with locale fallback
* Pro: Fix post template replacing the post content when duplicating a post
* Pro: Fix synchronization groups not correctly cleaned up when a language is deleted
* Pro: Fix incorrect sticky property when duplicating / synchronizing posts
* Pro: Fix "Page for posts" label after the page has been bulk translated
* Pro: Fix translated slug when the url includes a query string
* Pro: Synchronize ACF layout fields if a child field is synchronized or translatable
* Pro: Fix wrong field group translation displayed when using object cache with ACF
* Update plugin updater to 1.9.1
* Add compatibility with the block site title introduced in WP 5.9
* Add the list of wpml-config.xml files in the site health information
* Improve the performance of the get_pages() filter #980
* Improve the compatibility of 'wpml_object_id' with the original filter #972
* Prevent term_exists to be filtered by language in WP 6.0
* Fix some PHP 8.1 deprecations #949 #985
* Fix a fatal error in PHP 8.1 #987
* Fix category feed not redirected when the langage code is wrong #887
* Fix default category not created for secondary languages (introduced in 3.1) #997
* Fix parent page when the parent post type is not translatable #1001
* Fix the Yoast SEO breadcrumb when it includes a non-synchronized taxonomy #1005
* Fix a PHP Notice when adding a new language and Yoast SEO is active #979
* Fix a PHP warning in Yoast SEO compatibility #954

See [changelog.txt](https://plugins.svn.wordpress.org/polylang/trunk/changelog.txt) for older changelog
