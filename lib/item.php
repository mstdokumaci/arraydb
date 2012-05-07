<?php

	class ITEM implements arrayaccess {
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
				throw new Exception($field . ' is not a many to many relation field');

			if (in_array($id, $this->data[$field])) return false;

			$this->data[$field][]=$id;
			$this->save();
		}

		function delete_relation ($field, $id) {
			$id=intval($id);
			if (!isset($this->data[$field]) || !is_array($this->data[$field]))
				throw new Exception($field . ' is not a many to many relation field');

			if (!in_array($id, $this->data[$field])) return false;

			unset($this->data[$field][array_search($id, $this->data[$field])]);
			$this->save();
		}

		function duzenle ($bilgi) {
			global $baglan, $_YAPI;
			$yapi=$_YAPI[$this->tur];
			$sql=array();
			foreach ($bilgi as $ne=>$deger) {
				if (!(isset($yapi['s'][$ne]))) {
					if (in_array($ne, array('_yetki_', 'ip', 'lisan'))) {$this->bilgi[$ne]=$deger;}
					continue;
				}
				$s=$yapi['s'][$ne];
				if (isset($s['filtre']) && function_exists($s['filtre'])) {$deger=eval('return ' . $s['filtre'] . '($deger);');}
				if ($s['yabanci']!==false) {
					if ($y_tablo=yukle($s['yabanci']['tur'], $this->bilgi[$ne])) {$y_tablo->sil($s['yabanci']['sutun'], $this->seri);}
					if ($y_tablo=yukle($s['yabanci']['tur'], $deger)) {$y_tablo->ekle($s['yabanci']['sutun'], $this->seri);}
				}
				$sql[]=$ne . "='" . mysqli_real_escape_string($baglan, $deger) . "'";
				$this->bilgi[$ne]=$deger;
				if (isset($yapi['a']['kestirme']) && $ne==$yapi['a']['kestirme']['s']) {
					kestirme_baslik_ekle($deger, $yapi['a']['kestirme']['sayfa'], $this->seri);
				}
			}
			if (count($sql)) {
				$sql="UPDATE " . $this->tur . " SET " . implode(", ", $sql) . (($yapi['a']['tarihler']) ? ", degis_tarih='" . _GMT_STAMP_ . "'" : '') . " WHERE seri='" . $this->seri . "'";
				mysql_sorgu($sql);
				if ($yapi['a']['tarihler']) {$this->bilgi['degis_tarih']=_GMT_STAMP_;}
			}
			$this->kaydet();
		}

		function liste ($ne, $sart=false, $sirala=false, $sinirla=false, $say=false) {
			global $_YAPI;
			if (!(isset($this->bilgi[$ne]) && is_array($this->bilgi[$ne]))) {return false;}
			$yapi=$_YAPI[$this->tur];
			foreach ($yapi['sc'] as $sc) {
				if ($ne!=$sc['yerel']) {continue;}
				$sql[]=$sc['yabanci'] . "='" . $this->seri . "'";
				if ($sart!==false) {$sql[]='(' . $sart . ')';}
				return liste($sc['tur'], implode(' AND ', $sql), $sirala, $sinirla, $say);
			}
			foreach ($yapi['cc'] as $cc) {
				if ($ne!=$cc['yerel']) {continue;}
				$sql[]="seri IN (SELECT " . $cc['yerel'] . " FROM " . $cc['yardimci'] . " WHERE " . $cc['yabanci'] . "='" . $this->seri . "')";
				if ($sart!==false) {$sql[]='(' . $sart . ')';}
				return liste($cc['tur'], implode(' AND ', $sql), $sirala, $sinirla, $say);
			}
			foreach ($yapi['cy'] as $cy) {
				if ($ne!=$cy) {continue;}
				$sql[0]="(SELECT " . $this->tur . "1 AS seri FROM " . $cy . " WHERE " . $this->tur . "2='" . $this->seri . "')";
				$sql[0].="UNION (SELECT " . $this->tur . "2 AS seri FROM " . $cy . " WHERE " . $this->tur . "1='" . $this->seri . "')";
				if ($sart!==false) {$sql[]='(' . $sart . ')';}
				return liste($this->tur, implode(' AND ', $sql), $sirala, $sinirla, $say);
			}
		}

		private function save () {
			$this->cache->set('item_' . $this->name . '_' . $this->id, $this->model['conf']['ttl']);
		}
	}