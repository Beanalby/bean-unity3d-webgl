<?php

class Bean_util {

	private $table_games;
	private $bean_unity3d_db_version;
	private $default_unity3d_version;
	private $default_width;
	private $default_height;

	private static $unity3d_version_options = array(
		'2018.1' => '2018.1 or later'
	);
	public function get_unity3d_version_options() {
		return $unity3d_version_options;
	}
	public function get_unity3d_version_values() {
		return array_keys($unity3d_version_options);
	}

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
				updated_date date,
				path text,
				json_filename text,
				game_version text,
				unity3d_version text,
				PRIMARY KEY  (id)
			) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		add_option( 'bean_unity3d_db_version', $this->bean_unity3d_db_version);

		/* while in development, load it with test data */
		/* $wpdb->insert($table_name, array( */
		/* 	'id' => null, */
		/* 	'name' => 'Lickitank (LudumDare32)', */
		/* 	'slug' => 'LudumDare32', */
		/* 	'release_date' => '2015-04-08', */
		/* 	'path' => '2018/02/LudumDare34', */
		/* 	'json_filename' => 'webgl.json', */
		/* 	'version' => '2017.3')); */
		/* $wpdb->insert($table_name, array( */
		/* 	'id' => null, */
		/* 	'name' => 'Kitten Karnage (LudumDare33)', */
		/* 	'slug' => 'LudumDare33', */
		/* 	'release_date' => '2015-08-17', */
		/* 	'path' => '2018/02/LudumDare33', */
		/* 	'json_filename' => 'webgl.json', */
		/* 	'version' => '2017.3')); */
		/* $wpdb->insert($table_name, array( */
		/* 	'id' => null, */
		/* 	'name' => 'Buildinator (LudumDare34)', */
		/* 	'slug' => 'LudumDare34', */
		/* 	'release_date' => '2015-12-16', */
		/* 	'path' => '2018/02/LudumDare34', */
		/* 	'json_filename' => 'webgl.json', */
		/* 	'version' => '2017.3')); */
	}

	public function add_game($name) {
		$name = trim($name);
		if(empty($name)) {
			return new WP_Error('paramerror', 'Empty game name');
		}
		$slug = sanitize_file_name($name);
		if(empty($slug)) {
			return new WP_Error('paramerror', 'Invalid game name (empty slug)');
		}

		/* check if directory already exists.  If so, make a new slug */
		$slugBase = $slug; $loop=1;
		while(true) {
			$dir = wp_upload_dir()['path'] . '/' . $slug;
			if(!file_exists($dir)) {
				break;
			}
			/* try a new suffix and do it again */
			$loop += 1;
			$slug = $slugBase . '-' . strval($loop);
		}
		$path = wp_upload_dir()['subdir'] . '/' . $slug;
		global $wpdb;
		$result = $wpdb->insert($this->get_table_name(), array(
			'id' => null,
			'name' => $name,
			'slug' => $slug,
			'release_date' => NULL,
			'path' => $path,
			'game_version' => '1.0',
			'unity3d_version' => $this->default_unity3d_version
			));
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

	public function filter_gameid($gameid) {
		return filter_var($gameid, FILTER_SANITIZE_NUMBER_INT);
	}
	public function filter_name($name) {
		return filter_var($name, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);
	}
	public function filter_unity3d_version($unity3d_version) {
		if(in_array($unity3d_version, get_unity3d_version_values())) {
			return $unity3d_version;
		} else {
			return "";
		}
	}

	public function get_game($gameid) {
		global $wpdb;
		$stmt = $wpdb->prepare("SELECT * FROM $this->table_games where id=%s", $gameid);
		$results = $wpdb->get_results($stmt);

		if(count($results) === 0) {
			return new WP_Error('no_game', "game id [<code>" . esc_html($gameid) . "</code>] not found in table " . $this->table_games);
		}

		/* sanity check: shouldn't happen, BUT... */
		if(count($results) > 1) {
			return new WP_Error('multiple_games',
				'Internal error: multiple games (' . count($results)
				. ") found for game id [<code>" . esc_html($gameid) . "</code>] in table "
				. $this->table_games);
		}

		return $results[0];
	}

	public function get_game_by_name($name) {
		global $wpdb;
		$stmt = $wpdb->prepare("SELECT * FROM $this->table_games where name=%s", $name);
		$results = $wpdb->get_results($stmt);

		if(count($results) === 0) {
			return new WP_Error('no_game', "game name [<code>"
			. esc_html($name) . "</code>] not found in table "
			. $this->table_games);
		}

		/* sanity check: shouldn't happen, BUT... */
		if(count($results) > 1) {
			$error = 'Internal error: multiple games ('
				. count($results) . ") found for game name <code>["
				. esc_html($name) . "]</code> in table " . $this->table_games;
			return new WP_Error('multiple_games', $error);
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

	function update_game_record($game, $name, $unity3d_version) {
		global $wpdb;

		$name = $this->util->filter_name($name);
		if(empty($name)) {
			return new WP_Error("Invalid name, aborting edit");
		}
		$unity3d_version = $this->util->filter_unity3d_version($unity3d_version);
		if(empty($name)) {
			return new WP_Error("Invalid unity3d version, aborting edit");
		}
		$numRows = $wpdb->update(
			$this->get_table_name(),
			array(
				'name' => $name,
				'unity3d_version' => $unity3d_version
			),
			array(
				'id' => $game->id
			)
		);
		if($numRows === false) {
			return new WP_Error("Error updating game row for id [<code>"
			. esc_html($game->id) . "</code>]");
		}
		return $numRows;
	}

	function remove_existing_media($filename) {
		/* if the file already exists, remove it first so it doesn't try
		   to make a new filename.  No functions to look up by full
		   filepath, so we have to dig into DB directly. :( */
		$fullpath = wp_upload_dir()['url'] . "/" . $filename;
		global $wpdb;
		$stmt = $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $fullpath);
		$attachmentIDs = $wpdb->get_col($stmt);
		if($attachmentIDs) {
			foreach($attachmentIDs as $attachmentID) {
				wp_delete_attachment($attachmentID);
			}
		}
	}

	public function delete_game($game) {
		$count = $this->delete_game_files($game);
		$this->delete_game_record($game);
		return $count;
	}

	function delete_game_files($game) {
		$count = 0;

		// remove the individual files from wordpress' list of attachments
		$files = $this->get_game_files($game);
		if($files) {
			foreach($files as $file) {
				wp_delete_attachment($file->id, true);
				$count++;
			}
		}

		// also remove the directory itself, which should now be empty
		if( !rmdir(wp_upload_dir()['path']) ) {
			return new WP_Error('rm_fail', "Failed to remove game directory [<code>"
			. esc_html(wp_upload_dir()['path']) . "</code>]");
		}

		return $count;
	}

	function delete_game_record($game) {
		global $wpdb;
		$count = $wpdb->delete($this->get_table_name(), array(
			'id' => $game->id));
		if(0 === $count) {
			return new WP_Error('no_game', 'Could not find record for game id [<code>'
			. esc_html($game->id) . '</code>] in games table to remove');
		}
		if(1 != $count) {
			return new WP_Error('multiple_games',
				'Expected to remove 1 row from games table, but removed '
				. esc_html($count) . ' rows.');
		}
	}

	function save_json_filename($gameid, $filename) {
		global $wpdb;
		$numUpdated = $wpdb->update(
			$this->get_table_name(),
			array( 'json_filename' => $filename ),
			array( 'id' => $gameid )
		);
		if($numUpdated === false) {
			return new WP_Error('update_fail', "Error saving json_filename for game [<code>"
				. esc_html($gameid) . "</code>]", $wpdb->last_error, $wpdb->last_query);
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

	function get_default_width() {
		return $this->default_width;
	}
	function get_default_height() {
		return $this->default_height;
	}

	public function __construct() {
		global $wpdb;
		$this->bean_unity3d_db_version = '1.0';
		$this->default_unity3d_version = '2018.1';
		$this->default_width='960';
		$this->default_height='540';
		$this->table_games = $wpdb->prefix . 'bean_unity3d_games';
	}
}
?>