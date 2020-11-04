=== Polylang ===
Contributors: Chouby, manooweb, raaaahman, marianne38, sebastienserre
Donate link: https://polylang.pro
Tags: multilingual, bilingual, translate, translation, language, multilanguage, international, localization
Requires at least: 4.9
Tested up to: 5.5
Requires PHP: 5.6
Stable tag: 2.8.4
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

= 2.8.4 (2020-11-03) =

* Pro: Remove useless bulk translate action for ACF fields groups
* Pro: Fix the translation of the CPTUI labels when the language is set from the content
* Fix sitemaps redirected to the default language since WP 5.5.1
* Fix object cache not flushed for sticky posts #601
* Fix blog page broken when trashing a page and the blog page is not translated in all languages
* Fix custom flags ignored in WPML compatibility mode
* Fix breadcrumb for untranslated post types in Yoast SEO

= 2.8.3 (2020-10-13) =

* Honor install_languages capability to download language packs
* Pro: Fix integrations not loaded (with The Events Calendar, CPTUI, Content blocks)
* Pro: Fix fatal error with ACF if a flexible content includes a repeater and a relationship
* Pro: Fix terms sharing their slug impossible to update without changing the slug
* When available, use wpcom_vip_get_page_by_path() instead of get_page_by_path()
* Fix queries filtered when editing a post that was declared untranslatable after it got a language
* Fix issues with Yoast SEO 14.0+ (breadcrumbs, canonical, title and description)

= 2.8.2 (2020-09-08) =

* Pro: Fix posts sharing the same slug displayed on the same page
* Fix: Don't use a javascript localized string removed in WP 5.5 #568
* Fix fatal error in site health when no language is defined #563
* Fix various issues with Yoast SEO 14.x #65, #503, #505
* Fix fatal error with MU Domain Mapping when saving domains in Polylang settings #569

= 2.8.1 (2020-08-25) =

* Pro: Fix fatal error with WP 4.9
* Fix pll_the_languages() with 'raw' option returning html flag instead of flag url #558
* Fix compatibility with Duplicate Posts not correcly loaded #557
* Fix custom flag size in admin bar language switcher #559
* Fix tag clouds mixed in the classic editor #561

= 2.8 (2020-08-17) =

* Pro: Add a language switcher block
* Pro: Add compatibility with block image edition introduced in WP 5.5
* Pro: Fix our private taxonomies being displayed in the ACF field group rules.
* Pro: Fix incorrect flags loaded from the block editor
* Pro: Fix SSO causing a wrong redirect when using subdomains (introduced in 2.7.4)
* Pro: Fix a performance issue on the plugins list
* Pro: Fix option to automatically duplicate media in all languages when uploading a new file not honored in block image
* Use composer for autoload and Polylang Pro dependency on Polylang
* Display a flag for each post in the posts list tables (same for terms). #515
* Add test for the homepage translations to Site Health
* Add debug information to Site Health
* Add compatibility with the sitemaps introduced in WP 5.5 #451
* Always filter WP_Query by the current language
* Support wildcards in "admin-texts" parent keys in wpml-config.xml
* Fix sticky posts showed for all languages when the admin language filter is active #469
* Fix a performance issue on the pages list
* Fix dependency to jQuery Migrate removed from WP 5.5 #539
* Fix: output secure cookie when using a cache plugin and ssl #542
* Fix the possibility to create 2 terms with the same name in the same language, without specifying the second slug.
* Fix sticky posts appearing 2 times in WP 5.5

See [changelog.txt](https://plugins.svn.wordpress.org/polylang/trunk/changelog.txt) for older changelog
