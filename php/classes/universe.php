<?php

/**
* 
* Класс для работы со вселенной
* 
**/

class universe {

	public static $regions;
	public static $systems;
	public static $map;
	public static function init() {
		return new self();
	}

	private function universe() {
		foreach (db::query('SELECT * FROM `regions`') as $region) $regions[ $region['id'] ] = $region['name'];
		self::$regions = $regions;
		foreach ($regions as $regid => $regname) {
			$map[ $regid ] = array(
			  'name' => $regname
			, 'systemset' => array()
			);
		}
		foreach (db::query('SELECT `sys`.*, `reg`.`name` `regname` FROM `systems` `sys` JOIN `regions` `reg` ON (`sys`.`regionID` = `reg`.`id`)') as $system) $systems[ $system['id'] ] = $system;
		self::$systems = $systems;
		foreach ($systems as $sysid => $sysinfo) {
			$map[ $sysinfo['regionID'] ]['systemset'][ $sysinfo['id'] ] = array(
			  'name' => $sysinfo['name']
			, 'security' => $sysinfo['security']
			);
		}
		root::$_ALL['backtrace'][] = 'Took region set and star set from DataBase';
	}

/**
*	
*	Метод, отбирающий системы для определенных регионов (AJAX)
*	@param	regions - строка, где записаны ID нужных регионов через запятую (если отсутствует, берутся все регионы)
*	@return JSON-строка с информацией о системах
*	
**/
	public static function getSystems($regions) {
		$regionset = explode(',', $regions);
		foreach ($regionset as $regID) {
			foreach (self::$systems as $starid => $starinfo) {
				if ($starinfo['regionID'] == $regID) {
					$sort[] = $starinfo['name'];
					$star[ $starinfo['name'] ] = $starinfo;
					$star[ $starinfo['name'] ]['regionName'] = self::$regions[ $regID ];
				}
			}
		}
		sort($sort);
		foreach ($sort as $order => $name) {
			$res[ $order ] = $star[ $name ];
		}
		return json_encode($res);
	}

/**
*	
*	Поиск систем по части названия (AJAX)
*	@param	search - часть названия системы
*	@return закодированные в JSON системы
*	
**/
	public static function searchSystems() {
		$search = isset($_GET['search']) ? $_GET['search'] : 'nothing';
		$escapedString = db::escape($search);
		$q = "SELECT `systems`.*, `regions`.`name` `regionName` FROM `systems`	JOIN `regions` ON (`regions`.`id` = `systems`.`regionID`)	WHERE `systems`.`name` LIKE '%$escapedString%' ORDER BY `systems`.`name`";
		$r = db::query($q);
		if ($r != NULL) {
			$arr = array();
			foreach ($r as $system) {
				$ss = number_format($system['security'], 1, '.', '');
				$arr[] = array('id' => $system['id'], 'name' => $system['name'], 'security' => $ss, 'regionID' => $system['regionID'], 'regionName' => $system['regionName']);
			}
			return json_encode($arr);
		} else return 'NULL';
	}

}