<?php
/*
Plugin Name: Bean Unity3d WebGL
Plugin URI: http://beanalby.net/projects/beanUnity3d/
Description: Allows uploading and managing WebGL builds of Unity3d games
Version: 0.1
Autor: Jason Viers
Autor URI: http://beanalby.net
*/

/* function bean_unity3d_activation() { */
/* } */
/* register_activation_hook( __FILE__, 'bean_unity3d_activation' ); */

include( plugin_dir_path( __FILE__ ) . 'bean-util.php');
include( plugin_dir_path( __FILE__ ) . 'bean-shortcode.php');

class Bean_unity3d_webgl {

	function install() {
		$this->util->create_tables();
	}

	public static function uninstall() {
		$this->util->drop_tables();
	}

	function update_db_check() {
		$this->util->update_db_check();
	}

	function register_menu() {
		add_menu_page( 'Bean Admin', 'Bean Unity3d', 'manage_options', 'bean-unity3d-webgl/bean-manage.php', '', plugin_dir_url(__FILE__) . 'images/unity-logo.png', null);
		add_menu_page( 'Bean Test', 'Bean Test', 'manage_options', 'bean-unity3d-webgl/bean-test.php', '', null, null);
	}

	function upload_mimes($mimes = array()) {
		// allow uploading the file types that Unity3d WebGL uses
		$mimes['json'] = 'application/json';
		$mimes['unityweb'] = 'application/octet-stream';
		/* Here's a bunch that aren't extensions we use, but Wordpress needs to
		   know about 3 or 4 letter words that may appear in filenames.
		   Otherwise it will secretly rename
		   "game.data.unityweb" to "game.data_.unityweb" :-/ */
		$mimes['asm'] = 'application/octet-stream';
		$mimes['code'] = 'application/octet-stream';
		$mimes['data'] = 'application/octet-stream';
		return $mimes;
	}

	function init_shortcodes() {
		add_shortcode('bean_unity3d_game', 'bean_unity3d_shortcode_game');
	}

	public function __construct() {
		$this->util = new Bean_util();
		register_activation_hook( __FILE__, array($this, 'install'));
		register_uninstall_hook(__FILE__, 'Bean_unity3d_webgl::uninstall');
		add_action( 'plugins_loaded', array($this, 'update_db_check'));
		add_action('admin_menu', array($this, 'register_menu'));
		add_action('upload_mimes', array($this, 'upload_mimes'));
		add_action('init', array($this, 'init_shortcodes'));
	}
}
/* not actually using this stuff for now, but will in the future. */
/* function enqueue_scripts() { */
/* 	wp_register_script('jQueryui_widget', plugins_url('js/vendor/jquery.ui.widget.js', __FILE__), array('jquery'), '1.12.1', true); */
/* 	wp_register_script('jQuery_fileupload', plugins_url('js/jquery.fileupload.js', __FILE__), array('jquery'), '9.21.0', true); */
/* 	wp_register_script('bean_unity3d_webgl', plugins_url('js/bean-unity3d-webgl.js', __FILE__), array('jquery'), '1.0', true); */

/* 	wp_enqueue_script('jQueryui_widget'); */
/* 	wp_enqueue_script('jQuery_fileupload'); */
/* 	wp_enqueue_script('bean_unity3d_webgl'); */

/* 	wp_register_style( 'jQuery_fileupload', plugins_url('css/jquery.fileupload.css', __FILE__), false, '1.12.1' ); */
/* 	wp_enqueue_style('jQuery_fileupload'); */
/* } */

/* add_action('admin_enqueue_scripts', 'enqueue_scripts'); */


new Bean_unity3d_webgl();
?>
