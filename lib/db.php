<?php

	class DB {
		private $conn;

		function __construct ($conf) {
			if (!$this->conn=mysqli_connect($conf['hostname'], $conf['username'], $conf['password'], $conf['database']))
				throw new Exception('MySQL connection unsuccessfull. Config: ' . json_encode($conf) . ', Error: ' . mysqli_connect_error());
		}

		function insert ($table, $data) {
			$data=array_map(function ($k, $v) {return $k . "='" . $this->escape($v) . "'";}, $data);
			if (!mysqli_query($this->conn, "INSERT INTO " . $name . " SET " . implode(', ', $data)))
				throw new Exception('MySQL insert query error: ' . mysqli_error($this->conn));
			return mysqli_insert_id($this->conn);
		}

		function update ($table, $data, $condition='TRUE') {
			$data=array_map(function ($k, $v) {return $k . "='" . $this->escape($v) . "'";}, $data);
			if (!mysqli_query($this->conn, "UPDATE " . $name . " SET " . implode(', ', $data) . " WHERE " . $condition))
				throw new Exception('MySQL update query error: ' . mysqli_error($this->conn));
		}

		function delete ($table, $condition) {
			if (!mysqli_query($this->conn, "DELETE FROM " . $name . " WHERE " . $condition))
				throw new Exception('MySQL delete query error: ' . mysqli_error($this->conn));
		}

		function select ($sql) {
			if (!$result=mysqli_query($this->conn, $sql))
				throw new Exception('MySQL select query error: ' . mysqli_error($this->conn));
			return mysqli_fetch_all($result, MYSQLI_ASSOC);
		}

		function count ($sql) {
			if (!$result=mysqli_query($this->conn, $sql))
				throw new Exception('MySQL select query error: ' . mysqli_error($this->conn));
			return mysqli_num_rows($result, MYSQLI_ASSOC);
		}

		function escape ($value) {
			return mysqli_real_escape_string($this->conn, $value);
		}
	}