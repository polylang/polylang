=== Polylang ===
Contributors: Chouby, manooweb
Donate link: https://polylang.pro
Tags: multilingual, bilingual, translate, translation, language, multilanguage, international, localization
Requires at least: 4.7
Tested up to: 5.3
Requires PHP: 5.6
Stable tag: 2.6.8
License: GPLv3 or later

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

> The author does not provide support on the wordpress.org forum. Support and extra features are available to [Polylang Pro](https://polylang.pro) users.

If you wish to migrate from WPML, you can use the plugin [WPML to Polylang](https://wordpress.org/plugins/wpml-to-polylang/)

If you wish to use a professional or automatic translation service, you can install [Lingotek Translation](https://wordpress.org/plugins/lingotek-translation/), as an addon of Polylang. Lingotek offers a complete translation management system which provides services such as translation memory or semi-automated translation processes (e.g. machine translation > human translation > legal review).

= Credits =

Thanks a lot to all translators who [help translating Polylang](https://translate.wordpress.org/projects/wp-plugins/polylang).
Thanks a lot to [Alex Lopez](http://www.alexlopez.rocks/) for the design of the logo.
Most of the flags included with Polylang are coming from [famfamfam](http://famfamfam.com/) and are public domain.
Wherever third party code has been used, credit has been given in the code’s comments.

= Do you like Polylang? =

Don't hesitate to [give your feedback](http://wordpress.org/support/view/plugin-reviews/polylang#postform).

== Installation ==

1. Make sure you are using WordPress 4.7 or later and that your server is running PHP 5.6 or later (same requirement as WordPress itself)
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

* You need a separate addon to make Polylang and WooCommerce work together. [A Premium addon](https://polylang.pro/downloads/polylang-for-woocommerce/) is available.

= Do you need translation services? =

* If you want to use professional or automatic translation services, install and activate the [Lingotek Translation](https://wordpress.org/plugins/lingotek-translation/) plugin.

== Screenshots ==

1. The Polylang languages admin panel
2. The Strings translations admin panel
3. Multilingual media library
4. The Edit Post screen with the Languages metabox

== Changelog ==

= 2.6.8 (2019-12-11) =

* Pro: Fix conflict with JetThemesCore from Crocoblock
* Fix: better detection of REST requests when using plain permalinks
* Fix usage of deprecated action wpmu_new_blog in WP 5.1+
* Fix PHP notices with PHP 7.4

= 2.6.7 (2019-11-14) =

* Require PHP 5.6
* Fix PHP warning in WP 5.3

= 2.6.6 (2019-11-12) =

* Pro: Fix wrong ajax url when using one domain per language
* Pro: Fix conflict with user switching plugin when using multiple domains
* Pro: Fix latest posts block in WP 5.3
* Fix database error when attempting to sync an untranslated page parent
* Fix a conflict with the theme Neptune by Osetin

= 2.6.5 (2019-10-09) =

* Pro: Require ACF 5.7.11+ to activate the compatibility to avoid fatal errors with older versions
* Pro: Avoid translating empty front slug (could cause a wrong redirect to /wp-admin)
* Pro: Fix filter wp_unique_term_slug not always correctly applied.
* Pro: Fix a conflict with Divi causing post synchronization buttons to be displayed multiple times
* Avoid notice in WP CLI context

= 2.6.4 (2019-08-27) =

* Pro: Fix a conflict preventing meta synchronization when ACF is active
* Pro: Fix post metas not correctly copied when translating a Beaver Builder page
* Pro: Fix a fatal error when posts made with Elementor are synchronized
* Pro: Fix Prewiew button not working correctly when using one domain per language
* Pro: Fix post synchronization not available for WP CRON and WP CLI
* Fix future posts not available in the autocomplete input field of the languages metabox
* Fix translations files not loaded on REST requests
* Fix deleted term parent not synchronized

= 2.6.3 (2019-08-06) =

* Pro: Fix fatal error when updating an ACF field from frontend
* Pro: Add action 'pll_post_synchronized'
* Allow to get the current or default language object using the API. Props Jory Hogeveen. #359
* Fix empty span in languages switcher widget when showing only flags
* Fix wpml_register_single_string when updating the original string

= 2.6.2 (2019-07-16) =

* Pro: Fix slow admin in case the translations update server can't be reached
* Pro: Fix value not correctly translated for ACF clone fields in repeater
* Fix strings translations mixed when registered via the WPML compatibility. #381

= 2.6.1 (2019-07-03) =

* Pro: Fix Yoast SEO sitemap for inactive languages when using subdomains or multiple domains
* Fix fatal error in combination with Yoast SEO and Social Warfare
* Fix post type archive url in Yoast SEO sitemap

= 2.6 (2019-06-26) =

* Pro: Remove all languages files. All translations are now maintained on TranslationsPress
* Pro: Move the languages metabox to a block editor plugin
* Pro: Better management of user capabilities when synchronizing posts
* Pro: Separate REST requests from the frontend
* Pro: Copy the post slug when duplicating a post
* Pro: Duplicate ACF term metas when terms are automatically duplicated when creating a new post translation
* Pro: Fix hierarchy lost when duplicating terms
* Pro: Fix page shared slugs with special characters
* Pro: Fix synchronized posts sharing their slug when the language is set from the content
* Pro: Fix PHP warning with ACF Pro 5.8.1
* Pro: Fix ACF clone fields not translated in repeaters
* Better management of user capablities when synchronizing taxonomies terms and custom fields
* Extend string translations search to translated strings #207
* Update plugin updater to 1.6.18
* Honor the filter `pll_flag` when performing the flag validation when creating a new language
* Modify the title and the label for the language switcher menu items #307
* Add support for international domain names
* Add a title to the link icon used to add a translation #325
* Add a notice when a static front page is not translated in a language
* Add support for custom term fields in wpml-config.xml
* Add filter `pll_admin_languages_filter` for the list of items the admin bar language filter
* Add compatibility with WP Offload Media Lite. Props Daniel Berkman
* Yoast SEO: Add post type archive url in all languages to the sitemap
* Fix www. not redirected to not www. for the home page in multiple domains #311
* Fix cropped images not being synchronized
* Fix auto added page to menus when the page is created with the block editor
* Fix embed of translated static front page #318
* Fix a possible infinite redirect if the static front page is not translated
* Fix incorrect behavior of action 'wpml_register_single_string' when updating the string source
* Fix fatal error with Jetpack when no languages has been defined yet #330
* Fix a conflict with Laravel Valet. Props @chesio. #250
* Fix a conflict with Thesis.
* Fix a conflict with Pods in the block editor. Props Jory Hogeveen. #369
* Fix fatal error with Twenty Fourteen introduced in version 2.5.4. #374

See [changelog.txt](https://plugins.svn.wordpress.org/polylang/trunk/changelog.txt) for older changelog
