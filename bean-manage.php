<?php

class Bean_manage {

	public function add_game() {
		/* TODO */
	}

	function list_games() {
		global $pagenow, $plugin_page;
		$urlBase = add_query_arg('page', $plugin_page, admin_url($pagenow));
		$urlBase = add_query_arg('action', 'edit', $urlBase);
		$games = $this->util->get_games();
		foreach($games as $game) {
			$editUrl = add_query_arg('gameid', $game->id, $urlBase);
			echo "<p><a href='$editUrl'>Edit</a> $game->name</p>";
		}
	}

	function edit_game($game=null) {
		if(empty($game)) {
			/* no game passed as a parameter, check the query string */
			if(empty($gameid)) {
				$gameid = empty($_GET['gameid']) ? '' : $_GET['gameid'];
			}
			if(empty($gameid)) {
				echo "!!! Error: no game id provided<br/>\n";
				return;
			}
			$game = $this->util->get_game($gameid);
		}
		if($game == null) {
			echo "!!! Error: game not found<br/>\n";
			return;
		}

		global $pagenow, $plugin_page;
		$form_action = add_query_arg('page', $plugin_page, admin_url($pagenow));
		$form_action = add_query_arg('action', 'edit-save', $form_action);
		echo "<h2>Edit Game</h2>\n";
		echo "<form method='POST' action='$form_action' enctype='multipart/form-data'>\n";
		echo "<input type='hidden' name='gameid' value='$game->id'/>\n";
		echo "Name: <input type='text' name='name' size='30' value='$game->name'/><br/>\n";
		echo "slug: $game->slug<br/>\n";
		echo "path: $game->path<br/>\n";
		echo "File: <input type='file' name='uploadTest[]' multiple/><br/>\n";
		echo "<input type='submit' value='Save Changes'/><br/>";
		echo "</form>\n";
	}

	function  edit_save_game() {
		$gameid = empty($_POST['gameid']) ? '' : $_POST['gameid'];
		if(empty($gameid)) {
			echo "Error: no game id provided";
			return;
		}
		$game = $this->util->get_game($gameid);
		if($game == null) {
			echo "Game id $gameid not found";
			return;
		}

		/* make uploads for this game go to its own directory */
		$this->slug = $game->slug;
		add_filter('upload_dir', array($this, 'customize_upload_dir'));
		
		echo "<p>Saving $game->name!</p>\n";
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
			echo "Uploading <code>$value</code>... ";

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
					echo "Overwriting existing file id <code>$attachmentID</code>...\n";
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
				echo "<b>Error uploading</b>: ";
				foreach($attachment_id->errors['upload_error'] as $error) {
					echo "$error ";
				}
				echo "<br/>\n";
			} else {
				echo "Success.<br/>\n";
			}
		}
		echo "<hr/>\n";
		$this->edit_game($game);
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
		case 'edit':
			$this->edit_game();
			break;
		case 'edit-save':
			$this->edit_save_game();
			break;
		case 'list':
			$this->list_games();
			break;
		default:
			echo "!!! Error: unknown action '" . esc_html($action) . "'";
			break;
		}
	}
}

$bean = new Bean_manage();
$bean->do_page();
?>