<?php

/**
 * A trait to help testing widgets.
 */
trait PLL_Widgets_Trait {
	/**
	 * Copied from WP widgets tests
	 */
	public function clean_up_global_scope() {
		global $_wp_sidebars_widgets, $wp_widget_factory, $wp_registered_sidebars, $wp_registered_widgets, $wp_registered_widget_controls, $wp_registered_widget_updates;

		$_wp_sidebars_widgets = array();
		$wp_registered_sidebars = array();
		$wp_registered_widgets = array();
		$wp_registered_widget_controls = array();
		$wp_registered_widget_updates = array();
		$wp_widget_factory->widgets = array();

		parent::clean_up_global_scope();
	}
}
