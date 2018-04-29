<?php

class Bean_util {

	private $table_games;
	private $bean_unity3d_db_version;

	/* create tables on installation */
	public function create_tables() {
		global $wpdb;
		$table_name = $this->get_table_name();
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

	public function add_game($name) {
		$name = trim($name);
		if(empty($name)) {
			return new WP_Error('paramerror', 'Empty game name');
		}
		$slug = sanitize_title_with_dashes($name, NULL, 'save');
		/* echo "[$name] becomes [$slug]<br/>\n"; */
		/* echo '<p>upload_dir: <pre>'; var_dump(wp_upload_dir()); echo "</pre></p>\n"; */

		/* check if directory already exists.  If so, make a new slug */
		$slugBase = $slug; $loop=1;
		while(true) {
			$dir = wp_upload_dir()['path'] . '/' . $slug;
			/* echo "+++ does $dir exist?<br/>"; */
			if(!file_exists($dir)) {
				/* echo "+++ directory doesn't exist, using it\n"; */
				break;
			}
			/* try a new suffix and do it again */
			$loop += 1;
			$slug = $slugBase . '-' . strval($loop);
			/* echo "+++ dir exists, incremented loop=$loop, made slug=$slug<br/>\n"; */
		}
		/* echo "+++ using slug=$slug<br/>\n"; */
		$path = wp_upload_dir()['subdir'] . '/' . $slug;
		global $wpdb;
		$result = $wpdb->insert($this->get_table_name(), array(
			'id' => null,
			'name' => $name,
			'slug' => $slug,
			'release_date' => NULL,
			'path' => $path,
			'version' => '2017.3'));
		if(!$result) {
			return new WP_Error('sqlerror', $wpdb->last_error, $wpdb->last_query);
		}
		return $wpdb->insert_id;
	}

	public function drop_tables() {
		global $wpdb;
		$table_name = $this->get_table_name();

		delete_option( 'bean_unity3d_db_version' );
		$sql = "DROP TABLE IF EXISTS {$table_name}";
		$wpdb->query( $sql );
	}

	public function get_game($gameid) {
		global $wpdb;
		$stmt = $wpdb->prepare("SELECT * FROM $this->table_games where id=%s", $gameid);
		$results = $wpdb->get_results($stmt);

		if(count($results) === 0) {
			return new WP_Error('game <tt>' . esc_html($gameid) . '</tt> not found in table <tt>' . esc_html($this->table_games) . '</tt>');
			return null;
		}

		/* sanity check: shouldn't happen, BUT... */
		if(count($results) > 1) {
			echo 'Internal error: multiple games (' . count($results) . ') found for game id <tt>' . esc_html($gameid) . '</tt> in table <tt>' . esc_html($this->table_games) . '</tt>';
			return null;
		}

		return $results[0];
	}

	public function get_games() {
		global $wpdb;
		return $wpdb->get_results("SELECT * FROM $this->table_games");
	}

	public function get_game_files($game) {
		global $wpdb;
		// assume set_upload_dir has been called;
		$fullpath = wp_upload_dir()['url'] . "/%";
		/* echo "fullpath: $fullpath<br/>\n"; */
		$stmt = $wpdb->prepare("SELECT id, guid FROM $wpdb->posts WHERE guid like '%s';", $fullpath);
		return $wpdb->get_results($stmt);
	}

	public function get_table_name() {
		return $this->table_games;
	}

	function remove_existing_media($filename) {
		/* if the file already exists, remove it first so it doesn't try
		   to make a new filename.  No functions to look up by full
		   filepath, so we have to dig into DB directly. :( */
		$fullpath = wp_upload_dir()['url'] . "/" . $filename;
		global $wpdb;
		$stmt = $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $fullpath);
		/* echo "stmt: <pre>"; var_dump($stmt); echo "</pre>\n"; */
		$attachmentIDs = $wpdb->get_col($stmt);
		/* echo "+++ attachmentIDs: <pre>"; var_dump($attachmentIDs); echo "</pre>"; */
		if($attachmentIDs) {
			foreach($attachmentIDs as $attachmentID) {
				/* $msg = $msg . " Overwriting existing file id <code>" . esc_html($attachmentID) . "</code>...\n"; */
				wp_delete_attachment($attachmentID);
			}
		}
	}

	function set_upload_dir($game) {
		/* make uploads for this game go to its own directory */
		$this->slug = $game->slug;
		add_filter('upload_dir', array($this, 'customize_upload_dir'));
	}

	function customize_upload_dir($param) {
		if(!isset($this->slug)) {
			return;
		}
		$param['path'] = $param['path'] . '/' . $this->slug;
		$param['url'] = $param['url'] . '/' . $this->slug;
		return $param;
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