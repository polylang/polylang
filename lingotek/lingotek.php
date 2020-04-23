<?php
/**
 * @package Polylang
 */

/**
 * Class to manage Lingotek ads
 *
 * @since 1.7.7
 */
class PLL_Lingotek {
	const LINGOTEK = 'lingotek-translation/lingotek.php';

	/**
	 * Init
	 *
	 * @since 1.7.7
	 */
	public function init() {
		// The Lingotek tab
		add_filter( 'pll_settings_tabs', array( $this, 'add_tab' ) );
		add_action( 'pll_settings_active_tab_lingotek', array( $this, 'display_tab' ) );

		if ( PLL_SETTINGS && isset( $_GET['page'] ) && 'mlang_lingotek' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			add_action( 'admin_print_styles', array( $this, 'print_css' ) );
		}

		// The admin notice
		// Honor old dismissed pointers
		if ( ! PLL_Admin_Notices::is_dismissed( 'lingotek' ) && ! in_array( 'pll_lgt', explode( ',', get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) ) ) ) {
			$content = __( 'You’ve just upgraded to the latest version of Polylang! Would you like to automatically translate your website for free?', 'polylang' );

			$buttons = sprintf(
				'<a href="%s" class="button button-primary">%s</a>',
				admin_url( 'admin.php?page=mlang_lingotek' ),
				__( 'Learn more', 'polylang' )
			);

			if ( $link = $this->get_activate_link() ) {
				$content .= ' ' . __( 'Click on Activate Lingotek to start translating.', 'polylang' );

				$buttons = sprintf(
					'<a href="%s" class="button button-primary">%s</a>',
					$link,
					__( 'Activate Lingotek', 'polylang' )
				) . $buttons;
			}

			if ( is_plugin_active( self::LINGOTEK ) ) { // Needs /wp-admin/includes/plugin.php to be loaded
				PLL_Admin_Notices::dismiss( 'lingotek' );
			} else {
				PLL_Admin_Notices::add_notice( 'lingotek', '<p>' . $content . '</p><p>' . $buttons . '</p>' );
			}
		}
	}

	/**
	 * Adds the Lingotek tab in Polylang settings
	 *
	 * @since 1.7.7
	 *
	 * @param array $tabs list of tabs
	 * @return array modified liste of tabs
	 */
	public function add_tab( $tabs ) {
		$tabs['lingotek'] = 'Lingotek';
		return $tabs;
	}

	/**
	 * Displays the content in the Lingotek tab
	 *
	 * @since 1.7.7
	 */
	public function display_tab() {
		PLL_Admin_Notices::dismiss( 'lingotek' );

		$activate_link = $this->get_activate_link();

		$links = array(
			'activate' => array(
				'label'   => is_plugin_active( self::LINGOTEK ) ? __( 'Activated', 'polylang' ) : __( 'Activate', 'polylang' ),
				'link'    => $activate_link,
				'classes' => 'button button-primary' . ( $activate_link ? '' : ' disabled' ),
			),
			'translation' => array(
				'label'   => __( 'Request Translation', 'polylang' ),
				'link'    => 'http://www.lingotek.com/wordpress/translation_bid',
				'new_tab' => true,
				'classes' => 'button button-primary',
			),
			'services' => array(
				'label'   => __( 'Request Services', 'polylang' ),
				'link'    => 'http://www.lingotek.com/wordpress/extra_services',
				'new_tab' => true,
				'classes' => 'button button-primary',
			),
		);

		printf( '<p>%s</p>', esc_html__( 'Polylang is now fully integrated with Lingotek, a professional translation management system!', 'polylang' ) );

		$this->box(
			__( 'Automatically translate my site', 'polylang' ),
			__( 'Polylang is now fully integrated with Lingotek!', 'polylang' ),
			array(
				__( 'Access free machine translation for your site for up to 100,000 characters.', 'polylang' ),
				__( 'If you\'re on a tight budget, looking for near-instant results, and are okay with less-than-perfect quality, machine translation is an excellent option.', 'polylang' ),
			),
			array_intersect_key( $links, array_flip( array( 'activate' ) ) ),
			'image01.gif'
		);

		$this->box(
			__( 'Translation Management System', 'polylang' ),
			__( 'Do you need to connect to a professional translation management system?', 'polylang' ),
			array(
				__( 'Access free machine translation for your site for up to 100,000 characters.', 'polylang' ),
				__( 'Access an online translator workbench.', 'polylang' ),
				__( 'Have linguists compare side-by-side versions of original and translated text.', 'polylang' ),
				__( 'Save and re-use previously translated material (leverage translation memory (TM)).', 'polylang' ),
			),
			array_intersect_key( $links, array_flip( array( 'activate' ) ) ),
			'image02.png'
		);

		$this->box(
			__( 'Professionally translate my site', 'polylang' ),
			__( 'Do you need professional translations for your site?', 'polylang' ),
			array(
				__( 'Start the process of getting a professional translation bid.', 'polylang' ),
				__( 'When you activate your account, Lingotek will be able to get an accurate count of the number of words in your site. Then tell them which languages you wish to have them translated into.', 'polylang' ),
				__( 'Once activated, click on the "request translation bid" and a certified translation project manager will contact you for a no-obligations translation bid.', 'polylang' ),
			),
			array_intersect_key( $links, array_flip( array( 'activate', 'translation' ) ) ),
			'image03.png'
		);

		$this->box(
			__( 'Need extra services?', 'polylang' ),
			__( 'Do you need help translating your site?', 'polylang' ),
			array(
				__( 'Start the process of getting extra services.', 'polylang' ),
				__( 'Do you need someone to run your localization project?', 'polylang' ),
				__( 'Do you need customized workflows?', 'polylang' ),
				__( 'Do you have existing Translation Memories you would like to use?', 'polylang' ),
				__( 'Do you need help creating glossaries and terminologies?', 'polylang' ),
			),
			array_intersect_key( $links, array_flip( array( 'activate', 'services' ) ) ),
			'image04.png'
		);
	}

	/**
	 * Styles the content of the Lingotek tab
	 *
	 * @since 1.7.7
	 */
	public function print_css() {
		?>
		<style type="text/css">
		.ltk-feature {
			text-align: left;
			float: left;
			width: 310px;
			padding: 0px;
			border: 1px solid #ddd;
			margin-right: 3px;
			margin-bottom: 3px;
			height: 650px;
			background: #fafafa;
		}
		.rtl .ltk-feature {
			text-align: right;
			float: right;
		}
		.ltk-feature h3 {
			height: 1em;
		}
		.ltk-feature .ltk-image {
			text-align: center;
		}
		.ltk-feature img {
			margin: 10px;
			width: 180px;
			height: 180px;
			height: auto;
		}
		.ltk-feature ul {
			margin-left: 10px;
		}
		.rtl .ltk-feature ul {
			margin-right: 10px;
		}
		.ltk-feature ul li {
			list-style: inside disc;
			list-style-position: outside;
			padding-left: 0;
		}
		.rtl .ltk-feature ul li {
			padding-right: 0;
		}
		.ltk-feature .ltk-desc {
			height: 3em;
			width: 100%;
		}
		.ltk-feature .ltk-upper {
			background: #fff;
			padding: 20px;
		}
		.ltk-feature .ltk-lower {
			padding: 5px 20px 0px 20px;
			border-top: 1px solid #eee;
			font-size: 95%;
		}

		@media screen and ( max-width: 620px ) {
			.ltk-feature {
				height: auto;
				padding-bottom: 20px;
			}
		}
		</style>
		<?php
	}

	/**
	 * Outputs the content of each box
	 *
	 * @since 1.7.7
	 *
	 * @param string $title
	 * @param string $desc
	 * @param array  $list
	 * @param array  $links
	 * @param string $img
	 */
	protected function box( $title, $desc, $list, $links, $img ) {
		?>
		<div class="ltk-feature">
			<div class="ltk-upper">
				<div class="ltk-image">
					<img src="<?php echo esc_url( plugins_url( $img, __FILE__ ) ); ?> " width="220" height="220"/>
				</div>
				<h3><?php echo esc_html( $title ); ?></h3>
				<p class="ltk-desc"><?php echo esc_html( $desc ); ?></p>
				<?php
				foreach ( $links as $link_details ) {
					printf(
						'<a class = "%s" href = "%s"%s>%s</a> ',
						esc_attr( $link_details['classes'] ),
						esc_url( $link_details['link'] ),
						empty( $link_details['new_tab'] ) ? '' : ' target = "_blank"',
						esc_html( $link_details['label'] )
					);
				}
				?>
			</div>
			<div class="ltk-lower">
				<ul>
					<?php
					foreach ( $list as $item ) {
						printf( '<li>%s</li>', esc_html( $item ) );
					}
					?>
				</ul>
				<a href="http://www.lingotek.com/wordpress" target = "_blank"><?php esc_html_e( 'Learn more...', 'polylang' ); ?></a>
			</div>

		</div>
		<?php
	}

	/**
	 * Get a link to install / activate Lingotek
	 * depending on user rights and if plugin is already installed
	 *
	 * @since 1.7.7
	 *
	 * @return string
	 */
	protected function get_activate_link() {
		require_once ABSPATH . '/wp-admin/includes/plugin.php';

		if ( ! array_key_exists( self::LINGOTEK, get_plugins() ) ) {
			if ( current_user_can( 'install_plugins' ) ) {
				$plugin = dirname( self::LINGOTEK );
				return wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $plugin ), 'install-plugin_' . $plugin );
			}
		}

		elseif ( current_user_can( 'activate_plugins' ) ) {
			if ( ! is_plugin_active( self::LINGOTEK ) ) {
				return wp_nonce_url( 'plugins.php?action=activate&plugin=' . self::LINGOTEK, 'activate-plugin_' . self::LINGOTEK );
			}
		}

		return '';
	}
}
