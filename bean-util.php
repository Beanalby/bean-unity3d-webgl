<?php

class Bean_util {

	private $table_games;
	private $bean_unity3d_db_version;

	/* create tables on installation */
	public function create_tables() {
		global $wpdb;
		$table_name = $this->util->get_table_name();
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				name text NOT NULL,
				slug text,
				release_date date,
				path text,
				version text,
				PRIMARY KEY  (id)
			) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		add_option( 'bean_unity3d_db_version', $this->bean_unity3d_db_version);

		/* while in development, load it with test data */
		$wpdb->insert($table_name, array(
			'id' => null,
			'name' => 'Lickitank (LudumDare32)',
			'slug' => 'LudumDare32',
			'release_date' => '2015-04-08',
			'path' => '2018/02/LudumDare34',
			'version' => '2017.3'));
		$wpdb->insert($table_name, array(
			'id' => null,
			'name' => 'Kitten Karnage (LudumDare33)',
			'slug' => 'LudumDare33',
			'release_date' => '2015-08-17',
			'path' => '2018/02/LudumDare33',
			'version' => '2017.3'));
		$wpdb->insert($table_name, array(
			'id' => null,
			'name' => 'Buildinator (LudumDare34)',
			'slug' => 'LudumDare34',
			'release_date' => '2015-12-16',
			'path' => '2018/02/LudumDare34',
			'version' => '2017.3'));
	}

	public function drop_tables() {
		global $wpdb;
		$table_name = $this->util->get_table_name();

		delete_option( 'bean_unity3d_db_version' );
		$sql = "DROP TABLE IF EXISTS {$table_name}";
		$wpdb->query( $sql );
	}

	public function get_game($name = null) {
		global $wpdb;
		$stmt = $wpdb->prepare("SELECT * FROM $this->table_games where name=%s", $name);
		$results = $wpdb->get_results($stmt);

		if(count($results) === 0) {
			echo 'Error: game <tt>' . esc_html($name) . '</tt> not found in table <tt>' . esc_html($this->table_games) . '</tt>';
			return null;
		}

		/* sanity check: shouldn't happen, BUT... */
		if(count($results) > 1) {
			echo 'Internal error: multiple games (' . count($results) . ') found for name <tt>' . esc_html($name) . '</tt> in table <tt>' . esc_html($this->table_games) . '</tt>';
			return null;
		}

		return $results[0];
	}

	public function get_games() {
		global $wpdb;
		return $wpdb->get_results("SELECT * FROM $this->table_games");
	}

	public function get_table_name() {
		return $this->table_games;
	}

	function update_db_check() {
		if( get_site_option( 'bean_unity3d_db_version' ) != $this->bean_unity3d_db_version) {
			create_tables();
		}
	}


	public function __construct() {
		global $wpdb;
		$this->bean_unity3d_db_version = '1.0';
		$this->table_games = $wpdb->prefix . 'bean_unity3d_games';
	}
}
?>