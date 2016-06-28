=== Polylang ===
Contributors: Chouby
Donate link: https://polylang.pro
Tags: multilingual, bilingual, translate, translation, language, multilanguage, international, localization
Requires at least: 4.0
Tested up to: 4.5
Stable tag: 1.9.3
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

* You need a separate addon to make Polylang and WooCommerce work together. Our Premium addon is currently in beta stage and is available for tests to Polyang Pro users who request it.

= Do you need translation services? =

* If you want to use professional or automatic translation services, install and activate the [Lingotek Translation](https://wordpress.org/plugins/lingotek-translation/) plugin.

== Screenshots ==

1. The Polylang languages admin panel
2. The Strings translations admin panel
3. Multilingual media library
4. The Edit Post screen with the Languages metabox

== Changelog ==

= 1.9.3 (2016-06-28) =

* Pro: Allow to add slashes in url slugs translations
* Pro: Fix archive links not using translated slugs
* Pro: Fix visitor being redirected to 404 if his browser preference is set to an inactive language
* Fix strings translations table always back to page 1 when submitting the form (#14)
* Fix get_pages( array( 'lang' => '' ) ) not querying all the languages
* Fix switching the admin language filter can override the static front page settings (#16)

= 1.9.2 (2016-06-06) =

* Pro: fix unreachable hierarchical custom post type posts when they are sharing slugs across languages
* Fix missing argument 3 in icl_t
* Fix conflict with WooCommerce product variations

= 1.9.1 (2016-05-23) =

* Pro: add compatibility with Beaver Builder
* Pro: fix media wrongly created when adding a new media translation
* Add azb, ceb, de_CH_informal, es_GT, mr, nl_NL_formal to the predefined list of languages
* Fix the language switcher not linking to media translations for anonymous visitors

= 1.9 (2016-04-27) =

* Pro: add the possibility to translate custom post types slugs, taxonomies slugs and more
* Pro: add the possibility to share the same post or term slug accross languages
* Pro: add the possibility to duplicate the content when creating a new translation
* Pro: add the possibility to create all translations at once when uploading a media
* Pro: add the possibility to disable a language
* Add license and update management
* Add inline docs for all filters and actions
* When possible, the rel alternate hreflang now display only the language code (without the country code)
* When combined with flags in the language switcher, wrap the language name inside <span> tags
* Add customizer selective refresh support for the language switcher widget ( needs WP 4.5+ )
* Fix dynamic options of the language switcher widget not working in the customizer
* Fix possible error 404 on page shortlink when using subdomains or multiple domains
* Fix get_adjacent_post() and wp_get_archives() for untranslated post types ( needs WP 4.4+ )
* Fix language homepage urls not present in Yoast SEO sitemap (when the homepages display posts)

See changelog.txt for older changelog
