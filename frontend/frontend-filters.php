<?php
/**
 * @package Polylang
 */

/**
 * Filters content by language on frontend
 *
 * @since 1.2
 */
class PLL_Frontend_Filters extends PLL_Filters {
	public $cache;

	/**
	 * Constructor: setups filters and actions
	 *
	 * @since 1.2
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		parent::__construct( $polylang );

		$this->cache = new PLL_Cache();

		// Filters the WordPress locale
		add_filter( 'locale', array( $this, 'get_locale' ) );

		// Filter sticky posts by current language
		add_filter( 'option_sticky_posts', array( $this, 'option_sticky_posts' ) );

		// Rewrites archives links to filter them by language
		add_filter( 'getarchives_join', array( $this, 'getarchives_join' ), 10, 2 );
		add_filter( 'getarchives_where', array( $this, 'getarchives_where' ), 10, 2 );

		// Filters the widgets according to the current language
		add_filter( 'widget_display_callback', array( $this, 'widget_display_callback' ) );
		add_filter( 'sidebars_widgets', array( $this, 'sidebars_widgets' ) );

		if ( $this->options['media_support'] ) {
			add_filter( 'widget_media_image_instance', array( $this, 'widget_media_instance' ), 1 ); // Since WP 4.8
		}

		// Strings translation ( must be applied before WordPress applies its default formatting filters )
		foreach ( array( 'widget_text', 'widget_title' ) as $filter ) {
			add_filter( $filter, 'pll__', 1 );
		}

		// Translates biography
		add_filter( 'get_user_metadata', array( $this, 'get_user_metadata' ), 10, 4 );

		// FIXME test get_user_locale for backward compatibility with WP < 4.7
		if ( Polylang::is_ajax_on_front() && function_exists( 'get_user_locale' ) ) {
			add_filter( 'load_textdomain_mofile', array( $this, 'load_textdomain_mofile' ) );
		}
	}

	/**
	 * Returns the locale based on current language
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function get_locale() {
		return $this->curlang->locale;
	}

	/**
	 * Filters sticky posts by current language
	 *
	 * @since 0.8
	 *
	 * @param array $posts list of sticky posts ids
	 * @return array modified list of sticky posts ids
	 */
	public function option_sticky_posts( $posts ) {
		global $wpdb;

		// Do not filter sticky posts on REST requests as $this->curlang is *not* the 'lang' parameter set in the request
		if ( ! defined( 'REST_REQUEST' ) && $this->curlang && ! empty( $posts ) ) {
			$_posts = wp_cache_get( 'sticky_posts', 'options' ); // This option is usually cached in 'all_options' by WP

			if ( empty( $_posts ) || ! is_array( $_posts[ $this->curlang->term_taxonomy_id ] ) ) {
				$posts = array_map( 'intval', $posts );
				$posts = implode( ',', $posts );

				$languages = $this->model->get_languages_list( array( 'fields' => 'term_taxonomy_id' ) );
				$_posts = array_fill_keys( $languages, array() ); // Init with empty arrays
				$languages = implode( ',', $languages );

				// PHPCS:ignore WordPress.DB.PreparedSQL
				$relations = $wpdb->get_results( "SELECT object_id, term_taxonomy_id FROM {$wpdb->term_relationships} WHERE object_id IN ({$posts}) AND term_taxonomy_id IN ({$languages})" );

				foreach ( $relations as $relation ) {
					$_posts[ $relation->term_taxonomy_id ][] = (int) $relation->object_id;
				}
				wp_cache_add( 'sticky_posts', $_posts, 'options' );
			}

			$posts = $_posts[ $this->curlang->term_taxonomy_id ];
		}

		return $posts;
	}

	/**
	 * Modifies the sql request for wp_get_archives to filter by the current language
	 *
	 * @since 1.9
	 *
	 * @param string $sql JOIN clause
	 * @param array  $r   wp_get_archives arguments
	 * @return string modified JOIN clause
	 */
	public function getarchives_join( $sql, $r ) {
		return ! empty( $r['post_type'] ) && $this->model->is_translated_post_type( $r['post_type'] ) ? $sql . $this->model->post->join_clause() : $sql;
	}

	/**
	 * Modifies the sql request for wp_get_archives to filter by the current language
	 *
	 * @since 1.9
	 *
	 * @param string $sql WHERE clause
	 * @param array  $r   wp_get_archives arguments
	 * @return string modified WHERE clause
	 */
	public function getarchives_where( $sql, $r ) {
		return ! empty( $r['post_type'] ) && $this->model->is_translated_post_type( $r['post_type'] ) ? $sql . $this->model->post->where_clause( $this->curlang ) : $sql;
	}

	/**
	 * Filters the widgets according to the current language
	 * Don't display if a language filter is set and this is not the current one
	 *
	 * @since 0.3
	 *
	 * @param array $instance Widget settings
	 * @return bool|array false if we hide the widget, unmodified $instance otherwise
	 */
	public function widget_display_callback( $instance ) {
		// FIXME it looks like this filter is useless, now the we use the filter sidebars_widgets
		return ! empty( $instance['pll_lang'] ) && $instance['pll_lang'] != $this->curlang->slug ? false : $instance;
	}

	/**
	 * Remove widgets from sidebars if they are not visible in the current language
	 * Needed to allow is_active_sidebar() to return false if all widgets are not for the current language. See #54
	 *
	 * @since 2.1
	 * @since 2.4 The result is cached as the function can be very expensive in case there are a lot of widgets
	 *
	 * @param array $sidebars_widgets An associative array of sidebars and their widgets
	 * @return array
	 */
	public function sidebars_widgets( $sidebars_widgets ) {
		global $wp_registered_widgets;

		if ( empty( $wp_registered_widgets ) ) {
			return $sidebars_widgets;
		}

		$cache_key         = md5( maybe_serialize( $sidebars_widgets ) );
		$_sidebars_widgets = $this->cache->get( "sidebars_widgets_{$cache_key}" );

		if ( false !== $_sidebars_widgets ) {
			return $_sidebars_widgets;
		}

		foreach ( $sidebars_widgets as $sidebar => $widgets ) {
			if ( 'wp_inactive_widgets' === $sidebar || empty( $widgets ) ) {
				continue;
			}

			foreach ( $widgets as $key => $widget ) {
				// Nothing can be done if the widget is created using pre WP2.8 API :(
				// There is no object, so we can't access it to get the widget options
				if ( ! isset( $wp_registered_widgets[ $widget ]['callback'] ) || ! is_array( $wp_registered_widgets[ $widget ]['callback'] ) || ! isset( $wp_registered_widgets[ $widget ]['callback'][0] ) || ! is_object( $wp_registered_widgets[ $widget ]['callback'][0] ) || ! method_exists( $wp_registered_widgets[ $widget ]['callback'][0], 'get_settings' ) ) {
					continue;
				}

				$widget_settings = $wp_registered_widgets[ $widget ]['callback'][0]->get_settings();
				$number          = $wp_registered_widgets[ $widget ]['params'][0]['number'];

				// Remove the widget if not visible in the current language
				if ( ! empty( $widget_settings[ $number ]['pll_lang'] ) && $widget_settings[ $number ]['pll_lang'] !== $this->curlang->slug ) {
					unset( $sidebars_widgets[ $sidebar ][ $key ] );
				}
			}
		}

		$this->cache->set( "sidebars_widgets_{$cache_key}", $sidebars_widgets );

		return $sidebars_widgets;
	}

	/**
	 * Translates media in media widgets
	 *
	 * @since 2.1.5
	 *
	 * @param array $instance Widget instance data
	 * @return array
	 */
	public function widget_media_instance( $instance ) {
		if ( empty( $instance['pll_lang'] ) && $instance['attachment_id'] && $tr_id = pll_get_post( $instance['attachment_id'] ) ) {
			$instance['attachment_id'] = $tr_id;
			$attachment = get_post( $tr_id );

			if ( $instance['caption'] && ! empty( $attachment->post_excerpt ) ) {
				$instance['caption'] = $attachment->post_excerpt;
			}

			if ( $instance['alt'] && $alt_text = get_post_meta( $tr_id, '_wp_attachment_image_alt', true ) ) {
				$instance['alt'] = $alt_text;
			}

			if ( $instance['image_title'] && ! empty( $attachment->post_title ) ) {
				$instance['image_title'] = $attachment->post_title;
			}
		}
		return $instance;
	}

	/**
	 * Translates biography
	 *
	 * @since 0.9
	 *
	 * @param null   $null
	 * @param int    $id       User id
	 * @param string $meta_key
	 * @param bool   $single   Whether to return only the first value of the specified $meta_key
	 * @return null|string
	 */
	public function get_user_metadata( $null, $id, $meta_key, $single ) {
		return 'description' === $meta_key && $this->curlang->slug !== $this->options['default_lang'] ? get_user_meta( $id, 'description_' . $this->curlang->slug, $single ) : $null;
	}

	/**
	 * Filters the translation files to load when doing ajax on front
	 * This is needed because WP the language files associated to the user locale when a user is logged in
	 *
	 * @since 2.2.6
	 *
	 * @param string $mofile Translation file name
	 * @return string
	 */
	public function load_textdomain_mofile( $mofile ) {
		$user_locale = get_user_locale();
		return str_replace( "{$user_locale}.mo", "{$this->curlang->locale}.mo", $mofile );
	}
}
