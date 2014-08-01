<?

/**
*	
*	Тестовый класс, воротим здесь что захотим и всякие штуки тестируем именно тут.
*	Заодно это шаблон для создания других классов.
*	
**/

class wtf_four {	//<< Переименовать класс в <папка>_<контроллер>
	
	public static function init() {
		return new self();
	}
	
	private function wtf_four() {	// << переименовать конструктор под стать классу
		
		root::$_ALL['maintitle'] = 'Тестовая страница';
		root::$_ALL['maincaption'] = 'Заголовок страницы';
		root::$_ALL['mainsupport'] = 'Содержимое вспомогательного блока';
		root::$_ALL['maincontent'] = 'Содержимое центрального блока';
		root::$_ALL['backtrace'][] = 'initialized wtf/four';
	}
	
	
///////////////////// Шаблон закончился, осталось только закрыть фигурную скобку
	
	
	public static function level($par) {
		
		root::$_ALL['maintitle'] = 'Тестовая страница';
		root::$_ALL['maincaption'] = 'Заголовок страницы';
		root::$_ALL['mainsupport'] = 'Содержимое вспомогательного блока';
		root::$_ALL['maincontent'] = 'Содержимое центрального блока';
		root::$_ALL['backtrace'][] = 'initialized wtf/four';

		$q = "SELECT `id`, `name` FROM `systems`";
		$r = db::query($q);
		foreach ($r as $s) {
			$sysIDs[ $s['name'] ] = $s['id'];
		}
		echo '<pre>';

		$q = "
			SELECT `from`.`name` AS `fromname`,`to`.`name` AS `toname`,`coords`.`id`
				FROM `gates`
				JOIN `systems` AS `from` ON (`gates`.`from` = `from`.`id`)
				JOIN `systems` AS `to` ON (`gates`.`to` = `to`.`id`)
				JOIN `coords` ON (`to`.`name` = `coords`.`name`)
				WHERE `gates`.`id` < 50000000
				ORDER BY `toname`";
		$r = db::query($q);
		// var_dump($r);

		foreach ($r as $gate) {
			$route[ $gate['fromname'] ][ $gate['toname'] ][] = $gate['id'];
			$toID = $sysIDs[ $gate['toname'] ];
		}

		// var_dump($route);

		foreach ($route as $fromname => $from) {
			$fromID = $sysIDs[$fromname];
			foreach ($from as $toname => $variants) {
				$toID = $sysIDs[$toname];
				$str = '';
				foreach ($variants as $id) {
					$str .= ',' . $id;
				}
				$q = "SELECT `id` FROM `gates` WHERE `id` IN (" . substr($str, 1) . ")";
				$r = db::query($q);
				if (count($r) == 0) continue;
				foreach ($r as $unit) {
					$present[] = $unit['id'];
				}
				$diff = array_values(array_diff($variants, $present));
				if (count($diff) == 1) {
					$q = "UPDATE `gates` SET `id` = {$diff[0]} WHERE `from` = $fromID AND `to` = $toID";
					// $r = db::query($q);
					// var_dump($r);
				}
			}
		}

/*		$q = "SELECT * FROM `coords`";
		$coords = db::query($q);
		$q = "
			SELECT `from`.`name` AS `fromname`, `to`.`name` AS `toname`
				FROM `gates`
				JOIN `systems` AS `from` ON (`gates`.`from` = `from`.`id`)
				JOIN `systems` AS `to` ON (`gates`.`to` = `to`.`id`)";
		$gates = db::query($q);
		$i = 0;

		foreach ($gates as $gate) {
			$systems[ $gate['fromname'] ][] = $gate['toname'];
		}

		$arr = array();
		$crosses = array($systems[ $coords[0]['name'] ]);

		foreach ($coords as $k => $gate) {
			$cross = array();

			// if ($k > 100) break;

			foreach ($systems[ $gate['name'] ] as $neightbour) {
				if (array_search($neightbour, $crosses[$i]) !== FALSE) $cross[] = $neightbour;
			}

			// var_dump($cross);

			if (count($cross) != 0) {
				$clusters[$i][] = $gate['name'];
				$IDs[$i][ $gate['name'] ] = $gate['id'];
				$crosses[$i] = $cross;

			} else {
				$i++;
				$clusters[$i][] = $gate['name'];
				$IDs[$i][ $gate['name'] ] = $gate['id'];
				$crosses[$i] = $systems[ $gate['name'] ];
			}

		}

		// var_dump($IDs);

		foreach ($crosses as $k => $cross) {

			if (count($cross) != 1) {
				$ambiguous[$k] = array('cluster' => $clusters[$k], 'cross' => $cross);

			} else {

				foreach ($clusters[$k] as $neightbour) {
					$final[ $cross[0] ][$neightbour] = $IDs[$k][$neightbour];
				}

				// $diff = array_merge(array_diff($clusters[$k], $systems[ $cross[0] ]), array_diff($systems[ $cross[0] ], $clusters[$k]));
				// if(count($diff) != 0) var_dump($cross, $clusters[$k], $systems[ $cross[0] ], $diff, '<br/><br/><br/>');
			}

		}

		foreach ($ambiguous as $k => $district) {
			unset($clusters[$k], $crosses[$k]);
		}

		// var_dump(count($ambiguous));

		foreach ($ambiguous as $k => $district) {
			$cluster = $district['cluster'];
			$cross = $district['cross'];
			$q = "
				SELECT `from`.`name` AS `fromname`, `to`.`name` AS `toname`, `from`.`id` AS `fromid`, `to`.`id` AS `toid`, `gates`.`id`
				FROM `gates`
				JOIN `systems` AS `from` ON (`gates`.`from` = `from`.`id`)
				JOIN `systems` AS `to` ON (`gates`.`to` = `to`.`id`)
				WHERE `from` IN (";
			$from = $to = '';
			foreach ($cross as $system) {
				$from .= ',' . $sysIDs[$system];
			}
			foreach ($cluster as $system) {
				$to .= ',' . $sysIDs[$system];
			}
			$q .= substr($from, 1) . ') AND `to` IN (' . substr($to, 1) . ') AND `gates`.`id` < 50000000';
			$r = db::query($q);
			if (count($r) == 1) {
				// $q = "UPDATE `gates` SET `id` = {$IDs[$k][ $r[0]['toname'] ]} WHERE `from` = {$r[0]['fromid']} AND `to` = {$r[0]['toid']}";
				// $r1 = db::query($q);
				// var_dump($q, $r1);
			} else if (count($r) != 0) {
				// var_dump($q, $r);
				foreach ($r as $gate) {
					$q = "UPDATE `gates` SET `id` = {$IDs[$k][ $gate['toname'] ]} WHERE `from` = {$gate['fromid']} AND `to` = {$gate['toid']}";
					// var_dump($q);
					// var_dump($IDs[$k], $k);
					$compiled[$k][ $gate['fromid'] ][ $IDs[$k][ $gate['toname'] ] ] = $gate['toid'];
				}
			}
			var_dump($IDs[$k], $k, $cluster, $cross);
			// var_dump($r, $district);
		}

		// var_dump($compiled);

		// foreach ($compiled as $district) {
		// 	if (count($district) == 1) {
		// 		$fromid = array_keys($district);
		// 		foreach ($district[ $fromid[0] ] as $id => $jump) {
		// 			$q = "UPDATE `gates` SET `id` = {$id} WHERE `from` = {$fromid[0]} AND `to` = {$jump}";
		// 			$r = db::query($q);
		// 			var_dump($r);
		// 		}
		// 	}
		// }

		$counter = 0;
		$l = 0;

		// foreach ($final as $sysName => $neightbours) {
		// 	$l++;
		// 	if ($l >= 0) continue;
		// 	$fromID = $sysIDs[$sysName];
		// 	// var_dump($sysName, $neightbours);

		// 	foreach ($neightbours as $neightbour => $neighID) {
		// 		$toID = $sysIDs[$neightbour];
		// 		$q = "UPDATE `gates` SET `id` = $neighID WHERE `from` = $fromID AND `to` = $toID";
		// 		$r = db::query($q);
		// 		if ($r !== TRUE) var_dump($q, $r);
		// 		$counter++;
		// 	}
		// }

		// var_dump($counter);

		// for ($j = 0; $j < count($crosses); $j++) {
		// 	var_dump($clusters[$j], $crosses[$j]);
		// 	echo '<br/><br/>';
		// }

		// var_dump($clusters);
		// var_dump($crosses);
		// var_dump($ambiguous);
		// var_dump($final);*/

		echo '</pre>';
	}

}