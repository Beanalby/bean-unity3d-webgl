<?php

class Bean_manage {

	public function add_game() {
		/* TODO */
	}

	function list_games() {
		$games = $this->util->get_games();
		foreach($games as $game) {
			echo '<p>game: ' . $game->name . '</p>';
		}
	}

	public function __construct() {
		$this->util = new Bean_util();
	}

	public function do_page() {
		$action = null;
		if(!empty($_POST['action'])) {
			$action = $_POST['action'];
		} else {
			$action = 'list';
		}
		switch($action) {
			case 'list':
				$this->list_games();
				break;
			case 'add':
				$this->add_game();
				break;
			default:
				echo "!!! Error: unknown action '" . esc_html_e($action, 'textdomain');
				break;
		}
	}
}

$bean = new Bean_manage();
$bean->do_page();
?>