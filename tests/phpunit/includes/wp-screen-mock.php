<?php
/**
 * Used to create mocks for {@see WP_Screen} because this is class declared as final.
 * PHPUnit can't mock final classes, because the common way it mocks object is by inheriting them in child classes.
 *
 * WP_Screen has a few behaviour we don't want to trigger in our tests :
 *  - it registers itself in the $wp_current_screen global variable upon instantiation.
 *  - it triggers hooks
 *
 * Inspired by @see https://gist.github.com/DragonBe/24761f350984c35b73966809dd439135
 */
class Wp_Screen_Mock {

	/**
	 * The base type of the screen.
	 *
	 * This is typically the same as `$id` but with any post types and taxonomies stripped.
	 * For example, for an `$id` of 'edit-post' the base is 'edit'.
	 *
	 * @var string
	 */
	public $base;

	/**
	 * The unique ID of the screen.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * The post type associated with the screen, if any.
	 *
	 * The 'edit.php?post_type=page' screen has a post type of 'page'.
	 * The 'edit-tags.php?taxonomy=$taxonomy&post_type=page' screen has a post type of 'page'.
	 *
	 * @var string
	 */
	public $post_type;

	/**
	 * Reflection of the WP_Screen class.
	 *
	 * @var ReflectionClass
	 */
	private $screen;

	/**
	 * Wp_Screen_Mock constructor.
	 */
	public function __construct() {
		$this->screen = new ReflectionClass( WP_Screen::class );
	}

	/**
	 * Calls a method of the original class through its reflection.
	 *
	 * @param string $method_name The name of the original class' method.
	 * @param array  $args An array of argument to pas to this method.
	 * @return mixed
	 */
	public function __call( $method_name, $args ) {
		$screen = $this->screen->newInstance( $args );
		return $screen->$method_name();
	}
}
