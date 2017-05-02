<?php
class RSS {
	public $db;
	
	public function __construct($db) {
		date_default_timezone_set('GMT');
		$this->db = $db;
	}
	public function getJSON() {
		return json_decode(file_get_contents("https://raw.githubusercontent.com/TrumpTracker/trumptracker.github.io/master/_data/data.json"),true);
	}
	public function escape($text) {
		return mysqli_real_escape_string($this->db,$text);
	}
	public function stripQuotes($text) {
		$unquoted = preg_replace('/^(\'(.*)\'|"(.*)")$/', '$2$3', $text);
		return $unquoted;
	} 
	public function newMessage($message,$title,$source = '') {
		mysqli_query($this->db,"INSERT INTO tt_feed VALUES ('','" . $this->escape($this->stripQuotes($message)) . "','" . time() . "','" . $this->escape($title) . "','TrumpTracker','https://trumptracker.github.io','" . $this->escape($source) . "')");
	}
	public function parsePoints($json) {
		$points = array();
		foreach($json['promises'] as $p) {
			$points[$p['title']] = $p;
		}
		return $points;
	}
	public function parseDifference($points) {
		$old = mysqli_query($this->db,"SELECT * FROM tt_policies");
			while($y = mysqli_fetch_assoc($old)) {
				if(!isset($points[$y['title']])) {
					mysqli_query($this->db,"DELETE FROM tt_policies WHERE title='" . $this->escape($y['title']) . "'");
					$this->newMessage("\"$y[title]\" has been removed from the list of policies.","Policy removed");
				}
				elseif($points[$y['title']]['status'] != $y['status']) {
					if($points[$y['title']]['status'] == "Not started") {
						$this->newMessage("\"$y[title]\" has not been started yet :(","Policy updated",end($points[$y['title']]['sources']));
					}
					elseif($points[$y['title']]['status'] == "In progress") {
						$this->newMessage("\"$y[title]\" is now in progress!","Policy updated",end($points[$y['title']]['sources']));
					}
					elseif($points[$y['title']]['status'] == "Achieved") {
						$this->newMessage("\"$y[title]\" has been achieved!","Policy updated",end($points[$y['title']]['sources']));
					}
					elseif($points[$y['title']]['status'] == "Broken") {
						$this->newMessage("\"$y[title]\" has been broken :(","Policy updated",end($points[$y['title']]['sources']));
					}
					elseif($points[$y['title']]['status'] == "Compromised") {
						$this->newMessage("\"$y[title]\" has been compromised :|","Policy updated",end($points[$y['title']]['sources']));
					}
					mysqli_query($this->db,"UPDATE tt_policies SET status='" . $this->escape($points[$y['title']]['status']) . "' WHERE title='" . $this->escape($y['title']) . "'");
				}
			}
			foreach($points as $p) {
				if(mysqli_num_rows(mysqli_query($this->db,"SELECT * FROM tt_policies WHERE title='" . $this->escape($p['title']) . "'")) == 0) {
					mysqli_query($this->db,"INSERT INTO tt_policies VALUES ('','" . $this->escape($p['title']) . "','" . $this->escape($p['status']) . "','" . time() . "')");
					$this->newMessage("\"$p[title]\" has been added to the list of policies!","Policy added",$p['sources'][0]);
				}
			}
	}
	public function getDB() {
		$feed = mysqli_query($this->db,"SELECT * FROM tt_feed ORDER BY time DESC LIMIT 20");
		return $feed;
	}
}