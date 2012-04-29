<?php

	class DB {
		private $conn;

		function __construct ($conf) {
			if (!$this->conn=mysqli_connect($conf['hostname'], $conf['username'], $conf['password'], $conf['database']))
				throw new Exception('MySQL connection unsuccessfull. Config: ' . json_encode($conf) . ', Error: ' . mysqli_connect_error());
		}

		function insert($table, $data) {
			$data=array_map(function ($k, $v) {return $k . "='" . $v . "'";}, $data);
			if (!mysqli_query($this->conn, "INSERT INTO " . $name . " SET " . implode(', ', $data)))
				throw new Exception('MySQL query error: ' . mysqli_error($this->conn));
			return mysqli_insert_id($this->conn);
		}
	}