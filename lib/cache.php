<?php

	class CACHE {
		private static $instance;
		private $type, $conn;

		function __construct ($conf) {
		}

		function init ($conf=false) {
			self::$instance=new CACHE ($conf);
		}

		function get_instance () {
			if (!isset(self::$instance))
				throw new Exception('You have to initialize this class before using');
			return self::$instance;
		}

		function get ($key) {
			return apc_fetch($key);
		}

		function set ($key, $value, $ttl=null) {
			return apc_store($anahtar, $value, $ttl);
		}

		function delete ($key) {
			return apc_delete($key);
		}
	}