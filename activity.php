<?php

// add the `Activity` widget to the dashboard
add_action( 'wp_dashboard_setup', 'add_activity_dashboard_widget' );
function add_activity_dashboard_widget() {
	add_meta_box( 'dashboard_activity', __( 'Activity' ), 'wp_dashboard_activity', 'dashboard', 'normal', 'high' );
}

// callback function for `Activity` widget
function wp_dashboard_activity() {

	echo '<div id="activity-widget">';

	dash_show_published_posts( array(
		'display' => 2,
		'max' => 5,
		'status' => 'future',
		'title' => __( 'Publishing Soon' ),
		'id' => 'future-posts',
	) );
	dash_show_published_posts( array(
		'display' => 2,
		'max' => 5,
		'status' => 'publish',
		'title' => __( 'Recently Published' ),
		'id' => 'published-posts',
	) );
	dash_comments();

	echo '</div>';

}

// Generates `Publishing Soon` and `Recently Published` sections
function dash_show_published_posts( $args ) {

	$posts = new WP_Query(array(
		'post_type' => 'post',
		'post_status' => $args['status'],
		'orderby' => 'date',
		'order' => 'ASC',
		'posts_per_page' => intval( $args['max'] )
	));

	if ( $posts->have_posts() ) {

		echo '<div id="' . $args['id'] . '" class="activity-block">';
		echo $args['title'];

		if ( $posts->post_count > $args['display'] ) {
			echo '<small class="show-more"><a href="#">' . sprintf( __( 'See %s more&hellip;'), $posts->post_count - intval( $args['display'] ) ) . '</a></small>';
		}

		echo '<ul>';

		$i = 0;
		while ( $posts->have_posts() ) {
			$posts->the_post();
			printf(
				'<li%s><span>%s, %s</span> <a href="%s">%s</a></li>',
				( $i >= intval ( $args['display'] ) ? ' class="hidden"' : '' ),
				dash_relative_date( get_the_time( 'U' ) ),
				get_the_time(),
				get_edit_post_link(),
				get_the_title()
			);
			$i++;
		}

		echo '</ul>';
		echo '</div>';

	}

	wp_reset_postdata();

}

// show `Comments` section
function dash_comments( $total_items = 5 ) {
	global $wpdb;

	// Select all comment types and filter out spam later for better query performance.
	$comments = array();
	$start = 0;

	$comments_query = array( 'number' => $total_items * 5, 'offset' => 0 );
	if ( ! current_user_can( 'edit_posts' ) )
		$comments_query['status'] = 'approve';

	while ( count( $comments ) < $total_items && $possible = get_comments( $comments_query ) ) {
		foreach ( $possible as $comment ) {
			if ( ! current_user_can( 'read_post', $comment->comment_post_ID ) )
				continue;
			$comments[] = $comment;
			if ( count( $comments ) == $total_items )
				break 2;
		}
		$comments_query['offset'] += $comments_query['number'];
		$comments_query['number'] = $total_items * 10;
	}
	
	echo '<div id="latest-comments" class="activity-block">';
	_e( 'Comments' );

	if ( $comments ) {
		echo '<div id="the-comment-list" data-wp-lists="list:comment">';
		foreach ( $comments as $comment )
			_wp_dashboard_recent_comments_row( $comment );
		echo '</div>';

		if ( current_user_can('edit_posts') )
			_get_list_table('WP_Comments_List_Table')->views();

		wp_comment_reply( -1, false, 'dashboard', false );
		wp_comment_trashnotice();
	} else {
		echo '<p>' . __( 'No comments yet.' ) . '</p>';
	}
	
	echo '</div>';

}

// return relative date for given timestamp
function dash_relative_date( $time ) {

	$diff = floor( ( $time - time() ) / DAY_IN_SECONDS );

	if ( $diff == 0 ) {
		return __( 'Today' );
	}

	if ( $diff == 1 ) {
		return __( 'Tomorrow' );
	}

	return date( 'M jS', $time);

}
