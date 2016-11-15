=== Polylang ===
Contributors: Chouby
Donate link: https://polylang.pro
Tags: multilingual, bilingual, translate, translation, language, multilanguage, international, localization
Requires at least: 4.0
Tested up to: 4.7
Stable tag: 2.0.9
License: GPLv2 or later

Making WordPress multilingual

== Description ==

= Features  =

Polylang allows you to create a bilingual or multilingual WordPress site. You write posts, pages and create categories and post tags as usual, and then define the language for each of them. The translation of a post, whether it is in the default language or not, is optional.

* You can use as many languages as you want. RTL language scripts are supported. WordPress languages packs are automatically downloaded and updated.
* You can translate posts, pages, media, categories, post tags, menus, widgets...
* Custom post types, custom taxonomies, sticky posts and post formats, RSS feeds and all default WordPress widgets are supported.
* The language is either set by the content or by the language code in url, or you can use one different subdomain or domain per language
* Categories, post tags as well as some other metas are automatically copied when adding a new post or page translation
* A customizable language switcher is provided as a widget or in the nav menu
* The admin interface is of course multilingual too and each user can set the WordPress admin language in its profile

> The author does not provide support on the wordpress.org forum. Support and extra features are available to [Polylang Pro](https://polylang.pro) users.

If you wish to migrate from WPML, you can use the plugin [WPML to Polylang](https://wordpress.org/plugins/wpml-to-polylang/)

If you wish to use a professional or automatic translation service, you can install [Lingotek Translation](https://wordpress.org/plugins/lingotek-translation/), as an addon of Polylang. Lingotek offers a complete translation management system which provides services such as translation memory or semi-automated translation processes (e.g. machine translation > human translation > legal review).

= Credits =

Thanks a lot to all translators who [help translating Polylang](https://translate.wordpress.org/projects/wp-plugins/polylang).
Thanks a lot to [Alex Lopez](http://www.alexlopez.rocks/) for the design of the banner and the logo.
Most of the flags included with Polylang are coming from [famfamfam](http://famfamfam.com/) and are public domain.
Wherever third party code has been used, credit has been given in the code’s comments.

= Do you like Polylang? =

Don't hesitate to [give your feedback](http://wordpress.org/support/view/plugin-reviews/polylang#postform).

== Installation ==

1. Make sure you are using WordPress 4.0 or later and that your server is running PHP 5.2.4 or later (same requirement as WordPress itself)
1. If you tried other multilingual plugins, deactivate them before activating Polylang, otherwise, you may get unexpected results!
1. Install and activate the plugin as usual from the 'Plugins' menu in WordPress.
1. Go to the languages settings page and create the languages you need
1. Add the 'language switcher' widget to let your visitors switch the language.
1. Take care that your theme must come with the corresponding .mo files (Polylang automatically downloads them when they are available for themes and plugins in this repository). If your theme is not internationalized yet, please refer to the [Theme Handbook](https://developer.wordpress.org/themes/functionality/internationalization/) or ask the theme author to internationalize it.

== Frequently Asked Questions ==

= Where to find help ? =

* First time users should read [Polylang - Getting started](https://polylang.pro/doc-category/getting-started/), which explains the basics with a lot of screenshots.
* Read the [documentation](https://polylang.pro/doc/). It includes a [FAQ](https://polylang.pro/doc-category/faq/) and the [documentation for developers](https://polylang.pro/doc-category/developers/).
* Search the [community support forum](https://wordpress.org/search/). You will probably find your answer here.
* Read the sticky posts in the [community support forum](http://wordpress.org/support/plugin/polylang).
* If you still have a problem, open a new thread in the [community support forum](http://wordpress.org/support/plugin/polylang).
* [Polylang Pro](https://polylang.pro) users have access to our helpdesk.

= Is Polylang compatible with WooCommerce? =

* You need a separate addon to make Polylang and WooCommerce work together. [A Premium addon](https://polylang.pro/downloads/polylang-for-woocommerce/), currently in beta stage, is available.

= Do you need translation services? =

* If you want to use professional or automatic translation services, install and activate the [Lingotek Translation](https://wordpress.org/plugins/lingotek-translation/) plugin.

== Screenshots ==

1. The Polylang languages admin panel
2. The Strings translations admin panel
3. Multilingual media library
4. The Edit Post screen with the Languages metabox

== Changelog ==

= 2.0.9 (2016-11-15) =

* fix javascript error in some ajax requests

= 2.0.8 (2016-11-14) =

* Disable admin language feature in WP 4.7+
* Pro: fix case where a media could lose its parent post when translated on the fly by the content duplication
* Pro: fix on the fly media created at content duplication attached to parent page instead of child page
* Fix translations input fields not populated in languages metabox when creating a new translation in WP 4.7
* Fix possibility to delete the translations of the default category in WP 4.7
* Fix tag search not filtered per language in Quick edit in WP 4.7
* Fix dropdown language switcher not working for untranslated pages

= 2.0.7 (2016-10-18) =

* Fix issues with static front pages introduced in version 2.0.6

= 2.0.6 (2016-10-17) =

* Pro: Fix translated paged slug not working on paged static front page
* Add support for WPML filter 'wpml_language_form_input_field'
* Fix PHP notice when using the WPML filter 'wpml_current_language'
* Fix cases where the admin language filter is not correctly taken into account
* Fix paged static front pages in plain permalinks
* Fix paged static front pages for multiple domains (#43)
* Fix warning occuring when a 3rd party plugin attempts to register anything but a string in the strings translations panel
* Fix cross domain http request for media when using multiple domains or subdomains
* Fix error 404 on pages when no language has been created yet

= 2.0.5 (2016-09-22) Five years after! =

* Pro: Fix conflict with WPBakery Visual Composer
* Pro: Fix conflict between multiple domains SSO and FORCE_SSL_ADMIN
* Pro: Fix duplicated fields not displayed in new translation in ACF Pro 5.4+
* Add Tibetan and Silesian to the predefined languages list
* Remove duplicated strings from the strings translations (even when they have a different name or group)
* The languages and translations of custom post types and taxonomies are no more activated by default at activation
* Allow to deactivate auto translation in secondary by setting 'lang' to an empty value
* Fix: invalidate the cache of PLL_MO ids when adding a new language
* Fix: don't filter secondary queries when editing a post in an untranslated post type

= 2.0.4 (2016-09-06) =

* Add Gujarati to the predefined languages list
* Fix conflict with Page Builder. Other parts of the conflict are fixed in Page Builder 2.4.14
* Fix plugins translations incorrectly loaded in WP 4.6
* Fix error 404 on paged urls when using a non standard port

= 2.0.3 (2016-08-16) =

* Pro: Fix PHP notice when hiding the language code in url and the language is set from subdomains
* Pro: Fix one more media being created when the duplicate media in all languages is activated (introduced in 2.0)
* Pro: Fix shared term slugs not working on PHP 7
* Pro: Fix Polylang storing integers in some ACF Pro fields where ACF Pro stores strings
* Pro: Fix ACF Pro custom fields synchronized even when the custom fields synchronization option is deactivated (#40)
* Fix PHP notice: Undefined variable: original_value in /modules/wpml/wpml-api.php on line 168
* Fix translations loaded too soon by plugins not correctly reloaded since WP 4.6 (#39)
* Fix: Remove the delete link for translations of the default category on PHP 7
* Fix unescaped i18n strings in Lingotek presentation

= 2.0.2 (2016-08-03) =

* Avoid fatal error when a 3rd party theme or plugin has a malformed wpml-config.xml file: the malformed wpml-config.xml file is simply ignored

= 2.0.1 (2016-08-02) =

* Fix fatal error on PHP < 5.4 (introduced in 2.0)
* Fix custom flags not being loaded (introduced in 2.0)

= 2.0 (2016-08-02) =

* Pro: Improve integration with ACF Pro
* Pro: Add support for single sign on across multiple domains or subdomains
* Pro: Add support for browser language detection when using multiple domains
* Pro: Add support for translation of the static portion of the post permalink structure
* Pro: Fix deactivated languages appearing in Yoast SEO sitemaps
* Pro: Fix impossibility to visit a deactivated language when using subdomains or multiple domains (#10)
* Pro: Fix when sharing slug on the page for posts, only one of them is accessible (#33)
* Add the possibility to use the language switcher as dropdown in menu
* Add support for custom logo introduced in WP 4.5 (#6)
* The backend current language ( PLL()->curlang ) is now equal to the language of current post or term being edited (#19)
* The sample permalink is now updated when changing the language in the Languages metabox
* Revamp the wpml-config.xml reader to use simplexml instead of our custom xml parser
* Improve support for the WPML API (including Hook API introduced in WPML 3.2)
* Add support for translation of meta titles and descriptions of custom post types and custom taxonomies in Yoast SEO
* Replace uncached functions by WPCOM VIP functions when available
* Improve compatibility with WP 4.6
* Fix parent category wrongly assigned to post when synchronizing children categories (#21)
* Fix custom fonts not loaded when using multiple domains or subdomains
* Fix remove_accents() not working for German and Danish (#24)
* Fix incorrect static front pages urls on backend
* Fix impossible to directly enter the page number in strings translation table (introduced in 1.9.3)
* Fix conflict with WP Sweep (needs WP Sweep 1.0.8+)
* Fix potential performance issue by querying only taxonomies to show in quick edit to filter the category checklist
* Fix conflict (database error) with ReOrder-posts-within-categories plugin
* Fix languages per page option not saved

See [changelog.txt](https://plugins.svn.wordpress.org/polylang/trunk/changelog.txt) for older changelog
