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

global $bean_unity3d_db_version;
$bean_unity3d_db_version = '1.0';

class Bean_unity3d_webgl {

	function install() {
		global $wpdb;
		global $bean_unity3d_db_version;
		$table_name = $wpdb->prefix . "bean_unity3d_games";
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				name text NOT NULL,
				path text,
				version text,
				PRIMARY KEY  (id)
			) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		add_option( 'bean_unity3d_db_version', $bean_unity3d_db_version);
	}

	function uninstall() {
		global $wpdb;
		$table_name = $wpdb->prefix . "bean_unity3d_games";

		delete_option( 'bean_unity3d_db_version' );
		$sql = "DROP TABLE IF EXISTS {$table_name}";
		$wpdb->query( $sql );
	}

	function update_db_check() {
		global $bean_unity3d_db_version;
		if( get_site_option( 'bean_unity3d_db_version' ) != $bean_unity3d_db_version) {
			bean_unity3d_install();
		}
	}


	function register_menu() {
		add_menu_page( 'Bean Admin', 'Bean Unity3d', 'manage_options', 'bean-unity3d-webgl/bean-manage.php', '', null, null);
		add_menu_page( 'Bean Test', 'Bean Test', 'manage_options', 'bean-unity3d-webgl/bean-test.php', '', null, null);
	}

	function upload_mimes($mimes = array()) {
		// allow uploading the file types that Unity3d WebGL uses
		$mimes['json'] = 'application/json';
		$mimes['unityweb'] = 'application/octet-stream';
		return $mimes;
	}

	public function __construct() {
		register_activation_hook( __FILE__, array($this, 'install'));
		register_uninstall_hook(__FILE__, array($this, 'uninstall'));
		add_action( 'plugins_loaded', array($this, 'update_db_check'));
		add_action('admin_menu', array($this, 'register_menu'));
		add_action('upload_mimes', array($this, 'upload_mimes'));
	}
}

new Bean_unity3d_webgl();
?>
