<?php
/**
 * @package Polylang
 */

/**
 * Settings class for synchronization settings management
 *
 * @since 1.8
 */
class PLL_Settings_Sync extends PLL_Settings_Module {
	/**
	 * Stores the display order priority.
	 *
	 * @var int
	 */
	public $priority = 50;

	/**
	 * Constructor
	 *
	 * @since 1.8
	 *
	 * @param object $polylang polylang object
	 */
	public function __construct( &$polylang ) {
		parent::__construct(
			$polylang,
			array(
				'module'      => 'sync',
				'title'       => __( 'Synchronization', 'polylang' ),
				'description' => __( 'The synchronization options allow to maintain exact same values (or translations in the case of taxonomies and page parent) of meta content between the translations of a post or page.', 'polylang' ),
			)
		);
	}

	/**
	 * Deactivates the module
	 *
	 * @since 1.8
	 */
	public function deactivate() {
		$this->options['sync'] = array();
		update_option( 'polylang', $this->options );
	}

	/**
	 * Displays the settings form
	 *
	 * @since 1.8
	 */
	protected function form() {
		?>
		<ul class="pll-inline-block-list">
			<?php
			foreach ( self::list_metas_to_sync() as $key => $str ) {
				printf(
					'<li><label><input name="sync[%s]" type="checkbox" value="1" %s /> %s</label></li>',
					esc_attr( $key ),
					checked( in_array( $key, $this->options['sync'] ), true, false ),
					esc_html( $str )
				);
			}
			?>
		</ul>
		<?php
	}

	/**
	 * Sanitizes the settings before saving
	 *
	 * @since 1.8
	 *
	 * @param array $options
	 */
	protected function update( $options ) {
		$newoptions = array( 'sync' => empty( $options['sync'] ) ? array() : array_keys( $options['sync'], 1 ) );
		return $newoptions; // take care to return only validated options
	}

	/**
	 * Get the row actions.
	 *
	 * @since 1.8
	 *
	 * @return string[] Row actions.
	 */
	protected function get_actions() {
		return empty( $this->options['sync'] ) ? array( 'configure' ) : array( 'configure', 'deactivate' );
	}

	/**
	 * Get the list of synchronization settings.
	 *
	 * @since 1.0
	 *
	 * @return string[] Array synchronization options.
	 */
	public static function list_metas_to_sync() {
		return array(
			'taxonomies'        => __( 'Taxonomies', 'polylang' ),
			'post_meta'         => __( 'Custom fields', 'polylang' ),
			'comment_status'    => __( 'Comment status', 'polylang' ),
			'ping_status'       => __( 'Ping status', 'polylang' ),
			'sticky_posts'      => __( 'Sticky posts', 'polylang' ),
			'post_date'         => __( 'Published date', 'polylang' ),
			'post_format'       => __( 'Post format', 'polylang' ),
			'post_parent'       => __( 'Page parent', 'polylang' ),
			'_wp_page_template' => __( 'Page template', 'polylang' ),
			'menu_order'        => __( 'Page order', 'polylang' ),
			'_thumbnail_id'     => __( 'Featured image', 'polylang' ),
		);
	}
}
