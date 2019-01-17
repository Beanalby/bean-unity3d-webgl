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
			$this->show_error('add_error', "Error adding game: " . $result->get_error_message());
			return;
		}

		$game = $this->util->get_game($result);
		if(is_wp_error($game)) {
			$this->show_error('add_error', "Internal error: couldn't find game [<code>" . esc_html($result) . "</code>] just inserted: " . $game);
			return;
		}

		$this->show_info("New game '" . $name . "' added.");
		$this->edit_game($game);
	}

	function list_games() {
		global $pagenow, $plugin_page;
		$urlBase = add_query_arg('page', $plugin_page, admin_url($pagenow));
		$editBase = add_query_arg('action', 'edit', $urlBase);
		$deleteBase = add_query_arg('action', 'delete', $urlBase);
		$shortcodeBase = add_query_arg('action', 'shortcode', $urlBase);
		$games = $this->util->get_games();
		echo "<h2>Games</h2>";
		if(empty($games)) {
			echo "<p>No games yet.  Make one!</p>";
		} else {
			foreach($games as $game) {
				$editUrl = add_query_arg('gameid', $game->id, $editBase);
				$deleteUrl = add_query_arg('gameid', $game->id, $deleteBase);
				$shortcodeUrl = add_query_arg('gameid', $game->id, $shortcodeBase);
				echo "<p>";
				echo "<a href='$deleteUrl'>" . $this->get_icon_html('delete', 'Delete') . "Delete</a> ";
				echo "<a href='$editUrl'>" . $this->get_icon_html('pencil', 'Edit') . "Edit</a> ";
				echo "<a href='$shortcodeUrl'>" . $this->get_icon_html('application_link', 'Make Shortcode') . "Shortcode</a> ";

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
		if($game == null) {
			$this->show_error("Error: game not found");
			return;
		} else if(is_wp_error($game)) {
			$this->show_error($game);
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
		if($game == null) {
			$this->show_error("Error: game not found");
			return;
		} else if(is_wp_error($game)) {
			$this->show_error($game);
			return;
		}
		$this->util->set_upload_dir($game);

		$count = $this->util->delete_game($game);
		if(is_wp_error($count)) {
			$this->show_error($count);
		} else {
			$this->show_info("Successfully deleted game "
				. esc_html($game->name) . " and " . esc_html($count)
				. " game files");
		}
		$this->list_games();
	}

	function edit_game($game=null) {
		$unity3d_version_options = array(
			'2018.1' => '20181.1 or later'
		);

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
		if($game == null) {
			$this->show_error("Error: game not found");
			return;
		} else if(is_wp_error($game)) {
			$this->show_error($game);
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
		echo "Name: <input type='text' name='name' size='30' value='"
			. esc_html($game->name) . "'/><br/>\n";

		echo "Unity3d Version: ";
		echo "<select name='unity3d_version'>";
		foreach($unity3d_version_options as $value => $display) {
			$selected="";
			if($value === $game->unity3d_version) {
				$selected="selected";
			}
			echo "<option value='$value' $selected>$display</option>\n";
		}
		echo "</select><br/>\n";

		echo "slug: " . esc_html($game->slug) . "<br/>\n";
		echo "path: " . esc_html($game->path) . "<br/>\n";

		// show existing files
		$files = $this->util->get_game_files($game);
		if(!$files) {
			echo "<h3>Uploaded Files</h3>\n";
			echo " No files uploaded.  Upload all the files from your <code>build</code> subdirectory of the webgl build (<code>build.data.unityweb</code>, <code>UnityLoader,js</code>, etc.<br/>\n";
		} else {
			echo "<div id='bean_game_files'>";

			echo "<div>\n";
			echo "<div><label for='deleteAll'>Delete</label><br/><input id='deleteAll' type='checkbox' class='checkAll' title='delete all' data-group='deleteFile[]'/></div><div><h3>" . count($files) . " Uploaded Files</h3></div>\n";
			echo "</div>\n";
			foreach($files as $file) {
				$name = substr($file->guid, strrpos($file->guid, '/')+1);
				$escName = esc_html($name);
				$id = $file->id;
				$widgetId = "file" . $id;
				$isJson = ($name === $game->json_filename);
				echo "<div>";
				echo "<div><input id='$widgetId' type='checkbox' title='delete' name='deleteFile[]' value='$id'/></div>\n";
				echo "<div><label for='$widgetId'>$escName</label></div>\n";
				echo "</div>\n";
			}
			echo "</div>\n";
		}

		// new files
		echo "<p>Upload: <input type='file' name='uploadTest[]' multiple/></p>\n";
		echo "<input type='submit' value='Save Changes'/><br/>";
		echo "</form>\n";

		$return_link = add_query_arg('page', $plugin_page, admin_url($pagenow));
		$return_link = add_query_arg('action', 'list', $return_link);
		echo "<p><a href='" . esc_attr($return_link) . "'>Back to games list</a></p>\n";
	}

	function edit_game_save() {
		$_POST = stripslashes_deep( $_POST );

		$gameid = empty($_POST['gameid']) ? '' : $_POST['gameid'];
		if(empty($gameid)) {
			$this->show_error("Error: no game id provided");
			return;
		}
		$game = $this->util->get_game($gameid);
		if(is_wp_error($game)) {
			$this->show_error("Game id [<code>" . esc_html($gameid) . "</code>] not found");
			return;
		}

		// save changes to the fields of the game table
		$retVal = $this->edit_game_save_record($game);
		if(is_wp_error($retVal)) {
			$this->show_error($retVal);
		}
		// reload the game, now that it might have changed
		$game = $this->util->get_game($gameid);
		if(is_wp_error($game)) {
			$this->show_error("Game id [<code>" . esc_html($gameid) . "</code>] not found after saving (that's really weird.)");
			return;
		}

		// save changes to files uploaded or deleted
		$this->edit_game_save_files($game);
	}

	function edit_game_save_record($game) {
		$this->util->update_game_record($game,
			$_POST['name'], $_POST['unity3d_version']);
	}

	function edit_game_save_files($game) {
		$this->util->set_upload_dir($game);
		
		/* echo "deleteFile: <pre>"; var_dump($_POST['deleteFile']); echo "</pre>"; */
		if(!empty($_POST['deleteFile'])) {
			foreach($_POST['deleteFile'] as $deleteFile) {
				$msg = "Deleting file id $deleteFile";
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
				$retVal = $this->util->save_json_filename($game->id, $file['name']);
				if(is_wp_error($retVal)) {
					$this->show_error($retVal);
				}
			}

			$_FILES = array('uploadTest' => $file);
			$msg = "Uploading " . esc_html($value) . "... ";
			$this->util->remove_existing_media($value);

			$attachment_id = media_handle_upload('uploadTest', 0);
			if ( is_wp_error( $attachment_id ) ) {
				$msg = $msg . "Error uploading: ";
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

	function make_shortcode_UI($game=null) {
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
		if($game == null) {
			$this->show_error("Error: game not found");
			return;
		} else if(is_wp_error($game)) {
			$this->show_error($game);
			return;
		}

		global $pagenow, $plugin_page;
		$width = empty($_GET['width']) ? '' : $_GET['width'];
		$height = empty($_GET['height']) ? '' : $_GET['height'];
		$show_webgl_logo = empty($_GET['show_webgl_logo']) ? '' : ($_GET['show_webgl_logo'] === 'true');
		$show_title = empty($_GET['show_title']) ? '' : ($_GET['show_title'] === 'true');
		$show_fullscreen = empty($_GET['show_fullscreen']) ? '' : ($_GET['show_fullscreen'] === 'true');

		$shortcode = '[bean_unity3d_game name="' . esc_html($game->name) . '"';
		if(!empty($width)) {
			$shortcode .= ' width="' . esc_html($width) . '"';
		}
		if(!empty($height)) {
			$shortcode .= ' height="' . esc_html($height) . '"';
		}
		if($show_webgl_logo) {
			$shortcode .= ' show_webgl_logo=true';
		}
		if($show_title) {
			$shortcode .= ' show_title=true';
		}
		if($show_fullscreen) {
			$shortcode .= ' show_fullscreen=true';
		}
		$shortcode .= "]";


		echo "<h2>Make Shortcode</h2>";
		echo "<form id='bean_shortcode_form' method='GET' action='$pagenow'>\n";
		echo "<input type='hidden' name='page' value='"
			. esc_attr($plugin_page) . "'/>\n";
		echo "<input type='hidden' name='action' value='shortcode'/>\n";
		echo "<input type='hidden' name='gameid' value='" . esc_html($gameid) . "'/>\n";

		echo "<div>"; // <table>

		echo "<div>"; // <tr>
		echo "<div>Game:</div><div><b>" . esc_html($game->name) . "</b></div>";
		echo "</div>"; // </tr>

		echo "<div>";
		echo "<div>Width:</div><div><input type='text' name='width' value='"
			. esc_attr($width) . "' placeholder='"
			. esc_attr($this->util->get_default_width()) . "'/>px</div>";
		echo "</div>";

		echo "<div>";
		echo "<div>Height:</div><div><input type='text' name='height' value='"
			. esc_attr($height) .  "' placeholder='"
			. esc_attr($this->util->get_default_height()) . "'/>px</div>";
		echo "</div>";

		echo "<div>";
		echo "<div><label for='show_webgl_logo'>Show WebGL Logo:</label></div>";
		echo "<div><input id='show_webgl_logo' type='checkbox' name='show_webgl_logo' value='true' ";
		if($show_webgl_logo) {
			echo " checked";
		}
		echo "/></div>";
		echo "</div>";

		echo "<div>";
		echo "<div><label for='show_title'>Show game title:</label></div>";
		echo "<div><input id='show_title' type='checkbox' name='show_title' value='true' ";
		if($show_title) {
			echo " checked";
		}
		echo "/></div>";
		echo "</div>";

		echo "<div>";
		echo "<div><label for='show_fullscreen'>Show 'Fullscreen' button:</label></div>";
		echo "<div><input id='show_fullscreen' type='checkbox' name='show_fullscreen' value='true' ";
		if($show_fullscreen) {
			echo " checked";
		}
		echo "/></div>";
		echo "</div>";



		echo "</div>"; // </table>

		echo "<input type='submit' value='Apply Changes'/>";
		echo "</form>";

		echo "These options can be used via the shortcode:\n";
		echo "<div class='bean_shortcode'>";
		echo $shortcode;
		echo "</div>\n";

		/* echo "When used in a post or page will look like:<br/>\n"; */
		/* echo do_shortcode($shortcode); */
	}

	function get_icon_html($name, $alt) {
		return "<img class='inline-icon' src='" . plugin_dir_url(__FILE__) . "images/" . esc_html($name) . ".png' alt='" . esc_html($alt) . "'/>";
	}
	function show_error($msg, $dismissible=false) {
		if(is_wp_error($msg)) {
			foreach($msg->get_error_messages() as $error) {
				$this->internal_show_msg($error, $dismissible, 'error');
			}
		} else {
			$this->internal_show_msg($msg, $dismissible, 'error');
		}
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
		echo "<div class='notice $typeClass $dismissClass'><p>" . wp_kses_post($msg) . "</p></div>";
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
		case 'shortcode':
			$this->make_shortcode_UI();
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