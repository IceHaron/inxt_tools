<?php

/**
* 
* Класс для работы со вселенной
* 
**/

class universe {

	public static $regions;
	public static $regJumps;
	public static $systems;
	public static $sysJumps;
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
		self::$regJumps = db::query('SELECT DISTINCT `rfrom`.`name` as `fromName`, `rto`.`name` as `toName`
													FROM `gates` as `g`
														JOIN `systems` as `from` ON (`g`.`from` = `from`.`id`)
														JOIN `systems` as `to` ON (`g`.`to` = `to`.`id`)
														JOIN `regions` as `rfrom` ON (`rfrom`.`id` = `from`.`regionID`)
														JOIN `regions` as `rto` ON (`rto`.`id` = `to`.`regionID`)
													WHERE `rfrom`.`name` != `rto`.`name`;');
		foreach (db::query('SELECT `sys`.*, `reg`.`name` `regname` FROM `systems` `sys` JOIN `regions` `reg` ON (`sys`.`regionID` = `reg`.`id`)') as $system) $systems[ $system['id'] ] = $system;
		self::$systems = $systems;
		foreach ($systems as $sysid => $sysinfo) {
			$map[ $sysinfo['regionID'] ]['systemset'][ $sysinfo['id'] ] = array(
			  'name' => $sysinfo['name']
			, 'security' => $sysinfo['security']
			);
		}
		self::$sysJumps = db::query('SELECT DISTINCT `from`.`name` as `fromName`, `rfrom`.`name` as `fromReg`, `to`.`name` as `toName`, `rto`.`name` as `toReg` FROM `gates` as `g`
				JOIN `systems` as `from` ON (`g`.`from` = `from`.`id`)
				JOIN `systems` as `to` ON (`g`.`to` = `to`.`id`)
				JOIN `regions` as `rfrom` ON (`rfrom`.`id` = `from`.`regionID`)
				JOIN `regions` as `rto` ON (`rto`.`id` = `to`.`regionID`)');
		root::$_ALL['backtrace'][] = 'Took region set and system set from DataBase';
	}

/**
*	
*	Отбор систем для определенных регионов (AJAX)
*	@param	regions - строка, где записаны ID нужных регионов через запятую
*	@return string - JSON-строка с информацией о системах
*	
**/
	public static function getSystems($regions) {
		$regionset = explode(',', $regions);
		foreach ($regionset as $regID) {
			foreach (self::$systems as $systemid => $systeminfo) {
				if ($systeminfo['regionID'] == $regID) {
					$sort[] = $systeminfo['name'];
					$system[ $systeminfo['name'] ] = $systeminfo;
					$system[ $systeminfo['name'] ]['regionName'] = self::$regions[ $regID ];
				}
			}
		}
		sort($sort);
		foreach ($sort as $order => $name) {
			$res[ $order ] = $system[ $name ];
		}
		return json_encode($res);
	}

/**
*	
*	Получение карты региона
*	@param	region - название региона
*	@return array - карта региона (системы, прыжки, длины путей)
*	
**/
	public static function getRegionMap($region) {
		$j = db::query("SELECT DISTINCT `from`.`name` as `fromName`, `rfrom`.`name` as `fromReg`, `to`.`name` as `toName`, `rto`.`name` as `toReg` FROM `gates` as `g`
			JOIN `systems` as `from` ON (`g`.`from` = `from`.`id`)
			JOIN `systems` as `to` ON (`g`.`to` = `to`.`id`)
			JOIN `regions` as `rfrom` ON (`rfrom`.`id` = `from`.`regionID`)
			JOIN `regions` as `rto` ON (`rto`.`id` = `to`.`regionID`)
			WHERE `rfrom`.`name` = '$region';");
		foreach ($j as $jump) {
			if ($jump['fromReg'] != $jump['toReg']) $len = 1.01;
			else $len = 1;
			$routeDots[ $jump['fromName'] ][ $jump['toName'] ] = $len;
		}
		$foreign = '';
		while (count($j) > 0) {
			$jump = array_pop($j);
			if ($jump['toReg'] != $region) $foreign .= ", '" . $jump['toName'] . "'";
			foreach ($j as $jID => $injump) {
				if ($injump['toName'] == $jump['fromName']) unset($j[$jID]);
			}
			$jumps[] = $jump;
		}
		$dots = db::query("SELECT `s`.*, `r`.`name` as `regName` FROM `systems` as `s` JOIN `regions` as `r` ON (`s`.`regionID` = `r`.`id`) WHERE `r`.`name` = '$region' OR `s`.`name` IN (" . substr($foreign, 2) . ")");
		$map = array('dots' => $dots, 'jumps' => $jumps, 'routeDots' => $routeDots);
		return $map;
	}

/**
*	
*	Выделение куска карты, причастного к пути от системы до системы (AJAX)
*	@param	from - начало пути
*	@param	to - окончание пути
*	@return string - закодированные в JSON системы
*	
**/
	public static function getSystemsForRouter($from, $to) {
		root::$_ALL['notemplate'] = TRUE;
		foreach (self::$systems as $sysinfo) {
			if ($sysinfo['name'] == $from) $fromReg = $sysinfo['regname'];
			if ($sysinfo['name'] == $to) $toReg = $sysinfo['regname'];
		}
		foreach (self::$regJumps as $jump) {
			$routeDots[ $jump['fromName'] ][ $jump['toName'] ] = 1;
		}
		$a = array(); // Смежные системы
		$d = array(); // Длина пути
		$n = array(); // Вершины для посещения
		$p = array(); // Кратчайший путь
		$u = array(); // Посещенные вершины
		$regions = self::$regions;
		$now = '';
		$counter = 0;
		$min = 0;
		$mindot = '';
		$d[$fromReg] = 0;
		$u[$fromReg] = $d[$fromReg];
		$now = $fromReg;
		foreach ($regions as $regid => $regname) {
			if ($regname != $fromReg && $regid < 11000000 && $regid != 10000004 && $regid != 10000017 && $regid != 10000019) {
				$d[$regname] = 10000;
			}
		}
		// var_dump($routeDots);
		while (array_search(10000, $d) !== false && $now != $toReg/* && $counter < 100*/) {
			$trigger = false;
			// var_dump("<br/><br/>Entering to " . $now, $d[$now]);
			unset($n[$now]);
			$u[$now] = $d[$now];
			foreach ($routeDots[$now] as $i => $routeDot) {
				if ($d[$i] > $d[$now] + 1 && !isset($u[$i])) {
					$d[$i] = $d[$now] + 1;
				}
				// var_dump("<br/>Looking " . $i, $d[$i], isset($u[$i]));
				if (!isset($u[$i])) {
					$trigger = true;
					$n[$i] = $d[$i];
					$min = $d[$i];
					$mindot = $i;
					// var_dump("<br/>Setting to minimum: " . $i, $d[$i]);
				}
			}
			foreach ($n as $i => $not) {
				// var_dump("<br/>Calculating minimum for " . $i, $d[$i], $d[$i] <= $min, !isset($u[$i]), $trigger == false);
				if (($d[$i] <= $min && !isset($u[$i])) || $trigger == false) {
					// var_dump("Changed");
					$min = $d[$i];
					$mindot = $i;
				}
			}
			// console.log("Minimum: " + mindot, min);
			unset($n[$mindot]);
			$u[$mindot] = $min;
			$now = $mindot;
			$counter++;
		}
		// echo '<pre>';
		// var_dump($d, $counter);
		// echo '</pre>';
		// var_dump($now != $toReg, $counter < 100);
		$now = $toReg;
		$p[ $d[$now] ][] = $now;
		$counter = 0;
		while ($now != $fromReg/* && $counter < 100*/) {
			foreach ($routeDots[$now] as $i => $routeDot) {
				$a[$i] = $d[$i];
			}
			foreach ($routeDots[$now] as $i => $routeDot) {
				if ($d[$i] < $d[$now]) {
					$p[ $d[$i] ][] = $i;
					$now = $i;
				}
			}
			$counter++;
		}
		// foreach ($p as $i)
		// 	foreach ($i as $j)
		// 		unset($a[$j]);
		foreach ($routeDots[$now] as $i => $routeDot)
			$a[$i] = $d[$i];
		if (count($p) == 1) {
			$s = self::getRegionMap($p[0][0]);
			$systems = array('dots' => $s['dots'], 'jumps' => $s['jumps'],'routeDots' => $s['routeDots']);
		} else
			$systems = array('dots' => array(), 'jumps' => array(),'routeDots' => array());
		foreach ($a as $regname => $t) {
			$s = self::getRegionMap($regname);
			$systems['dots'] = array_merge($systems['dots'], $s['dots']);
			$systems['jumps'] = array_merge($systems['jumps'], $s['jumps']);
			$systems['routeDots'] = array_merge($systems['routeDots'], $s['routeDots']);
		}
		echo json_encode($systems);
	}

/**
*	
*	Поиск систем по части названия (AJAX)
*	@param  search - часть названия системы
*	@return string - закодированные в JSON системы
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

/**
*	
*	Расчет времени полета (AJAX)
*	@param  route - путь, который нужно просчитать
*	@return float - время полета
*	
**/
	public static function calcRouteTime($route, $mass, $agil, $warp, $accel, $decel) {
		$count = count($route);
		$au = 149597870700;
		$ship = array(
			  'inertia' => $agil
			, 'mass' => $mass
			, 'accel' => $accel
			, 'decel' => $decel
			, 'warpSpeed' => $warp * $au
		);
		$q = "
			SELECT `from`.`name` AS `fromname`, `to`.`name` AS `toname`, `gates`.`pos_x`, `gates`.`pos_y`, `gates`.`pos_z`
			FROM `gates`
			JOIN `systems` AS `from` ON (`from`.`id` = `gates`.`from`)
			JOIN `systems` AS `to` ON (`to`.`id` = `gates`.`to`)
			WHERE `from`.`name` IN ('" . implode("','", $route) . "');";
		$r = db::query($q);

		foreach ($r as $gate) {
			$gates[ $gate['fromname'] ][ $gate['toname'] ] = array((float)$gate['pos_x'], (float)$gate['pos_y'], (float)$gate['pos_z']);
		}
		// var_dump(self::calcJump(array('from' => array(4313123512320,569596846080,-1874440642560), 'to' => array(4313106063360,569600532480,-1874452439040)), $ship));

		$jumps[0] = array('from' => array(0,0,0), 'to' => $gates[ $route[0] ][ $route[1] ]);
		$details[0]['jump'] = 'Jump from ' . $route[0] . ' star to ' . $route[1] . ' gate';

		for ($i=1; $i < $count-1; $i++) {
			$jumps[$i] = array('from' => $gates[ $route[$i] ][ $route[$i-1] ], 'to' => $gates[ $route[$i] ][ $route[$i+1] ]);
			$details[$i]['jump'] = 'Jump in ' . $route[$i] . ' from ' . $route[$i-1] . ' gate to ' . $route[$i+1] . ' gate';
		}

		$jumps[$i] = array('from' => $gates[ $route[$i] ][ $route[$i-1] ], 'to' => array(0,0,0));
		$details[$i]['jump'] = 'Jump from ' . $route[$i-1] . ' gate to ' . $route[$i] . ' star';

		foreach ($jumps as $i => $jump) {
			$jumpinfo = self::calcJump($jumps[$i], $ship);
			if ($jumpinfo['range'] > 1000000000) $details[$i]['range'] = ' (' . round($jumpinfo['range'] / $au, 2) . ' AU long)';
			else $details[$i]['range'] = ' (' . round($jumpinfo['range']) . ' km long)';
			$time[$i] = $jumpinfo['time'];
			$details[$i]['time'] = $time[$i];
		}
		$summary = 0;

		foreach ($time as $i => $sec) {
			$summary += 10 + $sec;
		}

		$output = json_encode(array('summary' => round($summary, 1), 'detailed' => $details));

		return $output;
	}

/**
*	
*	Расчет времени прыжка
*	@param  jump - массив с координатами начала и конца
*	@param  ship - массив с параметрами корабля
*	@return float - время полета
*	
**/
	private static function calcJump($jump, $ship) {
		$range = sqrt(pow($jump['from'][0] - $jump['to'][0],2) + pow($jump['from'][1] - $jump['to'][1],2) + pow($jump['from'][2] - $jump['to'][2],2));
		$alignTime = $ship['inertia'] * $ship['mass'] * 1e-6 * (-log(0.25));
		$peakVel = $ship['warpSpeed'];
		$precision = min(strlen(round($range)), strlen(round($peakVel))) - 2;

		do {
			$accelTime = log($ship['accel']*$peakVel)/$ship['accel'];
			$accelRange = exp($ship['accel']*$accelTime);
			$decelTime = log($ship['decel']*$peakVel)/$ship['decel'];
			$decelRange = exp($ship['decel']*$decelTime);
			$cruiserRange = $range - $accelRange - $decelRange;
			$cruiserTime = $cruiserRange / $peakVel;
			$peakVel -= 1 * pow(10, $precision);
		} while ($cruiserRange < 0);

		$time = $alignTime + $accelTime + $cruiserTime + $decelTime;

		$output = array('range' => $range, 'time' => $time);

		return $output;
	}

}