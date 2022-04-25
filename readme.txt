=== Polylang ===
Contributors: Chouby, manooweb, raaaahman, marianne38, sebastienserre, greglone, hugod
Donate link: https://polylang.pro
Tags: multilingual, bilingual, translate, translation, language, multilanguage, international, localization
Requires at least: 5.6
Tested up to: 5.9
Requires PHP: 5.6
Stable tag: 3.2.2
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Go multilingual in a simple and efficient way. Keep writing posts, creating categories and post tags as usual while defining the language all at once.

== Description ==

With Polylang fully integrated to WordPress and using only its built-in core features (taxonomies), keep steady performances on your site and create a multilingual site featuring from just one extra language to 10 or more depending on your needs. There is no limit in the number of languages added and WordPress’ language packs are automatically downloaded when ready.

= Features =

Depending on the type of site you have built or are planning to build, a combination of plugins from the list below might be of interest:

### Polylang

Polylang and [Polylang Pro](https://polylang.pro) share the same core providing features such as:
* Translating posts, pages, media, categories, post tags, custom post types and taxonomies, RSS feeds; RTL scripts are supported.
* The language is either set by the language code in URL, or you can use a different sub-domain or domain per language.
* Automatic copy of categories, post tags and other metas when creating a new post or page translation.
* Translating menus and widgets.
* Customizable language switcher available as a widget or a navigation menu item.

### Polylang Pro

Helps optimizing the time spent translating your site with some very useful extra features such as:
* Better integration in the new Block Editor.
* Language switcher available as a block.
* Widget block editor and full Site Editing (FSE) compatibility.
* Duplicate and/or synchronize content across post translations.
* Improved compatibilities with other plugins such as [ACF Pro](https://polylang.pro/doc/working-with-acf-pro/).
* Share the same URL slug for posts or terms across languages.
* [Translate the slugs](https://polylang.pro/doc/translating-urls-slugs/) in the URL for category and author bases, custom post types and more...
* **Access to a Premium Support for personalized assistance**

### Polylang for WooCommerce

[Add-on](https://polylang.pro/downloads/polylang-for-woocommerce/) for the compatibility with WooCommerce will provide features such as:
* Translating WooCommerce pages (shop, check-out, cart, my account), product categories and global attribute terms directly in the WooCommerce interface.
* Translating WooCommerce e-mails and sending them to customers in their language.
* Products metadata synchronization.
* Compatibility with the native WooCommerce CSV import & export tool.
* Compatibility with popular plugins such as WooCommerce Subscriptions, Product Bundles, WooCommerce Bookings, Shipment tracking and more.
* Ability to use the WooCommerce REST API (available with Polylang Pro).
* **Access to a Premium Support for personalized assistance**

Neither of them will allow you to do automated translation. Nevertheless, you can install, alongside Polylang Pro or Polylang, a third party plugin such as [Lingotek Translation](https://wordpress.org/plugins/lingotek-translation/) which offers a complete translation management system and provides services such as a translation memory or semi-automated translation processes (e.g., machine translation => human translation => legal review).

If you wish to migrate from WPML, you can use the plugin [WPML to Polylang](https://wordpress.org/plugins/wpml-to-polylang/).

= Credits =

Thanks a lot to all translators who [help translating Polylang](https://translate.wordpress.org/projects/wp-plugins/polylang).
Thanks a lot to [Alex Lopez](http://www.alexlopez.rocks/) for the design of the logo.
Most of the flags included with Polylang are coming from [famfamfam](http://famfamfam.com/) and are public domain.
Wherever third party code has been used, credit has been given in the code’s comments.

== Installation ==

1. Make sure you are using WordPress 5.6 or later and that your server is running PHP 5.6 or later (same requirement as WordPress itself).
1. If you tried other multilingual plugins, deactivate them before activating Polylang, otherwise, you may get unexpected results!
1. Install and activate the plugin as usual from the 'Plugins' menu in WordPress.
1. The [setup wizard](https://polylang.pro/doc/setup-wizard/) is automatically launched to help you get started more easily with Polylang by configuring the main features.

== Frequently Asked Questions ==

= Where to find help ? =

* First time users should read [Polylang - Getting started](https://polylang.pro/doc-category/getting-started/), which explains the basics with a lot of screenshots.
* Read the [documentation](https://polylang.pro/doc/). It includes a [FAQ](https://polylang.pro/doc-category/faq/) and the [documentation for developers](https://polylang.pro/doc-category/developers/).
* Search the [community support forum](https://wordpress.org/search/). You will probably find your answer here.
* Read the sticky posts in the [community support forum](http://wordpress.org/support/plugin/polylang).
* If you still have a problem, open a new thread in the [community support forum](http://wordpress.org/support/plugin/polylang).
* [Polylang Pro and Polylang for WooCommerce](https://polylang.pro) users have access to our helpdesk.

= Is Polylang compatible with WooCommerce? =

* You need [Polylang for WooCommerce](https://polylang.pro/downloads/polylang-for-woocommerce/), a premium addon, to make both plugins work together.

= Do you need translation services? =

* If you want to use professional or automatic translation services, install and activate the [Lingotek Translation](https://wordpress.org/plugins/lingotek-translation/) plugin.

== Screenshots ==

1. The Polylang languages admin panel
2. The Strings translations admin panel
3. Multilingual media library
4. The Edit Post screen with the Languages metabox

== Changelog ==

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

= 3.1.4 (2022-01-31) =

* Pro: Adapt duplication and synchronization of the gallery block refactored in WP 5.9
* Fix UI glitch in the classic editor custom fields form when changing a post language in WP 5.9 #970

= 3.1.3 (2021-12-14) =

* Fix user description escaping #934
* Fix dismissable notice when creating a term in WP 5.9 #936
* Fix empty search not handled correctly. Props Dominik Schilling #937
* Fix warning occurring when a 3rd party plugin attempts to register anything else than a string using the WPML API #942
* Fix Yoast SEO columns not corectly drawn when quick editing a post #943

= 3.1.2 (2021-10-11) =

* Pro: Fix parent page not filtered by language in the block editor since WP 5.6
* Pro: Fix XLIFF mime type for PHP 7.0 and PHP 7.1
* Fix settings page displaying the media modules whne no language are defined
* Enforce Yoast SEO to use dynamic permalinks #882
* Yoast SEO: Fix static front page and blog page breadcrumb

= 3.1.1 (2021-08-16) =

* Pro: Fix a fatal error with The Events Calendar
* Allow to remove the cookie with the pll_cookie_expiration filter #905

= 3.1 (2021-07-27) =

* Add compatibility with WordPress 5.8
* Raise Minimum WordPress version to 5.4
* Pro: Allow to filter blocks by language in the widget block editor
* Pro: Allow to export and import XLIFF files for string translations
* Pro: Add the language switcher in the navigation block (experimental)
* Pro: Replace dashicons by svg icons in the block editor
* Pro: The Events Calendar: Add compatibility with Views V2 (only for sites using only one domain)
* Pro: Fix + icon displayed in the block editor sidebar when the user cannot create a translation
* Add a warning section to the site health for posts and terms without languages #825
* Require the simplexml extension in the site health if a wpml-config.xml is found #827
* Remove the information about the WPML compabitility mode in settings #843
* The browser preferred language detection is now deactivated by default
* The media are now untranslated by default
* Highlight the language filter in the admin toolbar when it's active #821
* Allow to query comments in multiple languages (just as posts and terms) #840
* Don't disable the translation input field in the classic metabox #841 Props Onatcer
* Optimize all images including flags #848 Props lowwebtech
* Don't redirect if WordPress doesn't validate the redirect url to avoid redirects to /wp-admin/ #879
* Fix media appearing to have a language after the language is changed in the media library grid view  #807
* Fix media not all deleted when bulk deleting from the grid view of the media library #830
* Fix when more than one language switcher are added to the same menu #853
* Fix PHP notice when adding a CPT archive link to a menu #868 Props davidwebca

See [changelog.txt](https://plugins.svn.wordpress.org/polylang/trunk/changelog.txt) for older changelog
