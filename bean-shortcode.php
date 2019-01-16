<?php

function bean_unity3d_shortcode_game($atts = [], $content = null, $tag = '') {
	$util = new Bean_util();
	// normalize attribute keys, lowercase
	$atts = array_change_key_case((array)$atts, CASE_LOWER);

	// currently no defaults, but set up just in case
	$wporg_atts = shortcode_atts([
		'name' => '',
		'width' => $util->get_default_width(),
		'height' => $util->get_default_height(),
		'show_fullscreen' => false,
		'show_title' => false,
		'show_webgl_logo' => false
	], $atts, $tag);

	// decode &lt; and &gt; if they occurred in the name
	$wporg_atts["name"] = html_entity_decode($wporg_atts["name"]);
	$game = $util->get_game_by_name($wporg_atts['name']);
	if(is_wp_error($game)) {
		return "<p>Error:  [[" . esc_html($game->get_error_message()) . "]]</p>";
	}
	$game_url = wp_upload_dir()['baseurl'] . $game->path;

	$content="";
	switch($game->unity3d_version) {
	case "2018.1":
		$css_url = plugins_url('css/bean-unity3d-webgl.css', __FILE__);
		$fullscreen_img_url = plugins_url("images/fullscreen.png", __FILE__);
		$webgl_logo_url = plugins_url("images/webgl-logo.png", __FILE__);
		$template_url = plugins_url("TemplateData-2018.1", __FILE__);
		$json_url = $game_url . "/" . esc_html($game->json_filename);

		$content .= "<link rel='stylesheet' href='" . esc_attr($css_url) . "'>\n";
		$content .= "<script src='" . $template_url . "/UnityProgress.js'></script>\n";
		$content .= "<script src='" . $game_url . "/UnityLoader.js'></script>\n";
		$content .= "<script>\n";
		$content .= "    var gameInstance = UnityLoader.instantiate('gameContainer', '$json_url', {onProgress: UnityProgress});\n";
		$content .= "</script>\n";
		$content .= "<div class='webgl-content' style='width: " . esc_attr($wporg_atts["width"]) . "px'>\n";
		$content .= "<div id='gameContainer' style='width: " . $wporg_atts["width"] . "px; height: " . $wporg_atts["height"] . "px'></div>\n";

		if($wporg_atts["show_webgl_logo"] or $wporg_atts["show_fullscreen"] or $wporg_atts["show_title"]) {
			$content .= "<div class='footer'>\n";
			if($wporg_atts["show_webgl_logo"]) {
				$content .= "<div class='webgl-logo' style=\"background-image: url('$webgl_logo_url')\"></div>\n";
			}
			if($wporg_atts["show_fullscreen"]) {
				$content .= "<div class='fullscreen' style=\"background-image: url('$fullscreen_img_url')\" onclick=\"gameInstance.SetFullscreen(1)\"></div>\n";
			}
			if($wporg_atts["show_title"]) {
				$content .= "<div class='title'>" . esc_attr($wporg_atts["name"]) . "</div>\n";
			}
			$content .= "</div>\n<!-- /footer -->\n";
		}

		$content .= "</div><!-- /webgl-content -->\n";
		break;
	default:
		$content = "<p>Internal Error: unsupported Unity3d version [" . esc_html($game->unity3d_version) . "] encountered</p>\n";
		break;
	}
	return $content;
}

?>
