<?php

	class ADB {
		private $ITEM, $ROW, $LIST;
		private $DM;
		private $db;

		function __construct ($DM, $conf) {
			$this->ITEM=$this->ROW=$this->LIST=array();
			$this->db=new DB($conf);

			$cached_dm=cache::get('__DATA_MODEL__');
			$cached_hash=cache::get('__DATA_MODEL_HASH__');
			if ($cached_dm!==false && $cached_hash!==false && $cached_hash==md5(serialize($DM))) {
				$this->DM=$cached_dm;
				return;
			}

			$this->DM=$DM;

			foreach ($DM as $name=>$item) {
				$item=$item+$this->get_initial_item();
				$item['conf']=$item['conf']+$this->get_initial_config();

				foreach ($item['fields'] as $f_name=>$field) {
					$field=$field+$this->get_initial_field_data($field['type']);
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
					) + get_initial_field_data($field['type']);
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

			cache::set('__DATA_MODEL_HASH__', md5(serialize($DM)));
			cache::set('__DATA_MODEL__', $this->DM);
		}

		function load ($name, $id) {
			if (!isset($this->DM[$name])) throw new Exception('Undefined item name: ' . $name);

			if (isset($this->ITEM[$name][$id])) return $this->ITEM[$name][$id];
			elseif ($item=cache::get('item_' . $name . '_' . $id)) return $item;

			$item=new ITEM($name, $id);
			return $ITEM;
		}

		function create ($name, $data) {
			if (!isset($this->DM[$name])) throw new Exception('Undefined item name: ' . $name);

			$item=$this->DM[$name];

			$insert=$foreigns=array();
			foreach ($data as $k=>$v) {
				if (!isset($item['fields'][$k])) {continue;}
				$field=$item['fields'][$k];
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
				$foreign_item->add_belong($foreign['field'], $id);
			}

			return $id;
		}

		private function get_initial_item () {
			static $list=array(
				'conf'=>array(),
				'has_one'=>array(),
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

		private function get_initial_field_data ($type) {
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
			return $list + $list_for_type[$type];
		}
	}