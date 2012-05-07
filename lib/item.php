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

			foreach ($this->model['has_many'] as $has_many) {$this->data[$has_many['local_name']]=$this->adb->id_list($has_many['type'], $has_many['foreign_name'] . "='" . $this->id . "'");}

			foreach ($yapi['cc'] as $cc) {
				$sql="SELECT " . $cc['yerel'] . " FROM " . $cc['yardimci'] . " WHERE " . $cc['yabanci'] . "='" . $seri . "'";
				$this->bilgi[$cc['yerel']]=array();
				if (!$tablo=mysql_sorgu($sql)) {continue;}
				while ($satir=mysqli_fetch_assoc($tablo)) {$this->bilgi[$cc['yerel']][]=intval($satir[$cc['yerel']]);}
			}
			foreach ($yapi['cy'] as $cy) {
				$sql="(SELECT " . $tur . "1 AS seri FROM " . $cy . " WHERE " . $tur . "2='" . $seri . "')";
				$sql.="UNION (SELECT " . $tur . "2 AS seri FROM " . $cy . " WHERE " . $tur . "1='" . $seri . "')";
				$this->bilgi[$cy]=array();
				if (!$tablo=mysql_sorgu($sql)) {continue;}
				while ($satir=mysqli_fetch_assoc($tablo)) {
					$this->bilgi[$cy][]=intval($satir['seri']);
				}
			}
			$this->save();
		}


		/** OFFSET FONKSİYONLARI **/

		function offsetexists ($ne) {return isset($this->bilgi[$ne]);}

		function offsetget ($ne) {
			return (isset($this->bilgi[$ne])) ? $this->bilgi[$ne] : false;
		}

		function offsetset ($ne, $deger) {
			global $baglan, $_YAPI;
			$yapi=$_YAPI[$this->tur];
			if (!(isset($yapi['s'][$ne]))) {
				if (in_array($ne, array('_yetki_', 'ip', 'lisan'))) {
					$this->bilgi[$ne]=$deger;
					$this->kaydet();
					return true;
				} else {return false;}
			}
			$s=$yapi['s'][$ne];
			if (isset($s['filtre']) && function_exists($s['filtre'])) {$deger=eval('return ' . $s['filtre'] . '($deger);');}
			$sql="UPDATE " . $this->tur . " SET " . $ne . "='" . mysqli_real_escape_string($baglan, $deger) . "'" . (($yapi['a']['tarihler']) ? ", degis_tarih='" . _GMT_STAMP_ . "'" : '') . " WHERE seri='" . $this->seri . "'";
			if (mysql_sorgu($sql)) {
				if ($s['yabanci']!==false) {
					if ($y_tablo=yukle($s['yabanci']['tur'], $this->bilgi[$ne])) {$y_tablo->sil($s['yabanci']['sutun'], $this->seri);}
					if ($y_tablo=yukle($s['yabanci']['tur'], $deger)) {$y_tablo->ekle($s['yabanci']['sutun'], $this->seri);}
				}
				$this->bilgi[$ne]=$deger;
				if ($yapi['a']['tarihler']) {$this->bilgi['degis_tarih']=_GMT_STAMP_;}
				if (isset($yapi['a']['kestirme']) && $ne==$yapi['a']['kestirme']['s']) {
					kestirme_baslik_ekle($deger, $yapi['a']['kestirme']['sayfa'], $this->seri);
				}
			}
			$this->kaydet();
			return true;
		}

		function offsetunset ($ne) {
			return false;
		}

		function ekle ($ne, $seri) {
			$seri=intval($seri);
			if (!(isset($this->bilgi[$ne]) && is_array($this->bilgi[$ne])) || in_array($seri, $this->bilgi[$ne])) {return false;}
			$this->bilgi[$ne][]=$seri;
			$this->kaydet();
		}

		function sil ($ne, $seri) {
			$seri=intval($seri);
			if (!(isset($this->bilgi[$ne]) && is_array($this->bilgi[$ne]) && !in_array($seri, $this->bilgi[$ne]))) {return false;}
			unset($this->bilgi[$ne][array_search($seri, $this->bilgi[$ne])]);
			$this->kaydet();
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


		/** OTURUM FONKSİYONLARI **/

		function sifre_dogru ($sifre, $ip='') {
			global $_YAPI;
			if (!($_YAPI[$this->tur]['a']['oturum'])) {return false;}
			if (function_exists($_YAPI[$this->tur]['s']['sifre']['filtre'])) {$sifre=eval('return ' . $_YAPI[$this->tur]['s']['sifre']['filtre'] . '($sifre);');}
			if ($ip=='') {return ($sifre===$this->bilgi['sifre']);}
			if (!($gtekrar=cache_ver('giris_tekrar'))) {$gtekrar=array();}
			if (!(isset($gtekrar[$ip]))) {$gtekrar[$ip]=0;}
			if ($gtekrar[$ip]>99) {$this->logla(2); return 400;}
			if ($sifre!==$this->bilgi['sifre']) {
				$this->logla(1);
				$gtekrar[$ip]++;
				cache_sakla('giris_tekrar', $gtekrar);
				return 300;
			}
			return 200;
		}

		function oturum_ac ($ip, $lisan) {
			global $_YAPI;
			if (!($_YAPI[$this->tur]['a']['oturum'])) {return false;}
			if ($this->bilgi['_yetki_']) {return false;}
			$this->bilgi['_yetki_']=(function_exists($this->tur . '_yetki')) ? eval('return ' . $this->tur . '_yetki($this);') : 1;
			$this->bilgi['_basla_tarih_']=_GMT_STAMP_;
			$this->offsetset('ip', $ip);
			$this->offsetset('lisan', $lisan);
			$this->hareket_etti();
			$this->kaydet();
			return true;
		}

		function hareket_etti () {
			global $_YAPI;
			if (!($_YAPI[$this->tur]['a']['oturum'])) {return false;}
			if (!($this->bilgi['_yetki_'])) {return false;}
			if (!($oturum_hareket=cache_ver('oturum_hareket'))) {$oturum_hareket=array();}
			$oturum_hareket[$this->tur][$this->seri]=_GMT_STAMP_;
			cache_sakla('oturum_hareket', $oturum_hareket);
			$this->kaydet();
			return true;
		}

		function oturum_kapat () {
			global $baglan, $_YAPI;
			if (!($_YAPI[$this->tur]['a']['oturum'])) {return false;}
			if (!($this->bilgi['_yetki_'])) {return false;}

			if (!($oturum_hareket=cache_ver('oturum_hareket'))) {$oturum_hareket=array();}
			$sql="INSERT INTO " . $this->tur . "_oturum SET " . $this->tur . "='" . $this->seri . "', ip='" . $this->bilgi['ip'] . "', basla_tarih='" . $this->bilgi['_basla_tarih_'] . "', sure='" . (_GMT_STAMP_-$this->bilgi['_basla_tarih_']) . "'";
			mysql_sorgu($sql);
			unset($oturum_hareket[$this->tur][$this->seri]);
			cache_sakla('oturum_hareket', $oturum_hareket);

			unset($this->bilgi['lisan']);
			$this->bilgi['_yetki_']=0;
			$this->kaydet();
			return true;
		}

		function oturum_ver ($basla, $bit) {
			global $baglan, $_YAPI;
			if (!($_YAPI[$this->tur]['a']['oturum'])) {return false;}
			$sql="SELECT * FROM " . $this->tur . "_oturum WHERE " . $this->tur . "='" . $this->seri . "' AND (basla_tarih BETWEEN '" . $basla . "' AND '" . $bit . "') ORDER BY basla_tarih";
			if (!($tablo=mysql_sorgu($sql))) {return false;}
			$oturum_ver=array();
			while ($satir=mysqli_fetch_assoc($tablo)) {
				$oturum_ver[]=array('ip'=>$satir['ip'], 'basla_tarih'=>$satir['basla_tarih'], 'sure'=>$satir['sure']);
			}
			return $oturum_ver;
		}

		function oturum_rapor () {
			global $baglan, $_YAPI;
			if (!($_YAPI[$this->tur]['a']['oturum'])) {return false;}
			$sql="SELECT SUM(sure) AS toplam_sure, COUNT(seri) AS toplam_adet, MAX(sure) AS en_uzun_sure FROM " . $this->tur . "_oturum WHERE " . $this->tur . "='" . $this->seri . "'";
			if (!($satir=mysqli_fetch_assoc(mysql_sorgu($sql)))) {return false;}
			$oturum_rapor=array('toplam_sure'=>$satir['toplam_sure'], 'toplam_adet'=>$satir['toplam_adet'], 'en_uzun_sure'=>$satir['en_uzun_sure']);
			$sql="SELECT * FROM " . $this->tur . "_oturum WHERE " . $this->tur . "='" . $this->seri . "' AND sure='" . $satir['en_uzun_sure'] . "'";
			if (!($satir=mysqli_fetch_assoc(mysql_sorgu($sql)))) {return false;}
			$oturum_rapor['en_uzun_sure_basla']=$satir['tarih'];
			return $oturum_rapor;
		}


		/** LOG FONKSİYONLARI **/

		function logla ($olay) {
			global $baglan, $_YAPI;
			if (!($_YAPI[$this->tur]['a']['oturum'])) {return false;}
			$sql="INSERT INTO " . $this->tur . "_log SET " . $this->tur . "='" . $this->seri . "', ip='" . $this->bilgi['ip'] . "', tarih='" . _GMT_STAMP_ . "', olay='" . $olay . "'";
			mysql_sorgu($sql);
			return true;
		}

		function log_ver ($basla, $bit) {
			global $baglan, $_YAPI;
			if (!($_YAPI[$this->tur]['a']['oturum'])) {return false;}
			$sql="SELECT * FROM " . $this->tur . "_log WHERE " . $this->tur . "='" . $this->seri . "' AND (tarih BETWEEN '" . $basla . "' AND '" . $bit . "') ORDER BY tarih";
			if (!($tablo=mysql_sorgu($sql))) {return false;}
			$log_ver=array();
			while ($satir=mysqli_fetch_assoc($tablo)) {
				$log_ver[]=array('ip'=>$satir['ip'], 'tarih'=>$satir['tarih'], 'olay'=>$satir['olay']);
			}
			return $log_ver;
		}

	}