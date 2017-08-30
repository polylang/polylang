=== Polylang ===
Contributors: Chouby
Donate link: https://polylang.pro
Tags: multilingual, bilingual, translate, translation, language, multilanguage, international, localization
Requires at least: 4.4
Tested up to: 4.8
Stable tag: 2.2.1
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

* You need a separate addon to make Polylang and WooCommerce work together. [A Premium addon](https://polylang.pro/downloads/polylang-for-woocommerce/) is available.

= Do you need translation services? =

* If you want to use professional or automatic translation services, install and activate the [Lingotek Translation](https://wordpress.org/plugins/lingotek-translation/) plugin.

== Screenshots ==

1. The Polylang languages admin panel
2. The Strings translations admin panel
3. Multilingual media library
4. The Edit Post screen with the Languages metabox

== Changelog ==

= 2.2.1 (2017-08-30) =

* Pro: partially refactor REST API classes
* Pro: Fix duplicate content user meta not removed from DB when uninstalling the plugin
* Fix strings translations not removed from DB when uninstalling the plugin
* Fix incorrect translation files loaded in ajax on front when the user is logged in (WP 4.7+)
* Fix widget language dropdown removed when saving a widget (introduced in 2.2)
* Fix queries with negative values for the 'cat' parameter (introduced in 2.2 for queries made on frontend)
* Fix performance issue in combination with some plugins when the language is set from the content (introduced in 2.2)

= 2.2 (2017-08-16) =

* Pro: Add support for the REST API
* Pro: Add integration with The Events Calendar
* Pro: Refactor ACF Pro integration for post metas and integrate term metas
* Pro: Ask confirmation if synchronizing a post overwrites an existing translation
* Pro: Separate sync post logic from interface
* Pro: Fix 'Detect browser language' option automatically deactivated
* Pro: Fix redirect to 404 when the 'page' slug translation includes non alphanumeric characters.
* Pro: Fix untranslated post type archive slug
* Pro: Fix ACF taxonomy fields not copied when the taxonomy is not translated #156
* Pro: Fix fatal error with ACF4
* Support a different content text direction in admin #45
* Add support for wildcards and 'copy-once' attribute in wpml-config.xml
* Add minimal support for the filters 'wpml_display_language_names' and 'icl_ls_languages'
* Improve compatibility with the plugin WordPress MU Domain Mapping #116
* Improve speed of the sticky posts filter #41
* Remove redirect_lang option for multiple domains and subdomains
* Use secure cookie when using SSL
* Allow to copy/sync term metas with the filter 'pll_copy_term_metas'
* Filter ajax requests in term.php according to the term language
* Add error message in customizer when setting an untranslated static front page #47
* Load static page class only if we are using a static front page
* Refactor parse_query filters to use the same code on frontend and admin
* Don't use add_language_to_link in filters
* Move ajaxPrefilter footer script on top
* Use wp_doing_ajax() instead of DOING_AJAX constant
* Fix queries custom tax not excluded from language filter on admin
* Fix WP translation not loaded when the language is set from the content on multisite.
* Fix the list of core post types in PLL_OLT_Manager for WP 4.7+
* Fix post name and tag slug incorrectly sanitized for German and Danish
* Fix lang attribute in dropdowns
* Fix wpml_permalink filter #139
* Fix WPML constants undefined on backend #151
* Fix a conflict with the plugin Custom Permalinks #143
* Fix menu location unexpectedly unset

= 2.1.6 (2017-07-17) =

* Pro: fix duplicate post button not working in PHP 7.1
* Pro: fix CPTUI untranslated labels on admin
* Adapt related posts filter to use slug instead of name to follow changes made on Jetpack server ( Props Steve Kaeser )
* Fix PHP notices when translating CPT and custom tax titles in Yoast SEO
* Fix PHP warning when all plugins are networked activated

= 2.1.5 (2017-05-31) =

* Add compatibility with new media widgets introduced in WP 4.8
* Removing the language information in URL for the default language is now default
* Update plugin updater class to 1.6.12
* Pro: fix PHP notices when duplicating the content
* Fix: test existence of `twentyseventeen_panel_count` instead of relying only on the active template
* Fix: set current property to false when removing the current-menu-item class #134 props @mowar
* Fix PHP notice when editing a term without language
* Fix possible PHP notice when deleting a category
* Fix fatal error with Gantry 5

= 2.1.4 (2017-05-16) =

* Pro: fix user not logged in on secondary domain when previewing changes
* Pro: fix archive links without language code in ACF link field (ACF 5.4.0+)
* Fix redirection from www subdomain to wrong language domain.
* Fix: selecting "Front page displays latest posts" in the customizer not cleaning the languages cache
* Fix accessibility of the admin language switcher

= 2.1.3 (2017-04-11) =

* Pro: Fix translated slug of 'page' if it is translated to an empty string
* Update plugin updater class to 1.6.11
* Strings registered with a wpml-config.xml file or WPML functions are now multiline by default
* Translate the site title in emails sent to the user
* Fix sanitize_user for specific locales
* Fix deprecation notice in Yoast SEO integration
* Fix: Clean term cache after the language has been set in mass #119

= 2.1.2 (2017-03-09) =

* Pro: Add filter 'pll_xdata_nonce_life'
* Pro: Fix translation of WooCommerce product attribute slug
* Pro: Fix product synchronization in WooCommerce 2.7
* Pro: Fix error message when bulk trashing synchronized posts
* Add option to discard item spacing in the output of pll_the_languages() ( Props Ceslav Przywara ) #93 #95
* Add as, dzo, kab, km, ml_IN, nl_BE, pa_IN, rhg, sah, ta_IN, tah, te, tt_RU to the predefined list of languages
* Update plugin updater class to 1.6.10
* Fix: Remove the dependency to is_ssl() to detect the language in the url ( language set from the directory name )
* Fix issue with secondary level domains
* Fix strings not translated in emails
* Fix incorrect usage of add_action() ( Props Peter J. Herrel ) #103
* Fix wrong redirect in customizer in WP 4.7

= 2.1.1 (2017-02-15) =

* Pro: Add filter 'pll_enable_duplicate_media' for a fine control of automatic media duplication
* Add filter 'pll_links_model' for the links model class name
* Trim any starting ^ from modified rewrite rules
* Pro: Fix wrong count of plugins to udpate
* Fix slashed strings translations not saved #94

= 2.1 (2017-01-25) =

* Minimum WordPress version is now 4.4
* Pro: Add support for synchronized posts (same post in multiple languages)
* Pro: Add support for custom post type UI and the Divi Builder
* Improve support of Yoast SEO (no category base and post type archive breadcrumb title)
* Move Languages menu at top level instead of submenu of the WordPress settings
* Copy the original post date when creating a translation and when the date is synchronized (Props Jory Hogeveen) #32
* Remove hreflang attributes on paged pages and paged posts
* Add label to widget language dropdown for better accessibility (Props Lawrence Francell) #53 #56
* Remove constants POLYLANG_URL and PLL_LOCAL_URL
* wp_get_sidebars_widgets() and is_active_sidebar() are now filtered according to widgets languages #54
* Add functions pll_esc_html__(), pll_esc_html_e(), pll_esc_attr__() and pll_esc_attr_e() to the API (Props jegbagus) #83
* Pro: Fix conflict between WooCommerce shop on front and translated shop base slug
* Pro: Fix $wp_rewrite search base and author_base not translated #68
* Pro: Fix page preview does not log in the user when using sudomains
* Fix: avoid setting the language cookie on 404 pages
* Fix: rewrite rules order modified for custom post types archives
* Fix: conflict with WP All Import causing our filters to fail in "Add Media" modal when editing a post
* Fix: auto add pages not working for nav menus assigned to several locations
* Fix: Jetpack infinite scroll for multiple domains #58 #74
* Fix: serialize error in Strings translations when balanceTags option is active #63
* Fix: static front page preview when redirected from the languages page #49
* Fix: Auto add pages not working for nav menus assigned to several locations
* Fix: Conflict with Woocommerce Show Single Variation
* Fix: Parent page not synchronized in Quick edit (introduced in 2.0.8)
* Fix: WPML API wpml_element_has_translations and wpml_post_language_details
* Fix: unattached media translations not in language switcher
* Fix: Conflict with WP Residence advanced search

See [changelog.txt](https://plugins.svn.wordpress.org/polylang/trunk/changelog.txt) for older changelog
