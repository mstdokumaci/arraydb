<?php

	class ITEM implements arrayaccess {
		private static $ITEM;
		private $name, $model, $id, $data;
		private $db, $adb, $cache;

		function __construct ($name, $model, $id, $row=false) {
			$this->db=DB::get_instance();
			$this->adb=ADB::get_instance();
			$this->cache=CACHE::get_instance();

			$this->name=$name;
			$this->model=$model;
			$this->id=$id;

			if ($data=$this->cache->get('item_' . $this->name . '_' . $this->id)) {
				$this->data=$data;
				return;
			}

			if ($row!==false) {
				$this->data=$row;
			} else {
				$sql="SELECT * FROM " . $this->name . " WHERE id='" . $this->id . "'";
				$result=$this->db->select($sql);
				if (!count($result))
					throw new Exception('No ' . $this->name . ' found with id ' . $this->id);

				$this->data=$result[0];
			}

			foreach ($this->model['has_many'] as $has_many)
				$this->data[$has_many['local_name']]=$this->adb->id_list($has_many['type'], $has_many['foreign_name'] . "='" . $this->id . "'");

			foreach ($this->model['many_to_many'] as $m2m) {
				$sql="SELECT " . $m2m['local_name'] . " FROM " . $m2m['relation_name'] . " WHERE " . $m2m['foreign_name'] . "='" . $id . "'";
				$this->data[$m2m['local_name']]=array();
				foreach ($this->db->select($sql) as $row) {
					$this->data[$m2m['local_name']][]=intval($row[$m2m['local_name']]);
				}
			}
			foreach ($this->model['self_ref'] as $self_ref) {
				$sql="(SELECT " . $this->name . "1 AS id FROM " . $self_ref . " WHERE " . $this->name . "2='" . $this->id . "')";
				$sql.="UNION (SELECT " . $this->name . "2 AS id FROM " . $self_ref . " WHERE " . $this->name . "1='" . $this->id . "')";
				$this->data[$self_ref]=array();
				foreach ($this->db->select($sql) as $row) {
					$this->data[$self_ref][]=intval($row['id']);
				}
			}

			$this->save();
		}


		/** OFFSET FONKSİYONLARI **/

		function offsetexists ($field) {return isset($this->data[$field]);}

		function offsetget ($field) {
			if (!isset($this->data[$field]))
				throw new Exception('No field found as ' . $field);

			return $this->data[$field];
		}

		function offsetset ($field, $value) {
			if (!(isset($this->model['fields'][$field])))
				throw new Exception('No field found as ' . $field);

			$field_model=$this->model['fields'][$field];
			if (isset($field_model['filter']) && function_exists($field_model['filter'])) {$value=eval('return ' . $field_model['filter'] . '($value);');}

			$update[$field]=$value;
			$update['update_date']=$_SERVER['REQUEST_TIME'];

			$this->db->update($this->name, $update, "id='" . $this->id . "'");

			if ($field_model['foreign']!==false) {
				$foreign_item=$this->adb->load($field_model['foreign']['type'], intval($this->data[$field]));
				$foreign_item->delete_relation($field_model['foreign']['field'], $this->id);

				$foreign_item=$this->adb->load($field_model['foreign']['type'], intval($value));
				$foreign_item->add_relation($field_model['foreign']['field'], $this->id);
			}
			$this->data[$field]=$value;
			$this->data['update_date']=$_SERVER['REQUEST_TIME'];

			$this->kaydet();
		}

		function offsetunset ($field) {
			throw new Exception('Field unset is not allowed.');
		}

		function add_relation ($field, $id) {
			$id=intval($id);
			if (!isset($this->data[$field]) || !is_array($this->data[$field]))
				throw new Exception($field . ' is not a relation field');

			if (in_array($id, $this->data[$field])) return false;

			$this->data[$field][]=$id;
			$this->save();
		}

		function delete_relation ($field, $id) {
			$id=intval($id);
			if (!isset($this->data[$field]) || !is_array($this->data[$field]))
				throw new Exception($field . ' is not a relation field');

			if (!in_array($id, $this->data[$field])) return false;

			unset($this->data[$field][array_search($id, $this->data[$field])]);
			$this->save();
		}

		function update ($data) {
			$update=$foreigns=array();

			foreach ($data as $k=>$v) {
				if (!isset($this->model['fields'][$k])) {continue;}

				$field=$item_model['fields'][$k];
				if (isset($field['filter']) && function_exists($field['filter'])) {$v=eval('return ' . $field['filter'] . '($v);');}

				$update[$k]=$v;

				if ($field['foreign']!==false) {
					$field['foreign']['id']=$v;
					$foreigns[$k]=$field['foreign'];
				}
			}
			$update['update_date']=$_SERVER['REQUEST_TIME'];

			$this->db->update($this->name, $update, "id='" . $this->id . "'");

			foreach ($foreigns as $k=>$foreign) {
				$foreign_item=$this->load($foreign['type'], intval($this->data[$k]));
				$foreign_item->delete_relation($foreign['field'], $this->id);

				$foreign_item=$this->load($foreign['type'], intval($foreign['id']));
				$foreign_item->add_relation($foreign['field'], $this->id);
			}

			$this->data['update_date']=$_SERVER['REQUEST_TIME'];
			$this->data=$update + $this->data;

			$this->save();
		}

		function count ($field, $condition=false, $order=false) {
			if (!isset($this->data[$field]) || !is_array($this->data[$field]))
				throw new Exception($field . ' is not a relation field');

			$sql=array();

			foreach ($this->model['has_many'] as $has_many) {
				if ($field!=$has_many['local_name']) {continue;}
				$sql[]=$has_many['foreign_name'] . "='" . $this->id . "'";
				if ($condition!==false) {$sql[]='(' . $condition . ')';}
				return $this->adb->count($has_many['type'], implode(' AND ', $sql), $order);
			}

			foreach ($this->model['many_to_many'] as $m2m) {
				if ($field!=$m2m['local_name']) {continue;}

				$sql[]=$m2m['type'] . ".id=" . $m2m['relation_name'] . "." . $m2m['local_name'];
				$sql[]=$m2m['relation_name'] . "." . $m2m['foreign_name'] . "='" . $this->id . "'";
				if ($condition!==false) {$sql[]='(' . $condition . ')';}

				return $this->adb->count_join($m2m['type'], $m2m['relation_name'], implode(' AND ', $sql), $order);
			}

			foreach ($this->model['self_ref'] as $self_ref) {
				if ($field!=$self_ref) {continue;}

				$sql[0]="((" . $m2m['type'] . ".id" . $self_ref . "." . $this->name . "1 AND " . $self_ref . "." . $this->name . "2=" . $this->id . ")";
				$sql[0].=" OR (" . $m2m['type'] . ".id" . $self_ref . "." . $this->name . "2 AND " . $self_ref . "." . $this->name . "1=" . $this->id . "))";
				if ($condition!==false) {$sql[]='(' . $condition . ')';}

				return $this->adb->count_join($this->name, $self_ref, implode(' AND ', $sql), $order);
			}
		}

		function id_list ($field, $condition=false, $order=false, $limit=false) {
			if (!isset($this->data[$field]) || !is_array($this->data[$field]))
				throw new Exception($field . ' is not a relation field');

			$sql=array();

			foreach ($this->model['has_many'] as $has_many) {
				if ($field!=$has_many['local_name']) {continue;}
				$sql[]=$has_many['foreign_name'] . "='" . $this->id . "'";
				if ($condition!==false) {$sql[]='(' . $condition . ')';}
				return $this->adb->id_list($has_many['type'], implode(' AND ', $sql), $order, $limit);
			}

			foreach ($this->model['many_to_many'] as $m2m) {
				if ($field!=$m2m['local_name']) {continue;}

				$sql[]=$m2m['type'] . ".id=" . $m2m['relation_name'] . "." . $m2m['local_name'];
				$sql[]=$m2m['relation_name'] . "." . $m2m['foreign_name'] . "='" . $this->id . "'";
				if ($condition!==false) {$sql[]='(' . $condition . ')';}

				return $this->adb->id_list_join($m2m['type'], $m2m['relation_name'], implode(' AND ', $sql), $order, $limit);
			}

			foreach ($this->model['self_ref'] as $self_ref) {
				if ($field!=$self_ref) {continue;}

				$sql[0]="((" . $m2m['type'] . ".id" . $self_ref . "." . $this->name . "1 AND " . $self_ref . "." . $this->name . "2=" . $this->id . ")";
				$sql[0].=" OR (" . $m2m['type'] . ".id" . $self_ref . "." . $this->name . "2 AND " . $self_ref . "." . $this->name . "1=" . $this->id . "))";
				if ($condition!==false) {$sql[]='(' . $condition . ')';}

				return $this->adb->id_list_join($this->name, $self_ref, implode(' AND ', $sql), $order, $limit);
			}
		}

		private function save () {
			$this->cache->set('item_' . $this->name . '_' . $this->id, $this->model['conf']['ttl']);
		}
	}