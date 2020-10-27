<?php
/**
 * @package Polylang
 */

if ( ! class_exists( 'WP_Widget_Calendar' ) ) {
	require_once ABSPATH . '/wp-includes/default-widgets.php';
}

/**
 * This classes rewrite the whole Calendar widget functionality as there is no filter on sql queries and only a filter on final output.
 * Code last checked: WP 5.5.
 *
 * A request to add filters on sql queries exists: http://core.trac.wordpress.org/ticket/15202.
 * Method used in 0.4.x: use of the get_calendar filter and overwrite the output of get_calendar function -> not very efficient (add 4 to 5 sql queries).
 * Method used since 0.5: remove the WP widget and replace it by our own -> our language filter will not work if get_calendar is called directly by a theme.
 *
 * @since 0.5
 */
class PLL_Widget_Calendar extends WP_Widget_Calendar {
	protected static $pll_instance = 0; // Can't use $instance of WP_Widget_Calendar as it's private :/.

	/**
	 * Outputs the content for the current Calendar widget instance.
	 * Modified version of the parent function to call our own get_calendar() method.
	 *
	 * @since 0.5
	 *
	 * @param array $args     Display arguments including 'before_title', 'after_title',
	 *                        'before_widget', and 'after_widget'.
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	public function widget( $args, $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';

		/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		if ( 0 === self::$pll_instance ) { #modified#
			echo '<div id="calendar_wrap" class="calendar_wrap">';
		} else {
			echo '<div class="calendar_wrap">';
		}
		empty( PLL()->curlang ) ? get_calendar() : self::get_calendar(); #modified#
		echo '</div>';
		echo $args['after_widget'];

		self::$pll_instance++; #modified#
	}

	/**
	 * Modified version of the WP get_calendar() function to filter the queries.
	 *
	 * @since 0.5
	 *
	 * @param bool $initial Optional, default is true. Use initial calendar names.
	 * @param bool $echo    Optional, default is true. Set to false for return.
	 * @return void|string Void if `$echo` argument is true, calendar HTML if `$echo` is false.
 	 */
	static public function get_calendar( $initial = true, $echo = true ) {
		global $wpdb, $m, $monthnum, $year, $wp_locale, $posts;

		$join_clause  = PLL()->model->post->join_clause(); #added#
		$where_clause = PLL()->model->post->where_clause( PLL()->curlang ); #added#

		$key   = md5( PLL()->curlang->slug . $m . $monthnum . $year ); #modified#
		$cache = wp_cache_get( 'get_calendar', 'calendar' );

		if ( $cache && is_array( $cache ) && isset( $cache[ $key ] ) ) {
			/** This filter is documented in wp-includes/general-template.php */
			$output = apply_filters( 'get_calendar', $cache[ $key ] );

			if ( $echo ) {
				echo $output;
				return;
			}

			return $output;
		}

		if ( ! is_array( $cache ) ) {
			$cache = array();
		}

		// Quick check. If we have no posts at all, abort!
		if ( ! $posts ) {
			$gotsome = $wpdb->get_var( "SELECT 1 as test FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' LIMIT 1" );
			if ( ! $gotsome ) {
				$cache[ $key ] = '';
				wp_cache_set( 'get_calendar', $cache, 'calendar' );
				return;
			}
		}

		if ( isset( $_GET['w'] ) ) {
			$w = (int) $_GET['w'];
		}
		// week_begins = 0 stands for Sunday.
		$week_begins = (int) get_option( 'start_of_week' );

		// Let's figure out when we are.
		if ( ! empty( $monthnum ) && ! empty( $year ) ) {
			$thismonth = zeroise( (int) $monthnum, 2 );
			$thisyear  = (int) $year;
		} elseif ( ! empty( $w ) ) {
			// We need to get the month from MySQL.
			$thisyear = (int) substr( $m, 0, 4 );
			// It seems MySQL's weeks disagree with PHP's.
			$d         = ( ( $w - 1 ) * 7 ) + 6;
			$thismonth = $wpdb->get_var( "SELECT DATE_FORMAT((DATE_ADD('{$thisyear}0101', INTERVAL $d DAY) ), '%m')" );
		} elseif ( ! empty( $m ) ) {
			$thisyear = (int) substr( $m, 0, 4 );
			if ( strlen( $m ) < 6 ) {
				$thismonth = '01';
			} else {
				$thismonth = zeroise( (int) substr( $m, 4, 2 ), 2 );
			}
		} else {
			$thisyear  = current_time( 'Y' );
			$thismonth = current_time( 'm' );
		}

		$unixmonth = mktime( 0, 0, 0, $thismonth, 1, $thisyear );
		$last_day  = gmdate( 't', $unixmonth );

		// Get the next and previous month and year with at least one post.
		$previous = $wpdb->get_row(
			"SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
			FROM $wpdb->posts $join_clause
			WHERE post_date < '$thisyear-$thismonth-01'
			AND post_type = 'post' AND post_status = 'publish' $where_clause
				ORDER BY post_date DESC
				LIMIT 1"
		);  #modified#
		$next     = $wpdb->get_row(
			"SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
			FROM $wpdb->posts $join_clause
			WHERE post_date > '$thisyear-$thismonth-{$last_day} 23:59:59'
			AND post_type = 'post' AND post_status = 'publish' $where_clause
				ORDER BY post_date ASC
				LIMIT 1"
		);  #modified#

		/* translators: Calendar caption: 1: Month name, 2: 4-digit year. */
		$calendar_caption = _x( '%1$s %2$s', 'calendar caption' );
		$calendar_output  = '<table id="wp-calendar" class="wp-calendar-table">
		<caption>' . sprintf(
			$calendar_caption,
			$wp_locale->get_month( $thismonth ),
			gmdate( 'Y', $unixmonth )
		) . '</caption>
		<thead>
		<tr>';

		$myweek = array();

		for ( $wdcount = 0; $wdcount <= 6; $wdcount++ ) {
			$myweek[] = $wp_locale->get_weekday( ( $wdcount + $week_begins ) % 7 );
		}

		foreach ( $myweek as $wd ) {
			$day_name         = $initial ? $wp_locale->get_weekday_initial( $wd ) : $wp_locale->get_weekday_abbrev( $wd );
			$wd               = esc_attr( $wd );
			$calendar_output .= "\n\t\t<th scope=\"col\" title=\"$wd\">$day_name</th>";
		}

		$calendar_output .= '
		</tr>
		</thead>
		<tbody>
		<tr>';

		$daywithpost = array();

		// Get days with posts.
		$dayswithposts = $wpdb->get_results(
			"SELECT DISTINCT DAYOFMONTH(post_date)
			FROM $wpdb->posts $join_clause WHERE post_date >= '{$thisyear}-{$thismonth}-01 00:00:00'
			AND post_type = 'post' AND post_status = 'publish'
			AND post_date <= '{$thisyear}-{$thismonth}-{$last_day} 23:59:59' $where_clause",
			ARRAY_N
		); #modified#

		if ( $dayswithposts ) {
			foreach ( (array) $dayswithposts as $daywith ) {
				$daywithpost[] = (int) $daywith[0];
			}
		}

		// See how much we should pad in the beginning.
		$pad = calendar_week_mod( gmdate( 'w', $unixmonth ) - $week_begins );
		if ( 0 != $pad ) {
			$calendar_output .= "\n\t\t" . '<td colspan="' . esc_attr( $pad ) . '" class="pad">&nbsp;</td>';
		}

		$newrow      = false;
		$daysinmonth = (int) gmdate( 't', $unixmonth );

		for ( $day = 1; $day <= $daysinmonth; ++$day ) {
			if ( isset( $newrow ) && $newrow ) {
				$calendar_output .= "\n\t</tr>\n\t<tr>\n\t\t";
			}
			$newrow = false;

			if ( current_time( 'j' ) == $day &&
				current_time( 'm' ) == $thismonth &&
				current_time( 'Y' ) == $thisyear ) {
				$calendar_output .= '<td id="today">';
			} else {
				$calendar_output .= '<td>';
			}

			if ( in_array( $day, $daywithpost, true ) ) {
				// Any posts today?
				$date_format = gmdate( _x( 'F j, Y', 'daily archives date format' ), strtotime( "{$thisyear}-{$thismonth}-{$day}" ) );
				/* translators: Post calendar label. %s: Date. */
				$label            = sprintf( __( 'Posts published on %s' ), $date_format );
				$calendar_output .= sprintf(
					'<a href="%s" aria-label="%s">%s</a>',
					get_day_link( $thisyear, $thismonth, $day ),
					esc_attr( $label ),
					$day
				);
			} else {
				$calendar_output .= $day;
			}

			$calendar_output .= '</td>';

			if ( 6 == calendar_week_mod( gmdate( 'w', mktime( 0, 0, 0, $thismonth, $day, $thisyear ) ) - $week_begins ) ) {
				$newrow = true;
			}
		}

		$pad = 7 - calendar_week_mod( gmdate( 'w', mktime( 0, 0, 0, $thismonth, $day, $thisyear ) ) - $week_begins );
		if ( 0 != $pad && 7 != $pad ) {
			$calendar_output .= "\n\t\t" . '<td class="pad" colspan="' . esc_attr( $pad ) . '">&nbsp;</td>';
		}

		$calendar_output .= "\n\t</tr>\n\t</tbody>";

		$calendar_output .= "\n\t</table>";

		$calendar_output .= '<nav aria-label="' . __( 'Previous and next months' ) . '" class="wp-calendar-nav">';

		if ( $previous ) {
			$calendar_output .= "\n\t\t" . '<span class="wp-calendar-nav-prev"><a href="' . get_month_link( $previous->year, $previous->month ) . '">&laquo; ' .
				$wp_locale->get_month_abbrev( $wp_locale->get_month( $previous->month ) ) .
			'</a></span>';
		} else {
			$calendar_output .= "\n\t\t" . '<span class="wp-calendar-nav-prev">&nbsp;</span>';
		}

		$calendar_output .= "\n\t\t" . '<span class="pad">&nbsp;</span>';

		if ( $next ) {
			$calendar_output .= "\n\t\t" . '<span class="wp-calendar-nav-next"><a href="' . get_month_link( $next->year, $next->month ) . '">' .
				$wp_locale->get_month_abbrev( $wp_locale->get_month( $next->month ) ) .
			' &raquo;</a></span>';
		} else {
			$calendar_output .= "\n\t\t" . '<span class="wp-calendar-nav-next">&nbsp;</span>';
		}

		$calendar_output .= '
		</nav>';

		$cache[ $key ] = $calendar_output;
		wp_cache_set( 'get_calendar', $cache, 'calendar' );

		if ( $echo ) {
			/** This filter is documented in wp-includes/general-template.php */
			echo apply_filters( 'get_calendar', $calendar_output );
			return;
		}
		/** This filter is documented in wp-includes/general-template.php */
		return apply_filters( 'get_calendar', $calendar_output );
	}
}
