=== Polylang ===
Contributors: Chouby
Donate link: https://polylang.pro
Tags: multilingual, bilingual, translate, translation, language, multilanguage, international, localization
Requires at least: 4.4
Tested up to: 4.9
Stable tag: 2.3.8
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

1. Make sure you are using WordPress 4.4 or later and that your server is running PHP 5.2.4 or later (same requirement as WordPress itself)
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

= 2.3.8 (2018-07-16) =

* Pro: Duplicate term meta when duplicating a post creates new terms
* Pro: Add compatibility with ACF Pro when it's bundled with the theme
* Pro: Fix a fatal error when duplicating posts
* Set cookie during the home redirect
* Accept a port in the url to detect the site home
* Add filter 'pll_is_cache_active' to allow to load the cache compatibility #270 #274
* Fix potential fatal error when a 3rd party misuses the 'wpml_active_languages' filter #268
* Fix Uncaught TypeError: s.split is not a function. Props Wouter Van Vliet #262
* Fix text alignment for RTL scripts in Lingotek panel #247
* Fix html language attribute filter on admin
* Fix cookie expiration time when set in js. Props Jens Nachtigall #271
* Fix fatal error when a 3rd party misuses the WP_Query tax_query param. Props JanneAalto #252
* Fix an edge case which could mess home pages on a multisite


= 2.3.7 (2018-06-07) =

* Pro: The Events Calendar: Fix untranslated events shown in all languages
* Avoid displaying edit links of translations of the privacy policy page to non-admin
* Fix draft created when creating a new page on multisite
* Do not prevent using the cache for home when using WP Rocket 3.0.5 or later #236
* Fix language filter applied to wrong queries on admin side

= 2.3.6 (2018-05-17) =

* Pro: Fix post type archive slug not translated in ACF page link fields
* WP 4.9.6: Translate the privacy policy page
* WP 4.9.6: Add the translated user descriptions to exported personal data
* Update Plugin updater to version 1.6.16
* Fix conflict with the plugin View Admin As. Props Jory Hogeveen. #253

= 2.3.5 (2018-05-08) =

* Pro: Fix translated CPT slugs when one CPT name is a substring of another one. Props Steve Reimer.
* Pro: Fix canonical redirection for post types archives when the CPT slug is translated
* Pro: Fix ACF private key uselessly synchronized when the public custom field is not synchronized
* Add filter 'pll_filter_query_excluded_query_vars'
* Redirect www. to non www. when using multiple domains
* Fix Yoast SEO category sitemap not filtered by language when using multiple domains
* Fix PLL_COOKIE === false not honored when using a cache plugin. #248
* Fix empty predefined languages list

= 2.3.4 (2018-03-27) =

* Pro: Fix conflict with Pods related to translated slugs for custom post types
* Add Friulian to the predefined languages list
* Fix conflict (javascript error) with Gütenberg #225
* Fix conflict on ajax requests introduced by WooCoommerce 3.3.4
* Fix queries by 'category_name' not auto translated #238

= 2.3.3 (2018-03-15) =

* Pro: Fix tax query using a term sharing slugs (fix a conflict with Fusion Builder)
* Restore Polylang (free) on REST requests, while disabling the language filter as in v2.3
* Rework auto translated query with taxonomy in different language #223
* Synchronize Yoast SEO primary category (needs Yoast SEO 7.0+)
* Fix PHP warning introduced by Yoast SEO 7.0 #229
* Fix tax query when using the relation 'OR'
* Fix a conflict with the combination of Barrel + WP Bakery Page Builder
* Fix broken redirect with MU domain mapping #226
* Fix site title not translated in password change email

= 2.3.2 (2018-03-05) =

* Pro: Fix REST requests not filtered by the requested language (introduced in 2.3).
* Pro: Fix error 404 on single posts if posts are untranslatable
* Deactivate Polylang (free) on REST requests by default.
* Fix translated terms unassigned from posts when deleting a term
* Fix auto translated query with taxonomy in different language returning empty results since WP 4.9 #223
* Fix conflict with a homepage option of the theme Extra
* Fix warning when filtering get_pages()

= 2.3.1 (2018-02-15) =

* Pro: Fix GET REST request with slug parameter deleting the post slug
* Fix http request with a custom query var being redirected to the home page #216

= 2.3 (2018-01-30) =

* Pro: Duplicating a post now duplicates untranslated terms and the featured image (if media are translatable)
* Pro: Add filter 'pll_sync_post_fields'
* Pro: Translate ACF Pro clone fields when creating a new field group translation
* Pro: Allow to share slugs when creating a post or term with the REST API
* Pro: Load asynchronously the script added on front for multiple domains and subdomains
* Pro: Fix 'lang' parameter not interpreted when the query includes 'name'
* Refactor the synchronization of metas for better synchronization and performance improvement
* Refactor the synchronization of taxonomy terms for performance improvement
* Refactor language and translations saving for performance improvement
* Refactor the synchronization of sticky posts
* Remove all languages files. All translations are now maintained on https://translate.wordpress.org/projects/wp-plugins/polylang #199
* Refactor the list of languages to merge predefined languages, Facebook locales and fixes for W3C locales
* Automatically deactivate Polylang when activating Polylang Pro
* Disable programmatically translated post types and taxonomies in settings. Props Ulrich Pogson. #180
* Set the cookie language in Javascript when a cache plugin is active
* Automatically remove the home page from cache when requesting the detection of the browser preferred language
* Use relative urls for the admin language filter in admin bar. #209
* Disable auto translation of WP_Term_Query if it has a 'lang' parameter
* Don't filter REST requests by default. #211
* Fix Yoast SEO statistics in dashboard showing only the default language. #211
* Fix WP Rocket clearing the cache of the wrong adjacent post
* Fix random header image
* Fix home page not correctly loaded when adding a query var
* Fix: Impossible to change the language code when the language code is also a WordPress locale.

See [changelog.txt](https://plugins.svn.wordpress.org/polylang/trunk/changelog.txt) for older changelog
