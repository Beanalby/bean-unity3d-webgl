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
		$deleteBase = add_query_arg('action', 'delete', $urlBase);
		$games = $this->util->get_games();
		echo "<h2>Games</h2>";
		if(empty($games)) {
			echo "<p>No games yet.  Make one!</p>";
		} else {
			foreach($games as $game) {
				$editUrl = add_query_arg('gameid', $game->id, $editBase);
				$deleteUrl = add_query_arg('gameid', $game->id, $deleteBase);
				echo "<p>";
				echo "<a href='$deleteUrl'>" . $this->get_icon_html('delete', 'Edit') . "Delete</a> ";
				echo "<a href='$editUrl'>" . $this->get_icon_html('pencil', 'Edit') . "Edit</a> ";
				echo esc_html($game->name);
				echo "</p>";
			}
		}
		$addUrl = add_query_arg('action', 'add', $urlBase);
		echo "<p><a href='$addUrl'>" . $this->get_icon_html('add', 'Add') . " Add a new game</a></p>\n";
	}

	function delete_game($game=null) {
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
		$this->util->set_upload_dir($game);
		echo "<p>Really delete <b>" . esc_html($game->name) . "</b> ";
		echo "and its files in <code>" . esc_html($game->path) . "</code>?";
		global $pagenow, $plugin_page;
		$baseUrl = add_query_arg('page', $plugin_page, admin_url($pagenow));
		$deleteUrl = add_query_arg('action', 'delete-confirmed', $baseUrl);
		echo "<form method='POST' action='$deleteUrl' enctype='multipart/form-data'>\n";
		// basic info
		echo "<input type='hidden' name='gameid' value='$game->id'/>\n";
		echo "<input type='submit' value='Delete Game'/>\n";
		echo "</form>\n";
	}

	function delete_game_confirmed($game=null) {
		if(empty($game)) {
			/* no game passed as a parameter, check the post body */
			if(empty($gameid)) {
				$gameid = empty($_POST['gameid']) ? '' : $_POST['gameid'];
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
		$this->util->set_upload_dir($game);

		$count = $this->util->delete_game($game);
		$this->show_info("Successfully deleted game <b>"
			. esc_html($game->name) . "</b> and " . esc_html($count)
			. " game files");
		$this->list_games();
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
		$this->util->set_upload_dir($game);
		global $pagenow, $plugin_page;
		$form_action = add_query_arg('page', $plugin_page, admin_url($pagenow));
		$form_action = add_query_arg('action', 'edit-save', $form_action);
		echo "<h2>Edit Game</h2>\n";
		echo "<form method='POST' action='$form_action' enctype='multipart/form-data'>\n";
		// basic info
		echo "<input type='hidden' name='gameid' value='$game->id'/>\n";
		echo "Name: <input type='text' name='name' size='30' value='" . esc_html($game->name) . "'/><br/>\n";
		echo "slug: " . esc_html($game->slug) . "<br/>\n";
		echo "path: " . esc_html($game->path) . "<br/>\n";

		// show existing files
		$files = $this->util->get_game_files($game);
		if(!$files) {
			echo "<h3>Uploaded Files</h3>\n";
			echo " No files uploaded.  Upload the files from your <code>webgl/build</code> directory.<br/>\n";
		} else {
			echo "<ul>\n";
			echo "<li><h3><input type='checkbox' class='checkAll' title='delete all' data-group='deleteFile[]'/>" . count($files) . " Uploaded Files</h3></li>\n";
			foreach($files as $file) {
				$name = substr($file->guid, strrpos($file->guid, '/')+1);
				$escName = esc_html($name);
				$id = $file->id;
				$widgetId = "file" . $id;
				$isJson = ($name === $game->json_filename);
				$liStyle = "";
				if($isJson) {
					// didn't like how this looked, leaving off for now
					/* $liStyle = "background: lightGreen"; */
				}
				echo "<li style='$liStyle'>";
				echo "<input id='$widgetId' type='checkbox' title='delete' name='deleteFile[]' value='$id'/>\n";
				echo "<label for='$widgetId'>$escName</label></li>\n";
			}
			echo "</ul>\n";
		}

		// new files
		echo "<p>Upload: <input type='file' name='uploadTest[]' multiple/></p>\n";
		echo "<input type='submit' value='Save Changes'/><br/>";
		echo "</form>\n";
	}

	function edit_game_save() {
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

		$this->util->set_upload_dir($game);
		
		/* echo "deleteFile: <pre>"; var_dump($_POST['deleteFile']); echo "</pre>"; */
		if(!empty($_POST['deleteFile'])) {
			foreach($_POST['deleteFile'] as $deleteFile) {
				$msg = "Deleting file id <b>" . esc_html($deleteFile) . "</b>.";
				$this->show_info($msg);
				if(false === wp_delete_attachment($deleteFile, true)) {
					$this->show_error("Error deleting file");
				}
			}
		}
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

			// if this is the .json file, make note of it for displaying game
			if(preg_match('/.json$/', $file['name'])) {
				$retVal = $this->util->save_json_filename($gameid, $file['name']);
				if(is_wp_error($retVal)) {
					$this->showInfo($retVal);
				}
			}

			$_FILES = array('uploadTest' => $file);
			$msg = "Uploading <code>" . esc_html($value) . "</code>... ";
			$this->util->remove_existing_media($value);

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

	public function __construct() {
		$this->util = new Bean_util();
	}


	public function do_page() {
		$action = empty($_GET['action']) ? 'list' : $_GET['action'];

		switch($action) {
		case 'add':
			$this->add_game();
			break;
		case 'add-save':
			$this->add_game_save();
			break;
		case 'delete':
			$this->delete_game();
			break;
		case 'delete-confirmed':
			$this->delete_game_confirmed();
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