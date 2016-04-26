<?php

if ( ! class_exists( 'WP_Widget_Recent_Comments' ) ) {
	require_once( ABSPATH . '/wp-includes/default-widgets.php' );
}

/*
 * backward compatibility with WP < 4.4
 * obliged to rewrite the whole functionnality to have a language dependant cache key
 * code base is WP 4.2
 *
 * @since 1.5
 */
class PLL_Widget_Recent_Comments extends WP_Widget_Recent_Comments {

	/*
	 * displays the widget
	 * modified version of the parent function to call to use a language dependant cache key
	 *
	 * @since 1.5
	 *
	 * @param array $args Display arguments including before_title, after_title, before_widget, and after_widget.
	 * @param array $instance The settings for the particular instance of the widget
	 */
	public function widget( $args, $instance ) {
		global $comments, $comment;

		$cache = array();
		if ( ! $this->is_preview() ) {
			$cache = wp_cache_get( 'widget_recent_comments', 'widget' );
		}
		if ( ! is_array( $cache ) ) {
			$cache = array();
		}

		if ( ! isset( $args['widget_id'] ) )
			$args['widget_id'] = $this->id;

		$lang = pll_current_language(); #added
		if ( isset( $cache[ $args['widget_id'] ][ $lang ] ) ) { #modified#
			echo $cache[ $args['widget_id'] ][ $lang ]; #modified#
			return;
		}

		$output = '';

		$title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : __( 'Recent Comments' );

		/** This filter is documented in wp-includes/default-widgets.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 5;
		if ( ! $number )
			$number = 5;

		/**
		 * Filter the arguments for the Recent Comments widget.
		 *
		 * @since 3.4.0
		 *
		 * @see WP_Comment_Query::query() for information on accepted arguments.
		 *
		 * @param array $comment_args An array of arguments used to retrieve the recent comments.
		 */
		$comments = get_comments( apply_filters( 'widget_comments_args', array(
			'number'      => $number,
			'status'      => 'approve',
			'post_status' => 'publish'
		) ) );

		$output .= $args['before_widget'];
		if ( $title ) {
			$output .= $args['before_title'] . $title . $args['after_title'];
		}

		$output .= '<ul id="recentcomments">';
		if ( $comments ) {
			// Prime cache for associated posts. (Prime post term cache if we need it for permalinks.)
			$post_ids = array_unique( wp_list_pluck( $comments, 'comment_post_ID' ) );
			_prime_post_caches( $post_ids, strpos( get_option( 'permalink_structure' ), '%category%' ), false );

			foreach ( (array) $comments as $comment ) {
				$output .= '<li class="recentcomments">';
				/* translators: comments widget: 1: comment author, 2: post link */
				$output .= sprintf( _x( '%1$s on %2$s', 'widgets' ),
					'<span class="comment-author-link">' . get_comment_author_link() . '</span>',
					'<a href="' . esc_url( get_comment_link( $comment->comment_ID ) ) . '">' . get_the_title( $comment->comment_post_ID ) . '</a>'
				);
				$output .= '</li>';
			}
		}
		$output .= '</ul>';
		$output .= $args['after_widget'];

		echo $output;

		if ( ! $this->is_preview() ) {
			$cache[ $args['widget_id'] ][ $lang ] = $output; #modified#
			wp_cache_set( 'widget_recent_comments', $cache, 'widget' );
		}
	}
}
