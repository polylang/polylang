<?php
/**
 * @package Polylang
 */

/**
 * Filters search forms when using permalinks
 *
 * @since 1.2
 */
class PLL_Frontend_Filters_Search {
	/**
	 * Instance of a child class of PLL_Links_Model.
	 *
	 * @var PLL_Links_Model
	 */
	public $links_model;

	/**
	 * Current language.
	 *
	 * @var PLL_Language|null
	 */
	public $curlang;

	/**
	 * Constructor
	 *
	 * @since 1.2
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		$this->links_model = &$polylang->links_model;
		$this->curlang = &$polylang->curlang;

		// Adds the language information in the search form
		// Low priority in case the search form is created using the same filter as described in http://codex.wordpress.org/Function_Reference/get_search_form
		add_filter( 'get_search_form', array( $this, 'get_search_form' ), 99 );

		// Adds the language information in the search block.
		add_filter( 'render_block_core/search', array( $this, 'get_search_form' ) );

		// Adds the language information in admin bar search form
		add_action( 'add_admin_bar_menus', array( $this, 'add_admin_bar_menus' ) );


		// Adds javascript at the end of the document
		// Was used for WP < 3.6. kept just in case
		if ( defined( 'PLL_SEARCH_FORM_JS' ) && PLL_SEARCH_FORM_JS ) {
			add_action( 'wp_footer', array( $this, 'wp_print_footer_scripts' ) );
		}
	}

	/**
	 * Adds the language information in the search form.
	 *
	 * Does not work if searchform.php ( prior to WP 3.6 ) is used or if the search form is hardcoded in another template file
	 *
	 * @since 0.1
	 *
	 * @param string $form The search form HTML.
	 * @return string Modified search form.
	 */
	public function get_search_form( $form ) {
		if ( empty( $form ) || empty( $this->curlang ) ) {
			return $form;
		}

		if ( $this->links_model->using_permalinks ) {
			// Take care to modify only the url in the <form> tag.
			preg_match( '#<form.+?>#', $form, $matches );
			$old = reset( $matches );
			if ( empty( $old ) ) {
				return $form;
			}
			// Replace action attribute (a text with no space and no closing tag within double quotes or simple quotes or without quotes).
			$new = preg_replace( '#\saction=("[^"\r\n]+"|\'[^\'\r\n]+\'|[^\'"][^>\s]+)#', ' action="' . esc_url( $this->curlang->get_search_url() ) . '"', $old );
			if ( empty( $new ) ) {
				return $form;
			}
			$form = str_replace( $old, $new, $form );
		} else {
			$form = str_replace( '</form>', '<input type="hidden" name="lang" value="' . esc_attr( $this->curlang->slug ) . '" /></form>', $form );
		}

		return $form;
	}

	/**
	 * Adds the language information in admin bar search form
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function add_admin_bar_menus() {
		remove_action( 'admin_bar_menu', 'wp_admin_bar_search_menu', 4 );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_search_menu' ), 4 );
	}

	/**
	 * Rewrites the admin bar search form to pass our get_search_form filter. See #21342.
	 * Code last checked: WP 5.4.1.
	 *
	 * @since 0.9
	 *
	 * @param WP_Admin_Bar $wp_admin_bar
	 * @return void
	 */
	public function admin_bar_search_menu( $wp_admin_bar ) {
		$form  = '<form action="' . esc_url( home_url( '/' ) ) . '" method="get" id="adminbarsearch">';
		$form .= '<input class="adminbar-input" name="s" id="adminbar-search" type="text" value="" maxlength="150" />';
		$form .= '<label for="adminbar-search" class="screen-reader-text">' .
					/* translators: Hidden accessibility text. */
					esc_html__( 'Search', 'polylang' ) .
				'</label>';
		$form .= '<input type="submit" class="adminbar-button" value="' . esc_attr__( 'Search', 'polylang' ) . '" />';
		$form .= '</form>';

		$wp_admin_bar->add_node(
			array(
				'parent' => 'top-secondary',
				'id'     => 'search',
				'title'  => $this->get_search_form( $form ), // Pass the get_search_form filter.
				'meta'   => array(
					'class'    => 'admin-bar-search',
					'tabindex' => -1,
				),
			)
		);
	}

	/**
	 * Allows modifying the search form if it does not pass get_search_form
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function wp_print_footer_scripts() {
		// Don't use directly e[0] just in case there is somewhere else an element named 's'
		// Check before if the hidden input has not already been introduced by get_search_form ( FIXME: is there a way to improve this ) ?
		// Thanks to AndyDeGroo for improving the code for compatibility with old browsers
		// http://wordpress.org/support/topic/development-of-polylang-version-08?replies=6#post-2645559
		$lang = esc_js( $this->curlang->slug );
		$js = "//<![CDATA[
		e = document.getElementsByName( 's' );
		for ( i = 0; i < e.length; i++ ) {
			if ( e[i].tagName.toUpperCase() == 'INPUT' ) {
				s = e[i].parentNode.parentNode.children;
				l = 0;
				for ( j = 0; j < s.length; j++ ) {
					if ( s[j].name == 'lang' ) {
						l = 1;
					}
				}
				if ( l == 0 ) {
					var ih = document.createElement( 'input' );
					ih.type = 'hidden';
					ih.name = 'lang';
					ih.value = '$lang';
					e[i].parentNode.appendChild( ih );
				}
			}
		}
		//]]>";
		echo '<script type="text/javascript">' . $js . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
