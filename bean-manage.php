<?php

class Bean_manage {

	private $table_games;

	public function add_game() {
	}

	function list_games() {
		global $wpdb;
		$games = $wpdb->get_results("SELECT * FROM $this->table_games");
		foreach($games as $game) {
			echo '<p>game: ' . $game->name . '</p>';
		}
	}

	public function __construct() {
		global $wpdb;
		$this->table_games = $wpdb->prefix . 'bean_unity3d_games';
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