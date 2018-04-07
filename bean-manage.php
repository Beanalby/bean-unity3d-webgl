<?php

class Bean_manage {

	public function add_game() {
		/* TODO */
	}

	function list_games() {
		global $pagenow, $plugin_page;
		$urlBase = add_query_arg('page', $plugin_page, admin_url($pagenow));
		$games = $this->util->get_games();
		foreach($games as $game) {
			$name = $game->name;
			$editUrl = add_query_arg('action', 'edit', $urlBase);
			$editUrl = add_query_arg('name', $name, $editUrl);
			echo "<p><a href='$editUrl'>Edit</a> $name</p>";
		}
	}

	function edit_game() {
		$name = empty($_GET['name']) ? '' : $_GET['name'];
		if(empty($name)) {
			echo "Error: no name provided";
			return;
		}

		$game = $this->util->get_game($name);
		if($game == null) {
			return;
		}

		global $pagenow, $plugin_page;
		$form_action = add_query_arg('page', $plugin_page, admin_url($pagenow));
		$form_action = add_query_arg('action', 'edit-save', $form_action);
		echo "<form method='POST' action='$form_action'>\n";
		echo "Name: <input type='text' name='name' size='30' value='$game->name'/><br/>\n";
		echo "<input type='submit'/><br/>";
		echo "</form>\n";
	}

	function  edit_save_game() {
		echo '<p>Saving ' . $_POST['name'] . "!</p>\n";
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