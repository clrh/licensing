<?php
/*
**************************************************************************************************************************
** CORAL Licensing Module v. 1.0
**
** Copyright (c) 2010 University of Notre Dame
**
** This file is part of CORAL.
**
** CORAL is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
**
** CORAL is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License along with CORAL.  If not, see <http://www.gnu.org/licenses/>.
**
**************************************************************************************************************************
*/



class DBService extends Object {

	protected $db;
	protected $config;
	protected $error;

	protected function init(NamedArguments $arguments) {
		parent::init($arguments);
		$this->config = new Configuration;
		$this->connect();
	}

	protected function dealloc() {
		$this->disconnect();
		parent::dealloc();
	}

	protected function checkForError() {
		if ($this->error = mysqli_error($this->db)) {
			throw new Exception("There was a problem with the database: " . $this->error);
		}
	}

	protected function connect() {
		$host = $this->config->database->host;
		$username = $this->config->database->username;
		$password = $this->config->database->password;
		$databaseName = $this->config->database->name;
		$this->db = mysqli_connect($host, $username, $password, $databaseName);
		$this->checkForError();
	}

	protected function disconnect() {
		//mysqli_close($this->db);
	}

	public function escapeString($value) {
		return mysqli_real_escape_string($this->db, $value);
	}

	public function getDatabase() {
		return $this->db;
	}

	public function processQuery($sql, $type = NULL) {
    //echo $sql. "<br />";
    $query_start = microtime(true);
		$result = mysqli_query($this->db, $sql);
		$query_end = microtime(true);
		$this->log($sql, $query_end - $query_start);

		$this->checkForError();
		$data = array();

		if ($result instanceof mysqli_result) {
			$resultType = MYSQLI_NUM;
			if ($type == 'assoc') {
				$resultType = MYSQLI_ASSOC;
			}
			while ($row = mysqli_fetch_array($result, $resultType)) {
				if (mysqli_affected_rows($this->db) > 1) {
					array_push($data, $row);
				} else {
					$data = $row;
				}
			}
			mysqli_free_result($result);
		} else if ($result) {
			$data = mysqli_insert_id($this->db);
		}

		return $data;
	}

	public function log($sql, $query_time) {
	  $threshold = $this->config->database->logQueryThreshold;
    if ($this->config->database->logQueries == "Y" && (!$threshold || $query_time >= $threshold)) {
      $util = new Utility();
      $log_path = $util->getModulePath()."/log";
      $log_file = $log_path."/database.log";
      $log_string = date("c")."\n".$_SERVER['REQUEST_URI']."\n".$sql."\nQuery completed in ".sprintf("%.3f", round($query_time, 3))." seconds";
      error_log($log_string."\n\n", 3, $log_file);
    }
	}

}

?>
