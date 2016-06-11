<?php

/**
 * manages the static front page and the page for posts on admin side
 *
 * @since 1.8
 */
class PLL_Admin_Static_Pages extends PLL_Static_Pages {

	/**
	 * constructor: setups filters and actions
	 *
	 * @since 1.8
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		parent::__construct( $polylang );

		// add post state for translations of the front page and posts page
		add_filter( 'display_post_states', array( $this, 'display_post_states' ), 10, 2 );

		// refresh language cache when a static front page has been translated
		add_action( 'pll_save_post', array( $this, 'pll_save_post' ), 10, 3 );

		// checks if chosen page on front is translated
		add_filter( 'pre_update_option_page_on_front', array( $this, 'update_page_on_front' ), 10, 2 );

		// Prevents WP resetting the option
		add_filter( 'pre_update_option_show_on_front', array( $this, 'update_show_on_front' ), 10, 2 );
	}

	/**
	 * add post state for translations of the front page and posts page
	 *
	 * @since 1.8
	 *
	 * @param array $post_states
	 * @param object $post
	 * @return array
	 */
	public function display_post_states( $post_states, $post ) {
		if ( in_array( $post->ID, $this->model->get_languages_list( array( 'fields' => 'page_on_front' ) ) ) ) {
			$post_states['page_on_front'] = __( 'Front Page' );
		}

		if ( in_array( $post->ID, $this->model->get_languages_list( array( 'fields' => 'page_for_posts' ) ) ) ) {
			$post_states['page_for_posts'] = __( 'Posts Page' );
		}

		return $post_states;
	}

	/**
	 * refresh language cache when a static front page has been translated
	 *
	 * @since 1.8
	 *
	 * @param int $post_id not used
	 * @param object $post not used
	 * @param array $translations
	 */
	public function pll_save_post( $post_id, $post, $translations ) {
		if ( in_array( $this->page_on_front, $translations ) ) {
			$this->model->clean_languages_cache();
		}
	}

	/**
	 * prevents choosing an untranslated static front page
	 * displays an error message
	 *
	 * @since 1.6
	 *
	 * @param int $page_id new page on front page id
	 * @param int $old_id old page on front page_id
	 * @return int
	 */
	public function update_page_on_front( $page_id, $old_id ) {
		if ( $page_id ) {
			$translations = count( $this->model->post->get_translations( $page_id ) );
			$languages = count( $this->model->get_languages_list() );

			if ( $languages > 1 && $translations != $languages ) {
				$page_id = $old_id;
				add_settings_error( 'reading', 'pll_page_on_front_error', __( 'The chosen static front page must be translated in all languages.', 'polylang' ) );
			}
		}

		return $page_id;
	}

	/**
	 * Prevents WP resetting the option if the admin language filter is active for a language with no pages
	 *
	 * @since 1.9.3
	 *
	 * @param string $value
	 * @param string $old_value
	 * @return string
	 */
	public function update_show_on_front( $value, $old_value ) {
		if ( ! empty( $GLOBALS['pagenow'] ) && 'options-reading.php' === $GLOBALS['pagenow'] && 'posts' === $value && ! get_pages() && get_pages( array( 'lang' => '' ) ) ) {
			$value = $old_value;
		}
		return $value;
	}
}
