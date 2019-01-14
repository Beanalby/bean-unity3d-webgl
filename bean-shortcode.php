<?php

function bean_unity3d_shortcode_game($atts = [], $content = null, $tag = '') {
	$util = new Bean_util();
	// normalize attribute keys, lowercase
	$atts = array_change_key_case((array)$atts, CASE_LOWER);

	// currently no defaults, but set up just in case
	$wporg_atts = shortcode_atts([
		'name' => '',
		'width' => $util->get_default_width(),
		'height' => $util->get_default_height()
	], $atts, $tag);
	$game = $util->get_game_by_name($wporg_atts['name']);
	if(is_wp_error($game)) {
		return "<p>Error:  [[" . esc_html($game->get_error_message()) . "]]</p>";
	}
	$gameUrl = wp_upload_dir()['baseurl'] . $game->path;

	$content="";
	switch($game->unity3d_version) {
	case "2018.1":
		$templateUrl = plugins_url("TemplateData-2018.1", __FILE__);

		$content .= "<link rel='shortcut icon' href='" . $templateUrl . "/favicon.ico'>\n";
		$content .= "<script src='" . $templateUrl . "/UnityProgress.js'></script>\n";
		$content .= "<script src='" . $gameUrl . "/UnityLoader.js'></script>\n";
		$content .= "<script>\n";
		$content .= "    var gameInstance = UnityLoader.instantiate(\"gameContainer\", \"" . $gameUrl . "/" . esc_html($game->json_filename) . "\", {onProgress: UnityProgress});\n";
		$content .= "</script>\n";
		$content .= "<div class='webgl-content'>\n";
		$content .= "<div id='gameContainer' style='width: " . $wporg_atts["width"] . "px; height: " . $wporg_atts["height"] . "px'></div>";
		$content .= "<div style='clear; both'>Content after</div>";
		$content .= "</div>";
		break;
	default:
		$content = "<p>Internal Error: unsupported Unity3d version [" . esc_html($game->unity3d_version) . "] encountered</p>\n";
		break;
	}
	return $content;
}

?>
