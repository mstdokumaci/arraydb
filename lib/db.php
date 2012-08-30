<?php namespace arraydb;

	class DB {
		private static $instance;
		private $conn;

		function __construct ($conf) {
			if (!$this->conn=mysqli_connect($conf['hostname'], $conf['username'], $conf['password'], $conf['database']))
				throw new \Exception('MySQL connection unsuccessfull. Config: ' . json_encode($conf) . ', Error: ' . mysqli_connect_error());
			mysqli_set_charset($this->conn, 'utf8');
		}

		static function init ($conf) {
			self::$instance=new DB ($conf);
		}

		static function get_instance () {
			if (!isset(self::$instance))
				throw new \Exception('You have to initialize this class before using');
			return self::$instance;
		}

		function insert ($table, $data) {
			foreach ($data as $k=>$v) $data[$k]="`" . $k . "`='" . $this->escape($v) . "'";
			if (!mysqli_query($this->conn, "INSERT INTO `" . $table . "` SET " . implode(', ', $data)))
				throw new \Exception('MySQL insert query error: ' . mysqli_error($this->conn));
			return mysqli_insert_id($this->conn);
		}

		function update ($table, $data, $condition='TRUE') {
			foreach ($data as $k=>$v) $data[$k]="`" . $k . "`='" . $this->escape($v) . "'";
			if (!mysqli_query($this->conn, "UPDATE `" . $table . "` SET " . implode(', ', $data) . " WHERE " . $condition))
				throw new \Exception('MySQL update query error: ' . mysqli_error($this->conn));
		}

		function delete ($table, $condition) {
			if (!mysqli_query($this->conn, "DELETE FROM `" . $table . "` WHERE " . $condition))
				throw new \Exception('MySQL delete query error: ' . mysqli_error($this->conn));
		}

		function table ($sql) {
			if (!mysqli_query($this->conn, $sql))
				throw new \Exception('MySQL query error: ' . mysqli_error($this->conn));
		}

		function select ($sql) {
			if (!$result=mysqli_query($this->conn, $sql))
				throw new \Exception('MySQL select query error: ' . mysqli_error($this->conn));

			$all=array();
			while ($row=mysqli_fetch_assoc($result)) $all[]=$row;
			return $all;
		}

		function count ($sql) {
			if (!$result=mysqli_query($this->conn, $sql))
				throw new \Exception('MySQL select query error: ' . mysqli_error($this->conn));
			return mysqli_num_rows($result);
		}

		function escape ($value) {
			return mysqli_real_escape_string($this->conn, $value);
		}
	}