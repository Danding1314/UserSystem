<?php

namespace Bestaford;

class Base {
	
	private $database, $base_prepare, $base_result;
	
	function __construct($plugin, $name) {
		$this->database = new \SQLite3($plugin->getDataFolder().$name.".db");
		$this->database->exec(stream_get_contents($plugin->getResource("database.sql")));
	}
	public function prepare($query) {
		$this->base_prepare = $this->database->prepare($query);
	}
	public function bind($name, $value) {
			$this->base_prepare->bindValue($name, $value);
	}
	public function execute() {
		$this->base_result = $this->base_prepare->execute();
	}
	public function get() {
		$row = array();
 		$i = 0;
 		while($res = $this->base_result->fetchArray(SQLITE3_ASSOC)) {
			$row[$i] = $res;
 			$i++;
 		}
 		return $row;
 	}
}