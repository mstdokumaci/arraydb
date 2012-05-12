<?php

	class DDL {
		private $DM, $table;
		private $db;

		function __construct ($DM) {
			$this->db=DB::get_instance();
			$this->DM=$DM;
		}

		function create_tables () {
			foreach ($this->table as $name=>$table) {
				$sql="DROP TABLE IF EXISTS " . $name;
				$this->db->table($sql);
				$sql="CREATE TABLE " . $name . " (" . implode(', ', array_merge($table['fields'], $tablo['keys'])) . ") ENGINE=InnoDB";
				$this->db->table($sql);
			}
		}

		function prepare_tables () {
			foreach ($this->DM as $name=>$table)
				$this->table[$name]=$this->prepare_table($name, $table);
		}

		private function prepare_table ($name, $table) {
			$conf=$table['conf'];
			$db_table['fields'][]='id ' . $this->get_field_type('numeric', $conf['len']) . ' NO NULL AUTO_INCREMENT';
			$db_table['keys'][]='PRIMARY KEY (id)';

			$db_table['fields'][]="create_date INT(11) UNSIGNED NOT NULL DEFAULT '0'";
			$db_table['keys'][]='KEY (create_date)';
			$db_table['fields'][]="update_date INT(11) UNSIGNED NOT NULL DEFAULT '0'";
			$db_table['keys'][]='KEY (update_date)';

			foreach ($table['fields'] as $field_name=>$field) {
				list($db_field, $db_key)=$this->prepare_field($field_name, $field);
				$db_table['fields'][]=$db_field;
				$db_table['keys'][]=$db_key;
			}

			foreach ($table['many_to_many'] as $m2m) {
				if (isset($this->table[$m2m['relation_name']]['created'])) {continue;}
				$this->table[$m2m['relation_name']]['created']=true;

				$this->table[$m2m['relation_name']]['fields'][]=$m2m['foreign_name'] . ' ' . $this->get_field_type('numeric', $conf['len']) . " NOT NULL DEFAULT '0'";
				$this->table[$m2m['relation_name']]['fields'][]=$m2m['local_name'] . ' ' . $this->get_field_type('numeric', $this->DM[$m2m['type']]['conf']['len']) . " NOT NULL DEFAULT '0'";
				$this->table[$m2m['relation_name']]['keys'][]='PRIMARY KEY (' . $m2m['foreign_name'] . ', ' . $m2m['local_name'] . ')';
			}

			foreach ($table['self_ref'] as $self_ref) {
				$type=$this->get_field_type('numeric', $conf['len']);
				$this->table[$self_ref]['fields'][]=$name . '1 ' . $type . " NOT NULL DEFAULT '0'";
				$this->table[$self_ref]['fields'][]=$name . '2 ' . $type . " NOT NULL DEFAULT '0'";
				$this->table[$self_ref]['keys'][]='PRIMARY KEY (' . $name . '1, ' . $name . '2)';
			}

			return $db_table;
		}

		private function prepare_field ($name, $field) {
			if ($field['type']=='numeric')
				$type=$this->get_field_type($field['type'], $field['len'], $field['signed'], $field['decimal']);
			else
				$type=$this->get_field_type($field['type'], $field['len']);

			$db_field=$name . ' ' . $type . " NOT NULL DEFAULT '" . ($field['type']=='numeric' ? '0' : '')  . "'";

			if ($field['unique'])
				$db_key='UNIQUE KEY (' . $name . ')';
			elseif ($field['index'])
				$db_key='KEY (' . $name . ')';

			return array($db_field, $db_key);
		}

		private function get_field_type ($type, $len, $signed=false, $decimal=0) {
			if ($type=='numeric' && $decimal) {
				if ($decimal>=$len)
					throw new Exception('Decimal length must be less than total length');
				if ($len>65)
					throw new Exception('Decimal field length can be 65 at most');
				return 'DECIMAL (' . $len . ', ' . $decimal . ')';
			}

			static $type_map=array('pass'=>'text');
			if (isset($type_map[$type])) {$type=$type_map[$type];}

			static $types=array(
				'numeric'=>array(3=>'TINYINT', 5=>'SMALLINT', 7=>'MEDIUMINT', 10=>'INT', 19=>'BIGINT'),
				'text'=>array(21000=>'VARCHAR', 9000001=>'MEDIUMTEXT')
			);

			$more_types=array_filter($types[$type], create_function('$maxlen, $name', 'return $maxlen>=' . $len . ';'));
			ksort($more_types);
			$name=array_shift($more_types);

			if (empty($name))
				throw new Exception('Field length is too much');

			if ($signed) $len++;

			if ($len<35001) $name . '(' . $len . ')';

			if ($type=='numeric' && !$signed) $name.=' UNSIGNED';

			return $name;
		}
	}
