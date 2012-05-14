<?php

	require_once('cache.php');
	require_once('db.php');
	require_once('ddl.php');
	require_once('item.php');

	class ADB {
		private static $instance;
		public $ROW, $COUNT, $LIST;
		public $db, $cache;
		private $DM;

		function __construct ($DM) {
			$this->ROW=$this->LIST=$this->COUNT=array();
			$this->db=DB::get_instance();
			$this->cache=CACHE::get_instance();

			$cached_dm=$this->cache->get('__DATA_MODEL__');
			$cached_hash=$this->cache->get('__DATA_MODEL_HASH__');
			if ($cached_dm!==false && $cached_hash!==false && $cached_hash==md5(serialize($DM))) {
				$this->DM=$cached_dm;
				return;
			}

			$this->DM=$DM;

			foreach ($DM as $name=>$item) {
				$item=$item+$this->get_initial_item();
				$item['conf']+=$this->get_initial_config();

				foreach ($item['fields'] as $f_name=>$field) {
					$field+=$this->get_initial_field_data($field);
					$item['fields'][$f_name]=$field;
				}

				foreach ($item['has_many'] as $local_name=>$has_many) {
					$has_many['local_name']=is_string($local_name) ? $local_name : $has_many['type'];
					if (!(isset($has_many['foreign_name']))) $has_many['foreign_name']=$name;
					$this->DM[$has_many['type']]['fields'][$has_many['foreign_name']]=array(
						'type'=>'numeric',
						'len'=>$item['conf']['len'],
						'foreign'=>array('type'=>$name, 'field'=>$has_many['local_name']),
						'index'=>true
					) + $this->get_initial_field_data(array('type'=>'numeric'));
					$item['has_many'][$local_name]=$has_many;
				}

				foreach ($item['many_to_many'] as $local_name=>$m2m) {
					if (isset($this->DM[$name]['many_to_many'][$local_name]['done'])) continue;
					$m2m['local_name']=is_string($local_name) ? $local_name : $m2m['type'];
					if (!isset($m2m['foreign_name'])) $m2m['foreign_name']=$name;
					if (!isset($m2m['relation_name'])) {
						$relation_name=array($m2m['foreign_name'], $m2m['local_name']);
						sort($relation_name);
						$m2m['relation_name']=implode('_', $relation_name);
					}
					$this->DM[$m2m['type']]['many_to_many'][$m2m['foreign_name']]=array(
						'done'=>true,
						'type'=>$name,
						'local_name'=>$m2m['foreign_name'],
						'foreign_name'=>$m2m['local_name'],
						'relation_name'=>$m2m['relation_name']
					);
					$item['many_to_many'][$local_name]=$m2m;
				}

				$this->DM[$name]=$item;
			}

			$this->cache->set('__DATA_MODEL_HASH__', md5(serialize($DM)));
			$this->cache->set('__DATA_MODEL__', $this->DM);
		}

		static function init ($DM) {
			self::$instance=new ADB ($DM);
		}

		static function get_instance () {
			if (!isset(self::$instance))
				throw new Exception('You have to initialize this class before using');
			return self::$instance;
		}

		function create_tables () {
			$ddl=new DDL($this->DM);
			$ddl->create_tables();
		}

		function load ($name, $id) {
			if (!isset($this->DM[$name])) throw new Exception('Undefined item name: ' . $name);

			if (isset($this->ROW[$name][$id]))
				return new ITEM($name, $this->DM[$name], $id, $this->ROW[$name][$id]);
			else
				return new ITEM($name, $this->DM[$name], $id);
		}

		function create ($name, $data) {
			if (!isset($this->DM[$name])) throw new Exception('Undefined item name: ' . $name);

			$item_model=$this->DM[$name];

			$insert=$foreigns=array();
			foreach ($data as $k=>$v) {
				if (!isset($item_model['fields'][$k])) {continue;}
				$field=$item_model['fields'][$k];
				if (isset($field['filter']) && function_exists($field['filter'])) {$v=eval('return ' . $field['filter'] . '($v);');}
				$insert[$k]=$v;
				if ($field['foreign']!==false) {
					$field['foreign']['id']=$v;
					$foreigns[]=$field['foreign'];
				}
			}
			$insert['create_date']=$_SERVER['REQUEST_TIME'];

			$id=$this->db->insert($name, $insert);

			foreach ($foreigns as $foreign) {
				$foreign_item=$this->load($foreign['type'], intval($foreign['id']));
				$foreign_item->add_relation($foreign['field'], $id);
			}

			return $id;
		}

		function delete ($name, $id) {
			if (!isset($this->DM[$name])) throw new Exception('Undefined item name: ' . $name);

			$item_model=$this->DM[$name];

			$item=$this->load($name, $id);

			$this->cache->delete('item_' . $name . '_' . $id);

			$this->db->delete($name, "id='" . $id . "'");

			foreach (array_filter(function ($el) {return $el['foreign']!==false;}, $item_model['fields']) as $k=>$f) {
				$foreign_item=$this->load($f['foreign']['type'], intval($f['foreign']['id']));
				$foreign_item->delete_relation($f['foreign']['field'], $id);
			}
			foreach ($item_model['has_many'] as $has_many) {
				foreach ($item[$has_many['local_name']] as $foreign_id) {
					$foreign_item=$this->load($has_many['type'], $foreign_id);
					$foreign_item[$has_many['foreign_name']]=0;
				}
			}
			foreach ($item_model['many_to_many'] as $m2m) {
				foreach ($item[$m2m['local_name']] as $foreign_id) {
					$this->unrelate($name, $id, $m2m['type'], $foreign_id, $m2m['relation_name']);
				}
			}
			foreach ($item_model['self_ref'] as $self_ref) {
				foreach ($item[$self_ref] as $self_ref_id) {
					$this->unrelate_self_ref($name, $self_ref, $id, $self_ref_id);
				}
			}

			unset($this->ITEM[$name][$id]);
			unset($this->ROW[$name][$id]);
			$this->cache->delete('item_' . $name . '_' . $id);
		}

		function relate ($name1, $id1, $name2, $id2, $relation_name=false) {
			if (!isset($this->DM[$name1])) throw new Exception('Undefined item name: ' . $name1);
			if (!isset($this->DM[$name2])) throw new Exception('Undefined item name: ' . $name2);

			$found=false;
			foreach ($this->DM[$name1]['many_to_many'] as $m2m) {
				if ($m2m['type']==$name2 && ($relation_name===false || $m2m['relation_name']==$relation_name)) {$found=true; break;}
			}
			if (!$found)
				throw new Exception('No defined relation to relate ' . $name1 . '(' . $id1 . ') to ' . $name2 . '(' . $id2 . ') for ' . $relation_name);

			$item1=$this->load($name1, $id1);
			$item2=$this->load($name2, $id2);

			if (in_array($id2, $item1[$m2m['local_name']])) {return;}

			$insert=array($m2m['foreign_name']=>$id1, $m2m['local_name']=>$id2);
			$this->db->insert($m2m['relation_name'], $insert);

			$item1->add_relation($m2m['local_name'], $id2);
			$item2->add_relation($m2m['foreign_name'], $id1);
		}

		function unrelate ($name1, $id1, $name2, $id2, $relation_name=false) {
			if (!isset($this->DM[$name1])) throw new Exception('Undefined item name: ' . $name1);
			if (!isset($this->DM[$name2])) throw new Exception('Undefined item name: ' . $name2);

			$found=false;
			foreach ($this->DM[$name1]['many_to_many'] as $m2m) {
				if ($m2m['type']==$name2 && ($relation_name===false || $m2m['relation_name']==$relation_name)) {$found=true; break;}
			}
			if (!$found)
				throw new Exception('No defined relation to unrelate ' . $name1 . '(' . $id1 . ') from ' . $name2 . '(' . $id2 . ') for ' . $relation_name);

			$item1=$this->load($name1, $id1);
			$item2=$this->load($name2, $id2);

			$condition=$m2m['foreign_name'] . "='" . $id1 . "' AND " . $m2m['local_name'] . "='" . $id2 . "'";
			$this->db->delete($m2m['relation_name'], $condition);

			$item1->delete_relation($m2m['local_name'], $id2);
			$item2->delete_relation($m2m['foreign_name'], $id1);
		}

		function self_relate ($name, $local_name, $id1, $id2) {
			if (!isset($this->DM[$name])) throw new Exception('Undefined item name: ' . $name);

			if (!(in_array($local_name, $this->DM[$name]['self_ref'])))
				throw new Exception('No defined relation to self relate ' . $name . '(' . $id1 . ') to ' . $local_name . '(' . $id2 . ')');

			$insert=array($name . '1'=>$id1, $name . '2'=>$id2);
			$this->db->insert($local_name, $insert);

			$item1=$this->load($name, $id1);
			$item2=$this->load($name, $id2);

			$item1->add_relation($local_name, $id2);
			$item2->add_relation($local_name, $id1);
		}

		function self_unrelate ($name, $local_name, $id1, $id2) {
			if (!isset($this->DM[$name])) throw new Exception('Undefined item name: ' . $name);

			if (!(in_array($local_name, $this->DM[$name]['self_ref'])))
				throw new Exception('No defined relation to self relate ' . $name . '(' . $id1 . ') to ' . $local_name . '(' . $id2 . ')');

			$item1=$this->load($name, $id1);
			$item2=$this->load($name, $id2);

			$condition="(" . $name . "1='" . $id1 . "' AND " . $name . "2='" . $id2 . "') OR (" . $name . "1='" . $id2 . "' AND " . $name . "2='" . $id1 . "')";
			$this->db->delete($local_name, $condition);

			$item1->delete_relation($local_name, $id2);
			$item2->delete_relation($local_name, $id1);
		}

		function find_unique ($name, $field, $value) {
			if (!isset($this->DM[$name])) throw new Exception('Undefined item name: ' . $name);

			if (!isset($this->DM[$name]['fields'][$field]))
				throw new Exception('No field as ' . $field . ' found for item ' . $name);

			$sql="SELECT * FROM " . $name . " WHERE " . $field . "='" . $this->db->escape($value) . "'";
			$result=$this->db->select($sql);
			if (count($result)) {
				$this->ROW[$name][$result[0]['id']]=$result[0];
				return $this->load($name, $result[0]['id']);
			}

			return false;
		}

		function count ($name, $condition=false, $order=false) {
			if (isset($this->COUNT[$name][$condition])) return $this->COUNT[$name][$condition];

			$sql=$this->prepare_select($name, $condition, $order);

			$this->COUNT[$name][$condition]=$this->db->count($sql);

			return $this->COUNT[$name][$condition];
		}

		function id_list ($name, $condition=false, $order=false, $limit=false) {
			if (isset($this->LIST[$name][$condition][$order][$limit])) return $this->LIST[$name][$condition][$order][$limit];

			$sql=$this->prepare_select($name, $condition, $order);

			if ($limit!==false) {$sql.=" LIMIT " . $limit;}

			$return=$this->LIST[$name][$condition][$order][$limit]=array();

			foreach ($this->db->select($sql) as $row) {
				$this->ROW[$name][intval($row['id'])]=$row;
				$return[]=intval($row['id']);
			}

			$this->LIST[$name][$condition][$order][$limit]=$return;

			return $return;
		}

		function count_join ($name, $table, $condition, $order) {
			if (isset($this->COUNT[$name][$table][$condition])) return $this->COUNT[$name][$table][$condition];

			$sql=$this->prepare_join_select($name, $table, $condition, $order);

			$this->COUNT[$name][$table][$condition]=$this->db->count($sql);

			return $this->COUNT[$name][$table][$condition];
		}

		function id_list_join ($name, $table, $condition=false, $order=false, $limit=false) {
			if (isset($this->LIST[$name][$table][$condition][$order][$limit])) return $this->LIST[$name][$table][$condition][$order][$limit];

			$sql=$this->prepare_join_select($name, $table, $condition, $order);

			if ($limit!==false) {$sql.=" LIMIT " . $limit;}

			$return=$this->LIST[$name][$table][$condition][$order][$limit]=array();

			foreach ($this->db->select($sql) as $row) {
				$this->ROW[$name][intval($row['id'])]=$row;
				$return[]=intval($row['id']);
			}

			$this->LIST[$name][$table][$condition][$order][$limit]=$return;

			return $return;
		}

		private function prepare_select ($name, $condition=false, $order=false) {
			if (!isset($this->DM[$name])) throw new Exception('Undefined item name: ' . $name);
			$item_model=$this->DM[$name];

			$sql='SELECT * FROM ' . $name;
			if ($condition!==false) $sql.=' WHERE ' . $condition;

			if ($order!==false) $sql.=" ORDER BY " . $this->prepare_order($name, $order);

			return $sql;
		}

		private function prepare_join_select ($name, $table, $condition=false, $order=false) {
			if (!isset($this->DM[$name])) throw new Exception('Undefined item name: ' . $name);
			$item_model=$this->DM[$name];

			$sql='SELECT ' . $name . '.* FROM ' . $name . ', ' . $table;
			if ($condition!==false) $sql.=' WHERE ' . $condition;

			if ($order!==false) $sql.=" ORDER BY " . $this->prepare_order($name, $order);

			return $sql;
		}

		private function prepare_order ($name, $order) {
			if (!isset($this->DM[$name])) throw new Exception('Undefined item name: ' . $name);
			$item_model=$this->DM[$name];

			foreach (explode(',', $order) as $p) {
				$p=trim(array_shift(explode(' ', trim($p))));
				if (isset($item_model['fields'][$p])) {continue;}
				foreach ($item_model['has_many'] as $has_many) {
					if ($p!=$has_many['local_name']) {continue;}
					$order=strtr($order, array($p=>"(SELECT COUNT(id) FROM ". $has_many['type'] . " WHERE " . $has_many['foreign_name'] . "=" . $name . ".id)"));
					continue 2;
				}
				foreach ($item_model['many_to_many'] as $m2m) {
					if ($p!=$m2m['local_name']) {continue;}
					$order=strtr($order, array($p=>"(SELECT COUNT(id) FROM ". $m2m['relation_name'] . " WHERE " . $m2m['foreign_name'] . "=" . $name . ".id)"));
					continue 2;
				}
				foreach ($item_model['self_ref'] as $self_ref) {
					if ($p!=$self_ref) {continue;}
					$order=strtr($order, array($p=>"(SELECT COUNT(id) FROM ". $self_ref . " WHERE " . $name . "1=" . $name . ".id OR " . $name . "2=" . $name . ".id)"));
					continue 2;
				}
			}

			return $order;
		}

		private function get_initial_item () {
			static $list=array(
				'conf'=>array(),
				'has_many'=>array(),
				'many_to_many'=>array(),
				'self_ref'=>array(),
				'fields'=>array()
			);
			return $list;
		}

		private function get_initial_config () {
			static $list=array(
				'len'=>5,
				'ttl'=>3600,
			);
			return $list;
		}

		private function get_initial_field_data ($field) {
			static $list=array(
				'type'=>'text',
				'unique'=>false,
				'index'=>false,
				'foreign'=>false
			);

			static $list_for_type=array(
				'text'=>array(
					'len'=>100
				),
				'numeric'=>array(
					'len'=>4,
					'decimal'=>0,
					'signed'=>false,
				),
				'pass'=>array(
					'len'=>40,
					'filter'=>'sha1'
				),
			);
			$field=$field+$list;
			return $field + $list_for_type[$field['type']];
		}
	}