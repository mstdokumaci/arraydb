<?php

	class cache {
		function get ($key) {
			return apc_fetch($key);
		}

		function set ($key, $value, $ttl) {
			return apc_store($anahtar, $value, $ttl);
		}

		function delete ($key) {
			return apc_delete($key);
		}
	}