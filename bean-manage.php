<?php

class Bean_manage {

	function add_game($name=NULL) {
		echo "<h2>Add Game</h2>\n";

		global $pagenow, $plugin_page;
		$form_action = add_query_arg('page', $plugin_page, admin_url($pagenow));
		$form_action = add_query_arg('action', 'add-save', $form_action);

		echo "<form method='POST' action='$form_action'>\n";
		echo "Name: <input type='text' name='name' value='" . esc_html($name) . "'/><br/>";
		echo "<input type='submit' value='Add Game'/><br/>\n";
		echo "</form>\n";
	}

	function add_game_save() {
		$name = empty($_POST['name']) ? '' : stripslashes($_POST['name']);
		if(empty($name)) {
			$this->show_error("Error: no name provided");
			return;
		}
		$result = $this->util->add_game($name);
		if(is_wp_error($result)) {
			$this->show_error("Error adding game: " . esc_html($result->get_error_message()));
			return;
		}
		/* echo '<p>result: <pre>'; var_dump($result); echo "</pre></p>\n"; */
		$game = $this->util->get_game($result);
		if(is_wp_error($game)) {
			$this->show_error("Internal error: couldn't find game [$result] just inserted" . $game);
			return;
		}

		$this->show_info("New game <code>" . esc_html($name) . "</code> added.");
		$this->edit_game($game);
	}

	function list_games() {
		global $pagenow, $plugin_page;
		$urlBase = add_query_arg('page', $plugin_page, admin_url($pagenow));
		$editBase = add_query_arg('action', 'edit', $urlBase);
		$games = $this->util->get_games();
		echo "<h2>Games</h2>";
		if(empty($games)) {
			echo "<p>No games yet.  Make one!</p>";
		} else {
			foreach($games as $game) {
				$editUrl = add_query_arg('gameid', $game->id, $editBase);
				echo "<p><a href='$editUrl'>" . $this->get_icon_html('pencil', 'Edit') . "Edit</a> " . esc_html($game->name) . "</p>";
			}
		}
		$addUrl = add_query_arg('action', 'add', $urlBase);
		echo "<p><a href='$addUrl'>" . $this->get_icon_html('add', 'Add') . " Add a new game</a></p>\n";
	}

	function edit_game($game=null) {
		if(empty($game)) {
			/* no game passed as a parameter, check the query string */
			if(empty($gameid)) {
				$gameid = empty($_GET['gameid']) ? '' : $_GET['gameid'];
			}
			if(empty($gameid)) {
				$this->show_error("Error: no game id provided");
				return;
			}
			$game = $this->util->get_game($gameid);
		}
		if($game == null || is_wp_error($game)) {
			$this->show_error("Error: game not found");
			return;
		}

		global $pagenow, $plugin_page;
		$form_action = add_query_arg('page', $plugin_page, admin_url($pagenow));
		$form_action = add_query_arg('action', 'edit-save', $form_action);
		echo "<h2>Edit Game</h2>\n";
		echo "<form method='POST' action='$form_action' enctype='multipart/form-data'>\n";
		echo "<input type='hidden' name='gameid' value='$game->id'/>\n";
		echo "Name: <input type='text' name='name' size='30' value='" . esc_html($game->name) . "'/><br/>\n";
		echo "slug: " . esc_html($game->slug) . "<br/>\n";
		echo "path: " . esc_html($game->path) . "<br/>\n";
		echo "File: <input type='file' name='uploadTest[]' multiple/><br/>\n";
		echo "<input type='submit' value='Save Changes'/><br/>";
		echo "</form>\n";
	}

	function  edit_game_save() {
		$gameid = empty($_POST['gameid']) ? '' : $_POST['gameid'];
		if(empty($gameid)) {
			$this->show_error("Error: no game id provided");
			return;
		}
		$game = $this->util->get_game($gameid);
		if(is_wp_error($game)) {
			$this->show_error("Game id " . esc_html($gameid) . " not found");
			return;
		}

		/* make uploads for this game go to its own directory */
		$this->slug = $game->slug;
		add_filter('upload_dir', array($this, 'customize_upload_dir'));
		
		/* echo '<p>upload_dir: <pre>'; */
		/* var_dump(wp_upload_dir()); */
		/* echo "</pre></p>\n"; */
		
		/* our upload has a single widget with an array of files in it, but
		   wodpress's media_handle_upload() expects an array of widgets, each
		   with a single file.  Rejigger things for what wordpress expects */
		$files = $_FILES['uploadTest'];
		foreach($files['name'] as $key => $value) {
			if(empty($value)) {
				continue;
			}
			$file = array(
				'name' => $files['name'][$key],
				'type' => $files['type'][$key],
				'tmp_name' => $files['tmp_name'][$key],
				'error' => $files['error'][$key],
				'size' => $files['size'][$key]
			);
			$_FILES = array('uploadTest' => $file);
			$msg = "Uploading <code>" . esc_html($value) . "</code>... ";

			/* if the file already exists, remove it first so it doesn't try
			   to make a new filename.  No functions to look up by full
			   filepath, so we have to dig into DB directly. :( */
			$fullpath = wp_upload_dir()['url'] . "/" . $value;
			global $wpdb;
			$stmt = $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $fullpath);
			/* echo "stmt: <pre>"; var_dump($stmt); echo "</pre>\n"; */
			$attachmentIDs = $wpdb->get_col($stmt);
			/* echo "+++ attachmentIDs: <pre>"; var_dump($attachmentIDs); echo "</pre>"; */
			if($attachmentIDs) {
				foreach($attachmentIDs as $attachmentID) {
					$msg = $msg . " Overwriting existing file id <code>" . esc_html($attachmentID) . "</code>...\n";
					wp_delete_attachment($attachmentID);
				}
			}
			/* 		echo "+++ [$fullpath]==[$attachment->guid]?<br/>\n"; */
			/* 		if($fullpath == $attachment->guid) { */
			/* 			echo "!!! IT MATCHES! removing $attachment->ID!<br/>\n"; */
			/* 		} */
			/* 	} */
			/* } else { */
			/* 	echo "+++ no attachments found<br/>"; */
			/* } */

			$attachment_id = media_handle_upload('uploadTest', 0);
			if ( is_wp_error( $attachment_id ) ) {
				$msg = $msg . "<b>Error uploading</b>: ";
				foreach($attachment_id->errors['upload_error'] as $error) {
					$msg = $msg . esc_html($error) . " ";
				}
				$this->show_error($msg);
			} else {
				$msg = $msg . "Success.";
				$this->show_info($msg);
			}
		}
		$this->show_info("Game " . esc_html($game->name) . " saved.");
		$this->edit_game($game);
	}

	function get_icon_html($name, $alt) {
		return "<img class='inline-icon' src='" . plugin_dir_url(__FILE__) . "images/" . esc_html($name) . ".png' alt='" . esc_html($alt) . "'/>";
	}
	function show_error($msg, $dismissible=false) {
		$this->internal_show_msg($msg, $dismissible, 'error');
	}
	function show_info($msg, $dismissible=false) {
		$this->internal_show_msg($msg, $dismissible, 'updated');
	}
	/* Do not call show_msg directly, use show_error or show_info */
	function internal_show_msg($msg, $dismissible, $typeClass) {
		$dismissClass="";
		if($dismissible) {
			$dismissClass="is-dismissible";
		}
		echo "<div class='notice $typeClass $dismissClass'><p>$msg</p></div>";
	}

	function customize_upload_dir($param) {
		$mydir = '/placeholder';
		if(isset($this->slug)) {
			$mydir = '/' . $this->slug;
		}
		$param['path'] = $param['path'] . $mydir;
		$param['url'] = $param['url'] . $mydir;
	
		error_log("path={$param['path']}");
		error_log("url={$param['url']}");
		error_log("subdir={$param['subdir']}");
		error_log("basedir={$param['basedir']}");
		error_log("baseurl={$param['baseurl']}");
		error_log("error={$param['error']}");
		return $param;
	}

	public function __construct() {
		$this->util = new Bean_util();
	}


	public function do_page() {
		$action = empty($_GET['action']) ? 'list' : $_GET['action'];

		echo "+++ Doing action=$action<br/>";
		switch($action) {
		case 'add':
			$this->add_game();
			break;
		case 'add-save':
			$this->add_game_save();
			break;
		case 'edit':
			$this->edit_game();
			break;
		case 'edit-save':
			$this->edit_game_save();
			break;
		case 'list':
			$this->list_games();
			break;
		default:
			$this->show_error("Error: unknown action '" . esc_html($action) . "'");
			break;
		}
	}
}

$bean = new Bean_manage();
$bean->do_page();
?>