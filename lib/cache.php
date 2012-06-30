<?php namespace arraydb;

	class CACHE {
		private static $instance;
		private $type, $conn, $path;
		private $prefix='';

		function __construct ($conf) {
			$this->type=$conf['type'];
			switch ($this->type) {
				case 'apc':
					if (!function_exists('apc_fetch'))
						throw new \Exception('APC extension is not installed');
					break;
				case 'memcached':
					$this->conn=memcache_connect($conf['host'], $conf['port'], $conf['timeout']);
					break;
				case 'file':
					$this->path=$conf['path'];
					if (!(is_readable($this->path) && is_writable($this->path)))
						throw new \Exception($this->path . ' directory must be readable and writable');
					break;
				default:
					$this->type='no-cache';
			}
			if (isset($conf['prefix'])) $this->prefix=$conf['prefix'];
		}

		static function init ($conf=false) {
			self::$instance=new CACHE ($conf);
		}

		static function get_instance () {
			if (!isset(self::$instance))
				throw new \Exception('You have to initialize this class before using');
			return self::$instance;
		}

		function get ($key) {
			$key=$this->prefix . $key;
			switch ($this->type) {
				case 'apc':
					return apc_fetch($key);
				case 'memcached':
					return memcache_get($this->conn, $key);
				case 'file':
					if (!is_file($this->path . DIRECTORY_SEPARATOR . $key)) return false;
					$data=explode("\n", file_get_contents($this->path . DIRECTORY_SEPARATOR . $key), 2);
					if ($data[0]<$_SERVER['REQUEST_TIME']) {
						unlink($this->path . DIRECTORY_SEPARATOR . $key);
						return false;
					}
					return unserialize($data[1]);
				case 'no-cache':
					return false;
			}
		}

		function set ($key, $value, $ttl=null) {
			$key=$this->prefix . $key;
			switch ($this->type) {
				case 'apc':
					return apc_store($key, $value, $ttl);
				case 'memcached':
					return memcache_set($this->conn, $key, $value, false, $ttl);
				case 'file':
					$data=($_SERVER['REQUEST_TIME']+$ttl) . "\n" . serialize($value);
					file_put_contents($this->path . DIRECTORY_SEPARATOR . $key, $data);
					return true;
				case 'no-cache':
					return false;
			}
		}

		function delete ($key) {
			$key=$this->prefix . $key;
			switch ($this->type) {
				case 'apc':
					return apc_delete($key);
				case 'memcached':
					return memcache_delete($this->conn, $key, 0);
				case 'file':
					if (!is_file($this->path . DIRECTORY_SEPARATOR . $key)) return;
					return unlink($this->path . DIRECTORY_SEPARATOR . $key);
				case 'no-cache':
					return false;
			}
		}

		function flush () {
			switch ($this->type) {
				case 'apc':
					return apc_clear_cache('user');
				case 'memcached':
					return memcache_flush($this->conn);
				case 'file':
					return system('rm -fr ' . $this->path . DIRECTORY_SEPARATOR . $this->prefix . '*');
				case 'no-cache':
					return false;
			}
		}
	}