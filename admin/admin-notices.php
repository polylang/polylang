<?php
/**
 * Displays notices to users
 *
 * @since 2.7
 */
class PLL_Admin_Notices {
	/**
	 * @var array {
	 *     string $name Used to identify the notice in the html
	 *     string $html HTML formated string to display
	 */
	protected static $notices = array();

	/**
	 * PLL_Admin_Notices constructor.
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'display_notices' ) );
	}

	/**
	 * Add a custom notice
	 *
	 * @param string $name Notice name
	 * @param string $html Content of the notice
	 */
	public static function add_notice($name, $html) {
		self::$notices[$name] = $html;
	}

	/**
	 * Get custom notices
	 *
	 * @return array
	 */
	public static function get_notices() {
		return self::$notices;
	}

	/**
	 * Displays notices to the user
	 *
	 * @param string $html The base HTML formatted string to display.
	 */
	public function display_notices() {
		foreach ( $this->get_notices() as $notice => $html ) {
			$this->display_notice( $notice, $html );
		}
	}

	/**
	 * Display an individual notice
	 *
	 * @param string $html An HTML formatted string to display
	 */
	protected function display_notice( $notice, $html ) {
		?>
		<div class="pll-notice notice notice-info">
			<?php
			echo wp_kses_post( $html );
			?>
		</div>
		<?php
	}
}
