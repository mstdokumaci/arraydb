<?php

	class ADB {
		private $ITEM, $ROW, $LIST;
		private $DM;

		function __construct ($DM) {
			$this->ITEM=$this->ROW=$this->LIST=array();
			$cached_dm=cache::get('__DATA_MODEL__');
			$cached_hash=cache::get('__DATA_MODEL_HASH__');
			if ($cached_dm!==false && $cached_hash!==false && $cached_hash==md5(serialize($DM))) {
				$this->DM=$cached_dm;
				return;
			}

			$this->DM=$DM+$this->get_initial_arrays();

			foreach ($DM as $name=>$array) {
				$array=$array+$this->get_initial_fields();
				$array['conf']=$array['conf']+$this->get_initial_config();

				foreach ($array['fields'] as $f_name=>$field) {
					$field=$field+$this->get_initial_field_data($field['type']);
					$array['fields'][$f_name]=$field;
				}

				foreach ($array['has_many'] as $local_name=>$has_many) {
					$has_many['local_name']=is_string($local_name) ? $local_name : $has_many['type'];
					if (!(isset($has_many['foreign_name']))) $has_many['foreign_name']=$name;
					$this->DM[$has_many['type']]['fields'][$has_many['foreign_name']]=array(
						'type'=>'numeric',
						'len'=>$array['conf']['len'],
						'foreign'=>array('type'=>$name, 'field'=>$has_many['local_name']),
						'index'=>true
					) + get_initial_field_data($field['type']);
					$array['has_many'][$local_name]=$has_many;
				}

				foreach ($array['many_to_many'] as $local_name=>$m2m) {
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
					$array['many_to_many'][$local_name]=$m2m;
				}

				$this->DM[$name]=$array;
			}

			cache::set('__DATA_MODEL_HASH__', md5(serialize($DM)));
			cache::set('__DATA_MODEL__', $this->DM);
		}

		private function get_initial_arrays () {
			static $list=array();
			return $list;
		}

		private function get_initial_fields () {
			static $list=array();
			return $list;
		}

		private function get_initial_config () {
			static $list=array();
			return $list;
		}

		private function get_initial_field_data ($type) {
			static $list=array();
			static $list_for_type=array();
			return $list + $list_for_type[$type];
		}
	}