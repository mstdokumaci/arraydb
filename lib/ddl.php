<?php

	class DDL {
		private $DM, $table;
		private $db;

		function __construct ($DM) {
			$this->db=DB::get_instance();
			$this->DM=$DM;
		}

		function create_tables () {
			foreach ($this->DM as $name=>$table) {
				$conf=$table['conf'];

			}
		}

		function prepare_table () {

		}

		function get_field_type ($type, $len, $signed=false, $decimal=0) {
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

	foreach ($_YAPI as $isim=>$tablo) {
		$a=$tablo['a'];
		if (!($tur=tur_isim('sayi', $a['uzun']))) {die('çok uzun tablo: ' . $isim);}
		$db[$isim]['s'][]='seri ' . $tur . ' NOT NULL AUTO_INCREMENT';
		$db[$isim]['k'][]='PRIMARY KEY (seri)';
		if ($a['tarihler']) {
			$db[$isim]['s'][]="ekle_tarih INT(11) UNSIGNED NOT NULL DEFAULT '0'";
			$db[$isim]['k'][]='KEY (ekle_tarih)';
			$db[$isim]['s'][]="degis_tarih INT(11) UNSIGNED NOT NULL DEFAULT '0'";
			$db[$isim]['k'][]='KEY (degis_tarih)';
		}
		if ($a['silme']) {
			$db[$isim]['s'][]="sil_tarih INT(11) UNSIGNED NOT NULL DEFAULT '0'";
			$db[$isim]['k'][]='KEY (sil_tarih)';
		}
		if ($a['oturum']) {
			if (!(isset($tablo['s']['sifre']) && $tablo['s']['sifre']['tur']=='sifre')) {die('oturum açabilen türün şifre sütunu eksik');}
			$db[$isim . '_oturum']['s'][]='seri INT(11) UNSIGNED NOT NULL AUTO_INCREMENT';
			$db[$isim . '_oturum']['k'][]='PRIMARY KEY (seri)';
			$db[$isim . '_oturum']['s'][]=$isim . " " . $tur . " NOT NULL DEFAULT '0'";
			$db[$isim . '_oturum']['k'][]='KEY ('. $isim . ')';
			$db[$isim . '_oturum']['s'][]="ip VARCHAR(15) NOT NULL DEFAULT ''";
			$db[$isim . '_oturum']['k'][]='KEY (ip)';
			$db[$isim . '_oturum']['s'][]="basla_tarih INT(11) UNSIGNED NOT NULL DEFAULT '0'";
			$db[$isim . '_oturum']['k'][]='KEY (basla_tarih)';
			$db[$isim . '_oturum']['s'][]="sure MEDIUMINT(6) UNSIGNED NOT NULL DEFAULT '0'";
			$db[$isim . '_oturum']['k'][]='KEY (sure)';

			$db[$isim . '_log']['s'][]='seri BIGINT(15) UNSIGNED NOT NULL AUTO_INCREMENT';
			$db[$isim . '_log']['k'][]='PRIMARY KEY (seri)';
			$db[$isim . '_log']['s'][]=$isim . " " . $tur . " NOT NULL DEFAULT '0'";
			$db[$isim . '_log']['k'][]='KEY ('. $isim . ')';
			$db[$isim . '_log']['s'][]="ip VARCHAR(15) NOT NULL DEFAULT ''";
			$db[$isim . '_log']['k'][]='KEY (ip)';
			$db[$isim . '_log']['s'][]="tarih INT(11) UNSIGNED NOT NULL DEFAULT '0'";
			$db[$isim . '_log']['k'][]='KEY (tarih)';
			$db[$isim . '_log']['s'][]="olay TINYINT(3) UNSIGNED NOT NULL DEFAULT '0'";
			$db[$isim . '_log']['k'][]='KEY (olay)';
		}
		foreach ($tablo['s'] as $s_isim=>$s) {
			if ($s['tur']=='sayi') {
				if (!($tur=tur_isim($s['tur'], $s['uzun'], $s['negatif'], $s['ondalik']))) {die('çok uzun sütun: ' . $isim . '.' . $s_isim);}
			} else {
				if (!($tur=tur_isim($s['tur'], $s['uzun']))) {die('çok uzun sütun: ' . $isim . '.' . $s_isim);}
			}
			$db[$isim]['s'][]=$s_isim . " " . $tur . " NOT NULL DEFAULT '" . (($s['tur']=='sayi') ? "0" : "") . "'";
			if ($s['tek'] && !($a['silme'])) {
				$db[$isim]['k'][]='UNIQUE KEY (' . $s_isim . ')';
			} elseif ($s['tek'] || $s['index']) {
				$db[$isim]['k'][]='KEY (' . $s_isim . ')';
			}
		}
		foreach ($tablo['cc'] as $cc) {
			if (isset($db[$cc['yardimci']]['c'])) {continue;}
			$db[$cc['yardimci']]['c']=true;
			if (!($tur=tur_isim('sayi', $a['uzun']))) {die('çok uzun tablo: ' . $isim);}
			$db[$cc['yardimci']]['s'][]=$cc['yabanci'] . ' ' . $tur . " NOT NULL DEFAULT '0'";
			if (!($tur=tur_isim('sayi', $_YAPI[$cc['tur']]['a']['uzun']))) {die('çok uzun tablo: ' . $cc['tur']);}
			$db[$cc['yardimci']]['s'][]=$cc['yerel'] . ' ' . $tur . " NOT NULL DEFAULT '0'";
			$db[$cc['yardimci']]['k'][]='PRIMARY KEY (' . $cc['yabanci'] . ', ' . $cc['yerel'] . ')';
		}
		foreach ($tablo['cy'] as $cy) {
			$tur=tur_isim('sayi', $a['uzun']);
			$db[$cy]['s'][]=$isim . '1 ' . $tur . " NOT NULL DEFAULT '0'";
			$db[$cy]['s'][]=$isim . '2 ' . $tur . " NOT NULL DEFAULT '0'";
			$db[$cy]['k'][]='PRIMARY KEY (' . $isim . '1, ' . $isim . '2)';
		}
	}

	foreach ($db as $isim=>$tablo) {
		$sql="DROP TABLE IF EXISTS " . $isim;
		if (!(mysql_sorgu($sql))) {die('db hatası');}
		$sql="CREATE TABLE " . $isim . " (" . implode(', ', array_merge($tablo['s'], $tablo['k'])) . ") ENGINE=MyISAM";
		if (!(mysql_sorgu($sql))) {die('db hatası');}
	}

	foreach ($_AYAR['genel'] as $anahtar=>$deger) {
		$sql="INSERT INTO genel SET anahtar='" . $anahtar . "', deger='" . $deger . "'";
		if (!(mysql_sorgu($sql))) {die('db hatası');}
	}

	function tur_isim($tur, $hane, $negatif=false, $ondalik=0) {
		if ($tur=='sayi' && $ondalik) {
			if ($ondalik>=$hane) {
				echo "ondalık basamak sayısı hane sayısını geçemez.\n";
				return false;
			}
			if ($hane>65) {return false;}
			return 'DECIMAL (' . $hane . ', ' . $ondalik . ')';
		}
		static $tur_donustur=array('sifre'=>'yazi');
		if (isset($tur_donustur[$tur])) {$tur=$tur_donustur[$tur];}
		static $turler=array(
			'sayi'=>array(3=>'TINYINT', 5=>'SMALLINT', 7=>'MEDIUMINT', 10=>'INT', 19=>'BIGINT'),
			'yazi'=>array(21000=>'VARCHAR', 9000001=>'MEDIUMTEXT')
		);
		foreach ($turler[$tur] as $hudut=>$isim) {
			if ($hane<$hudut) {
				if ($negatif) {$hane++;}
				$don=$isim;
				if ($hane<35001) {$don.='(' . $hane . ')';}
				if ($tur=='sayi' && !($negatif)) {$don.=' UNSIGNED';}
				return $don;
			}
		}
		return false;
	}