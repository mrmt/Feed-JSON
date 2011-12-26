<?php
/**
 * JSON Feed Template for displaying JSON Posts feed.
 *
 */
$callback = trim(esc_html(get_query_var('callback')));
$charset = get_bloginfo('charset');

function get_the_excerpt_max_charlength($charlength) {
	$excerpt = get_the_excerpt();
	if(strlen($excerpt) > $charlength) {
		return mb_substr($excerpt, 0, $charlength);
	}
	return $excerpt;
}

// use our json_encode
function my_json_encode( $string ) {
	require_once( 'class-json.php' );
	$json = new My_Services_JSON();
	return $json->encode( $string );
}

if ( have_posts() ) {
	$json = array();
	while ( have_posts() ) {
		the_post();
		$id = (int) $post->ID;

		if(get_option(FEED_JSON_ICON_URL)){
			$icon_url = get_option(FEED_JSON_ICON_URL);
		}else{
			$icon_url = FEED_JSON_ICON_URL_DEFAULT;
		}

		$single = array(
			'contentUri' => get_bloginfo('url'), // not used
			'title' => get_the_title(),
			'body' => get_the_excerpt_max_charlength(16) . '...', // not used
			'urls' => array(
				'pcUrl' => get_permalink(),
				'mobileUrl' => '', // not used
				'smartphoneUrl' => '',// not used
				),
			'images' => array(
				'force_array' => 1,
				'large' => '',// not used
				'medium' => '',// not used
				'small' => $icon_url,
				),
			'user' => array(
				'id' => '', // not used
				'displayName' => '', // not used
				'thumbnailUrl' => '', // not used
				),
			'favoriteCount' => '', // not used
			'commentCount' => '', // not used
			'sourceName' => '', // not used
			'created' => get_the_date('c','','',false) ,
			);
		$json[] = $single;
	}

	$json = '{"entry" : ' . my_json_encode($json) . '}';

	nocache_headers();
	if (!empty($callback)) {
		header("Content-Type: application/x-javascript; charset=$charset");
		echo "$callback($json);";
	} else {
		header("Content-Type: application/json; charset=$charset");
		echo "$json";
	}

} else {
	header("HTTP/1.0 404 Not Found");
	wp_die("404 Not Found");
}
