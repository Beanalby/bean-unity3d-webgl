<?php

function bean_unity3d_shortcode_game($atts = [], $content = null, $tag = '') {
	$util = new Bean_util();
	// normalize attribute keys, lowercase
	$atts = array_change_key_case((array)$atts, CASE_LOWER);

	// currently no defaults, but set up just in case
	$wporg_atts = shortcode_atts([
		'name' => ''
	], $atts, $tag);
	$game = $util->get_game($wporg_atts['name']);
	$content = '<p>game ' . esc_html($game->name) . ' lives in ' . esc_html($game->path) . '</p>';
	return $content;
}

?>
