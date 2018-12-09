<?php

function bean_unity3d_shortcode_game($atts = [], $content = null, $tag = '') {
	$util = new Bean_util();
	// normalize attribute keys, lowercase
	$atts = array_change_key_case((array)$atts, CASE_LOWER);

	// currently no defaults, but set up just in case
	$wporg_atts = shortcode_atts([
		'name' => '',
		'width' => '960px',
		'height' => '600px'
	], $atts, $tag);
	$game = $util->get_game_by_name($wporg_atts['name']);
	if(is_wp_error($game)) {
		return "<p>Error:  [[" . $game->get_error_code() . "]]</p>";
	}

	$templateUrl = plugins_url("TemplateData-2018.2", __FILE__);
	$gameUrl = wp_upload_dir()['baseurl'] . $game->path;

	/* $content .= "gameurl=" . $gameUrl . " <br/>\n"; */
	/* $content .= "<link rel='stylesheet' href='" . $templateUrl . "/style.css'>\n"; */
	$content = "<link rel='shortcut icon' href='" . $templateUrl . "/favicon.ico'>\n";
	$content .= "<script src='" . $templateUrl . "/UnityProgress.js'></script>\n";
	$content .= "<script src='" . $gameUrl . "/UnityLoader.js'></script>\n";
	$content .= "<script>\n";
	$content .= "    var gameInstance = UnityLoader.instantiate(\"gameContainer\", \"" . $gameUrl . "/webgl.json\", {onProgress: UnityProgress});\n";
	$content .= "</script>\n";
	$content .= "<div class='webgl-content'>\n";
	$content .= "<div id='gameContainer' style='width: " . $wporg_atts["width"] . "; height: " . $wporg_atts["height"] . "'></div>";
	$content .= "<div style='clear; both'>Content after</div>";
	$content .= "</div>";
	return $content;
}

?>
