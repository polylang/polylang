=== Polylang ===
Contributors: Chouby, manooweb, raaaahman, marianne38, sebastienserre
Donate link: https://polylang.pro
Tags: multilingual, bilingual, translate, translation, language, multilanguage, international, localization
Requires at least: 5.4
Tested up to: 5.8
Requires PHP: 5.6
Stable tag: 3.1.4
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

1. Make sure you are using WordPress 5.1 or later and that your server is running PHP 5.6 or later (same requirement as WordPress itself)
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
