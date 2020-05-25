=== Polylang ===
Contributors: Chouby, manooweb, raaaahman, marianne38
Donate link: https://polylang.pro
Tags: multilingual, bilingual, translate, translation, language, multilanguage, international, localization
Requires at least: 4.9
Tested up to: 5.4
Requires PHP: 5.6
Stable tag: 2.7.3
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

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

1. Make sure you are using WordPress 4.9 or later and that your server is running PHP 5.6 or later (same requirement as WordPress itself)
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

= 2.7.3 (2020-05-26) =

* Security: Slash metas
* Pro: Fix categories not savedafter the language has been switched in the block editor
* Pro: Fix ACF fields stored as integers instead of strings
* Pro: Fix ACF untranslated posts or terms being copied when creating a new translation
* Pro: Fix PHP notice with ACF when a repeater or group is included in a flexible content
* Pro: Fix "DevTools failed to load SourceMap" warning in browser console
* Update plugin updater to 1.7.1
* Honor the filter "pll_the_language_link" when the language switcher displays a dropdown #506
* Fix "Something went wrong" message when quick editing untranslated post types #508
* Fix wpseo_opengraph deprecated warning #509

= 2.7.2 (2020-04-27) =

* Pro: Re-allow to modify the capability for strings translations
* Pro: Fix redirect for posts having the same slug as a media
* Pro: Fix PHP notice with ACF flexible content
* Pro: Fix a fatal error with InfiniteWP
* Update plugin updater to 1.7
* Fix font in setup wizard

= 2.7.1 (2020-04-09) =

* Pro: Fix untranslated post types filtered by the parameter in the REST API #493
* Fix fatal error when the function idn_to_ascii is not available
* Fix PHP warning warning when a 3rd party plugin declares options not stored in DB in wpml-config.xml #492
* Fix fatal error when a 3rd party plugin declares options stored as objects in wpml-config.xml #494

= 2.7 (2020-04-06) =

* Minimum WordPress version is now 4.9
* Pro: Strings translations can now be exported and imported (in PO format)
* Pro: Allow to decide individually which ACF fields to copy or synchronize
* Pro: Add action pll_inactive_language_requested
* Pro; Fix fatal error in The Events Calendar compatibility when no language is defined yet
* Pro: Fix bulk translate when a post has no language
* Pro: Fix reusable block saved without language
* Pro: Fix post requested by slug not filtered in REST API, when the slug is shared
* Add a setup wizard
* Add Swahili, Upper Sorbian, Sindhi and Spanish from Uruguay to the list of predefined languages
* Add flags in the predefined list of languages
* Allow to hide the metaboxes from the screen options
* The deletion of the plugin's data at uninstall is now controlled by a PHP constant instead of an option #456
* Add parent in ajax response when selecting a term in autocomplete field #328
* Add Vary: Accept-Language http header in home page redirect. Props @chesio #452
* Improve performance to register/unregister WPML strings
* Add support for the action wpml_switch_language
* Add post_status to the list of accepted args of pll_count_posts()
* Apply the filter pll_preferred_language in wp-login.php
* Use filtered wrappers to create meta when creating media translations #231
* Allow to translate the Twenty Seventeen header video Youtube url #460
* Notices are now dismissed per site instead of per user #478
* Fix terms not visible in the quick edit when only one language is defined and teh admin language filter is active
* Fix post state not displayed for translations of the privacy policy page #395
* Fix wildcards not correctly interpreted in wpml-config.xml
* Fix product categories with special characters duplicated when importing WooCommerce products #474

See [changelog.txt](https://plugins.svn.wordpress.org/polylang/trunk/changelog.txt) for older changelog
